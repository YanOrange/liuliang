<?php
/**
 * 渠道表模型
 */

namespace app\model\api\learn;

use app\lib\api\other\CommonCourse;
use app\model\api\UserList;
use app\model\api\Merchant;
use laytp\BaseModel;
use app\lib\api\service\WeightService;
use app\model\api\Channel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
class CourseOrder extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'course_order';

    public static function getCourseOrderList($params)
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $courseOrderList = self::with(['course' => function($query){
                $query->with(['teacher' => function($query){
                    $query->field('id,teacher_name');
                }])->field('id,title,teacher_id,desc_image');
            }])
            ->where('uid',$GLOBALS['uid'])
            ->where('channel_id',$channelInfo['channel_id'])
            ->where('learn_course_id','>',0)
            ->field('id,learn_course_id,total_amount,pay_time,pay_status')
            ->order('id desc')
            ->paginate(10)
            ->toArray();
        return $courseOrderList;
    }

    public static function getCourseOrderDetail($params)
    {
        extract($params);
        $courseOrderInfo = self::with(['course' => function($query){
            $query->with(['teacher' => function($query){
                $query->field('id,teacher_name');
            }])->field('id,title,teacher_id,desc_image');
        }])
            ->where('id',$order_id)
            ->field('id,merchant_id,learn_course_id,total_amount,order_sn,pay_time,pay_status,create_time')
            ->find();
        if(!empty($courseOrderInfo)) {
            $courseOrderInfo['pay_time'] = !empty($courseOrderInfo['pay_time']) ? date('Y-m-d H:i:s',$courseOrderInfo['pay_time']) : '';
            $courseOrderInfo['is_jump_miniprogram'] = Merchant::where('id', $courseOrderInfo['merchant_id'])->value('is_jump_miniprogram');
        }
        return $courseOrderInfo;
    }

    public static function getCourseOrderRefund($params)
    {
        extract($params);
        $courseOrderInfo = self::where('id',$order_id)
            ->field('id,learn_course_id')
            ->find();
        LearnFeedback::create([
            'uid' => $GLOBALS['uid'],
            'course_id' => $courseOrderInfo['learn_course_id'],
            'order_id' => $order_id,
            'refund_amount' => $refund_amount,
            'content' => $content,
            'feedback_image' => $feedback_image,
            'feedback_phone' => $feedback_phone,
            'feedback_type' => 2
        ]);
        return [];
    }

    public function course()
    {
        return $this->belongsTo('app\model\api\learn\LearnCourse','learn_course_id','id')->removeOption('soft_delete');
    }


}
