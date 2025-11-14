<?php

namespace app\validate\api\learn;
use app\validate\BaseValidate;
class GuidePage extends BaseValidate
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
        'getGuidePage' => ['channel'],
    ];
}