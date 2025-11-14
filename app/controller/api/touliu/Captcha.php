<?php

namespace app\controller\api\touliu;
use app\model\api\Captcha as CaptchaModel;
use app\controller\api\BaseApi;
/**
 * 验证码接口
 */
class Captcha extends BaseApi
{
    public $noNeedLogin = ['*'];
    public $noNeedCheckSign = [''];
    //获取验证码
    public function getCaptcha()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\captcha\Captcha', 'getCaptcha');
        return $this->success('获取验证码成功', CaptchaModel::getCaptcha($params));
    }

}