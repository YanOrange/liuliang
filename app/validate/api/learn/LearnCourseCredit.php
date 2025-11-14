<?php

namespace app\validate\api\learn;
use app\validate\BaseValidate;
class LearnCourseCredit extends BaseValidate
{
    protected $rule = [
        'channel'           => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数不为空',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getCourseCreditList' => ['channel'],
        'addCourseCredit' => ['channel'],
    ];
}