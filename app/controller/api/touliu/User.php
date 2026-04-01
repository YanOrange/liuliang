<?php

namespace app\controller\api\touliu;

use app\controller\api\BaseApi;
use app\model\api\UserList as UserListModel;
use app\model\api\UserAgreement;
use app\model\api\PrivacyAgreement;
use app\model\api\v2\UserList;
use think\facade\Log;
/**
 * 用户接口
 */
class User extends BaseApi
{
    public $noNeedLogin = ['active','oneClickPhoneLogin','loginPhoneCaptcha','getPrivacyAgreementContent','getPrivacyAgreementUrl', 'getUserAgreementContent','getUserAgreementUrl','getAgreement','getPersonalTransferAgreementContent','getPersonalTransferAgreementUrl','wxAuthDesc','getSdkAgreementContent', 'overduePersonalInfoStatement'];
    public $noNeedCheckSign = ['active','getUserInfo','logoutUser','getPrivacyAgreementContent','getPrivacyAgreementUrl','getUserAgreementContent','getUserAgreementUrl','getAgreement','getPersonalTransferAgreementContent','getPersonalTransferAgreementUrl','wxAuthDesc', 'overduePersonalInfoStatement'];

    //手机验证码登录
    public function loginPhoneCaptcha()
    {
        $params = $this->request->post();
        // 120开头为测试账号
        if (substr($params['phone'], 0, 3) != 120) {
            $this->commonApiValidate($params, 'app\validate\api\user\User', 'loginPhoneCaptcha', new \stdClass());
        }

        $result = UserListModel::loginPhoneCaptcha($params);

        # 时间紧急，临时处理(登录成功后如果有留资信息，直接调用留资接口)
        $result['customer_link'] = '';
        $special = $params['login_special'] ?? 0;
        if ($special == 1) {
            $GLOBALS['uid'] = $result['id'];
            $rst = \app\model\api\Thread::saveFreeApply([
                'channel' => $params['channel'] ?? '',
                'app_version' => $params['app_version'] ?? '',
                'data' => [
                    ['field' => 'zhaiwu_monney', 'value' => $params['zhaiwu_monney']],
                    ['field' => 'zhaiwu_zhuangtai', 'value' => $params['zhaiwu_zhuangtai']],
                ]
            ]);
            $result['customer_link'] = $rst['customer_link'] ?? '';
        };

        return $this->success('登录成功', $result);
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
    //获取用户协议、隐私协议
    public function getAgreement()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\user\User', 'getAgreement', new \stdClass());
        return $this->success('获取协议成功', [
            'user_agreement_url' => UserAgreement::getUserAgreementContent($params),
            'privacy_agreement_url' => PrivacyAgreement::getPrivacyAgreementContent($params)
        ]);
    }
    //逾期个人信息声明
    public function overduePersonalInfoStatement()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\user\User', 'overduePersonalInfoStatement', new \stdClass());
        return $this->success('个人信息声明',UserList::overduePersonalInfoStatement($params));
    }
    //更换手机号
    public function changePhone()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\user\User', 'changePhone', new \stdClass());
        return $this->success('更换手机号', UserListModel::changePhone($params));
    }
    //验证码注销
    public function codeLogoutUser()
    {
        $params = $this->request->post();
        return $this->success('注销成功', UserListModel::codeLogoutUser($params));
    }
}