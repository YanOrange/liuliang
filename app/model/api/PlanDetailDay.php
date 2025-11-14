<?php

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class PlanDetailDay extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'plan_detail_day';
}