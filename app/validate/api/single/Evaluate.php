<?php

namespace app\validate\api\single;

use app\validate\BaseValidate;

class Evaluate extends BaseValidate
{
    protected $rule = [
        'content'      => 'require',
        'be_evaluated_type'      => 'require',
        'be_evaluated_id'      => 'require',
    ];

    protected $message = [
        'content.require' => '评价内容不为空',
        'be_evaluated_type.require' => '类目不能为空',
        'be_evaluated_id.require' => '课程或资源错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'addEvaluate' => ['content','be_evaluated_type','be_evaluated_id'],
    ];
}