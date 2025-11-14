<?php

namespace app\controller\api\customer;

use app\model\api\customer\User as UserModel;
use app\controller\api\BaseApi;
use laytp\library\Token;
use think\captcha\facade\Captcha;
use think\Session;
use think\Request;

class User extends BaseApi
{
    public $noNeedLogin = ['init','login','verify'];
    public $noNeedCheckSign = ['init','login','verify'];

    /**
     * 架构方法 设置参数
     * @access public
     * @param Session $session
     */
    public function init(Request $request, Session $session)
    {
        $cookieName   = $session->getName();
        $sessionId = $request->cookie($cookieName);
        return $this->success('session',['session_id' => $sessionId] );
    }

    //登陆
    public function login()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\user\Login', 'login', new \stdClass());
        return $this->success('登录成功', UserModel::login($params));
    }

    public function verify()
    {
        return $this->success('验证码', Captcha::createApi('base64'));
    }
}