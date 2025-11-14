<?php

namespace app\validate\api\show;
use app\validate\BaseValidate;
class Show extends BaseValidate
{
    protected $rule = [
        'channel'      => 'require',
        'app_bundle_id'      => 'require',
        'course_id' => 'require',
        'landing_page_id' => 'require',
        'part_job_id' => 'require',
        'lawyer_id' => 'require',
        'debt_platform' => 'require',
        'debt_amount' => 'require|gt:0',
        'total_periods' => 'require|number',
        'residue_periods' => 'require|number',
        'is_overdue' => 'require|in:0,1',
        'solution' => 'require',
        'debt_id' => 'require',
        'article_id' => 'require',
        'app_version' => 'require',
        'phone' => 'require',
        'nickname' => 'require',
        'debt_info' => 'require',
        'animator_type' => 'require',
        'id' => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
        'app_bundle_id.require' => '包名参数错误',
        'app_version.require' => 'app版本号参数错误',
        'course_id.require' => '该课程名额已满',
        'landing_page_id.require' => '参数错误',
        'part_job_id.require' => '参数错误',
        'lawyer_id.require' => '参数错误',
        'debt_platform.require' => '请输入欠款平台',
        'debt_amount.require' => '请输入欠款金额',
        'debt_amount.gt' => '欠款金额要大于0',
        'total_periods.require' => '请选择总期数',
        'total_periods.number' => '请选择总期数',
        'residue_periods.require' => '请选择剩余期数',
        'residue_periods.number' => '请选择剩余期数',
        'is_overdue.require' => '请选择是否逾期',
        'is_overdue.in' => '请选择是否逾期',
        'solution.require' => '请输入期待的解决方案',
        'debt_id.require' => '参数错误',
        'article_id.require' => '参数错误',
        'phone.require' => '请输入手机号',
        'nickname.require' => '请输入姓名',
        'debt_info.require' => '请输入欠款平台',
        'animator_type.require' => '请选择类目',
        'id.require' => '资讯参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'homePage' => ['channel','app_bundle_id'],
        'getInformationList' => ['channel','app_bundle_id'],
        'getInformationInfo' => ['id', 'channel','app_bundle_id'],
        'yqPortal' => ['channel','app_bundle_id'],
        'getAppClassList' => ['channel'],
        'getCourseInfo' => ['channel', 'course_id'],
        'getAbLandingPageInfo' => ['landing_page_id'],
        'getPartJobDetail' => ['channel','app_bundle_id', 'part_job_id'],
        'homePageV2' => ['channel','app_bundle_id','app_version'],
        'getLawyerDetailV2' => ['channel','app_bundle_id','app_version','lawyer_id'],
//        'createOverdueDebt' => ['channel','app_bundle_id','app_version','debt_platform','debt_amount','total_periods','residue_periods','is_overdue','solution'],
        'createOverdueDebt' => ['channel','app_bundle_id','app_version','debt_platform','debt_amount'],
        'getMyOverdueDebtDetail' => ['channel','app_bundle_id','app_version','debt_id', 'zw_mold'],
        'likeLawyer' => ['channel','app_bundle_id','app_version','lawyer_id'],
        'getConsultList' => ['channel','app_bundle_id'],
        'getArticleDetail' => ['channel','app_bundle_id','app_version','article_id'],
        'getUserVip' => ['channel','app_bundle_id','app_version', 'phone', 'nickname', 'debt_info'],
        'keyAnimator' => ['channel','app_bundle_id','animator_type'],
    ];
}