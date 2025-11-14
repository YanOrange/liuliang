<?php

namespace app\model\admin;

use app\model\api\Course;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class AccompanyingHospital extends BaseModel
{
    use SoftDelete;

    //模型
    protected $name = 'accompanying_hospital';

}