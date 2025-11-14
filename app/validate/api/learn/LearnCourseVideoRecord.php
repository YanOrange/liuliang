<?php

namespace app\validate\api\learn;
use app\validate\BaseValidate;
class LearnCourseVideoRecord extends BaseValidate
{
    protected $rule = [
        'course_id'              => 'require',
        'course_section_id'      => 'require',
        'video_duration'         => 'require',
        'play_duration'          => 'require',
    ];

    protected $message = [
        'course_id.require' => '课程参数错误',
        'course_section_id.require' => '课程参数错误',
        'video_duration.require' => '视频时长不能为空',
        'play_duration.require' => '视频播放时长不能为空',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'addCourseVideoRecord' => ['course_id','course_section_id','video_duration','play_duration'],
    ];
}