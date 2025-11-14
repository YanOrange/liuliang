<?php

namespace app\validate\admin\distribution;

use think\Validate;
use app\model\admin\App as AppModel;
use app\validate\BaseValidate;

class DistributionPartnerUser extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'nickname'              => 'require|length:2,30',
        'password'              => 'require',
        'confirm_password'      => 'require|checkConfirmPassword',
        'channel_id'            => 'require'
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'nickname.require'             => '昵称不能为空',
        'nickname.length'              => '昵称长度2-30',
        'password.require'             => '请输入密码',
        'confirm_password.require'     => '请输入确认密码',
        'channel_id.require'           => '请选择渠道',
    ];

    //验证密码一致性
    protected function checkConfirmPassword($confirmPassword, $rule, $data){
        if ($confirmPassword !== $data['password']) {
            return '两次输入密码不一致';
        }
        return true;
    }
    protected $scene = [
        'add' => ['nickname','password','confirm_password','channel_id'],
        'edit' => ['nickname','password','confirm_password','channel_id'],
    ];
}