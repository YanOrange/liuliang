<?php

namespace app\lib\api\other;
use app\model\admin\NoJumpWechatPhone;
use app\model\api\UserList;
use app\model\api\Merchant;
use app\model\api\Channel;
use app\model\api\Thread;
use app\model\api\Course as CourseModel;

class CourseJumpWx
{
    //获取课程跳微状态
    public static function getCourseJumpWxStatus($courseId = 0, $channel = null, $uid = 0, $ip = null)
    {
        $user = UserList::where('id', !empty($uid) ? $uid : $GLOBALS['uid'])->find();
        //$channel = $user->channel;

        if (empty($channel)) {
            $channel = $user->channel;
        }
        if ($channel == 'yqclzs_ios') {
            return 0;
        }
        $h = date("H");
        if ($channel == 'msgdyq_oppo' && $h >= 8 && $h <= 20) {
            return 0;

        }
        if (UserCity::checkCity($channel, $ip)) {
            return 0;
        }
        $channelInfo = Channel::where('channel_name', $channel)->find();
        $isJumpWxStatus = self::patrolJumpWx($user);
        if ($isJumpWxStatus) {
            $isJumpWxStatus = self::versionJumpWx($channelInfo, $user);
            if ($isJumpWxStatus) {
                $isJumpWxStatus = self::MerchantJumpWx($courseId, !empty($uid) ? $uid : $GLOBALS['uid']);
            }
        }
        // var_dump($isJumpWxStatus > 0 ? $isJumpWxStatus : 0);
        return $isJumpWxStatus > 0 ? $isJumpWxStatus : 0;
    }
    //获取商户跳微状态
    public static function MerchantJumpWx($courseId = 0, $uid = 0)
    {
        $isJumpWxStatus = 1;
        $merchantId = Thread::where('course_id|part_course_id','=',$courseId)->where('uid', $uid)->order('id desc')->value('merchant_id');
        // var_dump($merchantId);
        if (!empty($merchantId)) {
            $isJumpWxStatus = Merchant::where('id', $merchantId)->value('is_jump_miniprogram');
        }
        //  var_dump($isJumpWxStatus);
        return $isJumpWxStatus;
    }
    //过滤巡查跳微状态app\middleware\api
    public static function patrolJumpWx($user = null)
    {
        $checkCount = 0;
        $isJumpWxStatus = 1;
        if (!empty($user['phone'])) {
            $checkCount = NoJumpWechatPhone::where('phone', $user['phone'])->count();
        }
        if ($checkCount > 0) {
            $isJumpWxStatus = 0;
        }
        return $isJumpWxStatus;
    }
    //根据渠道版本号判断是否跳微信
    public static function versionJumpWx($channelInfo = null, $user = null)
    {
        $isJumpWxStatus = 1;
        if (isset($channelInfo['jump_wechat_version']) && !empty($channelInfo['jump_wechat_version'])) {
            $isJumpWxStatus = $channelInfo['is_jump_miniprogram'];
        }
        return $isJumpWxStatus;
    }
}