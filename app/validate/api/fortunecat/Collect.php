<?php

namespace app\validate\api\fortunecat;

use app\validate\BaseValidate;
class Collect extends BaseValidate
{
    protected $rule = [
        'course_id'      => 'require',
        'is_collect'     => 'require',
    ];

    protected $message = [
        'course_id.require' => '课程id参数错误',
        'is_collect.require' => '收藏参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'collect' => ['course_id','is_collect'],
    ];
}