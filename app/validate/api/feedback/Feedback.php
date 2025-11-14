<?php

namespace app\validate\api\feedback;
use app\validate\BaseValidate;
class Feedback extends BaseValidate
{
    protected $rule = [
        'content'       => 'require|length:10,200',
        'contact'       => 'require|length:5,30',
    ];

    protected $message = [
        'content.require'      => '请输入反馈内容',
        'content.length'       => '反馈内容10-200字',
     //   'contact.require'      => '请输入联系方式',
      //  'contact.length'       => '请输入5-30位联系方式',
    ];
    /**
     * 验证场景
     */
    protected $scene = [
        'saveFeedback' => ['content'],
    ];
}