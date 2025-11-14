<?php
/**
 * 渠道表模型
 */

namespace app\model\api\learn;

use laytp\BaseModel;
use app\lib\api\service\WeightService;
use app\model\api\Channel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class LearnCourseSection extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'learn_course_section';

    public function course()
    {
        return $this->belongsTo('app\model\api\learn\LearnCourse','course_id','id')->removeOption('soft_delete');
    }

    public function joint()
    {
        return $this->belongsTo('app\model\api\learn\LearnCourseSection','id','section_pid')->removeOption('soft_delete');
    }

}
