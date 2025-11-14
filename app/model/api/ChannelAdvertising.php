<?php

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\api\Channel;

class ChannelAdvertising extends BaseModel
{
    use SoftDelete;

    protected $name = 'channel_advertising';

    public static function getPageAdvertisingV2($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $advertisingData = self::field('image,open_mode')->where('channel_id', $channelInfo['channel_id'])->where('status', 1)->order('id desc')->find();
        return $advertisingData;
    }
}