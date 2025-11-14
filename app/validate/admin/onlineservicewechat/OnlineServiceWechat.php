<?php

namespace app\validate\admin\onlineservicewechat;
use app\validate\BaseValidate;
class OnlineServiceWechat extends BaseValidate
{
    protected $rule = [
        'service_name'            => 'require',
        'channel_ids'             => 'require',
        'auto_reply_content'      => 'require',
        'auto_push_time'          => 'require|gt:0',
        'speech_btn_desc'         => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'service_name.require'      => '请输入客服名称',
        'channel_ids.require'      => '请选择关联渠道',
        'auto_reply_content.require'        => '请输入自动回复内容',
        'auto_push_time.require'        => '请输入自动回复推送时间',
        'auto_push_time.gt'        => '自动回复推送时间不能为0',
        'speech_btn_desc.require'        => '请输入话术按钮文案',
    ];

    protected $scene = [
        'add' => ['service_name','channel_ids','auto_reply_content','auto_push_time','speech_btn_desc'],
        'edit' => ['service_name','channel_ids','auto_reply_content','auto_push_time','speech_btn_desc'],
    ];
}