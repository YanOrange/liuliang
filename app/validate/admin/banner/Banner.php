<?php

namespace app\validate\admin\banner;

use think\Validate;
use app\validate\BaseValidate;

class Banner extends BaseValidate
{
    protected $rule = [
        'image' => 'require',
        'merchant_id' => 'require',
        'status' => 'require|in:0,1',
        'channel_ids' => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'image.require' => '请上传背景图',
        'merchant_id.require' => '请选择商户',
        'status.require' => '请选择状态',
        'status.in' => '请选择状态',
        'channel_ids.require' => '请选择关联渠道',
    ];
    protected $scene = [
        'add' => ['image', 'merchant_id', 'status', 'channel_ids'],
        'edit' => ['image', 'merchant_id', 'status', 'channel_ids'],
    ];
}