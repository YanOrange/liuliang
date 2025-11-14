<?php

namespace app\validate\api\courseorder;
use app\validate\BaseValidate;
use app\model\api\App;
class CourseOrder extends BaseValidate
{
    protected $rule = [
        'course_id'    => 'require',
        'app_bundle_id' => 'require|checkPayConfig',
        'pay_type' => 'require|in:alipay,wxpay',
    ];

    protected $message = [

        'app_bundle_id.require' => '分页参数错误',
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
        'payApplyCourse' => ['course_id', 'app_bundle_id', 'pay_type'],
    ];
}