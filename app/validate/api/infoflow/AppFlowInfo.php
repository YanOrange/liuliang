<?php

namespace app\validate\api\infoflow;

use app\validate\BaseValidate;

class AppFlowInfo extends BaseValidate
{
    protected $rule = [
        'channel'    => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getAppFlowList' => ['channel'],
    ];
}