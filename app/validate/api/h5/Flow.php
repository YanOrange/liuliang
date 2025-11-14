<?php

namespace app\validate\api\h5;

use app\validate\BaseValidate;
class Flow extends BaseValidate
{
    protected $rule = [
        'for_flow_id'      => 'require',
        'channel'      => 'require',
        'start_time'      => 'require',
    ];

    protected $message = [
        'for_flow_id.require' => '主题id参数错误',
        'channel.require' => '渠道参数错误',
        'start_time.require' => '开始时间参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'addPvUv' => ['for_flow_id','channel','start_time'],
    ];
}