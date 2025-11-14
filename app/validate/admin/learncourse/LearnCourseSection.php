<?php

namespace app\validate\admin\learncourse;

use think\Validate;
use app\model\admin\App as AppModel;
use app\validate\BaseValidate;

class LearnCourseSection extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'course_id'                  => 'require',
        'section_title'              => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'course_id.require'              => '课程不能为空',
        'section_title.require'          => '章节标体不能为空',
    ];

    protected $scene = [
        'add' => ['course_id','section_title',],
        'edit' => ['section_title'],
    ];
}