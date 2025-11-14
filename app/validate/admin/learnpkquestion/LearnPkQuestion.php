<?php

namespace app\validate\admin\learnpkquestion;

use think\Validate;
use app\model\admin\App as AppModel;
use app\validate\BaseValidate;

class LearnPkQuestion extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'question_title'                 => 'require',
        'question_answer'                => 'require',
        'question_answer_selected'       => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'question_title.require'             => '问题名称不能为空',
        'question_answer.length'              => '问题答案不能为空',
        'question_answer_selected.require'        => '请选择问题答案',
    ];

    protected $scene = [
        'add' => ['question_title','question_answer','question_answer_selected'],
        'edit' => ['question_title','question_answer','question_answer_selected'],
    ];
}