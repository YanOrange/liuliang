<?php
/**
 * 推荐阅读表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\admin\LearnCourseSection;

class LearnCourse extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'learn_course';

    protected $append = ['course_joint'];

    public function getCourseJointAttr($value,$data)
    {
        $courseJoint = LearnCourseSection::where('course_id',$data['id'])->where('section_pid','>',0)->count();
        return '第'.$courseJoint.'小节';
    }

}
