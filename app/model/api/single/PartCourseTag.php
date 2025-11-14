<?php

namespace app\model\api\single;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class PartCourseTag extends BaseModel
{
    use SoftDelete;

    protected $name = 'part_course_tag';

}