<?php
/**
 * 推荐阅读表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class LearnPkQuestion extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'learn_pk_question';

    protected $append = [
        'question_answer'
    ];

    public function getQuestionAnswerAttr($value,$data)
    {
        $questionAnswer = '';
        $questionAnswerOptions = json_decode($data['question_answer_options'],true);
        foreach($questionAnswerOptions as $item){
            if($item['selected'] != 0){
                $questionAnswer = $item['question_answer'];
            }
        }
        return $questionAnswer;
    }
}
