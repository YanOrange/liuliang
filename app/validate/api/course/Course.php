<?php

namespace app\validate\api\course;
use app\validate\BaseValidate;
class Course extends BaseValidate
{
    protected $rule = [
        'channel'      => 'require',
        'page'      => 'require',
        'pagesize'      => 'require',
        'course_id'      => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
        'page.require' => '分页参数错误',
        'pagesize.require' => '分页参数错误',
        'course_id.require' => '课程id参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getCourseIsHotCollectList' => ['channel'],
        'getCourseList' => ['channel', 'page', 'pagesize'],
        'getCourseDetail' => ['course_id','channel'],
    ];
}