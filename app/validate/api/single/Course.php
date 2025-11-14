<?php

namespace app\validate\api\single;

use app\validate\BaseValidate;

class Course extends BaseValidate
{
    protected $rule = [
        'channel'      => 'require',
        'course_id'      => 'require',
        'course_order_id'      => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
        'course_id.require' => '课程参数错误',
        'course_order_id.require' => '订单参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getCourseDetail' => ['channel','course_id'],
        'getCourseList' => ['channel'],
        'getCourseOrderList' => ['channel'],
        'getCourseOrderDetail' => ['channel','course_order_id'],
        'getSingleCourseDetail' => ['channel','course_id'],
    ];
}