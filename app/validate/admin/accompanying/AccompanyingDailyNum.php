<?php

namespace app\validate\admin\accompanying;

use think\Validate;
use app\model\admin\AppClass as AppClassModel;
use app\validate\BaseValidate;

class AccompanyingDailyNum extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'datetime'              => 'require',
        'wechat_num'              => 'require',
        'learn_num'              => 'require',
        'visit_num'              => 'require',
        'unknown_num'              => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'datetime.require'             => '请选择陪诊日期',
        'wechat_num.require'              => '请填加V总数',
        'learn_num.require'              => '请填写学习人数',
        'visit_num.require'              => '请填写就诊人数',
        'unknown_num.require'              => '请填写未知人数',
    ];

    protected $scene = [
        'add' => ['datetime'],
        'edit' => ['datetime'],
    ];
}