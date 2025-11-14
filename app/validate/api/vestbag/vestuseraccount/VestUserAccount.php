<?php

namespace app\validate\api\vestbag\vestuseraccount;
use app\validate\BaseValidate;
class VestUserAccount extends BaseValidate
{
    protected $rule = [
        'icon_id'    => 'require',
        'account_name'    => 'require',
        'account_remaining'    => 'require',
    ];

    protected $message = [
        'icon_id.require' => '请选择图标',
        'account_name.require' => '请输入账户名称',
        'account_remaining.require' => '请输入账户余额',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'addAccount' => ['icon_id', 'account_name', 'account_remaining'],
    ];
}