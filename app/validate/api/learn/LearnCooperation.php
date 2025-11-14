<?php

namespace app\validate\api\learn;
use app\validate\BaseValidate;
class LearnCooperation extends BaseValidate
{
    protected $rule = [
        'channel'      => 'require',
        'nickname'      => 'require',
        'phone'      => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
        'nickname.require' => '名称不能为空',
        'phone.require' => '手机号不能为空',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'addCooperation' => ['channel','nickname','phone'],
    ];
}