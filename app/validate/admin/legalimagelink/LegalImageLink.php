<?php

namespace app\validate\admin\legalimagelink;

use think\Validate;
use app\validate\BaseValidate;

class LegalImageLink extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'legal_images'              => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'legal_images.require'             => '法务图片不能为空',
    ];

    protected $scene = [
        'add' => ['legal_images'],
        'edit' => ['legal_images'],
    ];
}