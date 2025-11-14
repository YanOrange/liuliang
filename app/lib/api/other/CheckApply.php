<?php

namespace app\lib\api\other;
use app\model\api\Thread;
use app\model\api\Channel;
use app\model\api\Merchant;

class CheckApply
{
    //检测是否报名
    public static function checkApplyCount($channel = null)
    {
        $channelInfo = Channel::getChannelAppClass($channel);
        $isMoreApply = $channelInfo['is_more_apply'];
        if ($isMoreApply) {
            $userCountThread = Thread::where('uid', $GLOBALS['uid'])->count();
            $checkOutsideMerchant = Merchant::where('app_class_id', $channelInfo['app_class_id'])->where('is_switch', 1)->where('is_source', 2)->count();
            $countMerchant = Merchant::where('app_class_id', $channelInfo['app_class_id'])->where('is_switch', 1)->where('is_source', $checkOutsideMerchant ? 2 : 1)->count();
        }
        $userThread = Thread::where('uid', $GLOBALS['uid'])->order('id desc')->find();
        return [
            'isCheckApply' => $isMoreApply ? ($userCountThread >= $countMerchant ? true : false) : (!empty($userThread) ? true : false),
            'userThread' => $userThread,
        ];
    }
}