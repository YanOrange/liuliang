<?php

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class OppoOwnerBalance extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'oppo_owner_balance';
}