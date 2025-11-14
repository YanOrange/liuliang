<?php
/**
 * 隐私协议表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\model\api\Channel;
class PrivacyAgreement extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'privacy_agreement';

    //获取隐私协议url
    public static function getPrivacyAgreementUrl($params)
    {
        extract($params);
        $channelId = Channel::where('channel_name', $channel)->value('id');
        $channelId = $channelId ?? 0;
        $id = self::whereFindInSet('channel_ids', $channelId)->order('id desc')->value('id');
        return input('server.REQUEST_SCHEME') . '://' . input('server.SERVER_NAME') . '/api.user/getPrivacyAgreementUrl?id=' . $id . '&channel=' . $channel;
    }
    //获取隐私协议内容
    public static function getPrivacyAgreementContent($params)
    {
        extract($params);
        $channelId = Channel::where('channel_name', $channel)->value('id');
        $channelId = $channelId ?? 0;
        return self::whereFindInSet('channel_ids', $channelId)->order('id desc')->value('content');
    }

}
