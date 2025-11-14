<?php

namespace app\validate\admin\website;
use app\validate\BaseValidate;

class WebsiteLeaveMessage extends BaseValidate
{
    protected $rule = [
        'follow_desc' => 'require|length:2,200',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'follow_desc.require' => '请输入跟进内容',
        'follow_desc.length' => '跟进内容长度2-200个字符',
    ];
    protected $scene = [
        'followUser' => ['follow_desc'],
    ];
}