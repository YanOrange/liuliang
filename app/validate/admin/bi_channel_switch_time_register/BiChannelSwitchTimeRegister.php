<?php

namespace app\validate\admin\bi_channel_switch_time_register;

use app\validate\BaseValidate;

class BiChannelSwitchTimeRegister extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'release_day' => 'require',
        'unix_close_time' => 'require',
        'store' => 'require',
        'channel_id' => 'require',
        'app_class_id' => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'app_class_id.require' => '请选择类目',
        'channel_id.require' => '请选择渠道',
        'store.require' => '请选择商店',
        'release_day.require' => '请选择开户时间',
        'unix_close_time.require' => '请选择关户时间',
    ];

    protected $scene = [
        'add' => ['release_day', 'unix_close_time','store','channel_id','app_class_id'],
        'edit' => ['release_day', 'unix_close_time','store','channel_id','app_class_id'],
    ];
}