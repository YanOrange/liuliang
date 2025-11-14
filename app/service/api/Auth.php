<?php

namespace app\service\api;

use laytp\traits\Error;
use think\facade\Request;
use app\model\api\UserList;

/**
 * Api权限服务实现者
 * Class Auth
 * @package app\api\service
 */
class Auth
{
    use Error;
    protected $_noNeedLogin = [];//无需登录的方法名数组

    /**
     * 设置无需登录的方法名数组
     * @param array $noNeedLogin
     */
    public function setNoNeedLogin($noNeedLogin = [])
    {
        $this->_noNeedLogin = $noNeedLogin;
    }

    /**
     * 获取无需登录的方法名数组
     * @return array
     */
    public function getNoNeedLogin()
    {
        return $this->_noNeedLogin;
    }

    /**
     * 当前节点是否需要登录
     * @param bool $noNeedLogin
     * @return bool true:需要登录,false:不需要登录
     */
    public function needLogin($noNeedLogin = false)
    {
        $noNeedLogin === false && $noNeedLogin = $this->getNoNeedLogin();
        $noNeedLogin = is_array($noNeedLogin) ? $noNeedLogin : explode(',', $noNeedLogin);
        //为空表示所有方法都需要登录，返回true
        if (!$noNeedLogin) {
            return true;
        }
        $noNeedLogin = array_map('strtolower', $noNeedLogin);
        $request     = Request::instance();
        //判断当前请求的操作名是否存在于不需要登录的方法名数组中，如果存在，表明不需要登录，返回false
        if (in_array(strtolower($request->action()), $noNeedLogin) || in_array('*', $noNeedLogin)) {
            return false;
        }

        //默认为需要登录
        return true;
    }

    /**
     * 验证签名
     */
    public function checkVerifyToken()
    {
        $request = Request::instance();
        $token = $request->header('token');
        if (empty($token)) {
            $this->setError('token错误');
            return false;
        }
        $uid = checkJwtToken($token);
        if (empty($uid)) {
            $this->setError('token错误');
            return false;
        }
        if(is_numeric($uid)){
            $status = UserList::where('id',$uid)->value('status');
            if($status != 1){
                $this->setError('账号异常');
                return false;
            }
        }
        $GLOBALS['uid'] = $uid;
        return true;
    }
}