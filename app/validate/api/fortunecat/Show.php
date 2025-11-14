<?php

namespace app\validate\api\fortunecat;

use app\validate\BaseValidate;
class Show extends BaseValidate
{
    protected $rule = [
        'channel'      => 'require',
        'app_bundel_id' => 'require',
        'team_id'     => 'require',
        'course_id'     => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
        'app_bundle_id.require' => '包名参数错误',
        'team_id.require' => '参数错误',
        'course_id.require' => '参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'homePage' => ['channel'],
        'getZwhkyhFirstPage' => ['channel'],
        'getPartClassList' => ['channel'],
        'getOverdueTeamDetail' => ['channel'],
        'courseDetail' => ['channel','course_id'],
    ];
}