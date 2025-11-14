<?php
/**
 * 后台应用表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\ExceptionStd;
use app\lib\api\vres\Aes;
class JzdCustomerAssignLimitNumLog extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'jzd_customer_assign_limit_num_log';

}
