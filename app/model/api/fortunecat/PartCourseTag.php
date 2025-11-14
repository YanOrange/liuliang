<?php

namespace app\model\api\fortunecat;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class PartCourseTag extends BaseModel
{
    use SoftDelete;

    protected $name = 'part_course_tag';

    public static function getPartCourseTagNames($partCourseTagId = '')
    {
        $partCourseTagIds = !empty($partCourseTagId) ? explode(',',$partCourseTagId) : [];
        $PartCourseTagNames = PartCourseTag::whereIn('id',$partCourseTagIds)->column('tag_name');
        return $PartCourseTagNames;
    }
}