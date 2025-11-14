<?php

namespace app\validate\admin\wechatuserthread;

use think\Validate;
use app\validate\BaseValidate;

class WechatUserThread extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'wechat_number'      => 'require',
        'wechat_nickname'      => 'require',
        'merchant_id'      => 'require',
        'customer_id'      => 'require|gt:0',
        'channel'       => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'wechat_number.require'          => '微信号不能为空',
        'wechat_nickname.require'     => '微信昵称不能为空',
        'merchant_id.require'          => '请选择商户',
        'customer_id.require'     => '请选择销售',
        'customer_id.gt'     => '请选择销售',
        'channel.require'            => '请选择渠道',
    ];

    protected $scene = [
        'add' => ['channel'],
        'edit' => [''],
        'addTwo' => ['channel','merchant_id'],
        'addSix' => ['channel'],
    ];
}