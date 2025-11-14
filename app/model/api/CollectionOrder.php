<?php

namespace app\model\api;

use app\lib\api\payapi\Alipay;
use app\lib\api\payapi\Wxpay;
use app\model\api\PayActionLog as PayActionLogModel;
use laytp\BaseModel;
use think\facade\Config;
use think\facade\Db;
use think\model\concern\SoftDelete;

class CollectionOrder extends BaseModel {
    use SoftDelete;

    protected $name = 'collection_order';


    public static function info($params = [],$request) {
        extract($params);
        PayActionLogModel::create([
            'merchant_id'    => 142,
            'rule'           => $request->request()['s'],
            'menu'           => '获取订单详情1--' . $order_sn,
            'request_body'   => json_encode($request->param()),
            'request_header' => json_encode($request->header()),
            'ip'             => $request->ip(),
        ]);
        if (empty($order_sn)) {
            return self::_error('未找到订单');
        }
        $oderSn = base64_decode($order_sn);
        PayActionLogModel::create([
            'merchant_id'    => 142,
            'rule'           => $request->request()['s'],
            'menu'           => '获取订单详情2--' . $oderSn,
            'request_body'   => json_encode($request->param()),
            'request_header' => json_encode($request->header()),
            'ip'             => $request->ip(),
        ]);
        $order = self::where('qr_code_num', $oderSn)->find();
        if (empty($order)) {
            return self::_error('未找到订单');
        }
        $order = $order->toArray();
        $merchantId = $order['merchant_id'];
        if (!$order['status']) {
            return self::_error('二维码已被关闭');
        }

        if ($order['order_status'] != 1) {
            $code = 300;
            if ($order['order_status'] == 2) {
                $code = 302;
            }
            return self::_error('订单：' . self::orderStatus($order['order_status']), $code);
        }

        if ($order['effect_time_type'] !=8 && $order['effect_time_stamp'] - time() < 0) {
            return self::_error('二维码已过期');
        }

        $fee = Db::name('collection_order_fee')
            ->alias('f')
            ->field(['f.bank_amount',
                'b.title'])
            ->leftJoin('bank_platform b', 'f.bank_id = b.id')
            ->where('f.is_finished', '=', '0')
            ->where('f.delete_time', '=', '0')
            ->where('f.id', 'in', $order['fee_id']);
        //->wherunique_nume('f.', '=', $order['unique_num']);

        $fee = $fee->select();

        $order['fee_type'] = '';

        return [
            'order'   => $order,
            'feeList' => $fee
        ];
    }


