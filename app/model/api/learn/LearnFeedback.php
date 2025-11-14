<?php
/**
 * 渠道表模型
 */

namespace app\model\api\learn;

use app\service\admin\UserServiceFacade;
use laytp\BaseModel;
use app\lib\api\service\WeightService;
use app\model\api\Channel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class LearnFeedback extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'learn_feedback';

    //作业列表
    public static function getWorkList($params)
    {
        extract($params);
        $workList = self::where('uid',$GLOBALS['uid'])
            ->where('course_id',$course_id)
            ->where('feedback_type',3)
            ->field('id,feedback_image,content')
            ->paginate(10)
            ->toArray();
        return $workList;
    }

    //作业详情
    public static function getWorkDetail($params)
    {
        extract($params);
        $workInfo = self::where('id',$feedback_id)
            ->field('id,feedback_image,content,feedback_answer')
            ->find();
        return $workInfo;
    }

    //提交作业
    public static function submitWork($params)
    {
        extract($params);
        self::create([
            'uid' => $GLOBALS['uid'],
            'course_id' => $course_id,
            'feedback_type' => 3,
            'feedback_image' => $feedback_image,
            'content' => $content
        ]);
        return [];
    }

    //问题反馈
    public static function addFeedback($params)
    {
        extract($params);
        self::create([
            'uid' => $GLOBALS['uid'],
            'feedback_type' => 1,
            'feedback_image' => $feedback_image ?? '',
            'content' => $content,
            'feedback_phone' => $feedback_phone
        ]);
        return [];
    }


}
