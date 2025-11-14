<?php

namespace app\validate\admin\gatheruserinfo;

use app\validate\BaseValidate;

class GatherUserInfo extends BaseValidate
{
//数组顺序就是检测的顺序
    protected $rule = [
        'title'           => 'require',
        'field'           => 'require',
        'gather_info_json' => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'title.require'             => '名称不能为空',
        'field.length'              => '字段不能为空',
        'gather_info_json.require'  => '信息值不能为空',
    ];

    protected $scene = [
        'add' => ['title','field','gather_info_json'],
        'edit' => ['title','field','gather_info_json'],
    ];
}