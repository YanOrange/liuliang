<?php

namespace app\model\api\fortunecat;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class StudyVideoResource extends BaseModel
{
    use SoftDelete;
    //模型
    protected $name = 'study_video_resource';
}