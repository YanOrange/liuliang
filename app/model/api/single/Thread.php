<?php
namespace app\model\api\single;

use app\model\api\single\SingleCourse;
use app\model\api\Customer;
use app\model\api\single\Merchant;
use app\model\api\UserList;
use app\model\api\Channel;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\lib\api\service\CustomerService;
use app\lib\api\service\MerchantService;
use app\lib\api\city\IpCity;
use think\facade\Event;
use think\facade\Db;
use think\facade\Config;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use app\model\api\single\SingleResource;
use think\facade\Log;

class Thread extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'thread';

    //线索单价类型
    const THREAD_PRICE_TYPE_LEISURE = 1; //闲时
    const THREAD_PRICE_TYPE_PEAK = 2; //高峰

    protected $hidden = [];
    //是否已报名
    public static function checkApplyCourse($courseId = 0)
    {
        if (!isset($GLOBALS['uid'])) {
            return 0;
        }
        $checkApplyCourse = self::where('uid', $GLOBALS['uid'])->where('course_id', $courseId)->count();
        return $checkApplyCourse ? 1 : 0;
    }

    //获取已报名课程客服二维码
    public static function getApplyQrCode($params = [])
    {
        extract($params);
        $qrcode_image = '';
        $merchant = [];
        $customer = self::where('uid', $GLOBALS['uid'])->where('course_id', $course_id)->order('id desc')->field('customer_id,merchant_id')->find();
        if (!empty($customer['customer_id'])) {
            $qrcode_image = Customer::where('id', $customer['customer_id'])->value('qr_code');
        }
        if (!empty($customer['merchant_id'])) {
            $merchant = Merchant::where('id', $customer['merchant_id'])->field('customer_qrcode_explain,customer_explain_status')->find();
        }
        $data['qrcode_explain'] = isset($merchant['customer_qrcode_explain']) && !empty($merchant['customer_qrcode_explain']) ? json_decode($merchant['customer_qrcode_explain']) : [];
        $data['explain_status'] = $merchant['customer_explain_status'] ?? 0;
        $data['qrcode_image'] = !empty($qrcode_image) ? (strpos($qrcode_image, 'https') !== false ? $qrcode_image : str_replace('http', 'https', $qrcode_image)) : '';
        return $data;
    }

    //资源、消息商户客服
    public static function getResourceMessageCustomerQrcode($params = [])
    {
        extract($params);
        $data = [];
        $merchantId = isset($merchant_id) && !empty($merchant_id) ? $merchant_id : 0;
        $resourceMessageId = isset($resource_message_id) && !empty($resource_message_id) ? $resource_message_id : 0;
        $beType = isset($be_type) && !empty($be_type) ? $be_type : 1;
        if(!empty($merchantId) && !empty($resourceMessageId)){
            if ($beType == 1) {
                $customer = self::where('uid', $GLOBALS['uid'])->where('merchant_id', $merchantId)->where('resource_id', $resourceMessageId)->order('id desc')->field('customer_id,merchant_id')->find();
            }
            if ($beType == 2) {
                $customer = self::where('uid', $GLOBALS['uid'])->where('merchant_id', $merchantId)->where('app_message_id', $resourceMessageId)->order('id desc')->field('customer_id,merchant_id')->find();
            }
            if (!empty($customer['customer_id'])) {
                $qrcode_image = Customer::where('id', $customer['customer_id'])->value('qr_code');
            }
            if (!empty($merchantId)) {
                $merchant = Merchant::where('id', $merchantId)->field('customer_qrcode_explain_single,customer_explain_status')->find();
            }
            $data['qrcode_explain'] = isset($merchant['customer_qrcode_explain_single']) && !empty($merchant['customer_qrcode_explain_single']) ? json_decode($merchant['customer_qrcode_explain_single']) : [];
            $data['explain_status'] = $merchant['customer_explain_status'] ?? 0;
            $data['qrcode_image'] = !empty($qrcode_image) ? (strpos($qrcode_image, 'https') !== false ? $qrcode_image : str_replace('http', 'https', $qrcode_image)) : '';
        }
        return $data;
    }

    //长按识别二维码
    public static function discernQrCode($params = [])
    {
        extract($params);
        self::where('uid', $GLOBALS['uid'])->where('course_id', $course_id)->update(['is_discern_qrcode' => 1]);
    }

    //资源、消息长按识别二维码
    public static function resourceMessageDiscernQrCode($params = [])
    {
        extract($params);
        $merchantId = isset($merchant_id) && !empty($merchant_id) ? $merchant_id : 0;
        $resourceMessageId = isset($resource_message_id) && !empty($resource_message_id) ? $resource_message_id : 0;
        $beType = isset($be_type) && !empty($be_type) ? $be_type : 1;
        if(!empty($merchantId) && !empty($resourceMessageId)) {
            if ($beType == 1) {
                self::where('uid', $GLOBALS['uid'])->where('merchant_id', $merchantId)->where('resource_id', $resourceMessageId)->update(['is_discern_qrcode' => 1]);
            }
            if ($beType == 2) {
                self::where('uid', $GLOBALS['uid'])->where('merchant_id', $merchantId)->where('app_message_id', $resourceMessageId)->update(['is_discern_qrcode' => 1]);
            }
        }
    }

    //免费报名
    public static function freeApplyCourse($params = [])
    {
        extract($params);
        try {
            $merchantId = 0;
            $courseType = isset($course_type) ? $course_type : 0;
            if(empty($courseType)){
                new Exception('报名参数错误');
            }
            if (self::checkApplyCourse($course_id)) {
                new Exception('该课程已报名过');
            }
            $userInfo = UserList::find($GLOBALS['uid']);
            if($courseType == 1) {
                $courseInfo = Course::find($course_id);
                if (empty($courseInfo) || $courseInfo->status == 0) {
                    new Exception('该课程已结束报名');
                }
                $merchantId = $courseInfo->merchant_id;
                $merchantInfo = Merchant::find($merchantId);
            }
            if($courseType == 2) {
                $courseInfo = SingleCourse::find($course_id);
                if (empty($courseInfo) || $courseInfo->status == 0) {
                    new Exception('该课程已结束报名');
                }
                $merchantId = (new MerchantService)->getMerchantServiceId($courseInfo->merchant_ids, $userInfo->age_range_id);
                $merchantInfo = Merchant::find($merchantId);
            }
            if (empty($merchantInfo) || $merchantInfo->is_switch == 0) {
                new Exception('该课程已结束报名');
            }
            if ($courseInfo->entry_fee > 0) {
                new Exception('该课程需要先支付后报名');
            }
            $customerId = (new CustomerService)->getCustomerServiceId($merchantId);
            $customerInfo = Customer::withTrashed()->find($customerId);
            if (empty($customerInfo)) {
                new Exception('该课程已结束报名');
            }

            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
            $ageRange = $gatherInfo['name'];
            $channelInfo = Channel::getChannelAppClass($userInfo->channel);
            $weightArr = isset($merchantInfo->age_range_weight_json) && !empty($merchantInfo->age_range_weight_json) ? json_decode($merchantInfo->age_range_weight_json, true) : [];
            $weight = isset($weightArr[$ageRange]) && !empty($weightArr[$ageRange]) ? $weightArr[$ageRange] : 0;
            if($weight <= 0){
                new Exception('你不满足报名要求');
            }

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
                $ret = self::create([
                    'uid' => $GLOBALS['uid'],
                    'course_id' => $course_id,
                    'entry_fee' => $courseInfo->entry_fee,
                    'merchant_id' => $merchantId,
                    'customer_id' => $customerId,
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
                    'landing_page_id' => $landing_page_id ?? 0,
                    'thread_type' => $threadType,
                    'is_many_organization' => $channelInfo['is_many_organization'],
                    'is_free_try' => $merchantInfo->is_free_try,
                    'is_test' => $userInfo->is_test,
                    'is_special_channel_customer' => checkYxyhChannel($userInfo->channel)
                ]);
                if($ret) {
                    Event::trigger('ApplySuccessAfter', ['merchant' => $merchantInfo, 'thread' => ['uid' => $userInfo->id, 'customerId' => $customerId, 'thread_price' => $threadPriceInfo['thread_price'], 'threadId' => $ret->id]]);
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
            Log::error('单机构2.0免费报名错误：'.$e->getMessage());
            new Exception('报名失败');
        }
    }

    //是否已报名资源
    public static function checkApplyResource($resourceId = 0)
    {
        if (!isset($GLOBALS['uid'])) {
            return 0;
        }
        $checkApplyResource = self::where('uid', $GLOBALS['uid'])->where('resource_id', $resourceId)->count();
        return $checkApplyResource ? 1 : 0;
    }

    //免费报名资源
    public static function freeApplyResource($params = [])
    {
        extract($params);
        try {
            if (self::checkApplyResource($resource_id)) {
                new Exception('该课程已报名过');
            }
            $resourceInfo = SingleResource::find($resource_id);
            if (empty($resourceInfo) || $resourceInfo->status == 0) {
                new Exception('该课程已结束报名');
            }
            $userInfo = UserList::find($GLOBALS['uid']);
            $channelInfo = Channel::getChannelAppClass($userInfo->channel);
            $merchantId = (new MerchantService)->getMerchantServiceId($resourceInfo->merchant_ids, $userInfo->age_range_id);
            $merchantInfo = Merchant::find($merchantId);
            if (empty($merchantInfo) || $merchantInfo->is_switch == 0) {
                new Exception('该课程已结束报名');
            }
            $customerId = (new CustomerService)->getCustomerServiceId($merchantId);
            $customerInfo = Customer::withTrashed()->find($customerId);
            if (empty($customerInfo)) {
                new Exception('该课程已结束报名');
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
                $ret = self::create([
                    'uid' => $GLOBALS['uid'],
                    'resource_id' => $resource_id,
                    'merchant_id' => $merchantId,
                    'customer_id' => $customerId,
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
                    'is_many_organization' => $channelInfo['is_many_organization'],
                    'is_free_try' => $merchantInfo->is_free_try,
                    'is_test' => $userInfo->is_test,
                    'is_special_channel_customer' => checkYxyhChannel($userInfo->channel)

                ]);
                if($ret) {
                    Event::trigger('ApplySuccessAfter', ['merchant' => $merchantInfo, 'thread' => ['uid' => $userInfo->id, 'customerId' => $customerId, 'thread_price' => $threadPriceInfo['thread_price'],'threadId' => $ret->id]]);
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
            new Exception('报名失败');
        }
    }

    public function merchant()
    {
        return $this->belongsTo('app\model\api\single\Merchant','merchant_id','id')->removeOption('soft_delete');
    }

    public function course()
    {
        return $this->belongsTo('app\model\api\single\SingleCourse','course_id','id')->removeOption('soft_delete');
    }

    public function customer()
    {
        return $this->belongsTo('app\model\api\Customer','customer_id','id')->removeOption('soft_delete');
    }

}
