<?php

namespace app\validate\admin\for_flow;

use think\Validate;
use app\validate\BaseValidate;

class FlowInfoRule extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'channel_ids'              => 'require',
        'company'                => 'require',
        'content'                => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'channel_ids.require'         => '渠道不能为空',
        'company.length'              => '公司名称不能为空',
        'content.require'             => '内容不能为空'
    ];

    protected $scene = [
        'add' => ['channel_ids','company','content'],
        'edit' => ['channel_ids','company','content'],
    ];
}