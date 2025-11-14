<?php
/**
 * 渠道表模型
 */

namespace app\model\api\learn;

use laytp\BaseModel;
use app\lib\api\service\WeightService;
use app\model\api\Channel;
use app\model\api\UserList;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class LearnCourseCredit extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'learn_course_credit';

    protected $append = ['credit_type'];

    public static function UserCreditInfo($params)
    {
        extract($params);
        $weekStr = ['一','二','三','四','五','六','日'];
        $channelInfo = Channel::getChannelAppClass($channel);
        $userCredit = UserList::where('id',$GLOBALS['uid'])->value('credit_num');
        $userCreditSum = self::where('uid',$GLOBALS['uid'])
            ->where('credit_num','>',0)
            ->sum('credit_num');
        $isTodaySign = self::where('uid',$GLOBALS['uid'])
            ->whereDay('create_time')
            ->count() > 0 ? 1 : 0;
        $data['is_today_sign'] = $isTodaySign;
        $data['week_sign'] = [];
        $weekDayArr = getWeekDay();
        $continuousSignDay = 0;
        foreach($weekDayArr as $key => $date){
            $userCreditDay = self::where('uid',$GLOBALS['uid'])
                ->whereDay('create_time',$date)
                ->find();
            if(!empty($userCreditDay)){
                $continuousSignDay++;
                $data['week_sign'][] = $weekStr[$key];
            }
        }
        $data['current_credit'] = $userCredit;
        $data['total_credit'] = $userCreditSum;
        $data['continuous_sign_day'] = $continuousSignDay;
        return $data;
    }

    public static function getCourseCreditList($params)
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $courseCreditList = self::with(['course' => function($query){
            $query->field('id,title');
        }])->where('channel_id',$channelInfo['channel_id'])
            ->where('uid',$GLOBALS['uid'])
            ->field('id,course_id,credit_type,credit_num,create_time')
            ->paginate(10)
            ->toArray();
        return $courseCreditList;
    }

    public function getCreditTypeAttr($value, $data)
    {
        $creditType = '';
        if($data['credit_type'] == 1){
            $creditType = '每日签到';
        }else if($data['credit_type'] == 2){
            $creditType = '会员每日赠送';
        }else if($data['credit_type'] == 3){
            $creditType = '每日学习';
        }else if($data['credit_type'] == 4){
            $creditType = '购买课程';
        }
        return $creditType;
    }

    public static function addCourseCredit($params)
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $res = self::create([
            'uid' => $GLOBALS['uid'],
            'channel_id' => $channelInfo['channel_id'],
            'course_id' => $course_id ?? 0,
            'credit_type' => $credit_type,
            'credit_num' => 5
        ]);
        if($res){
            $userInfo = UserList::where('id',$GLOBALS['uid'])->find();
            $userInfo->credit_num = $userInfo->credit_num + 5;
            $userInfo->save();
        }
        return [];
    }

    public function course()
    {
        return $this->belongsTo('app\model\api\learn\LearnCourse','course_id','id')->removeOption('soft_delete');
    }


}
