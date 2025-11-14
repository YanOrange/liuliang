<?php

namespace app\validate\api\coursecollect;
use app\validate\BaseValidate;
class CourseCollect extends BaseValidate
{
    protected $rule = [
        'course_id'      => 'require',
        'page'      => 'require',
        'pagesize'      => 'require',
    ];

    protected $message = [
        'page.require' => '分页参数错误',
        'pagesize.require' => '分页参数错误',
        'course_id.require' => '请选择收藏的课程',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getCourseCollectList' => ['page', 'pagesize'],
        'addCourseCollect' => ['course_id'],
        'cancelCourseCollect' => ['course_id'],
    ];
}