<?php

namespace app\model\api\single;

use app\model\api\Channel;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class MyCourseOrder extends BaseModel
{
    use SoftDelete;

    protected $name = 'course_order';

//    protected $hidden = [
//        'course',
//        'singleCourse'
//    ];

    public static function getCourseOrderList($params = [])
    {
        extract($params);
        $payStatus = isset($pay_status) && !empty($pay_status) ? $pay_status : 0;
        $channel = isset($channel) && !empty($channel) ? $channel : '';
        $channelInfo = Channel::getChannelAppClass($channel);
        $page_num = 10;
        $courseOrder = [];
        $where[] = ['uid','=',$GLOBALS['uid']];
        if($payStatus){
            $where[] = ['pay_status','=',$payStatus];
        }
        if($channel){
            $courseOrder = self::with(['course' => function($query){
                    $query->field('id,title,video_cover_image');
                },'singleCourse' => function($query){
                    $query->field('id,title,video_cover_image');
                }])
                ->where($where)
                ->where('channel',$channel)
                ->where('app_id',$channelInfo['app_id'])
                ->field('id,course_id,order_sn,total_amount,pay_status')
                ->order('id','desc')
                ->paginate($page_num)
                ->toArray();
            if(isset($courseOrder['data'])){
                $courseOrder = $courseOrder['data'];
                if(!empty($courseOrder)){
                    foreach($courseOrder as $key=>&$val){
                        $val['title'] = '';
                        $val['video_cover_image'] = '';
                        if(isset($val['course']) && !empty($val['course'])){
                            $val['title'] = isset($val['course']['title']) ? $val['course']['title'] : '';
                            $val['video_cover_image'] = isset($val['course']['video_cover_image']) ? $val['course']['video_cover_image'] : '';
                        }
                        if(isset($val['singleCourse']) && !empty($val['singleCourse'])){
                            $val['title'] = isset($val['singleCourse']['title']) ? $val['singleCourse']['title'] : '';
                            $val['video_cover_image'] = isset($val['singleCourse']['video_cover_image']) ? $val['singleCourse']['video_cover_image'] : '';
                        }
                        unset($courseOrder[$key]['course']);
                        unset($courseOrder[$key]['singleCourse']);
                    }
                }
            }
        }
        return $courseOrder;
    }

    public static function getCourseOrderDetail($params = [])
    {
        extract($params);
        $courseOrderId = isset($course_order_id) && !empty($course_order_id) ? $course_order_id : 0;
        $courseOrderInfo = CourseOrder::with(['course'])
            ->where('id',$courseOrderId)
            ->field('id,course_id,order_sn,total_amount,original_price,pay_type,pay_status,pay_time')
            ->find();
        if(!empty($courseOrderInfo)){
            $courseOrderInfo['preferential_amount'] = $courseOrderInfo['original_price'] > $courseOrderInfo['total_amount'] ? $courseOrderInfo['original_price'] - $courseOrderInfo['total_amount'] : '0.00';
            $courseOrderInfo['pay_time'] = !empty($courseOrderInfo['pay_time']) ? date('Y年m月d日 H:i:s',$courseOrderInfo['pay_time']) : '';
            $courseOrderInfo['pay_type'] = self::getPayType($courseOrderInfo);
        }
        return $courseOrderInfo;
    }

    public static function getPayType($data)
    {
        switch($data['pay_type']){
            case 'alipay':
                $payType = '支付宝';
                break;
            case 'wxpay':
                $payType = '微信';
                break;
            default:
                $payType = '未知';
        }
        return $payType;
    }

    public function course()
    {
        return $this->belongsTo('app\model\api\single\Course','course_id','id')
            //->bind(['title','video_cover_image'])
            ->removeOption('soft_delete');
    }

    public function singleCourse()
    {
        return $this->belongsTo('app\model\api\single\SingleCourse','course_id','id')
            //->bind(['title','video_cover_image'])
            ->removeOption('soft_delete');
    }
}