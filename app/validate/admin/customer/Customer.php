<?php

namespace app\validate\admin\customer;

use think\Validate;
use app\model\admin\Customer as CustomerModel;
use app\validate\BaseValidate;

class Customer extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'account_name'         => 'require|length:2,30|checkAccountName:',
        'login_mobiles'         => 'require|checkLoginMobiles:',
        'pwd'                  => 'require',
        'nickname'         => 'require',
        'avatar'         => 'require',
        'merchant_id'         => 'require',
        'daily_intake_limit_nums'         => 'require',
        'weight'         => 'require|between:1,1000',
        'thread_status'            => 'require|in:1,0',
        'status'               => 'require|in:1,0',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'account_name.require'     => '用户名不能为空',
        'account_name.length'      => '用户名长度2-30',
        'pwd.require'              => '密码不能为空',
        'login_mobiles.require'              => '手机号不能为空',
        'nickname.require'     => '请输入昵称',
        'avatar.require'     => '请上传头像',
        'merchant_id.require'     => '请选择商户',
        'daily_intake_limit_nums.require'     => '请输入当日线索分配数量限制',
        'weight.require'     => '请输入权重',
        'weight.between'     => '权重1-1000区间',
        'thread_status.require'        => '请设置线索状态',
        'thread_status.in'        => '请设置线索状态',
        'status.require'           => '请设置账号的状态',
        'status.in'           => '请设置账号的状态',
    ];

    //验证用户名的唯一性
    protected function checkAccountName($accountName, $rule, $data){
        $merchantId = CustomerModel::getFieldByAccountName($accountName, 'id');
        if (!isset($data['id'])) {
            if ($merchantId) {
                return '用户名已存在';
            }
        }
        if($merchantId && $merchantId != $data['id']){
            return '用户名已存在';
        }

        return true;
    }
    //验证手机号唯一性
    protected function checkLoginMobiles($loginMobiles, $rule, $data){
        $loginMobiles = explode(',',$loginMobiles);
        foreach($loginMobiles as $phone) {
            if (!empty($phone)) {
                $merchantId = CustomerModel::whereFindInSet('login_mobiles', $phone)->value('id');
                if (!isset($data['id'])) {
                    if ($merchantId) {
                        return '手机号已存在';
                    }
                }
                if ($merchantId && $merchantId != $data['id']) {
                    return '手机号已存在';
                }
            }
        }

        return true;
    }

    protected $scene = [
        'add' => ['account_name','login_mobiles','pwd','nickname','merchant_id','daily_intake_limit_nums','weight','thread_status','status'],
        'edit' => ['account_name','login_mobiles','nickname','merchant_id','daily_intake_limit_nums','weight','thread_status','status'],
    ];
}