<?php

namespace app\validate\api\captcha;
use app\validate\BaseValidate;
use app\model\api\Captcha as CaptchaModel;
class Captcha extends BaseValidate
{
    protected $rule = [
        'phone'      => 'require|checkIsPhone|checkPhoneLimit:',
        'type'   => 'require|in:1,2,3,4,5',
    ];

    protected $message = [
        'phone.require' => '请输入手机号',
        'type.require'      => '参数错误',
        'type.in'        => '参数错误',
    ];
    protected function checkPhoneLimit($phone, $rule, $data) {
        $ip = request()->ip();
        $startDate = date('Y-m-d ') . '00:00:00';
        $endDate   = date('Y-m-d ') . '23:59:59';
        $limitCount = CaptchaModel::where('create_time', '>', $startDate)
            ->where('create_time', '<=', $endDate)
            ->where('ip', $ip)
            ->count();
        if ($limitCount >= 5) {
            return '获取验证码异常';
        }
        return true;
    }

    /**
     * 验证场景
     */
    protected $scene = [
        'getCaptcha' => ['phone', 'type'],
    ];
}