<?php

namespace app\validate\api\vestbag\vestuseraccountdetail;
use app\validate\BaseValidate;
class VestUserAccountDetail extends BaseValidate
{
    protected $rule = [
        'icon_id'    => 'require',
        'type'    => 'require|in:1,2,all',
        'account_id'    => 'require',
        'account_remaining' => 'require',
        'month' => 'require',
        'current_month_budget_amount' => 'require',
    ];

    protected $message = [
        'icon_id.require' => '请选择图标',
        'type.require' => '请选择支出或收入',
        'account_id.require' => '请选择账户',
        'account_remaining.require' => '请输入金额',
        'current_month_budget_amount.require' => '请输入本月预算金额',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'addAccountDetail' => ['icon_id', 'type', 'account_id', 'account_remaining'],
        'getAccountDetailList' => ['type'],
        'setCurrentMonthBudget' => ['current_month_budget_amount'],
    ];
}