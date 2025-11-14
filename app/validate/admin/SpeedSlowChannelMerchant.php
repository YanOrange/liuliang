<?php

namespace app\validate\admin;

use think\Validate;
use app\model\admin\SpeedSlowChannelMerchant as SpeedSlowChannelMerchantModel;
use app\validate\BaseValidate;

class SpeedSlowChannelMerchant extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'channel_id'              => 'require',
        'merchant_id'         => 'require',
        'channel_set_speed_feed_num'          => 'require|egt:0',
        'channel_set_slow_num'          => 'require|egt:0',
    ];
    //定义内置方法检验失败后返回的字符
    protected $message = [
        'channel_id.require'             => '请选择渠道',
        'merchant_id.require'        => '请选择商户',
        'channel_set_speed_feed_num.require'        => '请输入加速周期数量',
        'channel_set_speed_feed_num.egt'        => '请输入加速周期数量',
        'channel_set_slow_num.require'        => '请输入减速周期数量',
        'channel_set_slow_num.egt'        => '请输入减速周期数量',

    ];
    protected $scene = [
        'add' => ['channel_id','merchant_id','channel_set_speed_feed_num', 'channel_set_slow_num'],
        'edit' => ['channel_id','merchant_id','channel_set_speed_feed_num', 'channel_set_slow_num'],
    ];
}