<?php

namespace app\model\api\infoflow;

use app\lib\api\city\IpCity;
use app\lib\api\exception\Exception;
use app\lib\api\exception\ExceptionStd;
use app\lib\api\oneclicklogin\OneClickPhoneLogin;
use app\lib\api\service\CustomerService;
use app\lib\api\service\WeightService;
use app\model\api\Channel;
use app\model\api\Customer;
use app\model\api\h5\UserList;
use app\model\api\Merchant;
use laytp\BaseModel;
use think\facade\Config;
use think\facade\Db;
use think\facade\Event;
use think\facade\Log;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
class OneClickPhoneThread extends BaseModel
{
    protected $name = 'thread';

    //是否已报名
    public static function checkApplyAppFlow($phone,$appFlowId = 0)
    {
        $checkApplyAppFlow = 0;
        $uid = UserList::where('phone',$phone)->where('flow_id',$appFlowId)->order('id desc')->value('id');
        if(!empty($uid)){
            $checkApplyAppFlow = self::where('uid', $uid)->where('flow_id', $appFlowId)->count();
        }
        return $checkApplyAppFlow ? 1 : 0;
    }

    //获取已报名课程客服二维码
    public static function getApplyQrCode($params = [])
    {
        extract($params);
        $qrcode_image = '';
        $merchant = [];
        $uid = UserList::where('phone', $phone)->where('flow_id', $for_flow_id)->value('id');
        $customer = self::where('uid', $uid)->where('flow_id', $for_flow_id)->order('id desc')->field('customer_id,merchant_id')->find();
        if (!empty($customer['customer_id'])) {
            $qrcode_image = Customer::where('id', $customer['customer_id'])->value('qr_code');
        }
        if (!empty($customer['merchant_id'])) {
            $merchant = Merchant::where('id', $customer['merchant_id'])->field('customer_qrcode_explain,customer_explain_status')->find();
        }
        $data['qrcode_explain'] = isset($merchant['customer_qrcode_explain']) && !empty($merchant['customer_qrcode_explain']) ? json_decode($merchant['customer_qrcode_explain']) : [];
        $data['explain_status'] = $merchant['customer_explain_status'] ?? 0;
        $data['qrcode_image'] = !empty($qrcode_image) ? (strpos($qrcode_image, 'https') !== false ? $qrcode_image : str_replace('http', 'https', $qrcode_image)) : '';
        $data['app_name'] = '';
        return $data;
    }

    //长按识别二维码
    public static function discernQrCode($params = [])
    {
        extract($params);
        self::where('uid', $GLOBALS['uid'])->where('flow_id', $for_flow_id)->update(['is_discern_qrcode' => 1]);
    }

    //免费报名
    public static function freeApplyAppFlow($params = [])
    {
        extract($params);
        $ageRangeId = $age_range_id ?? 0;
        $channelInfo = Channel::getChannelAppClass($channel);
        Db::startTrans();
        try {
            $phone = (new OneClickPhoneLogin())->oneClickCheck(['token' => $token, 'accessToken' => $accessToken], $app_bundle_id);
            $appFlowInfo = ForFlow::where('status',1)
                ->where('type',2)
                ->whereFindInSet('app_ids',$channelInfo['app_id'])
                ->order('id','desc')
                ->find();
            if (empty($appFlowInfo)) {
                new Exception('该名额已结束');
            }
            if (self::checkApplyAppFlow($phone,$appFlowInfo->id)) {
                new Exception('已领取名额');
            }
            $merchantId = self::getMerchantId($channelInfo['app_id'],$ageRangeId);
            $merchantInfo = Merchant::find($merchantId);
            if (empty($merchantInfo) || $merchantInfo->is_switch == 0) {
                new Exception('该名额已结束');
            }
            $customerId = (new CustomerService)->getCustomerServiceId($merchantId);
            $customerInfo = Customer::withTrashed()->find($customerId);
            if (empty($customerInfo)) {
                new Exception('该名额已结束');
            }
            $userInfo = UserList::where('phone',$phone)->where('flow_id',$appFlowInfo->id)->find();
            if(empty($userInfo)){
                $userInfo = self::createUserList($params,$phone,$appFlowInfo->id);
            }
            $cityInfo = IpCity::getIpToCity();
            $threadPriceInfo = Merchant::getMerchantThreadPrice($merchantInfo);
            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
            $ageRange = $gatherInfo['name'];
            $ret = self::create([
                'uid' => $userInfo->id,
                'flow_id' => $appFlowInfo->id,
                'merchant_id' => $merchantId,
                'customer_id' => $customerId,
                'province' => $cityInfo['province_name'] ?? '',
                'city' => $cityInfo['city_name'] ?? '',
                'age' => $ageRange,
                'channel' => $userInfo->channel,
                'app_id' => $channelInfo['app_id'],
                'app_class_id' => $channelInfo['app_class_id'],
                'thread_price' => $threadPriceInfo['thread_price'],
                'thread_price_type' => $threadPriceInfo['thread_price_type'],
                'source' => 3,
            ]);
            Event::trigger('ApplySuccessAfter', ['merchant' => $merchantInfo, 'thread' => ['uid' => $userInfo->id,'customerId' => $customerId,'thread_price' => $threadPriceInfo['thread_price']]]);
            Db::commit();
            return ['for_flow_id' => $appFlowInfo->id,'phone' => $phone,'is_jump_miniprogram' => $merchantInfo->is_jump_miniprogram,'wx_mini_pageurl' => 'pages/QRCode/QRCode','token' => $userInfo->token];
        } catch (\Exception $e) {
            Db::rollback();
            new Exception('领取失败');
        }
    }

