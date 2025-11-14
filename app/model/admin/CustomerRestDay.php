<?php
/**
 * 后台应用表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\ExceptionStd;
use app\lib\api\vres\Aes;
class CustomerRestDay extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'customer_rest_day';

}
