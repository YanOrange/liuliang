<?php

namespace app\validate\api\single;
use app\validate\BaseValidate;
class Show extends BaseValidate
{
    protected $rule = [
        'channel'      => 'require',
        'app_bundle_id'      => 'require',
        'sort_id'      => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
        'app_bundle_id.require' => '包名参数错误',
        'sort_id.require' => '排序参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'choiceCourse' => ['channel'],
        'recommendCourse' => ['channel','sort_id'],
    ];
}