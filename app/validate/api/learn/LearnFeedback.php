<?php

namespace app\validate\api\learn;
use app\validate\BaseValidate;
class LearnFeedback extends BaseValidate
{
    protected $rule = [
        'channel'           => 'require',
        'content'           => 'require',
        'feedback_id'           => 'require',
        'feedback_image'    => 'require',
    ];

    protected $message = [
        'channel.require' => '渠道参数不为空',
        'content.require' => '作业内容不为空',
        'feedback_id.require' => '提交参数错误',
        'feedback_image.require' => '作业图片不为空',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getWorkList' => ['channel'],
        'submitWork' => ['channel','content','feedback_image'],
        'addFeedback' => ['channel','content','feedback_phone'],
        'getWorkDetail' => ['channel','feedback_id'],
    ];
}