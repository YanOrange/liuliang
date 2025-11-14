<?php

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class PartCourseTag extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'part_course_tag';
}