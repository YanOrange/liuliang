<?php
/**
 * 应用表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class GdtUserClickId extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'gdt_user_clickid';

}
