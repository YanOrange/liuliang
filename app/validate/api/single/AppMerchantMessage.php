<?php

namespace app\validate\api\single;

use app\validate\BaseValidate;

class AppMerchantMessage extends BaseValidate
{
    protected $rule = [
        'channel'      => 'require',
        'app_message_id'      => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
        'app_message_id.require' => '消息参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getMerchantMessageList' => ['channel'],
        'readMessage' => ['app_message_id'],
        'getCustomerQrcode' => ['app_message_id'],
    ];
}