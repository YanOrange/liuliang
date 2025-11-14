<?php

namespace app\validate\api\single;

use app\validate\BaseValidate;

class Banner extends BaseValidate
{
    protected $rule = [
        'channel'      => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getBannerList' => ['channel'],
    ];
}