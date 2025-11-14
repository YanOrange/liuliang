<?php

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class VivoPlanDetailDay extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'vivo_plan_detail_day';
}