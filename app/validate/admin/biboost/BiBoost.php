<?php

namespace app\validate\admin\biboost;

use app\validate\BaseValidate;

class BiBoost extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'teenager_count' => 'between:0,99999',
        'deletion_count' => 'between:0,99999',
        'weixin_count' => 'between:0,99999',
        'money' => 'between:0,99999',
        'merchant_id' => 'require',
        'pre_sales_id' => 'require',
        'after_sales_id' => 'require',
        'cpa' => 'between:0,99999',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'teenager_count.between' => '未成年人补量条数位于0~99999',
        'deletion_count.between' => '秒删补量条数位于0~99999',
        'weixin_count.between' => '加微信补量条数位于0~99999',
        'money.between' => '补量金额位于0~99999',
        'merchant_id.require' => '请选择补量商户',
        'pre_sales_id.require' => '请选择所属前端销售',
        'after_sales_id.require' => '请选择所属后端销售',
        'cpa.between' => 'CPA位于0~99999',
    ];

    protected $scene = [
        'add' => ['teenager_count', 'deletion_count', 'weixin_count', 'cpa', 'money', 'merchant_id', 'pre_sales_id', 'after_sales_id'],
        'edit' => ['teenager_count', 'deletion_count', 'weixin_count', 'cpa', 'money', 'merchant_id', 'pre_sales_id', 'after_sales_id', 'id'],
    ];
}