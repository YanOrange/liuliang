<?php

namespace app\validate\admin\channelhomeconfig;

use app\validate\BaseValidate;

class ChannelHomeConfig extends BaseValidate
{
    protected $rule = [
        'channel_ids'            => 'require',
        'banner'             => 'require',
        'top_image'      => 'require',
        'navigation'          => 'require',
        'sub_image'         => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'channel_ids.require'      => '请选择关联渠道',
        'banner.require'      => '请上传banner图',
        'top_image.require'        => '请上传头部图',
        'navigation.require'        => '请上传导航图',
        'sub_image.require'        => '请上传副图',
    ];

    protected $scene = [
        'add' => ['channel_ids','banner','top_image','navigation','sub_image'],
        'edit' => ['channel_ids','banner','top_image','navigation','sub_image'],
    ];
}