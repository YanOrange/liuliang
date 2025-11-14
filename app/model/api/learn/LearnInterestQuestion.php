<?php
/**
 * 渠道表模型
 */

namespace app\model\api\learn;

use app\model\admin\LearnBanner;
use laytp\BaseModel;
use app\lib\api\service\WeightService;
use app\model\api\Channel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class LearnInterestQuestion extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'learn_interest_question';

    public static function getQuestionDetail($params)
    {
        extract($params);
        $questionDetail = self::where('id',$interest_id)
            ->field('id,interest_title,question_answer_json,result_answer_json')
            ->find();
        $questionDetail['questionAnswer'] = [];
        $questionDetail['resultAnswer'] = [];
        $questionDetail['question_answer_json'] = json_decode($questionDetail['question_answer_json'],true);
        $questionDetail['result_answer_json'] = json_decode($questionDetail['result_answer_json'],true);
        if(isset($question_id) && !empty($question_id)) {
            $finishNum = $question_id;
            $answerKey = array_search($num,['A','B','C','D','E','F','G','H']);
            if (is_numeric($questionDetail['question_answer_json'][$question_id-1]['question_answer_options'][$answerKey]['question_answer_id'])) {
                $questionDetail['questionAnswer'] = $questionDetail['question_answer_json'][$question_id];
            } else {
                $questionDetail['resultAnswer'] = $questionDetail['result_answer_json'][$answerKey];
            }
        }else{
            $finishNum = 0;
            $questionDetail['questionAnswer'] = $questionDetail['question_answer_json'][0];
        }
        $questionDetail['question_btn'] = count($questionDetail['question_answer_json']) > 1 ? 1 : 2;
        $totalNum = count($questionDetail['question_answer_json']);
        unset($questionDetail['question_answer_json']);
        unset($questionDetail['result_answer_json']);
        $data['questionNum'] = ['finish_num' => $finishNum,'total_num' => $totalNum];
        $data['questionDetail'] = $questionDetail;
        return $data;
    }

    public static function getQuestionList($params)
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $data['questionImage'] = LearnBanner::where('status',1)
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            ->where('banner_type',3)
            ->value('image');
        $questionList = self::where('status',1)
            ->field('id,interest_title,question_image')
            ->order('id desc')
            ->paginate(20)
            ->toArray();
        foreach($questionList['data'] as &$item){
            $item['evaluate_num'] = rand(100,999);
        }
        $data['questionList'] = $questionList;
        return $data;
    }


}
