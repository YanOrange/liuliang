<?php

namespace app\validate\api\single;
use app\validate\BaseValidate;
use app\model\api\App;
class CourseOrder extends BaseValidate
{
    protected $rule = [
        'course_id'    => 'require',
        'app_bundle_id' => 'require|checkPayConfig',
        'pay_type' => 'require|in:alipay,wxpay',
        'course_type' => 'require|in:1,2',
    ];

    protected $message = [
        'course_id.require' => '课程参数错误',
        'app_bundle_id.require' => '包名参数错误',
        'pay_type.require' => '支付参数错误',
        'pay_type.in' => '支付参数错误',
        'course_type.require' => '课程参数错误',
        'course_type.in' => '课程参数错误',
    ];
    protected function checkPayConfig($appBundleId, $rule, $data) {
        $config = App::where('android_bundle_id|ios_bundle_id|wxmini_bundle_id', '=', $appBundleId)->find();
        if (empty($config)) {
            return '支付配置参数错误';
        }
        return true;
    }
    /**
     * 验证场景
     */
    protected $scene = [
        'payApplyCourse' => ['course_id', 'app_bundle_id', 'pay_type','course_type'],
    ];
}