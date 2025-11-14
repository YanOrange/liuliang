<?php

namespace app\validate\api\aso;
use app\validate\BaseValidate;
class AsoIdfaCallback extends BaseValidate
{
    protected $rule = [
        'appid'        => 'require',
        'idfa'         => 'require',
        'source'       => 'require',
        'callback'     => 'require',
    ];

    protected $message = [
        'appid.require' => 'appid不能为空',
        'idfa.require' => 'idfa不能为空',
        'source.require' => '渠道不能为空',
        'callback.require' => '回调地址不能为空',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'queryIdfa' => ['appid','idfa'],
        'clickCallbackIdfa' => ['appid','idfa','callback'],
    ];
}