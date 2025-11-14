<?php

namespace app\validate\api\single;
use app\validate\BaseValidate;
class VideoArticle extends BaseValidate
{
    protected $rule = [
        'channel'      => 'require',
        'video_id'      => 'require',
        'article_id'      => 'require',
        'course_id'      => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数错误',
        'video_id.require' => '视频参数错误',
        'article_id.require' => '文章参数错误',
        'course_id.require' => '课程参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getVideoArticleList' => ['channel'],
        'getVideoList' => ['channel','video_id'],
        'getArticleList' => ['channel','article_id'],
        'getCourseDetail' => ['course_id'],
        'setLikeNum' => ['video_id'],
    ];
}