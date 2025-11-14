<?php

namespace app\validate\admin\extrathreadconfig;

use think\Validate;
use app\validate\BaseValidate;

class ExtraThreadConfig extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'source'              => 'require',
        'account_id'          => 'require',
        'channel_id'          => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'source.require'             => '请选择来源',
        'account_id.require'          => '请输入标识',
        'channel_id.require'         => '请选择渠道',
    ];
    protected $scene = [
        'add' => ['source','account_id','channel_id'],
        'edit' => ['source','account_id','channel_id'],
    ];
}
