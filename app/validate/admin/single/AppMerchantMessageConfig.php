<?php

namespace app\validate\admin\single;

use app\validate\BaseValidate;

class AppMerchantMessageConfig extends BaseValidate {
    //数组顺序就是检测的顺序
    protected $rule = [
        'merchant_ids' => 'require',
        'app_ids' => 'require',
        'avatar' => 'require',
        'nickname' => 'require',
        'btn_desc' => 'require',
    ];
    //定义内置方法检验失败后返回的字符
    protected $message = [
        'merchant_ids.require' => '请选择商户',
        'app_ids.require' => '请选择应用',
        'avatar.require' => '请上传头像',
        'nickname.require' => '请输入昵称',
        'btn_desc.require' => '请输入按钮文案',
    ];

    protected $scene = [
        'add' => ['merchant_ids','app_ids','avatar','nickname','btn_desc'],
        'edit' => ['merchant_ids','app_ids','avatar','nickname','btn_desc'],
    ];
}