<?php

namespace app\validate\api\app_user_start_record;
use app\validate\BaseValidate;
class AppUserStartRecord extends BaseValidate
{
    //数组顺序就是检测的顺序，比如这里，会先检测code验证码的正确性
    protected $rule = [
        'channel'    => 'require',
        'app_bundle_id' => 'require',
        'start_time' => 'require',
        'end_time' => 'require',
        'use_time' => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'channel.require'    => '渠道参数错误',
        'app_bundle_id.require'    => '包名参数错误',
        'start_time.require'    => '启动app时间参数错误',
        'end_time.require'    => '退出app时间参数错误',
        'use_time.require'    => '使用时长参数错误',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'startApp' => ['channel','app_bundle_id','start_time','end_time','use_time'],
    ];
}