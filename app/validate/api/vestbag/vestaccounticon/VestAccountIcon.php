<?php

namespace app\validate\api\vestbag\vestaccounticon;
use app\validate\BaseValidate;
class VestAccountIcon extends BaseValidate
{
    protected $rule = [
        'type'    => 'require|in:1,2,3',
    ];

    protected $message = [
        'type.require' => '图标类型错误',
        'type.in' => '图标类型错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getAccountIconList' => ['type'],
    ];
}