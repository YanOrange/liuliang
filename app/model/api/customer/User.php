<?php

namespace app\model\api\customer;

use app\lib\api\exception\Exception;
use app\model\admin\login\Log;
use app\service\admin\AuthServiceFacade;
use app\service\admin\UserServiceFacade;
use app\validate\admin\user\Login;
use laytp\BaseModel;
use laytp\library\Random;
use laytp\library\Token;
use think\facade\Config;
use think\model\concern\SoftDelete;
use think\captcha\facade\Captcha;

class User extends BaseModel
{
    use SoftDelete;

    //模型表名
    protected $name = 'admin_user';

    public static function login($params = [])
    {
        try {
            extract($params);
            $loginUserInfo = self::where('username', $username)
                ->where('status', 1)
                ->field('id,username,nickname,avatar')
                ->findOrEmpty();
            //$loginUserInfo->login_time = date('Y-m-d H:i:s');
            $loginUserInfo->login_ip = request()->ip();
            $loginUserInfo->save();
            $userId = $loginUserInfo['id'];
            $token = Random::uuid();
            $loginUserInfo['token'] = $token;
            Token::set($token, $userId, 24 * 60 * 60 * 3);
            return $loginUserInfo;
        }catch (\Exception $e){
            new Exception('登录失败');
        }
    }

}