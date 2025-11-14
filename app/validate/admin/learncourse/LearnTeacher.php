<?php

namespace app\validate\admin\learncourse;

use think\Validate;
use app\model\admin\App as AppModel;
use app\validate\BaseValidate;

class LearnTeacher extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'teacher_name'               => 'require',
        'teacher_image'              => 'require',
        'channel_id'                 => 'require',
        'teacher_tags'               => 'require',
        'qualification'              => 'require',
        'experience'                 => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'teacher_name.require'           => '老师名称不能为空',
        'teacher_image.require'          => '老师图片不能为空',
        'channel_id.require'             => '渠道不能为空',
        'teacher_tags.require'           => '老师标签不能为空',
        'qualification.require'          => '资质不能为空',
        'experience.require'             => '教学经验不能为空',
    ];

    protected $scene = [
        'add' => ['teacher_name','teacher_image','channel_id','teacher_tags', 'qualification','experience'],
        'edit' => ['teacher_name','teacher_image','channel_id','teacher_tags','qualification','experience'],
    ];
}