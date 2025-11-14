<?php

namespace app\validate\admin\biclassqrcodenumber;

use app\validate\BaseValidate;

class BiClassQrcodeNumber extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'num' => 'require|gt:0',
        'thedate' => 'require',
        'gmv' => 'require|gt:0',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'num.require' => '请填写加微数',
        'num.gt' => '加微数必须大于0',
        'thedate.require' => '请选择日期',
        'gmv.require' => '请填写销售额',
        'gmv.gt' => '销售额必须大于0',
    ];

    protected $scene = [
        'add' => ['num','gmv','thedate'],
        'edit' => ['num','gmv','thedate'],
    ];
}