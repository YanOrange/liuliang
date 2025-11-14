<?php

namespace app\validate\admin\part;

use think\Validate;
use app\validate\BaseValidate;
class StudyVideoResource extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'video_title'              => 'require',
        'video_url'              => 'require',
        'study_nums'              => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'video_title.require'             => '请输入视频标题',
        'video_url.require'              => '请上传视频',
        'study_nums.require'              => '请输入学习人数',
    ];

    protected $scene = [
        'add' => ['video_title','video_url','study_nums'],
        'edit' => ['video_title','video_url','study_nums'],
    ];
}