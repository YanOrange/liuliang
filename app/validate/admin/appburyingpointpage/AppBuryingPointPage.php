<?php

namespace app\validate\admin\appburyingpointpage;

use app\validate\BaseValidate;

class AppBuryingPointPage extends BaseValidate
{
//数组顺序就是检测的顺序
    protected $rule = [
        'page_name'              => 'require',
        'page_id'                => 'require',
        'page_image'             => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'page_name.require'         => '页面名称不能为空',
        'page_id.require'           => '页面ID不能为空',
        'page_image.require'        => '请上传页面截图',
    ];

    protected $scene = [
        'add' => ['page_name','page_id'],
        'edit' => ['page_name','page_id'],
    ];
}