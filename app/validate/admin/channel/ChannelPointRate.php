<?php

namespace app\validate\admin\channel;

use think\Validate;
use app\model\admin\Channel as ChannelModel;
use app\validate\BaseValidate;

class ChannelPointRate extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'channel_id'         => 'require',
        'ids'         => 'require',
        'point_date'          => 'require',
        'point_rate'         => 'require',
    ];

        //定义内置方法检验失败后返回的字符
    protected $message = [
        'ids.require'             => '请选择数据',
        'channel_id.require'             => '请选择渠道',
        'point_date.require'             => '返点日期不能为空',
        'point_rate.length'              => '返点数不能为空',
    ];

    protected $scene = [
        'add' => ['channel_id','point_date','point_rate'],
        'edit' => ['channel_id','point_date','point_rate'],
        'editAll' => ['ids'],
    ];
}