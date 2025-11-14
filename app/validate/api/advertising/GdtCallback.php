<?php

namespace app\validate\api\advertising;

use app\validate\BaseValidate;

class GdtCallback extends BaseValidate
{
    protected $rule = [
        'thread_id'      => 'require',
        'current_action_id'      => 'require',
    ];

    protected $message = [
        'thread_id.require' => '线索参数错误',
        'current_action_id.require' => '标签参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'callback' => ['thread_id','current_action_id'],
    ];
}