    // 跳转支付
    public static function payApplySzm($params = [], $request = null) {
        extract($params);
        $oderSn = base64_decode($order_sn);
        $order = self::where('qr_code_num', '=', $oderSn)
            ->where('delete_time', '=', '0')
            ->find();
        PayActionLogModel::create([
            'merchant_id'    => 260,
            'rule'           => $request->request()['s'],
            'menu'           => '获取支付参数1--' . $oderSn,
            'request_body'   => json_encode($request->param()),
            'request_header' => json_encode($request->header()),
            'ip'             => $request->ip(),
        ]);
        if (empty($order)) {
            return self::_error('未找到订单');
        }


        if (!$order['status']) {
            return self::_error('二维码已被关闭');
        }

        if ($order['order_status'] != 1) {
            return self::_error('订单：' . self::orderStatus($order['order_status']));
        }

        if ($order['effect_time_type'] !=8 && $order['effect_time_stamp'] - time() < 0) {
            return self::_error('二维码已过期');
        }
        $orderParams = [
            'total_amount' => $order['fee_amount'],
            'order_sn'     => $order['order_sn'],
            'desc'         => '商品订单' . $order['order_sn'],
            'system_type'  => $system_type ?? '',
            'code'         => isset($code) ? $code : '',
            'openid'       => isset($openid) ? $openid : '',
        ];
        $merchantId = $order['merchant_id'];

        # todo 特殊处理（律而信9月27之前的线索继续走国之良接口，之后线索走律而信接口）
        $payConfig = Config::load("extra/pay", "pay");
        $payConfig = $payConfig[$merchantId] ?? $payConfig[260];

//        $payConfig = Config::load("extra/pay", "pay");
//        $payConfig = $payConfig[$merchantId];
        PayActionLogModel::create([
            'merchant_id'    => 260,
            'rule'           => $request->request()['s'],
            'menu'           => '获取支付参数2--' . $order['order_sn'] . '--' . $order['id'] . '--' . $pay_type,
            'request_body'   => json_encode($orderParams),
            'request_header' => json_encode($request->header()),
            'ip'             => $request->ip(),
        ]);
        if ($pay_type == 'hsqpay') {
            $res = self::getPayParams($pay_type, $orderParams, $payConfig);
            if (isset($res['code'])) {
                PayActionLogModel::create([
                    'merchant_id'    => 260,
                    'rule'           => $request->request()['s'],
                    'menu'           => '获取支付参数3--' . $order['order_sn'] . '--' . $order['id'] . '--' . $pay_type,
                    'request_body'   => json_encode([$res['msg']]),
                    'request_header' => json_encode($request->header()),
                    'ip'             => $request->ip(),
                ]);
                return self::_error($res['msg'], 400);
            } else {
                $data = [
                    'order_sn' => $order['order_sn'],
                    'pay_url'  => $res,
                ];
                PayActionLogModel::create([
                    'merchant_id'    => 260,
                    'rule'           => $request->request()['s'],
                    'menu'           => '获取支付参数3--' . $order['order_sn'] . '--' . $order['id'] . '--' . $pay_type,
                    'request_body'   => json_encode($data),
                    'request_header' => json_encode($request->header()),
                    'ip'             => $request->ip(),
                ]);
                return $data;
            }
        } else {
            $data = [
                'order_sn' => $order['order_sn'],
                'pay_url'  => self::getPayParams($pay_type, $orderParams, $payConfig),
            ];
            PayActionLogModel::create([
                'merchant_id'    => 260,
                'rule'           => $request->request()['s'],
                'menu'           => '获取支付参数3--' . $order['order_sn'] . '--' . $order['id'] . '--' . $pay_type,
                'request_body'   => json_encode($data),
                'request_header' => json_encode($request->header()),
                'ip'             => $request->ip(),
            ]);
            return $data;
        }
    }


    //获取支付参数
    public static function getPayParams($payType = null, $orderParams = [], $payConfig = []) {
        if ($payType == 'alipay') {
            $payParams = Alipay::h5ForFlowAliAppPay($orderParams, $payConfig);
            return $payParams;
        }

        if ($payType == 'wxpay') {
            $openid = $orderParams['openid'];
            if ($openid) {
                $payParams = Wxpay::WeixinJSBridge($orderParams, $payConfig);
                return ['openid' => $openid,
                    'param'  => $payParams];
            } else {
                $openinfo = self::getOpenId($orderParams['code'], $payConfig);
                if (!$openinfo['status']) return false;

                $orderParams['openid'] = $openinfo['openid'];
                $payParams = Wxpay::WeixinJSBridge($orderParams, $payConfig);
                //file_put_contents("wxpay9.txt", "2_get_wx_openid_error_" . json_encode(['openid' => $openinfo['openid'], 'param' => $payParams]), FILE_APPEND);
                return ['openid' => $openinfo['openid'],
                    'param'  => $payParams];
            }
        }
    }


    //获取openid
    public static function getOpenId($code, $payConfig) {
        $payConfig = $payConfig['wxpay'];
        $openidurl = $payConfig['openidurl'] . "appid="
            . $payConfig['wxpayAppId']
            . "&secret=" . $payConfig['wxsecret']
            . "&code=" . $code . "&grant_type=authorization_code";
        $data = file_get_contents($openidurl);
        $arr = json_decode($data, true);

        if (!isset($arr['openid'])) {
            //file_put_contents("wxpay9.txt", "3_get_wx_openid_error_" . json_encode($arr), FILE_APPEND);
            return ['status' => false,
                'msg'    => $arr['errmsg'],
                'openid' => ''];
        }

        return ['status' => true,
            'msg'    => 'success',
            'openid' => $arr['openid']];
    }


    // 获取订单详情

    protected static function _error($msg, $code = 300) {
        return ['code' => $code,
            'msg'  => $msg];
    }


}