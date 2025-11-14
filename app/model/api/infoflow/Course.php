<?php

namespace app\model\api\infoflow;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class Course extends BaseModel
{
    use SoftDelete;

    protected $name = 'course';
}