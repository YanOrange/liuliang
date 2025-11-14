<?php

namespace app\validate\admin\advertising;

use think\Validate;
use app\validate\BaseValidate;

class AdvertisementAccountConfig extends BaseValidate
{
    protected $rule = [
        'platform_id'   => 'require',
        'agent_id'      => 'require',
        'channel_id'    => 'require',
        'put_in_status' => 'require|in:0,1',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'platform_id.require'   => '请选择平台',
        'agent_id.require'      => '请选择供应商',
        'channel_id.require'    => '请选择渠道',
        'put_in_status.require' => '请选择状态',
        'put_in_status.in'      => '请选择状态',

    ];
    protected $scene = [
        'add' => ['platform_id','agent_id','channel_id','put_in_status','put_in_status'],
        'edit' => ['platform_id','agent_id','channel_id','put_in_status','put_in_status'],
    ];
}