<?php

namespace app\validate\admin\thread;

use think\Validate;
use app\validate\BaseValidate;
use Yurun\PaySDK\Base;

class ThreadTag extends BaseValidate {
    protected $rule = [
        'cate_id' => 'require',
        'merchant_ids' => 'require',
        'title' => 'require|length:1,20',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'cate_id.require' => '请选择标签类别',
        'merchant_ids.require' => '请选择所属商户',
        'title.require' => '请输入标签名称',
        'title.length' => '标签名称长度1-20字',
    ];
    protected $scene = [
        'add' => ['cate_id',  'merchant_ids', 'title'],
        'edit' => ['cate_id',  'merchant_ids', 'title'],
    ];
}