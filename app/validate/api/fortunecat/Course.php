<?php

namespace app\validate\api\fortunecat;

use app\validate\BaseValidate;
class Course extends BaseValidate
{
    protected $rule = [
        'thread_id'      => 'require',
        'course_id'      => 'require',
        'channel'        => 'require',
    ];

    protected $message = [
        'thread_id.require' => '学习id参数错误',
        'course_id.require' => '课程id参数错误',
        'channel.require' => '渠道参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getPartCourseDetail' => ['course_id','channel'],
        'getLiveCourseDetail' => ['course_id','channel'],
        'getLiveCourseList' => ['channel'],
        'getpartClassList' => ['channel'],
        'getMoreLiveCourseList' => ['channel'],
        'getStudyCourseList' => ['channel'],
        'getStudyCourseDetail' => ['thread_id','channel'],
        'getTrainCourseDetail' => ['course_id','channel'],
        'getStudyCourseDetail20' => ['course_id','channel'],
        'getStudyCourseList20' => ['channel'],
    ];
}