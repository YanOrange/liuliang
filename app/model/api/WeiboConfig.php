<?php
/**
 * 应用表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class WeiboConfig extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'weibo_config';

}
