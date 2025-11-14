<?php

namespace app\validate\admin\app_for_flow;

use think\Validate;
use app\validate\BaseValidate;

class AppForFlow extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'for_flow_title'              => 'require|length:2,30',
        'app_ids'                     => 'require',
        'system_avatar'               => 'require',
        'hint_official'               => 'require|length:2,20',
        'btn_desc'                    => 'require|length:2,20',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'for_flow_title.require'             => '信息流名称不能为空',
        'for_flow_title.length'              => '信息流名称长度2-30',
        'app_ids.require'                    => '请选择应用',
        'system_avatar.require'              => '请上传系统头像',
        'hint_official.require'              => '请输入提示文案',
        'hint_official.length'               => '提示文案长度2-20',
        'btn_desc.require'                   => '请输入按钮文案',
        'btn_desc.length'                    => '按钮文案长度2-20',
    ];
    protected $scene = [
        'add' => ['for_flow_title','app_ids','system_avatar','hint_official','btn_desc'],
        'edit' => ['for_flow_title','app_ids','system_avatar','hint_official','btn_desc'],
    ];
}
