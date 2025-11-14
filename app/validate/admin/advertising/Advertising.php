<?php

namespace app\validate\admin\advertising;

use think\Validate;
use app\validate\BaseValidate;

class Advertising extends BaseValidate
{
    protected $rule = [
        'is_many_organization'  => 'require|in:1,2,3',
        'page_id'               => 'require',
        'image'                 => 'require',
        'merchant_id'           => 'require',
        'open_mode'                => 'require|in:1,2',
        'jump_mode'                => 'require|in:0,1,2',
        'status'                => 'require|in:0,1',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'is_many_organization.require'        => '请选择机构版本',
        'is_many_organization.in'        => '请选择机构版本',
        'image.require'      => '请上传背景图',
        'page_id.require'      => '请选择页面',
        'merchant_id.require'        => '请选择商户',
        'open_mode.require'        => '请选择打开方式',
        'open_mode.in'        => '请选择打开方式',
        'jump_mode.require'        => '请选择跳转',
        'jump_mode.in'        => '请选择跳转方式',
        'status.require'        => '请选择状态',
        'status.in'        => '请选择状态',

    ];
    protected $scene = [
        'add' => ['is_many_organization','image','merchant_id','open_mode','jump_open','status'],
        'edit' => ['is_many_organization', 'image','merchant_id','open_mode','jump_open','status'],
    ];
}