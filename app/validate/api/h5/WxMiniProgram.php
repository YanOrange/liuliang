<?php

namespace app\validate\api\h5;

use app\validate\BaseValidate;
class WxMiniProgram extends BaseValidate
{
    protected $rule = [
        'token'      => 'require',
        'course_id'  => 'require',
    ];

    protected $message = [
        'token.require' => '参数错误',
        'course_id.require' => '参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getMiniPath' => ['token','course_id'],
    ];
}