<?php

namespace app\validate\admin\appburyingpointevent;

use app\validate\BaseValidate;

class AppBuryingPointEvent extends BaseValidate
{
//数组顺序就是检测的顺序
    protected $rule = [
        'event_name'              => 'require',
        'event_id'                => 'require',
        'event_image'             => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'event_name.require'         => '事件名称不能为空',
        'event_id.require'           => '事件ID不能为空',
        'event_image.require'        => '请上传事件截图',
    ];

    protected $scene = [
        'add' => ['event_name','event_id'],
        'edit' => ['event_name','event_id'],
    ];
}