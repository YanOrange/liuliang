<?php

namespace app\validate\admin\wxofficialaccount;

use think\Validate;
use app\model\admin\WxOfficialAccount as WxOfficialAccountModel;
use app\validate\BaseValidate;

class WxOfficialAccount extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'account_name'       => 'require|length:2,30|checkAccountName:',
        'appId'              => 'require',
        'admin_id'              => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'account_name.require'             => '公众号名称不能为空',
        'account_name.length'              => '公众号名称长度2-30',
        'admin_id.require'                 => '负责人不能为空',
    ];

    //验证应用名的唯一性
    protected function checkAccountName($accountName, $rule, $data){
        $accountId = WxOfficialAccountModel::getFieldByAccountName($accountName, 'id');
        if (!isset($data['id'])) {
            if ($accountId) {
                return '公众号名称已存在';
            }
        }
        if ($accountId && $accountId != $data['id']) {
            return '公众号名称已存在';
        }
        return true;
    }
    protected $scene = [
        'add' => ['account_name','appId','admin_id'],
        'edit' => ['account_name','appId','admin_id'],
    ];
}