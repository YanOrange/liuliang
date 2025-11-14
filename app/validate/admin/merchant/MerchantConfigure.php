<?php

namespace app\validate\admin\merchant;

use app\validate\BaseValidate;

class MerchantConfigure extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'merchant_id'       => 'require',
        'age_range_ids'       => 'require',
        'identify_ids'       => 'require',
        'education_ids'       => 'require',
        'is_has_computer'       => 'require|in:0,1',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'merchant_id.require'      => '请选择商户',
        'age_range_ids.require'    => '请选择年龄范围',
        'identify_ids.require'     => '请选择身份',
        'education_ids.require'    => '请选择学历',
        'is_has_computer.require'  => '请选择是否有电脑',
        'is_has_computer.in'       => '请选择是否有电脑'
    ];

    protected $scene = [
        'add' => ['merchant_id','age_range_ids','identify_ids','education_ids','is_has_computer'],
        'edit' => ['merchant_id','age_range_ids','identify_ids','education_ids','is_has_computer'],
    ];
}