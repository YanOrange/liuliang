<?php

namespace app\validate\api\fortunecat;

use app\validate\BaseValidate;

class CourseOrder extends BaseValidate
{
    protected $rule = [
        'course_order_id'   => 'require',
        'channel'    => 'require',
        'app_bundle_id'    => 'require',
    ];

    protected $message = [
        'course_order_id.require' => '参数错误',
        'channel.require' => '渠道参数错误',
        'app_bundle_id.require' => '包名参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getCourseOrderList' => ['channel','app_bundle_id'],
        'getCourseOrderDetail' => ['course_order_id'],
    ];
}