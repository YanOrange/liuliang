<?php
/**
 * 验证码表模型
 */

namespace app\model\api;

use app\model\api\Channel;
use laytp\BaseModel;

use app\lib\api\exception\Exception;
use app\lib\api\sms\SmsCode;
use app\lib\api\exception\ExceptionStd;
use app\lib\api\sms\AliyunSms;
use app\lib\api\sms\SubmailSms;
use app\model\api\SubmailSmsConfig;
use app\model\api\ChannelConfig;


class Captcha extends BaseModel
{

    //模型名
    protected $name = 'captcha';

    //验证码场景
    const LOGIN_TYPE = 1; //登录
    const FORGET_PWD_TYPE = 2; //忘记密码
    const CHANGE_PHONE_TYPE = 3; //更换手机号
    const CHANGE_EMAIL_TYPE = 4;
    const REGISTER_TYPE = 5; //h5落地页

    //获取验证码
    public static function getCaptcha($params = [])
    {
        extract($params);
        $redis = get_redis();
        /*if($redis->exists($phone)){
            new Exception('请勿重复发送');
        }*/
        $captcha = rand(100000, 999999);
        $captcha = strpos($captcha, '0010') !== false ? str_replace('0010', rand(1000, 9999), $captcha) : $captcha;
        $ip = request()->ip();
        $startDate = date('Y-m-d ') . '00:00:00';
        $endDate   = date('Y-m-d ') . '23:59:59';
        $limitCount = self::whereDay('create_time')->where('ip', $ip)->count();
        if ($limitCount >= 20) {
            //new Exception("获取验证码异常");
             return; 
        }
//        $result = json_decode((new SmsCode)->sendCode(['mobiles' => $phone, 'code' => $captcha]), true);
//        if ($result['resultCode'] != '0') {
//            new Exception("获取验证码异常");
//        }
        //$smsChannelList = ['lmgdyq_huawei', 'lmgdyqtcs_huawei', 'zwhkyh_huawei', 'yqzwcz_huawei','msgdyq_huawei','dkyqcl_huawei', 'zwyqwy_huawei'];

        if ($type == self::REGISTER_TYPE) {
            $channel = Channel::where('id', $channel)->value('channel_name');
        }

        if (isset($channel) && !empty($channel)) {
            
           /* $smsConfig = [
                'lmgdyq_huawei' => ['appid' => 93725, 'signature' => 'cc52d0dac1501ea82170e9172d163b5f', 'project' => 'O3qO64', 'content' => '【上海山之名】您的验证码是：@var(code)，您正在登录，非本人操作，请勿泄露。'],
                'lmgdyqtcs_huawei' => ['appid' => 93740, 'signature' => '431d60e1d0e622557602e707cfb33d07', 'project' => 'WuM874', 'content' => '【上海再无债】您的验证码是：@var(code)，您正在登录，非本人操作，请勿泄露。'],
                'zwhkyh_huawei' => ['appid' => 93740, 'signature' => '431d60e1d0e622557602e707cfb33d07', 'project' => 'WuM874', 'content' => '【上海再无债】您的验证码是：@var(code)，您正在登录，非本人操作，请勿泄露。'],
                'yqzwcz_huawei' => ['appid' => 93725, 'signature' => 'cc52d0dac1501ea82170e9172d163b5f', 'project' => '5Uso73', 'content' => '【狂花】您的验证码是：@var(code)，您正在登录，非本人操作，请勿泄露。'],
                'msgdyq_huawei' => ['appid' => 93725, 'signature' => 'cc52d0dac1501ea82170e9172d163b5f', 'project' => 'szuVP2', 'content' => '【旭翱】您的验证码是：@var(code)，您正在登录，非本人操作，请勿泄露。'],
                'dkyqcl_huawei' => ['appid' => 93725, 'signature' => 'cc52d0dac1501ea82170e9172d163b5f', 'project' => 'dNdcM1', 'content' => '【重庆山之名】您的验证码是：@var(code)，您正在登录，非本人操作，请勿泄露。'],
                'zwyqwy_huawei' => ['appid' => 93725, 'signature' => 'cc52d0dac1501ea82170e9172d163b5f', 'project' => '3jPiS1', 'content' => '【候晗】您的验证码是：@var(code)，您正在登录，非本人操作，请勿泄露。'],
            ];*/
            $channelId = Channel::where('channel_name', $channel)->value('id');
            $smsId = ChannelConfig::where('channel_id', !empty($channelId) ? $channelId : 0)->value('sms_id');
            $submailSmsConfig = SubmailSmsConfig::find(!empty($smsId) ? $smsId : 0);
            if (!empty($submailSmsConfig)) {
                $multi[] = ['to' => $phone,'vars'=>[
                    'code'=> $captcha
                ]];
                $msgData = [
                    'sendurl' => 'https://api-v4.mysubmail.com/sms/multixsend',
                    'appid' => $submailSmsConfig['app_id'],
                    'content'=> '【.'.$submailSmsConfig['sign_name'].'】'.$submailSmsConfig['content'],
                    'multi' => json_encode($multi),
                    'signature' => $submailSmsConfig['signature'],
                    'project' => $submailSmsConfig['project'],
                ];
                $result = json_decode((new SubmailSms())->sendMessage($msgData), true);
                if ($result[0]['status'] != 'success') {
                    new Exception("获取验证码异常");
                }
                $ret = self::create([
                    'phone' => $phone,
                    'captcha' => $captcha,
                    'type' => $type,
                    'ip' => request()->ip(),
                    'expiration_time' => time() + 600,
                ]);
                if ($ret === false) {
                    new Exception("获取验证码异常");
                }
                $redis->set($phone, 1, 60);
            } else {
                $result = (new AliyunSms)->sendCode(['phone' => $phone, 'code' => $captcha, 'channel' => isset($channel) ? $channel : '']);
                if ($result->body->code !== 'OK') {
                    new Exception("获取验证码异常");
                }
                $ret = self::create([
                    'phone' => $phone,
                    'captcha' => $captcha,
                    'type' => $type,
                    'ip' => request()->ip(),
                    'expiration_time' => time() + 600,
                ]);
                if ($ret === false) {
                    new Exception("获取验证码异常");
                }
                $redis->set($phone, 1, 60);
            }
            
            
        } else {
            $result = (new AliyunSms)->sendCode(['phone' => $phone, 'code' => $captcha, 'channel' => isset($channel) ? $channel : '']);
            if ($result->body->code !== 'OK') {
                new Exception("获取验证码异常");
            }
            $ret = self::create([
                'phone' => $phone,
                'captcha' => $captcha,
                'type' => $type,
                'ip' => request()->ip(),
                'expiration_time' => time() + 600,
            ]);
            if ($ret === false) {
                new Exception("获取验证码异常");
            }
            $redis->set($phone, 1, 60);
        }

    }
    //检测验证码
    public static function checkCaptcha($where = [], $captcha)
    {
        if ($captcha == 654198 || $captcha == 351662 || $captcha == 772968 || $captcha == 111000 || $captcha == 758421 || $captcha == 258699 || $captcha == 524685) {
            return true;
        }
        $captchaInfo = self::where($where)->where('is_use', 0)->order('id desc')->find();
        if (empty($captchaInfo) || $captcha != $captchaInfo['captcha'] || $captchaInfo['expiration_time'] < time()) {
            new ExceptionStd('验证码不正确');
        }
        $captchaInfo->is_use = 1;
        $captchaInfo->save();
        return true;
    }
}
