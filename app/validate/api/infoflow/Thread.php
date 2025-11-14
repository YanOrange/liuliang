<?php

namespace app\validate\api\infoflow;

use app\validate\BaseValidate;

class Thread extends BaseValidate
{
    protected $rule = [
        'channel'    => 'require',
        'phone'    => 'require',
        'captcha'    => 'require',
        'token'    => 'require',
        'accessToken'    => 'require',
        'app_bundle_id'    => 'require',
        'for_flow_id'    => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
        'phone.require' => '手机号参数错误',
        'captcha.require' => '验证码参数错误',
        'token.require' => 'token参数错误',
        'accessToken.require' => 'accessToken参数错误',
        'app_bundle_id.require' => '包名参数错误',
        'for_flow_id.require' => '信息流参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'freeApplyAppFlow' => ['channel','phone','captcha'],
        'oneClickPhoneFreeApplyAppFlow' => ['channel','token','accessToken','app_bundle_id'],
        'getApplyQrCode' => ['phone','for_flow_id'],
        'discernQrCode' => ['phone','for_flow_id'],
    ];
}