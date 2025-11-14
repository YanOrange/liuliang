<?php
/**
 * 渠道表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class OnlineServiceWechat extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'online_service_wechat';

    public function getDefaultSpeechAttr($value, $data)
    {
        return isset($data['default_speech']) && !empty($data['default_speech']) ? json_decode($data['default_speech'], true) : new \stdClass();
    }
}
