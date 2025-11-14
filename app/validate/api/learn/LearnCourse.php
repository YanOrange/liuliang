<?php

namespace app\validate\api\learn;
use app\validate\BaseValidate;
class LearnCourse extends BaseValidate
{
    protected $rule = [
        'course_id'      => 'require',
        'channel'      => 'require',
        'teacher_id'      => 'require',
    ];

    protected $message = [
        'course_id.require' => '课程参数错误',
        'channel.require' => '渠道参数错误',
        'teacher_id.require' => '老师参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getCourseDetail' => ['course_id','channel'],
        'getCourseList' => ['channel'],
        'getTeacherInfo' => ['teacher_id'],
        'collectCourse' => ['course_id'],
    ];
}