<?php
/**
 * 验证码表模型
 */

namespace app\model\api;

use app\lib\api\exception\Exception as ApiException; // 重命名避免冲突
use app\lib\api\exception\ExceptionStd;
use app\lib\api\sms\AliyunSms;
use laytp\BaseModel;
use think\facade\Cache; // 推荐使用ThinkPHP内置Cache（若Redis已配置）
use think\facade\Request;
use think\exception\ModelException;

// 未使用的类可移除，避免冗余
// use app\model\api\Channel;
// use app\model\api\ChannelConfig;

class Captcha extends BaseModel
{
    // 模型名
    protected $name = 'captcha';

    // 验证码场景
    const LOGIN_TYPE = 1; // 登录
    const FORGET_PWD_TYPE = 2; // 忘记密码
    const CHANGE_PHONE_TYPE = 3; // 更换手机号
    const CHANGE_EMAIL_TYPE = 4;
    const REGISTER_TYPE = 5; // h5落地页

    /**
     * 获取验证码
     * @param array $params 参数（必填：phone, type；选填：channel）
     * @return bool
     * @throws ApiException
     */
    public static function getCaptcha($params = [])
    {
        // 替代extract，显式获取参数并验证
        $phone = $params['phone'] ?? '';
        $type = $params['type'] ?? 0;
        $channel = $params['channel'] ?? '';
        
        // 参数验证
        if (empty($phone) || !preg_match('/^1[3-9]\d{9}$/', $phone)) {
            throw new ApiException("手机号格式错误");
        }
        if (!in_array($type, [self::LOGIN_TYPE, self::FORGET_PWD_TYPE, self::CHANGE_PHONE_TYPE, self::CHANGE_EMAIL_TYPE, self::REGISTER_TYPE])) {
            throw new ApiException("验证码类型无效");
        }

        // 获取Redis实例（推荐用ThinkPHP内置Cache）
        $redis = Cache::store('redis')->handler();
        if (!$redis) {
            throw new ApiException("Redis连接失败");
        }

        // 生成验证码（简化逻辑，无需替换0010）
        $captcha = rand(100000, 999999);
        $ip = Request::ip(); // 替代request()助手函数，更规范

        // 限制单IP每日发送次数（优化查询：用whereTime）
        $limitCount = self::where('ip', $ip)
            ->whereTime('create_time', 'today')
            ->count();
        if ($limitCount >= 20) {
            throw new ApiException("今日验证码发送次数已达上限");
        }

        // 处理渠道名称（仅注册场景）
        if ($type == self::REGISTER_TYPE && !empty($channel)) {
            // 若$channel是ID则查名称，否则直接使用
            if (is_numeric($channel)) {
                $channelName = Channel::where('id', $channel)->value('channel_name');
                $channel = $channelName ?: $channel; // 查不到则保留原值
            }
        }

        // 阿里云短信配置
        $aliyunConfig = [
            'accessKeyId' => env('ali.accesskeyid'),
            'accessKeySecret' => env('ali.accesskeysecret'),
            'signName' => env('ali.signname'),
            'templateCode' => env('ali.templatecode')
        ];

        try {
            // 实例化阿里云短信类
            $aliyunSms = new AliyunSms($aliyunConfig);
            
            // 发送验证码
            $result = $aliyunSms->sendCode([
                'phone' => $phone, 
                'code' => $captcha, 
                'channel' => $channel
            ]);
            print "123";
            print_r($result);
            // 检查发送结果
            if (empty($result)) {
                throw new ApiException("短信发送失败：接口未返回有效数据");
            }
            if (is_array($result)) {
                $result = (object)$result;
            }
            if (!is_object($result) || !isset($result->body)) {
                $errorMsg = is_scalar($result) ? (string)$result : '未知错误';
                throw new ApiException("短信发送失败：{$errorMsg}");
            }
            if ($result->body->code !== 'OK') {
                $errorMsg = $result->body->message ?? '未知错误';
                throw new ApiException("短信发送失败：{$errorMsg}");
            }

            // 记录验证码到数据库（ThinkPHP的create失败会抛ModelException）
            self::create([
                'phone' => $phone,
                'captcha' => $captcha,
                'type' => $type,
                'ip' => $ip,
                'expiration_time' => time() + 600, // 10分钟有效期
                'channel' => $channel // 补充存储渠道
            ]);

            // 限制60秒内重复发送（键名区分类型+手机号，避免冲突）
            $redisKey = "captcha_limit_{$type}_{$phone}";
            $redis->set($redisKey, 1, 60);

            return true; // 成功返回true

        } catch (ModelException $e) {
            throw new ApiException("验证码记录失败：{$e->getMessage()}");
        } catch (ApiException $e) {
            throw $e; // 直接抛出API异常
        } catch (\Exception $e) {
            print('123321');
            throw new ApiException("短信发送异常：{$e->getMessage()}");
        }
    }

    /**
     * 检测验证码
     * @param array $where 查询条件（必填：phone, type）
     * @param string $captcha 用户输入的验证码
     * @return bool
     * @throws ExceptionStd
     */
    public static function checkCaptcha($where = [], $captcha)
    {
        // 测试验证码（建议仅在测试环境启用）
        $testCaptchas = ['654198', '351662', '772968', '111000', '758421', '258699', '524685'];
        if (in_array($captcha, $testCaptchas)) {
            return true;
        }

        // 验证查询条件
        if (empty($where['phone']) || empty($where['type'])) {
            throw new ExceptionStd('查询条件不完整');
        }

        // 获取未使用的最新验证码
        $captchaInfo = self::where($where)
            ->where('is_use', 0)
            ->where('expiration_time', '>', time()) // 先过滤过期的
            ->order('id desc')
            ->find();

        // 验证验证码有效性
        if (empty($captchaInfo) || $captcha != $captchaInfo['captcha']) {
            throw new ExceptionStd('验证码不正确或已过期'); // 需throw异常
        }

        // 标记为已使用
        $captchaInfo->is_use = 1;
        $captchaInfo->save();

        return true;
    }
}