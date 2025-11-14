<?php

namespace app\model\api\single;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class PartClass extends BaseModel
{
    use SoftDelete;
    //表名
    protected $name = 'part_class';
}