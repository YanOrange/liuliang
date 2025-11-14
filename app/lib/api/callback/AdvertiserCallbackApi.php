<?php

namespace app\lib\api\callback;

use app\lib\api\http\Http;
use app\lib\api\exception\Exception;
use app\model\api\Channel;
use app\model\api\AdvertiserCallbackChannel;

class AdvertiserCallbackApi
{
    public function channelAdvertiserCallback($callBackData = [], $callbackType = 0)
    {
        $callbackFlag = false;
        $behaviorType = $callBackData['dataType'];
        $channelInfo = Channel::getChannelAppClass($callBackData['user']['channel']);
        $advertiserCallbackChannel = AdvertiserCallbackChannel::where('channel_id',$channelInfo['channel_id'])
            ->whereFindInSet('behavior_type',$behaviorType)
            ->where('is_callback',1)
            ->find();
        if(!empty($advertiserCallbackChannel)) {
            if($behaviorType == 'pay'){
                if($advertiserCallbackChannel->callback_type == $callbackType){
                    $callbackFlag = true;
                    $callBackData['users']['ascribeType'] = $advertiserCallbackChannel->attributional_type == 1 ? 1 : 0;
                    event('UserCallbackRecord', $callBackData);//广告主回传
                }
            }else{
                $callbackFlag = true;
                $callBackData['user']['ascribeType'] = $advertiserCallbackChannel->attributional_type == 1 ? 1 : 0;
                event('UserCallbackRecord', $callBackData);//广告主回传
            }
        }
        return $callbackFlag;
    }
}