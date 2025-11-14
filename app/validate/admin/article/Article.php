<?php

namespace app\validate\admin\article;

use think\Validate;
use app\validate\BaseValidate;

class Article extends BaseValidate
{
    protected $rule = [
        'title' => 'require',
        'image' => 'require',
        'content' => 'require',
        'merchant_id' => 'require',
        'status' => 'require|in:0,1',
        'channel_ids' => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'title.require' => '请输入标题',
        'image.require' => '请上传背景图',
        'merchant_id.require' => '请选择商户',
        'status.require' => '请选择状态',
        'status.in' => '请选择状态',
        'channel_ids.require' => '请选择关联渠道',
    ];
    protected $scene = [
        'add' => ['title', 'merchant_id', 'image', 'status', 'content', 'channel_ids'],
        'edit' => ['title', 'merchant_id', 'image', 'status', 'content', 'channel_ids'],
    ];
}