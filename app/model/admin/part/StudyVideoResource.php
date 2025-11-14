<?php
/**
 * 学习资源表模型
 */

namespace app\model\admin\part;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
class StudyVideoResource extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'study_video_resource';


}
