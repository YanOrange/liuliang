<?php
/**
 * 报名表模型
 */

namespace app\model\api\learn;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\model\api\Channel;

class LearnCooperation extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'learn_cooperation';

    public static function addCooperation($params)
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        self::create([
            'channel_id' => $channelInfo['channel_id'],
            'nickname' => $nickname,
            'phone' => $phone,
            'matter_content' => $matter_content ?? ''
        ]);
        return [];
    }

}