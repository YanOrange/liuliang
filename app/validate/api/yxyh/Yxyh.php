<?php

namespace app\validate\api\yxyh;
use app\validate\BaseValidate;
class Yxyh extends BaseValidate
{
    //数组顺序就是检测的顺序，比如这里，会先检测code验证码的正确性
    protected $rule = [
        'channel'    => 'require',
        'app_bundle_id' => 'require',
        'course_id' => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'channel.require'    => '渠道参数错误',
        'app_bundle_id.require'    => '包名参数错误',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'yxyhHomePage' => ['channel', 'app_bundle_id'],
        'getCourseDetail' => ['channel', 'course_id'],
    ];
}