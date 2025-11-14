<?php

namespace app\model\api;

use app\lib\api\exception\ExceptionStd;
use app\model\api\Channel;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class CustomerAutoInputLog extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'customer_auto_input_log';

}