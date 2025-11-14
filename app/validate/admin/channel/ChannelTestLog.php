<?php

namespace app\validate\admin\channel;

use app\validate\BaseValidate;

class ChannelTestLog extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'title' => 'require',
        'channel_id' => 'require',
        'cycle_time' => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'title.require' => '测试名称不能为空',
        'channel_id.length' => '请选择关联渠道',
        'cycle_time.require' => '请选择测试周期',
    ];

    protected $scene = [
        'add' => ['title', 'channel_id', 'cycle_time'],
        'edit' => [],
    ];
}