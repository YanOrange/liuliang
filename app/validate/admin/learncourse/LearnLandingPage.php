<?php

namespace app\validate\admin\learncourse;

use think\Validate;
use app\model\admin\App as AppModel;
use app\validate\BaseValidate;

class LearnLandingPage extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'landing_page_type'               => 'require',
        'channel_ids'                 => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'landing_page_type.require'           => '落地页类型不能为空',
        'channel_ids.require'          => '渠道不能为空',
    ];

    protected $scene = [
        'add' => ['landing_page_type','channel_ids'],
        'edit' => ['landing_page_type','channel_ids'],
    ];
}