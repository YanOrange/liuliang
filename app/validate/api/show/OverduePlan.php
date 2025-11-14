<?php

namespace app\validate\api\show;
use app\validate\BaseValidate;
class OverduePlan extends BaseValidate
{
    protected $rule = [
        'channel'      => 'require',
        'app_bundle_id'      => 'require',
        'app_version' => 'require',
        'plan_type' => 'require|in:1,2',
        'title' => 'require',
        'desc' => 'require',
        'total_periods' => 'require|number',
        'residue_periods' => 'requireIf:plan_type,1|number',
        'due_date' => 'require',
        'reminder_time' => 'require',
        'month_nums' => 'requireIf:plan_type,2|number',
        'plan_id' => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
        'app_bundle_id.require' => '包名参数错误',
        'app_version.require' => 'app版本号参数错误',
        'plan_type.require' => '提醒类型参数错误',
        'plan_type.in' => '提醒类型参数错误',
        'title.require' => '请输入平台名称或任务标题',
        'desc.require' => '请输入平台类型或任务描述',
        'total_periods.require' => '请选择总期数',
        'total_periods.number' => '请选择总期数',
        'residue_period.requireIf' => '请选择剩余期数',
        'residue_period.number' => '请选择剩余期数',
        'due_date.require' => '请选择还款日或固定日期',
        'reminder_time.require' => '请选择提醒时间',
        'month_nums.requireIf' => '请选择月数',
        'month_nums.number' => '请选择月数',
        'plan_id.require' => '参数错误',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'createPlan' => ['channel','app_bundle_id','app_version','plan_type','title', 'desc', 'total_periods', 'residue_periods', 'due_date', 'reminder_time'],
        'savePlanStatus' => ['channel','app_bundle_id','app_version','plan_id'],
        'getIntelligentMeasurement' => ['channel','app_bundle_id','app_version'],
    ];
}