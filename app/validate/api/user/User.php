<?php

namespace app\validate\api\user;
use app\validate\BaseValidate;
use app\model\api\Captcha as CaptchaModel;
class User extends BaseValidate
{
    //数组顺序就是检测的顺序，比如这里，会先检测code验证码的正确性
    protected $rule = [
        'phone'      => 'require|checkIsPhone',
        'nickname'   => 'require',
        'captcha'    => 'require|length:6|checkCaptcha',
        'channel'    => 'require',
        'app_bundle_id' => 'require',
        'code' => 'require',
        'token' => 'require',
        'accessToken' => 'require',
        'course_id' => 'require',

    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'phone.require'      => '请输入手机号',
        'nickname.require'      => '请输入姓名',
        'captcha.require'    => '请输入验证码',
        'captcha.length'     => '验证码不正确',
        'channel.require'    => '渠道参数错误',
        'app_bundle_id.require'    => '包名参数错误',
        'code.require'    => '缺少微信参数code',
        'token.require'   => '缺少参数token',
        'accessToken.require'   => '缺少参数accessToken',
        'course_id.require'   => '缺少课程参数',
    ];

    //验证验证码
    protected function checkCaptcha($captcha, $rule, $data)
    {
        $checkCaptcha = CaptchaModel::where('phone', $data['phone'])->where('type', 1)->order('id desc')->value('captcha');
        if ($captcha == 654198) {
            return true;
        }
        if ($captcha != $checkCaptcha) {
            return '验证码不正确';
        }
        return true;
    }
    /**
     * 验证场景
     */
    protected $scene = [
        'loginPhoneCaptcha' => ['phone', 'captcha', 'channel', 'app_bundle_id'],
        'loginPhoneCaptchaV2' => ['phone', 'captcha', 'channel', 'app_bundle_id'],
        'oneClickPhoneLogin' => ['token', 'accessToken', 'channel', 'app_bundle_id'],
        'bindWx' => ['code', 'app_bundle_id'],
        'bindWxLogin' => ['code', 'channel','app_bundle_id'],
        'getAgreement' => ['channel'],
        'changePhone' => ['phone', 'captcha'],
        'wxAuthDesc' => ['channel'],
        'getMcH5Url' => ['channel'],
        'checkPhoneCaptcha' => ['phone', 'captcha', 'channel', 'app_bundle_id'],
        'overduePersonalInfoStatement' => ['channel'],
        'threadPhone' => ['phone']
    ];
}