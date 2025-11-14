<?php

namespace app\model\api\single;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
class StudyVideoResourse extends BaseModel
{
    use SoftDelete;
    //表名
    protected $name = 'study_video_resource';
}