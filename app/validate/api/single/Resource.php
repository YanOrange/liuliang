<?php

namespace app\validate\api\single;

use app\validate\BaseValidate;

class Resource extends BaseValidate
{
    protected $rule = [
        'channel'      => 'require',
        'resource_id'      => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
        'resource_id.require' => '资源参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getResourceList' => ['channel'],
        'getResourceDetail' => ['channel','resource_id'],
        'getCustomerQrcode' => ['resource_id'],
    ];
}