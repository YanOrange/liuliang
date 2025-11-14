<?php
/**
 * 后台商户表模型
 */

namespace app\model\api;

use app\lib\api\exception\Exception;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class PayActionLog extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'pay_action_log';

}
