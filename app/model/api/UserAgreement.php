<?php
/**
 * 用户协议表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\model\api\Channel;
use app\model\Conf;
class UserAgreement extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'user_agreement';

    //获取用户协议url
    public static function getUserAgreementUrl($params)
    {
        extract($params);
        $channelId = Channel::where('channel_name', $channel)->value('id');
        $channelId = $channelId ?? 0;
        $id = self::whereFindInSet('channel_ids', $channelId)->order('id desc')->value('id');
        return input('server.REQUEST_SCHEME') . '://' . input('server.SERVER_NAME') . '/api.user/getUserAgreementUrl?id=' . $id .'&channel='.$channel;
    }

    //获取用户协议url
    public static function getUserAgreementContent($params)
    {
        extract($params);
        $channelId = Channel::where('channel_name', $channel)->value('id');
        $channelId = $channelId ?? 0;
        return self::whereFindInSet('channel_ids', $channelId)->order('id desc')->value('content');
    }
    
    //第三方信息共享清单
    public static function getSdkAgreementContent($params = [])
    {
        return Conf::where('key', 'sdk_agreement_content')->value('value');
    }
}
