<?php

namespace app\validate\api\h5;

use app\validate\BaseValidate;
class ForFlow extends BaseValidate
{
    protected $rule = [
        'for_flow_id'      => 'require',
        'channel'      => 'require',
    ];

    protected $message = [
        'for_flow_id.require' => '主题id参数错误',
        'channel.require' => '渠道参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getForFlowDetail' => ['for_flow_id','channel'],
    ];
}