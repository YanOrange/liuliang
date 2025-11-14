<?php

namespace app\validate\admin\useragreement;

use think\Validate;
use app\validate\BaseValidate;

class UserAgreement extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'title'                 => 'require|length:2,30',
        'content'               => 'require',
        'channel_ids'                => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'title.require'             => '标题不能为空',
        'title.length'              => '标题长度2-30',
        'content.require'        => '请输入内容',
        'channel_ids.require'        => '请选择关联渠道',
    ];

    protected $scene = [
        'add' => ['title','content','channel_ids'],
        'edit' => ['title','content','channel_ids'],
    ];
}