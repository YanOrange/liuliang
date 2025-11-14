<?php

namespace app\validate\api\fortunecat;

use app\validate\BaseValidate;

class StudyCourse extends BaseValidate
{
    protected $rule = [
        'thread_id'      => 'require',
        'video_resource_id'        => 'require',
    ];

    protected $message = [
        'thread_id.require' => '参数错误',
        'video_resource_id.require' => '参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'setStudyVideoFinish' => ['thread_id','video_resource_id'],
    ];
}