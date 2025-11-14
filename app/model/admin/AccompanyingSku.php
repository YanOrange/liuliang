<?php

namespace app\model\admin;

use app\model\api\Course;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AccompanyingSku extends BaseModel
{
    use SoftDelete;

    //模型
    protected $name = 'accompanying_sku';
    
}