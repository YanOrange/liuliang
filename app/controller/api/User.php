<?php

namespace app\controller\api;
use app\model\api\UserList as UserListModel;
use app\model\api\UserAgreement;
use app\model\api\PrivacyAgreement;
use app\model\api\PersonalTransferAgreement;
use think\facade\Config;
use app\model\api\LandingPage;
use app\model\api\Channel;
use app\model\api\App;
use think\facade\Log;

/**
 * 用户接口
 */
class User extends BaseApi
{
    public $noNeedLogin = ['active','oneClickPhoneLogin','loginPhoneCaptcha','getPrivacyAgreementContent','getPrivacyAgreementUrl', 'getUserAgreementContent','getUserAgreementUrl','getAgreement','getPersonalTransferAgreementContent','getPersonalTransferAgreementUrl','wxAuthDesc','getSdkAgreementContent'];
    public $noNeedCheckSign = ['active','getUserInfo','logoutUser','getPrivacyAgreementContent','getPrivacyAgreementUrl','getUserAgreementContent','getUserAgreementUrl','getAgreement','getPersonalTransferAgreementContent','getPersonalTransferAgreementUrl','wxAuthDesc'];


    //本机号码一键登陆
    public function oneClickPhoneLogin()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\user\User', 'oneClickPhoneLogin', new \stdClass());
        return $this->success('登录成功', UserListModel::oneClickPhoneLogin($params));
    }
    //绑定微信
    public function bindWx()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\user\User', 'bindWx', new \stdClass());
        return $this->success('登录成功', UserListModel::bindWx($params));
    }
    //手机验证码登录
    public function loginPhoneCaptcha()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\user\User', 'loginPhoneCaptcha', new \stdClass());
        return $this->success('登录成功', UserListModel::loginPhoneCaptcha($params));
    }
    public function active()
    {
        $params = $this->request->post();
        Log::info('执行激活方法：active，参数：' . json_encode($params));

        UserListModel::active($params);

        return $this->success('记录成功', null);
    }
    //获取用户信息
    public function getUserInfo()
    {
        $params = $this->request->post();
        return $this->success('获取用户信息', UserListModel::getUserInfo($params));
    }
    //编辑用户资料
    public function editUserInfo()
    {
        $params = $this->request->post();
        return $this->success('保存成功', UserListModel::editUserInfo($params));
    }
    //隐私协议Url
    public function getPrivacyAgreementUrl($id = 0, $channel = null)
    {
        $content = PrivacyAgreement::where('id', $id)->value('content');
        echo $content;
    }
    //用户协议Url
    public function getUserAgreementUrl($id = 0, $channel = null)
    {
        $content = UserAgreement::where('id', $id)->value('content');
        echo $content;
    }
    //个人信息授权传输协议Url
    public function getPersonalTransferAgreementUrl($id = 0)
    {
        $content = PersonalTransferAgreement::where('id', $id)->value('content');
        echo $content;
    }
    //隐私协议内容
    public function getPrivacyAgreementContent()
    {
        $params = $this->request->get();
        $content = PrivacyAgreement::getPrivacyAgreementContent($params);
        $content = '<title>隐私协议</title>' . $content;
        extract($params);
        echo $content;
    }
    //用户协议内容
    public function getUserAgreementContent()
    {
        $params = $this->request->get();
        $content = UserAgreement::getUserAgreementContent($params);
        $content = '<title>用户协议</title>' . $content;
        extract($params);
        echo $content;
    }
     //SDK协议内容
    public function getSdkAgreementContent()
    {
        $params = $this->request->get();
        $content = UserAgreement::getSdkAgreementContent($params);
        $content = '<title>第三方信息共享清单</title>' . $content;
        extract($params);
        echo $content;
    }
    //个人信息授权传输协议内容
    public function getPersonalTransferAgreementContent()
    {
        $params = $this->request->get();
        echo PersonalTransferAgreement::getPersonalTransferAgreementContent($params);
    }
    //获取用户协议、隐私协议、个人信息授权传输协议url
    public function getAgreement()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\user\User', 'getAgreement', new \stdClass());
        return $this->success('获取协议成功', [
            'user_agreement_url' => UserAgreement::getUserAgreementUrl($params),
            'privacy_agreement_url' => PrivacyAgreement::getPrivacyAgreementUrl($params),
            'personal_transfer_agreement_url' => PersonalTransferAgreement::getPersonalTransferAgreementUrl($params),
        ]);
    }
    //更换手机号
    public function changePhone()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\user\User', 'changePhone', new \stdClass());
        return $this->success('更换手机号', UserListModel::changePhone($params));
    }

    //注销
    public function logoutUser()
    {
        $params = $this->request->post();
        return $this->success('注销成功', UserListModel::logoutUser());
    }

    //验证码注销
    public function codeLogoutUser()
    {
        $params = $this->request->post();
        return $this->success('注销成功', UserListModel::codeLogoutUser($params));
    }
}