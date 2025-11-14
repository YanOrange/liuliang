<?php
/**
 * 个人信息授权传输表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\model\api\Channel;
class PersonalTransferAgreement extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'personal_transfer_agreement';

    //获取个人授权传输协议url
    public static function getPersonalTransferAgreementUrl($params)
    {
        extract($params);
        $channelId = Channel::where('channel_name', $channel)->value('id');
        $channelId = $channelId ?? 0;
        $id = self::whereFindInSet('channel_ids', $channelId)->order('id desc')->value('id');
        return input('server.REQUEST_SCHEME') . '://' . input('server.SERVER_NAME') . '/api.user/getPersonalTransferAgreementUrl?id=' . $id;
    }

    //获取个人授权传输协议内容
    public static function getPersonalTransferAgreementContent($params)
    {
        extract($params);
        $channelId = Channel::where('channel_name', $channel)->value('id');
        $channelId = $channelId ?? 0;
        return self::whereFindInSet('channel_ids', $appId)->order('id desc')->value('content');
    }

}
