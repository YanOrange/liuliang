<?php

namespace app\validate\admin\website;
use app\validate\BaseValidate;

class WebsitePostList extends BaseValidate
{
    protected $rule = [
        'post_name' => 'require|length:2,200',
        'post_desc' => 'require|length:2,2000',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'post_name.require' => '请输入岗位名称',
        'post_name.length' => '岗位名称长度2-200个字符',
        'post_desc.require' => '请输入岗位要求',
        'post_desc.length' => '岗位要求长度2-2000',
    ];
    protected $scene = [
        'add' => ['post_name', 'post_desc'],
        'edit' => ['post_name', 'post_desc'],
    ];
}