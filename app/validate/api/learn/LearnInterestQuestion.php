<?php

namespace app\validate\api\learn;
use app\validate\BaseValidate;
class LearnInterestQuestion extends BaseValidate
{
    protected $rule = [
        'interest_id'      => 'require',
        'channel'      => 'require',
    ];

    protected $message = [
        'interest_id.require' => '问题参数错误',
        'channel.require' => '渠道参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'getQuestionDetail' => ['interest_id','channel'],
        'getQuestionList' => ['channel'],
    ];
}