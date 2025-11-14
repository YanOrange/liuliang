<?php

namespace app\validate\admin\biboostphone;

use app\validate\BaseValidate;

class BiBoostPhone extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'merchant_id' => 'require',
        'phone' => 'require',
        'type' => 'require',
        'boosttime' => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'merchant_id.require' => '请选择补量商户',
        'phone.require' => '请填写手机号',
        'type.require' => '请选择补量类型',
        'boosttime.require' => '请选择补量日期',
    ];

    protected $scene = [
        'add' => ['merchant_id', 'phone', 'type', 'boosttime'],
        'edit' => ['merchant_id', 'phone', 'type', 'boosttime', 'id'],
    ];
}