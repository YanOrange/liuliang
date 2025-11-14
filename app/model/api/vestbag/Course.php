<?php
/**
 * 课程落地页
 */

namespace app\model\api\vestbag;

use app\model\api\Channel;
use app\model\api\Thread;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use think\facade\Db;
use app\lib\api\service\MerchantServiceJob;
use app\model\api\LandingPage;
use app\model\api\Merchant;
use function AlibabaCloud\Client\envNotEmpty;
use app\lib\api\other\CourseJumpWx;

class Course extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'course';
    //渠道跳转微信判断
    public static function getChannelJumpWechat($channel, $appVersion = '1.0.0', $merchantId = 0)
    {
        $isJumpMiniprogram = 1;
        $channelInfo = Channel::where('channel_name',$channel)->field('id,is_jump_miniprogram,jump_wechat_version')->find();
        if (isset($channelInfo['jump_wechat_version']) && !empty($channelInfo['jump_wechat_version']) &&  $channelInfo['jump_wechat_version'] == $appVersion) {
            $isJumpMiniprogram = $channelInfo['is_jump_miniprogram'];
        }
        if ($isJumpMiniprogram) {
            $isJumpMiniprogram = Merchant::where('id', $merchantId)->value('is_jump_miniprogram');
        }
        return $isJumpMiniprogram;
    }
    public static function courseDetail($channel = null, $entryFee = 0, $appVersion = '1.0.0')
    {
        $applyInfo = Thread::where('uid', $GLOBALS['uid'])->order('id desc')->find();
        $channelInfo = Channel::getChannelAppClass($channel);
        if (!empty($applyInfo)) {
            return [
                'course_id' => $applyInfo->course_id,
                'is_jump_miniprogram' => CourseJumpWx::getCourseJumpWxStatus($applyInfo->course_id, $channel),
                'is_apply' => 1,
                'landingPage' => new \stdClass(),
                'btn_desc' => '立即咨询债务解决方案',
            ];
        }
        $merchantList = MerchantServiceJob::getMerchantIsPayCount($channelInfo);
        $merchantId = MerchantServiceJob::sortMerchantList($merchantList, $channelInfo, $entryFee);
        $course = Course::field('id,video_url,btn_desc')->where('merchant_id',$merchantId)->where('course_type',0)->find();
        $courseId = isset($course->id) ? $course->id : 0;
        $landingPage = new \stdClass();
        if ($channelInfo['is_landing_page']) {
            $landingPage = LandingPage::field('id,landing_image,end_image,desc_image,course_id')->where('course_id', $courseId)->find();
            if (!empty($landingPage)) {
                $landingPage = $landingPage->toArray();
                $landingPage['video_url'] =  isset($course->video_url) ? $course->video_url : '';
            }
        }
        return [
            'course_id' => $courseId,
            'is_jump_miniprogram' =>  CourseJumpWx::getCourseJumpWxStatus($courseId, $channel),
            'is_apply' => 0,
            'landingPage' => !empty($landingPage) ? $landingPage : new \stdClass(),
            'btn_desc' => isset($course->btn_desc) && !empty($course->btn_desc) ? $course->btn_desc : '立即咨询债务解决方案',
        ];
    }

    public static function getLandingPageInfo($params = [])
    {
        extract($params);
        return self::courseDetail($channel, 0, $app_version);
    }

}
