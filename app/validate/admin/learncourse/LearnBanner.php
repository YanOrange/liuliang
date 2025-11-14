<?php

namespace app\validate\admin\learncourse;

use think\Validate;
use app\model\admin\App as AppModel;
use app\validate\BaseValidate;

class LearnBanner extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'channel_ids'               => 'require',
        'image'               => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'channel_ids.require'     => '请选择渠道',
        'image.require'           => '请上传图片',
    ];

    protected $scene = [
        'add' => ['channel_ids','image'],
        'edit' => ['channel_ids','image'],
    ];
}