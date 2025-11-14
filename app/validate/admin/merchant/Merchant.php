<?php

namespace app\validate\admin\merchant;

use think\Validate;
use app\model\admin\Merchant as MerchantModel;
use app\validate\BaseValidate;

class Merchant extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'account_name'         => 'require|length:2,30|checkAccountName:',
        'login_mobiles'         => 'require|checkLoginMobiles:',
        'pwd'                  => 'require',
        'merchant_name'         => 'require',
        'merchant_logo'         => 'require',
        'is_switch'            => 'require',
        'status'               => 'require',
        'thread_period_num'    => 'require|number|gt:0',
        'capital_landing_page_share_merchant_ratio1'    => 'require|number',
        'capital_landing_page_share_merchant_ratio2'    => 'require|number|gt:0',
        'normal_mileage'         => 'require|number|gt:0',
        'customer_supplement'         => 'require|number',
        'natural_supplement'         => 'require|number',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'account_name.require'     => '用户名不能为空',
        'account_name.length'      => '用户名长度2-30',
        'pwd.require'              => '登陆密码不能为空',
        'login_mobiles.require'    => '手机号不能为空',
        'merchant_name.require'     => '请输入商户名称',
        'merchant_logo.require'     => '请上传商户logo',
        'is_switch.require'        => '请设置进量状态',
        'status.require'           => '请设置账号的状态',
        'thread_period_num.require'           => '请填写线索周期数量',
        'thread_period_num.gt'           => '线索周期数量是大于0的整数',
        'thread_period_num.number'           => '线索周期数量是大于0的整数',
        'capital_landing_page_share_merchant_ratio1.require'           => '请填写0元留资落地页分配一分商户比值1',
        'capital_landing_page_share_merchant_ratio1.number'           => '0元留资落地页分配一分商户比值是整数1',
        'capital_landing_page_share_merchant_ratio2.require'           => '请填写0元留资落地页分配一分商户比值2',
        'capital_landing_page_share_merchant_ratio2.gt'           => '0元留资落地页分配一分商户比值2是大于0的整数',
        'capital_landing_page_share_merchant_ratio2.number'           => '0元留资落地页分配一分商户比值2是大于0的整数',
        'normal_mileage.require'           => '请填写正常跑量',
        'normal_mileage.gt'           => '正常跑量是大于0的整数',
        'normal_mileage.number'           => '正常跑量是大于0的整数',
        'customer_supplement.require'           => '请填写客服补量',
        'customer_supplement.number'           => '客服补量是整数',
        'natural_supplement.require'           => '请填写自然补量',
        'natural_supplement.number'           => '自然补量是整数',
    ];

    //验证用户名的唯一性
    protected function checkAccountName($accountName, $rule, $data){
        $merchantId = MerchantModel::getFieldByAccountName($accountName, 'id');
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
                $merchantId = MerchantModel::whereFindInSet('login_mobiles', $phone)->where('company_id',$data['company_id'])->value('id');
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
        'add' => ['account_name','login_mobiles','pwd','thread_period_num','merchant_name','merchant_logo','is_switch','status','capital_landing_page_share_merchant_ratio1'],
        'edit' => ['account_name','login_mobiles','merchant_name','thread_period_num','merchant_logo','is_switch','status','capital_landing_page_share_merchant_ratio2'],
        'setSupplement' => ['customer_supplement','natural_supplement'],
    ];

    public function checkTenImCustomer($clsId, $status, $icon)
    {
        if ($status && $clsId == env('TENIM.YUQICLSID') && empty($icon)) {
            return ['status' => false, 'msg' => '请上传客服咨询入口图标'];
        }

        return ['status' => true, 'msg' => 'OK'];
    }
}