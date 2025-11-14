<?php

namespace app\validate\admin\single;

use app\validate\BaseValidate;

class SingleVideoArticleValidate extends BaseValidate {
    //数组顺序就是检测的顺序
    protected $rule = [
        'type' => 'require',
        'app_ids' => 'require',
        'title' => 'require',
        'video_url' => 'require',
        'video_image' => 'require',
        'course_id' => 'require',
        'play_nums' => 'require',
    ];
    //定义内置方法检验失败后返回的字符
    protected $message = [
        'type.require' => '请选择视频分类',
        'app_ids.require' => '请选择应用',
        'title.require' => '请输入标题',
        'video_url.require' => '请上传视频',
        'video_image.require' => '请上传视频封面',
        'course_id.require' => '请选择关联课程',
        'play_nums.require' => '请输入播放次数'
    ];

    protected $scene = [
        'addVideo' => ['type','app_ids','title','video_url','video_image','play_nums'],
        'editVideo' => ['type','app_ids','title','video_url','video_image','play_nums'],
        'addArticle' => ['type','app_ids','article_id','play_nums'],
        'editArticle' => ['type','app_ids','article_id','play_nums'],
    ];
}