<?php

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class OppoOwnerConsumeBill extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'oppo_owner_consume_bill';
}