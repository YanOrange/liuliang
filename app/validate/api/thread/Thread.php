<?php

namespace app\validate\api\thread;
use app\validate\BaseValidate;
class Thread extends BaseValidate
{
    protected $rule = [
        'course_id'    => 'require',
        'channel'    => 'require',
        'merchant_id'    => 'require',
        'username'     => 'require',
        'phone'    => 'require|mobile',
        'age'     => 'max:50',
        'sex'     => 'max:50',
        'education' => 'max:50',
    ];

    protected $message = [
        'course_id.require' => '请选择报名的课程',
        'username.require' => '请输入姓名',
        'phone.require' => '请输入手机号',
        'phone.mobile' => '请输入正确的手机号',
        'channel.require' => '渠道参数错误',
        'merchant_id.require' => '商户参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'freeApplyCourse' => ['course_id'],
        'jzdThread' => ['username', 'phone'],
        'applyThread' => ['age', 'sex','education'],
//        'saveFreeApply' => ['channel', 'merchant_id'],
        'saveFreeApply' => ['channel'],
    ];
}