<?php

namespace app\validate\admin\nojumpwechatphone;

use app\validate\BaseValidate;
use app\model\admin\NoJumpWechatPhone as NoJumpWechatPhoneModel;

class NoJumpWechatPhone  extends BaseValidate
{
//数组顺序就是检测的顺序
    protected $rule = [
        'phone'          => 'require|checkPhone:',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'phone.require'             => '手机号不能为空',
    ];

    //验证渠道名的唯一性
    protected function checkPhone($phone, $rule, $data) {
        $phoneId = NoJumpWechatPhoneModel::getFieldByPhone($phone, 'id');
        if (!isset($data['id'])) {
            if ($phoneId) {
                return '手机号已存在';
            }
        }
        if ($phoneId && $phoneId != $data['id']) {
            return '手机号已存在';
        }
        return true;
    }

    protected $scene = [
        'add' => ['phone'],
        'edit' => ['phone'],
    ];
}