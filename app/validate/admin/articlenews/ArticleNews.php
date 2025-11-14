<?php

namespace app\validate\admin\articlenews;
use app\validate\BaseValidate;
class ArticleNews extends BaseValidate
{
    protected $rule = [
        'title'         => 'require',
        'image'         => 'require',
        'content'         => 'require',
        'is_show'                => 'require|in:0,1',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'title.require'      => '请输入标题',
        'image.require'      => '请上传背景图',
        'status.require'        => '请选择状态',
        'is_show.in'        => '请选择状态',
    ];

    protected $scene = [
        'add' => ['title','merchant_id','image','status','content'],
        'edit' => ['title','merchant_id','image','status','content'],
    ];
}