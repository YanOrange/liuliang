<?php

namespace app\validate\admin\externaluser;

use app\model\admin\ExternalUser;
use think\Validate;
use app\validate\BaseValidate;

class Edit extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'id'               => 'require',
        'username'         => 'require|length:2,30|checkUsername:',
        'nickname'         => 'require|length:2,30',
        'password'         => 'length:6,30|confirm:re_password',
        'status'           => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'username.require'         => '用户名不能为空',
        'username.length'          => '用户名长度2-30',
        'nickname.require'         => '昵称不能为空',
        'nickname.length'          => '昵称长度2-30',
        'password.length'          => '密码长度6-30',
        'password.confirm'         => '两次密码输入不相同',
        'status.require'           => '请设置账号的状态',
    ];

    //验证用户名的唯一性
    protected function checkUsername($username, $rule, $data){
        $userId = ExternalUser::getFieldByUsername($username,'id');
        if($userId && $userId != $data['id']){
            return '用户名已存在';
        }
        return true;
    }
}