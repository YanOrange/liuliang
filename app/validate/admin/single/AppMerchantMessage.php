<?php

namespace app\validate\admin\single;

use app\validate\BaseValidate;

class AppMerchantMessage extends BaseValidate {
    //数组顺序就是检测的顺序
    protected $rule = [
        'app_message_id' => 'require',
        'title' => 'require',
        'content' => 'require',
        'times' => 'require',
    ];
    //定义内置方法检验失败后返回的字符
    protected $message = [
        'app_message_id.require' => '请选择商户应用主体',
        'title.require' => '请输入消息标题',
        'content.require' => '请输入消息内容',
        'times.require' => '请输入显示时长',


    ];

    protected $scene = [
        'add' => ['app_message_id','title','content','times'],
        'edit' => ['app_message_id','title','content','times'],
    ];
}