    //获取商户
    public static function getMerchantId($appId, $ageRangeId = 0)
    {
        $merchantId = 0;
        $merchantIds = Course::where('status',1)
            ->whereFindInSet('app_ids',$appId)
            ->group('merchant_id')
            ->column('merchant_id');
        if(!empty($merchantIds)){
            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($ageRangeId, 'age_range_id');
            $ageRange = $gatherInfo['name'];
            $merchantList = Merchant::whereIn('id',$merchantIds)
                ->where('is_switch',1)
                ->field('id,age_range_weight_json,is_source')
                ->select()
                ->toArray();
            if(!empty($merchantList)) {
                $sourceY = 0;
                $sourceN = 0;
                foreach ($merchantList as $key => &$val) {
                    if (!empty($ageRange)) {
                        $ageRangeWeight = json_decode($val['age_range_weight_json'], true);
                        $val['weight'] = isset($ageRangeWeight[$ageRange]) && !empty($ageRangeWeight[$ageRange]) ? $ageRangeWeight[$ageRange] : 0;
                        if ($val['weight'] > 0) {
                            if ($val['is_source'] == 1) {
                                $sourceN++;
                            }
                            if ($val['is_source'] == 2) {
                                $sourceY++;
                            }
                        } else {
                            unset($merchantList[$key]);
                        }
                    }else{
                        $val['weight'] = 10;
                        if ($val['is_source'] == 1) {
                            $sourceN++;
                        }
                        if ($val['is_source'] == 2) {
                            $sourceY++;
                        }
                    }
                    unset($merchantList[$key]['age_range_weight_json']);
                }
                if ($sourceY > 0 && $sourceN > 0) {
                    foreach ($merchantList as $item => $value) {
                        if ($value['is_source'] == 1) {
                            unset($merchantList[$item]);
                        }
                    }
                }
                $merchantId = (new WeightService)->initData($merchantList);
            }
        }
        return $merchantId;
    }

    //添加注册用户信息
    public static function createUserList($params = [], $phone, $appFlowId = 0)
    {
        extract($params);
        $userInfo = UserList::where('phone',$phone)->where('flow_id',$appFlowId)->find();
        $customAnswer = isset($custom_answer) && !empty($custom_answer) ? $custom_answer : [];
        $answerId = isset($answer_id) && !empty($answer_id) ? $answer_id : [];
        if(!empty($userInfo)){
            return $userInfo;
        }else {
            try {
                $nickname = $nickname ?? '';
                $is_switch = 0;
                $merchant = Merchant::where('is_switch', 1)->where('is_source', 2)->count();
                if ($merchant > 0) {
                    $is_switch = 1;
                }
                $oldUser = UserList::where('phone', $phone)->find();
                if (empty($nickname)) {
                    $nickname = UserList::whereLike('nickname', '匿名%')->order('id desc')->value('nickname');
                    if (!empty($nickname)) {
                        $number = (int)mb_substr($nickname, 2);
                        $number = $number + 1;
                        $nickname = '匿名' . $number;
                    } else {
                        $nickname = '匿名1';
                    }
                }
                $user = UserList::create([
                    'phone' => $phone,
                    'nickname' => $nickname,
                    'login_time' => date("Y-m-d H:i:s"),
                    'login_ip' => request()->ip(),
                    'channel' => $channel,
                    'wx_nickname' => isset($oldUser->wx_nickname) ? $oldUser->wx_nickname : '',
                    'avatar' => isset($oldUser->avatar) ? $oldUser->avatar : '',
                    'wxopenid' => isset($oldUser->wxopenid) ? $oldUser->wxopenid : '',
                    'age_range_id' => isset($age_range_id) ? $age_range_id : 0,
                    'identity_id' => isset($identity_id) ? $identity_id : 0,
                    'education_id' => isset($education_id) ? $education_id : 0,
                    'is_has_computer_id' => isset($is_has_computer) ? $is_has_computer : 0,
                    'study_goal_id' => isset($study_goal_id) ? $study_goal_id : 0,
                    'app_flow_answer' => json_encode(['flow_id' => $appFlowId,'answer_id' => $answerId,'que_answer' => $customAnswer]),
                    'is_switch' => $is_switch,
                    'flow_id' => $appFlowId,
                    'source' => 3,
                ]);
                $token = getJwtToken($user->id);
                $userInfo = UserList::find($user->id);
                $userInfo['token'] = $token;
                return $userInfo;
            }catch (\Exception $e){
                Db::rollback();
                new ExceptionStd('领取失败');
            }
        }

    }

}