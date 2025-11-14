<?php

namespace app\lib\api\other;
use app\lib\api\exception\Exception;
use app\model\api\Course;
use app\lib\api\service\MerchantServiceJob;
use app\model\api\Thread;
use app\model\api\Channel;
use app\model\api\App;
use app\lib\api\other\UserCity;
use app\model\api\Merchant;
use app\model\api\OnlineServiceWechat;
use app\model\api\single\SingleCourse;
//公共课程
class CommonCourse
{
    //根据公共课程获取好课推荐课程id
    public static function getCommonCourseToCourseId($courseId = 0, $channel = null)
    {
        $applyInfo = Thread::where('uid', $GLOBALS['uid'])->where('part_course_id', $courseId)->whereDay('create_time')->order('id desc')->find();
        $channelInfo = Channel::getChannelAppClass($channel);
        $chatConfig = OnlineServiceWechat::whereFindInSet('channel_ids', $channelInfo['channel_id'])->find();
        $chatConfig = !empty($chatConfig) ? $chatConfig : new \stdClass();
        if (!empty($applyInfo)) {
            $isJumpWx = CourseJumpWx::getCourseJumpWxStatus($applyInfo->course_id, $channel);
            return ['course_id' => $applyInfo->course_id, 'merchant_id' => $applyInfo->merchant_id,'is_jump_miniprogram' => $isJumpWx, 'is_apply' => 1, 'chatConfig' => $chatConfig];
        }
        $entryFee = Course::where('id', $courseId)->value('entry_fee');
        if ($entryFee === NULL) {
            $entryFee = SingleCourse::where('id', $courseId)->value('entry_fee');
        }
        $appInfo = App::where('id',$channelInfo['app_id'])->find()->toArray();

        $merchantList = MerchantServiceJob::getMerchantIsPayCount($channelInfo);
        $merchantIdData = Thread::where('uid', $GLOBALS['uid'])->whereDay('create_time')->group('merchant_id')->column('merchant_id');
        if (!empty($merchantIdData)) {
            $merchantData = $entryFee > 0 ? $merchantList['tempPayMerchantData'] : $merchantList['tempFreeMerchantData'] ;
            foreach ($merchantData as $item => $val) {
                if (in_array($val['id'], $merchantIdData)) {
                    unset($merchantData[$item]);
                }
            }
            $merchantDataList = array_values($merchantData);
            if (!empty($merchantDataList)) {
                $randKey = array_rand($merchantDataList);
                $merchantId = $merchantDataList[$randKey]['id'];
            } else {
                $applyInfo = Thread::where('uid', $GLOBALS['uid'])->order('id desc')->find();
                if (!empty($applyInfo)) {
                    $isJumpWx = CourseJumpWx::getCourseJumpWxStatus($applyInfo->course_id, $channel);
                    return ['course_id' => $applyInfo->course_id, 'merchant_id' => $applyInfo->merchant_id,'is_jump_miniprogram' => $isJumpWx, 'is_apply' => 1, 'chatConfig' => $chatConfig];
                }
            }
        } else {
            if (UserCity::checkCity($channel)) {
                $merchantId = Merchant::where('app_class_id', $channelInfo['app_class_id'])->where('is_switch', 1)->where('is_source', 1)->value('id');
            } else {
                $merchantId = MerchantServiceJob::sortMerchantList($merchantList, $channelInfo, $entryFee);

            }
        }
        $courseId = Course::where('merchant_id',$merchantId)->where('course_type',0)->value('id');
        return ['course_id' => $courseId, 'merchant_id' => $merchantId,'is_jump_miniprogram' => CourseJumpWx::getCourseJumpWxStatus($courseId, $channel), 'is_apply' => 0, 'chatConfig' => $chatConfig];
    }
}