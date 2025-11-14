<?php

namespace app\lib\api\other;
use app\lib\api\exception\Exception;
use app\model\api\Course;
use app\lib\api\service\MerchantServiceJob;
use app\model\api\Thread;
use app\model\api\Channel;
use app\model\api\App;
use app\model\api\Merchant;
//公共课程
class CommonCourseV2
{
    //根据公共课程获取好课推荐课程id
    public static function getCommonCourseToCourseId($channel = null, $entryFee = 0)
    {
        $applyInfo = Thread::where('uid', $GLOBALS['uid'])->whereDay('create_time')->order('id desc')->find();
        $channelInfo = Channel::getChannelAppClass($channel);
        $appInfo = App::where('id',$channelInfo['app_id'])->find()->toArray();
        if (!empty($applyInfo)) {
            $isJumpWx = CourseJumpWx::getCourseJumpWxStatus($applyInfo->course_id, $channel);
            return ['course_id' => $applyInfo->course_id, 'merchant_id' => (string)$applyInfo->merchant_id,'is_jump_miniprogram' => $isJumpWx, 'is_apply' => 1, 'apply_success_msg' => Merchant::where('id', $applyInfo->merchant_id)->value('apply_success_msg')];
        }
        $merchantList = MerchantServiceJob::getMerchantIsPayCount($channelInfo);
        $merchantId = MerchantServiceJob::sortMerchantList($merchantList, $channelInfo, $entryFee);
        $courseId = Course::where('merchant_id', $merchantId)->where('course_type',0)->value('id');
        return ['course_id' => $courseId, 'merchant_id' => (string)$merchantId,'is_jump_miniprogram' => CourseJumpWx::getCourseJumpWxStatus($courseId, $channel),'is_apply' => 0, 'apply_success_msg' => Merchant::where('id', $merchantId)->value('apply_success_msg')];
    }
}