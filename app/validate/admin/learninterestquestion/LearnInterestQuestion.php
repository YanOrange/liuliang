<?php

namespace app\validate\admin\learninterestquestion;

use think\Validate;
use app\model\admin\App as AppModel;
use app\validate\BaseValidate;

class LearnInterestQuestion extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'question_title'                 => 'require',
        'question_image'                => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'question_title.require'             => '问题名称不能为空',
        'question_image.length'              => '问题图片不能为空',
    ];

    protected $scene = [
        'add' => ['question_title','question_image'],
        'edit' => ['question_title','question_image'],
    ];
}