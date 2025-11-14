<?php

namespace app\model\api\single;

use app\lib\api\city\IpCity;
use app\lib\api\service\CustomerService;
use app\lib\api\service\MerchantService;
use app\model\api\Channel;
use app\model\api\Customer;
use app\model\api\single\Merchant;
use app\model\api\UserList;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use laytp\BaseModel;
use think\facade\Event;
use think\model\concern\SoftDelete;
use app\model\api\single\AppMerchantMessageConfig;
use think\facade\Db;
use app\lib\api\exception\Exception;
use app\model\api\single\AppMerchantMessageUser;

class AppMerchantMessage extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'app_merchant_message';

    protected $hidden = ['merchant_ids'];

    //消息列表
    public static function getMerchantMessageList($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $merchantList = \app\model\api\single\Merchant::getMerchantIds($channelInfo);
        $merchantMessageConfigList = [];
        if(!empty($merchantList)){
            foreach($merchantList as $val) {
                $merchantMessageConfigList[] = AppMerchantMessageConfig::where('status', 1)
                    ->whereFindInSet('merchant_ids', $val['id'])
                    ->whereFindInSet('app_ids', $channelInfo['app_id'])
                    ->field('id')
                    ->select();
            }
        }
        $merchantMessageIds = [];
        foreach($merchantMessageConfigList as $key=>$item){
            if(empty($item)) {
                unset($merchantMessageIds[$key]);
            }else{
                foreach($item as $val){
                    $merchantMessageIds[] = $val['id'];
                }
            }
        }
        $merchantMessageList = AppMerchantMessageConfig::whereIn('id',$merchantMessageIds)
            ->field('id,merchant_ids,nickname,avatar,num,btn_desc')
            ->order('id desc')
            ->select();
        return $merchantMessageList;
    }

    //标记已读消息
    public static function readMessage($params = [])
    {
        extract($params);
        $appMessageIds = explode(',',$app_message_id);
        Db::startTrans();
        try {
            foreach ($appMessageIds as $app_message_id){
                AppMerchantMessageUser::create([
                    'app_message_id' => $app_message_id,
                    'uid' => $GLOBALS['uid'],
                    'is_read' => 1
                ]);
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            new Exception("设置失败");
        }

    }

    //商户客服
    public static function getCustomerQrcode($params = [])
    {
        extract($params);
        $data = [];
        $merchantId = isset($merchant_id) && !empty($merchant_id) ? $merchant_id : 0;
        if(!empty($merchantId)){
            $customerId = (new CustomerService)->getCustomerServiceId($merchantId);
            if (!empty($customerId)) {
                $qrcode_image = Customer::where('id', $customerId)->value('qr_code');
            }
            if (!empty($merchantId)) {
                $merchant = Merchant::where('id', $merchantId)->field('customer_qrcode_explain,customer_explain_status')->find();
            }
            $data['qrcode_explain'] = isset($merchant['customer_qrcode_explain']) && !empty($merchant['customer_qrcode_explain']) ? json_decode($merchant['customer_qrcode_explain']) : [];
            $data['explain_status'] = $merchant['customer_explain_status'] ?? 0;
            $data['qrcode_image'] = !empty($qrcode_image) ? (strpos($qrcode_image, 'https') !== false ? $qrcode_image : str_replace('http', 'https', $qrcode_image)) : '';
        }
        return $data;
    }

    //是否已报名消息
    public static function checkApplyMerchantMessage($merchantAppId = 0, $merchantId = 0)
    {
        if (!isset($GLOBALS['uid'])) {
            return 0;
        }
        $checkApplyMerchantMessage = Thread::where('uid', $GLOBALS['uid'])
            ->where('app_message_id', $merchantAppId)
            ->where('merchant_id',$merchantId)
            ->count();
        return $checkApplyMerchantMessage ? 1 : 0;
    }

    //免费报名消息
    public static function freeApplyMerchantMessage($params = [])
    {
        extract($params);
        try {
            $merchantMessageInfo = AppMerchantMessageConfig::find($app_message_id);
            if (empty($merchantMessageInfo) || $merchantMessageInfo->status == 0) {
                new Exception('抱歉同学，以上活动都已结束！');
            }
            $userInfo = UserList::find($GLOBALS['uid']);
            $channelInfo = Channel::getChannelAppClass($userInfo->channel);
            $merchantId = (new MerchantService)->getMerchantServiceId($merchantMessageInfo->merchant_ids, $userInfo->age_range_id);
            if (self::checkApplyMerchantMessage($merchantMessageInfo->id,$merchantId)) {
                new Exception('抱歉同学，以上活动都已结束！');
            }
            $merchantInfo = Merchant::find($merchantId);
            if (empty($merchantInfo) || $merchantInfo->is_switch == 0) {
                new Exception('抱歉同学，以上活动都已结束！');
            }
            $customerId = (new CustomerService)->getCustomerServiceId($merchantId);
            $customerInfo = Customer::withTrashed()->find($customerId);
            if (empty($customerInfo)) {
                new Exception('抱歉同学，以上活动都已结束！');
            }

            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
            $ageRange = $gatherInfo['name'];
            $store = '';
            if(strpos($userInfo->channel, 'oppo') !== false) $store = 'oppo';
            if(strpos($userInfo->channel, 'vivo') !== false) $store = 'vivo';
            if(strpos($userInfo->channel, 'xiaomi') !== false) $store = 'xiaomi';
            $threadType = 0;
            if(isset($merchantInfo->is_jump_miniprogram)){
                if($merchantInfo->is_jump_miniprogram > 0){
                    $threadType = 3;
                }else{
                    $threadType = 1;
                }
            }

            $cityInfo = IpCity::getIpToCity();
            $threadPriceInfo = \app\model\api\Merchant::getMerchantThreadPrice($merchantInfo);
            $redis = get_redis();
            $redisKey = env('redis.merchant_amount_redis_v2_key'). $merchantInfo->id;
            if (!$redis->exists($redisKey)) {
                $redis->set($redisKey, floatToInt($merchantInfo->residue_amount));
            }
            $redis->watch($redisKey);
            $merchantStore = $redis->get($redisKey);
            $threadPrice = $userInfo->is_test == 1 ? 0 : floatToInt($threadPriceInfo['thread_price']);
            if ($merchantStore < $threadPrice) {
                new Exception('该课程名额已满');
            }
            $redis->multi();
            $redis->decrBy($redisKey, $threadPrice);
            $result = $redis->exec();
            if($result) {
                $ret = Thread::create([
                    'uid' => $GLOBALS['uid'],
                    'merchant_id' => $merchantId,
                    'customer_id' => $customerId,
                    'app_message_id' => $merchantMessageInfo->id,
                    'province' => $cityInfo['province_name'] ?? '',
                    'city' => $cityInfo['city_name'] ?? '',
                    'age' => $ageRange,
                    'channel' => $userInfo->channel,
                    'store' => $store,
                    'thread_price' => $threadPriceInfo['thread_price'],
                    'thread_price_type' => $threadPriceInfo['thread_price_type'],
                    'channel_id' => $channelInfo['channel_id'],
                    'app_id' => $channelInfo['app_id'],
                    'app_class_id' => $channelInfo['app_class_id'],
                    'thread_type' => $threadType,
                    'is_many_organization' => $channelInfo['is_many_organization']
                ]);
                if($ret) {
                    Event::trigger('ApplySuccessAfter', ['merchant' => $merchantInfo, 'thread' => ['uid' => $userInfo->id, 'customerId' => $customerId, 'thread_price' => $threadPriceInfo['thread_price']]]);
                    $callBackData = [
                        'user' => $userInfo,
                        'dataType' => 'submit',
                    ];
                    event('UserCallbackRecord', $callBackData);//广告主回传
                    return ['merchant_id' => $merchantId, 'is_jump_miniprogram' => $merchantInfo->is_jump_miniprogram];
                }
                $redis->incrBy($redisKey, $threadPrice);
                new Exception('该课程名额已满');
            }
            new Exception('该课程名额已满');
        } catch (\Exception $e) {
            new Exception('抱歉同学，以上活动都已结束！');
        }
    }

    public function getCreateTimeAttr($value,$data)
    {
        $createTime = 0;
        if(!empty($data['create_time'])){
            if($data['create_time'] > time()){
                $data['create_time'] = time();
            }
            $createTime = date('m月d').date(' H:i',$data['create_time']);
        }
        return $createTime;
    }

}