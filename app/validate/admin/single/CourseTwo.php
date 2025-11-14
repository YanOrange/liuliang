<?php

namespace app\validate\admin\single;

use app\validate\BaseValidate;

class CourseTwo extends BaseValidate {
    //数组顺序就是检测的顺序
    protected $rule = [
        'status' => 'require|in:1,0',
        'merchant_ids' => 'require',
        'title' => 'require|length:2,30',
        'entry_fee' => 'require',
        'video_cover_image' => 'require',
        'video_entry_image' => 'require',
        'apply_btn_image' => 'require',
        'apply_course_image' => 'require',
    ];
    //定义内置方法检验失败后返回的字符
    protected $message = [
        'status.require' => '请选择课程状态',
        'merchant_ids.require' => '请选择商户',
        'title.require' => '请输入课程标题',
        'title.length' => '课程标题长度2-30字',
        'entry_fee.require' => '请输入售卖价',
        'video_cover_image.require' => '请上传课程封面',
        'video_entry_image.require' => '请上传课程封面',
        'apply_btn_image.require' => '请上传课程封面',
        'apply_course_image.require' => '请上传课程封面',
    ];

    protected $scene = [
        'add' => ['status','merchant_ids','title','entry_fee','video_cover_image','video_entry_image','apply_btn_image','apply_course_image'],
        'edit' => ['status','merchant_ids','title','entry_fee','video_cover_image','video_entry_image','apply_btn_image','apply_course_image'],
    ];
}