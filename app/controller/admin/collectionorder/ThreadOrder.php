<?php

namespace app\controller\admin\collectionorder;

use app\model\admin\order\CollectionOrderLog;
use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use think\facade\Config;

class ThreadOrder extends Backend
{
    protected $model;//当前模型对象
    protected $orderModel;//当前订单模型对象

    protected $noNeedAuth = ['*'];
    protected $noNeedLogin = [''];

    protected $identityList = ["-", "学生", "职场", "自由职业", "全职宝妈", "公职职业编"];
    protected $educationList = ["-", "高中以下", "高中及职高", "大专", "本科及以上"];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\Thread();
        $this->orderModel = new \app\model\admin\order\CollectionOrder();
    }

    //添加账单
    public function createOrder()
    {
        $id = $this->request->post('id',0);
        if(!intval($id)){
            return $this->error('参数错误');
        }
        $pay_type = $this->request->post('pay_type',1);
        $price = $this->request->post('price',0);

        if(!$price || !is_numeric($price)){
            return $this->error('金额输入错误');
        }

        if($price*100 < 1  ||  $price  > 999999 ){
            return $this->error('金额范围必须大于 1 或小于 999999');
        }
        $lenPrice = explode('.',$price);
        if(!empty($lenPrice[1]) &&  strlen($lenPrice[1]) >2 ){
            return $this->error('金额范围为两位小数');
        }

        $info = $this->model->with('user')->where('id',$id)->find();

        $orderInfo = $this->orderModel->where('thread_id',$id)->where('order_status',1)->find();
        if($orderInfo){
            return $this->error('已存在未支付订单');
        }

        $username = '';
        if(!empty($info['user'])){
            $username = $info['user']['nickname'] ?: $info['user']['wx_nickname'];
        }

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];

        $order_sn = create_order_sn();
        $qr_code_num = create_order_sn();
        $url = '?order_sn=' . base64_encode($qr_code_num);
        $params = [
            'thread_id' => $id,//线索id
            'merchant_id' => $info['merchant_id'], //商户端
            'customer_id' => $info['customer_id'], //客服
            'data_type' => 1, //线索管理的来源类型 1 投流
            'uid' => $info['user']['id'] ?? '', //客户姓名
            'user_name' =>  $username, //客户姓名
            'user_idcard' => '',//客户身份证号码
            'user_phone' => $info['user']['phone'] ?? '',//客户手机号
            'effect_time_type' => 8,//8.长期有效
            'qr_code' => $url,//收款码链接
            'status' => 1,//状态  1开启 2关闭
            'order_status' => 1,//订单状态 1待付款 2已付款 3已关闭 4已失效
            'order_sn' => $order_sn,//订单号
            'qr_code_num' => $qr_code_num,//二维码单号
            //'pay_type' => $pay_type ==1 ? 'wxpay' : 'alipay',//支付方式 wxpay alipay
            'pay_status' => 0,//支付状态 0待支付 1已支付
            //'pay_amount' => $price,//支付金额
            'fee_amount' => $price,//服务费用金额
            'is_external_thread' => 0,//是否外部线索 1是 0否
            'admin_user_id' => $loginUserInfo['id'],//创建人
            'admin_user_type' => 0,//创建人类型1商户 2客服
            'admin_user_name' => $loginUserInfo['username'] ?? '',//创建人
            'create_time' => time(),//创建时间
            'type' => 1,//订单类型 1在线收款 2线下补录
        ];

        $result = $this->handleCreateOrder($params);
        $resReturn = [];
        if($result){
           $resReturn = $this->getRetData($params);
        }
        return $this->success('数据获取成功', $resReturn);
    }

    //添加订单
    protected function handleCreateOrder($params)
    {
        $orderResult = $this->orderModel->create($params);

        return $orderResult ?? 0;
    }

    //返回信息
    protected function getRetData($params)
    {
        //$params['merchant_id'] = '';
        $debtinfo = Config::load("extra/company" , "extra");
        $debtinfo = $debtinfo[$params['merchant_id']] ?? $debtinfo[260];

        //在线收款
        $payImgUrl = $debtinfo['h5_url'] . $params['qr_code'];
        $payBgImgUrl = $debtinfo['third_pay_qrcode_bg'];
        $aliPayBgImgUrl = $debtinfo['ali_pay_qrcode_bg'];
        $wxPayBgImgUrl = $debtinfo['wx_pay_qrcode_bg'];
        $h5BgImgUrl = $debtinfo['h5_pay_bg'];

        return [
            'is_pay'             => 0,
            'pay_img_url'        => $payImgUrl,
            'wx_pay_img_url'     => '',
            'ali_pay_img_url'    => '',
            'pay_bg_img_url'     => $payBgImgUrl,
            'ali_pay_bg_img_url' => $aliPayBgImgUrl,
            'wx_pay_bg_img_url' => $wxPayBgImgUrl,
            'h5_pay_bg_url' => $h5BgImgUrl,
            'fee_amount' => $params['fee_amount'] ?? 0,
            'valid_time' => "长期有效",
        ];
    }


    public function editOrder()
    {
        $id = $this->request->post('id',0);
        $orderid = $this->request->post('orderid',0);
        if(!intval($id) || !intval($orderid)){
            return $this->error('参数错误');
        }
        $pay_type = $this->request->post('pay_type',1);
        $price = $this->request->post('price',0);

        if(!$price || !is_numeric($price)){
            return $this->error('金额输入错误');
        }
        if($price*100 < 1  ||  $price  > 999999 ){
            return $this->error('金额范围必须大于 1 或小于 999999');
        }
        $lenPrice = explode('.',$price);
        if(!empty($lenPrice[1]) &&  strlen($lenPrice[1]) >2 ){
            return $this->error('金额范围为两位小数');
        }
        
        $orderInfo = $this->orderModel->where('id',$orderid)->find();
        if(!$orderInfo){
            return $this->error('订单不存在');
        }
        if($orderInfo['order_status'] != 1 ){
            return $this->error('订单非待支付状态不可更改');
        }

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $order_sn = create_order_sn();
        //数据更新
        $updateData = [
            'fee_amount' => $price,
            'order_sn' => $order_sn,//订单号
        ];
        $orderRes = $this->orderModel->where('id',$orderid)->where('thread_id',$id)->update($updateData);

        //日志记录

        $logData = [
            'order_id' => $orderid,
            'thread_id' => $id,
            'admin_id' => $loginId,
            'old_data' => json_encode($orderInfo),
            'new_data' => json_encode($updateData),
        ];
        CollectionOrderLog::create($logData);
        $orderInfo['fee_amount'] = $price;
        $resReturn = [];
        if($orderRes){
            $resReturn = $this->getRetData($orderInfo);
        }
        return $this->success('数据获取成功', $resReturn);
    }
}