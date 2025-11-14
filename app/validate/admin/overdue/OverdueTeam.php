<?php

namespace app\validate\admin\overdue;
use app\validate\BaseValidate;
class OverdueTeam extends BaseValidate
{
    protected $rule = [
        'nickname'         => 'require',
        'appointment'         => 'require',
        'introduce'         => 'require',
        'avator'                => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'nickname.require'      => '请输入昵称',
        'avator.require'      => '请上传头像',
        'appointment.require'        => '请输入职务',
        'introduce.require'        => '请输入简介',
    ];

    protected $scene = [
        'add' => ['nickname','avator','appointment','introduce'],
        'edit' => ['nickname','avator','appointment','introduce'],
    ];
}