<?php
/**
 * 推荐阅读表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class LearnCourseSection extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'learn_course_section';

}
