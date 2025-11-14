<?php

namespace app\validate\api\single;

use app\validate\BaseValidate;

class Thread extends BaseValidate
{
    protected $rule = [
        'course_id'    => 'require',
        'resource_id'    => 'require',
        'app_message_id'    => 'require',
        'merchant_id'    => 'require',
        'resource_message_id'    => 'require',
        'course_type'    => 'require|in:1,2',
    ];

    protected $message = [
        'course_id.require' => '请选择报名的课程',
        'resource_id.require' => '请选择报名的资源',
        'app_message_id.require' => '请选择报名的消息',
        'merchant_id.require' => '请求参数错误',
        'resource_message_id.require' => '消息资源参数错误',
        'course_type.require' => '课程参数错误',
        'course_type.in' => '课程参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'freeApplyCourse' => ['course_id','course_type'],
        'freeApplyResource' => ['resource_id'],
        'freeApplyMerchantMessage' => ['app_message_id'],
        'getApplyQrCode' => ['merchant_id'],
        'getResourceMessageCustomerQrcode' => ['merchant_id','resource_message_id'],
        'resourceMessageDiscernQrCode' => ['merchant_id','resource_message_id'],
    ];
}