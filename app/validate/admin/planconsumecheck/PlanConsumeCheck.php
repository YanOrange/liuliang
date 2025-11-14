<?php

namespace app\validate\admin\planconsumecheck;

use think\Validate;
use app\model\admin\AppClass as AppClassModel;
use app\validate\BaseValidate;

class PlanConsumeCheck extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'channel'           => 'require',
        'flow_consume'      => 'require|gt:0',
        'plantime'       => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'channel.require'          => '渠道不能为空',
        'flow_consume.require'     => '总消耗不能为空',
        'flow_consume.gt'     => '总消耗不能为空',
        'plantime.require'     => '时间范围不能为空',
    ];

    protected $scene = [
        'add' => ['channel','flow_consume','plantime'],
        'edit' => ['channel','flow_consume','plantime'],
    ];
}