<?php
/**
 * 课程表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\lib\api\exception\ExceptionStd;
use app\model\api\Thread;
use app\model\api\Course;
use app\model\api\Merchant;
use app\model\api\UserList;
use app\model\api\PayConfig;
use app\lib\api\payapi\Alipay;
use app\lib\api\payapi\Wxpay;
use think\facade\Db;
use think\facade\Config;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use app\lib\api\service\MerchantServiceJob;
use app\model\api\learn\LearnCourse;

class CourseOrder extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'course_order';
    //付费报名1
    public static function payApplyCourse($params = [])
    {
        extract($params);
        $part_course_id = (isset($part_course_id) && !empty($part_course_id)) ? $part_course_id : ((isset($part_courser_id) && !empty($part_courser_id)) ? $part_courser_id : 0);
        if (isset($part_course_id) && !empty($part_course_id)) {
            $isAllowApply = Course::where('id', $part_course_id)->where('course_type',1)->value('is_allow_apply');
            if ($isAllowApply === 0) {
                new ExceptionStd('请注意接听电话');
            }
        }
        if(isset($learn_course_id) && !empty($learn_course_id)){
            return self::payApplyLearnCourse($params);
        }
        if (Thread::checkApplyCourse($course_id)) {
            new ExceptionStd('已经领取过了');
        }
        $courseInfo = Course::find($course_id);
        if (empty($courseInfo)) {
            new ExceptionStd('名额已满3');
        }
        /*if ($courseInfo->entry_fee <= 0) {
            new ExceptionStd('该课程不需要支付');
        }*/
        $partCourseId = 0;
        $userInfo = UserList::find($GLOBALS['uid']);
        $channelInfo = Channel::getChannelAppClass($userInfo->channel);
        if ($channelInfo['is_under_eighteen_apply'] == 0 && $userInfo->age_range_id == 1) {
            new ExceptionStd('该课程仅对符合年龄的人群开放');
        }
    
        if($courseInfo->course_type > 0){
            $partCourseId = $course_id;
            $merchantService = new MerchantServiceJob();
            $merchantList = $merchantService::getMerchantIsPayCount($channelInfo);
            $merchantId = $merchantService::sortMerchantList($merchantList,$channelInfo,$courseInfo->entry_fee);
            if (Thread::checkApplyJobCourse($merchantId)) {
                new ExceptionStd('已经领取过了');
            }
            $merchantInfo = Merchant::find($merchantId);
            $courseInfo = Course::where('merchant_id',$merchantId)->where('course_type',0)->find();
            if(empty($courseInfo)){
                new ExceptionStd('名额已满1');
            }
        }else{
            $merchantInfo = Merchant::find($courseInfo->merchant_id);
        }
        $appMerchantChannelInput = appMerchantChannelInput($userInfo->channel);
        if (empty($merchantInfo) || ($merchantInfo->is_switch == 0 && !$appMerchantChannelInput)) {
            new ExceptionStd('名额已满2');
        }
        //$userInfo = UserList::find($GLOBALS['uid']);
        //$channelInfo = Channel::getChannelAppClass($userInfo->channel);
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
        $ageRange = $gatherInfo['name'];
        $weightArr = isset($merchantInfo->age_range_weight_json) && !empty($merchantInfo->age_range_weight_json) ? json_decode($merchantInfo->age_range_weight_json, true) : [];
        $weight = isset($weightArr[$ageRange]) && !empty($weightArr[$ageRange]) ? $weightArr[$ageRange] : 0;
        if($weight <= 0){
            new ExceptionStd('你不满足领取条件');
        }
        if($channelInfo['is_many_organization'] == 3){
            $checkApplyMerchant = Thread::where('uid', $GLOBALS['uid'])->where('merchant_id', $merchantInfo->id)->where('is_many_organization',3)->count();
            if($checkApplyMerchant >= 3){
                new ExceptionStd('名额已满4');
            }
        }
        $threadPriceInfo = Merchant::getMerchantThreadPrice($merchantInfo, $channelInfo);
        Db::startTrans();
        try {
            $order_sn = create_order_sn();
            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
            $ageRange = $gatherInfo['name'];
            $courseOrderData = [
                'uid' => $GLOBALS['uid'],
                'merchant_id' => $merchantInfo->id,
                'course_id' => $courseInfo->id,
                'part_course_id' => isset($part_course_id) ? $part_course_id : $partCourseId,
                'order_sn' => $order_sn,
                'total_amount' => $courseInfo->entry_fee,
                'original_price' => $courseInfo->original_price ?? 0,
                'channel'      => $userInfo->channel,
                'app_bundle_id' => $app_bundle_id,
                'pay_type' => $pay_type,
                'ip' => request()->ip(),
                'thread_price' => $threadPriceInfo['thread_price'],
                'thread_price_type' => $threadPriceInfo['thread_price_type'],
                'thread_price_origin' => !empty($merchantInfo->thread_price_origin) ? $merchantInfo->thread_price_origin : $threadPriceInfo['thread_price'],
                'age' => $ageRange,
                'channel_id' => $channelInfo['channel_id'],
                'app_id' => $channelInfo['app_id'],
                'app_class_id' => $channelInfo['app_class_id'],
                'landing_page_id' => $landing_page_id ?? 0,
                'app_version' => $app_version ?? 0,
            ];
            $courseOrder = new self();
            $courseOrder->save($courseOrderData);
            $orderParams = [
                'total_amount' => $courseInfo->entry_fee,
                'order_sn' => $order_sn,
                'desc' => '商品订单' . $order_sn,
                'app_bundle_id' => $app_bundle_id,
                'system_type' => $system_type ?? ''
            ];
            Db::commit();
            return self::getPayParams($pay_type, $orderParams);
        } catch (\Exception $e) {
            Db::rollback();
            new ExceptionStd($e->getMessage());
        }
    }

    public static function payApplyLearnCourse($params = [])
    {
        extract($params);
        if (Thread::checkApplyCourse($course_id)) {
            new ExceptionStd('已经领取过了');
        }
        $learnThread = Thread::where('uid',$GLOBALS['uid'])->where('learn_course_id',$learn_course_id)->count();
        if($learnThread){
            new ExceptionStd('已经领取过了');
        }
        $courseInfo = LearnCourse::find($learn_course_id);
        if (empty($courseInfo) || $courseInfo->status == 0) {
            new ExceptionStd('名额已满1');
        }
        /*if ($courseInfo->entry_fee <= 0) {
            new ExceptionStd('该课程不需要支付');
        }*/
        $userInfo = UserList::find($GLOBALS['uid']);
        $channelInfo = Channel::getChannelAppClass($userInfo->channel);
        if ($channelInfo['is_under_eighteen_apply'] == 0 && $userInfo->age_range_id == 1) {
            new ExceptionStd('该课程仅对符合年龄的人群开放');
        }

        $merchantId = Course::where('id',$course_id)->value('merchant_id');
//        $merchantService = new MerchantServiceJob();
//        $merchantList = $merchantService::getMerchantIsPayCount($channelInfo);
//        $merchantId = $merchantService::sortMerchantList($merchantList,$channelInfo,$courseInfo->entry_fee);
        if (Thread::checkApplyJobCourse($merchantId)) {
            new ExceptionStd('已经领取过了');
        }
        $merchantInfo = Merchant::find($merchantId);
        $courseInfo = Course::where('merchant_id',$merchantId)->where('course_type',0)->find();
        if(empty($courseInfo)){
            new ExceptionStd('名额已满2');
        }

        if (empty($merchantInfo) || $merchantInfo->is_switch == 0) {
            new ExceptionStd('名额已满3');
        }
        //$userInfo = UserList::find($GLOBALS['uid']);
        //$channelInfo = Channel::getChannelAppClass($userInfo->channel);
        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
        $ageRange = $gatherInfo['name'];
        $weightArr = isset($merchantInfo->age_range_weight_json) && !empty($merchantInfo->age_range_weight_json) ? json_decode($merchantInfo->age_range_weight_json, true) : [];
        $weight = isset($weightArr[$ageRange]) && !empty($weightArr[$ageRange]) ? $weightArr[$ageRange] : 0;
        if($weight <= 0){
            new ExceptionStd('你不满足领取条件');
        }
        if($channelInfo['is_many_organization'] == 3){
            $checkApplyMerchant = Thread::where('uid', $GLOBALS['uid'])->where('merchant_id', $merchantInfo->id)->where('is_many_organization',3)->count();
            if($checkApplyMerchant >= 3){
                new ExceptionStd('名额已满4');
            }
        }
        $threadPriceInfo = Merchant::getMerchantThreadPrice($merchantInfo, $channelInfo);
        Db::startTrans();
        try {
            $order_sn = create_order_sn();
            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
            $ageRange = $gatherInfo['name'];
            $courseOrderData = [
                'uid' => $GLOBALS['uid'],
                'merchant_id' => $merchantInfo->id,
                'course_id' => $course_id ?? 0,
                'learn_course_id' => $learn_course_id ?? 0,
                'order_sn' => $order_sn,
                'total_amount' => $courseInfo->entry_fee,
                'original_price' => $courseInfo->original_price ?? 0,
                'channel'      => $userInfo->channel,
                'app_bundle_id' => $app_bundle_id,
                'pay_type' => $pay_type,
                'ip' => request()->ip(),
                'thread_price' => $threadPriceInfo['thread_price'],
                'thread_price_type' => $threadPriceInfo['thread_price_type'],
                'thread_price_origin' => !empty($merchantInfo->thread_price_origin) ? $merchantInfo->thread_price_origin : $threadPriceInfo['thread_price'],
                'age' => $ageRange,
                'channel_id' => $channelInfo['channel_id'],
                'app_id' => $channelInfo['app_id'],
                'app_class_id' => $channelInfo['app_class_id'],
                'landing_page_id' => $landing_page_id ?? 0,
                'app_version' => $app_version ?? 0,
            ];
            $courseOrder = new self();
            $courseOrder->save($courseOrderData);
            $orderParams = [
                'total_amount' => $courseInfo->entry_fee,
                'order_sn' => $order_sn,
                'desc' => '商品订单' . $order_sn,
                'app_bundle_id' => $app_bundle_id,
                'system_type' => $system_type ?? ''
            ];
            Db::commit();
            return self::getPayParams($pay_type, $orderParams);
        } catch (\Exception $e) {
            Db::rollback();
            new ExceptionStd($e->getMessage());
        }
    }
    //获取支付参数
    public static function getPayParams($payType = null, $orderParams = [])
    {
        if ($payType == 'alipay') {
            $payParams = Alipay::aliAppPay($orderParams);
            return ['pay_params' => $payParams];
        }
        if ($payType == 'wxpay') {
            $payParams = Wxpay::wxAppPay($orderParams);
            return $payParams;
        }
    }
}
