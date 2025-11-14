<?php

namespace app\validate\admin\promotionmethod;

use think\Validate;
use app\validate\BaseValidate;

class PromotionMethod extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'name'              => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'name.require'             => '名称不能为空',
    ];
    protected $scene = [
        'add' => ['name'],
        'edit' => ['name'],
    ];
}
