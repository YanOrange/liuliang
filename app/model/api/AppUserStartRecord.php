<?php
/**
 * app启动表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class AppUserStartRecord extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'app_user_start_record';
}
