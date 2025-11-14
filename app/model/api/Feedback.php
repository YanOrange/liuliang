<?php
/**
 * 意见反馈表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class Feedback extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'feedback';
    //手机验证码登录
    public static function saveFeedback($params = [])
    {
        extract($params);
        if(!trim($content)){
            new Exception('反馈内容无效');
        }
        $ret = self::create([
            'content' => $content,
            'contact' => $contact,
            'uid' => $GLOBALS['uid'],
        ]);
        if ($ret !== false) {
            return;
        }
        new Exception('系统异常');
    }

}
