<?php

namespace app\validate\api\advertising;

use app\validate\BaseValidate;

class Advertising extends BaseValidate
{
    protected $rule = [
        'channel'      => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getPageAdvertising' => ['channel'],
        'getPageAdvertisingV2' => ['channel'],
    ];
}