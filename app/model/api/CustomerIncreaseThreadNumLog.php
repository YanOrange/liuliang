<?php
/**
 * 应用表模型
 */

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class CustomerIncreaseThreadNumLog extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'customer_increase_thread_num_log';

}
