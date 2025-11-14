<?php
/**
 * 账户图标表模型
 */

namespace app\model\api\vestbag;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\ExceptionStd;

class VestAccountIcon extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'vest_account_icon';

    public static  function getAccountIconList($param = [])
    {
        extract($param);
        return self::field('id,icon_name,icon_bright_url,icon_dark_url')->where('type', $type)->select()->toArray();
    }

}
