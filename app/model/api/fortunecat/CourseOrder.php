<?php

namespace app\model\api\fortunecat;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class CourseOrder extends BaseModel
{
    use SoftDelete;

    protected $name = 'course_order';

    public static function getCourseOrderList($params = [])
    {
        extract($params);
        $payStatus = isset($pay_status) && !empty($pay_status) ? $pay_status : 0;
        $channel = isset($channel) && !empty($channel) ? $channel : '';
        $appBundleId = isset($app_bundle_id) && !empty($app_bundle_id) ? $app_bundle_id : '';
        $page_num = 10;
        $courseOrder = [];
        $where = " uid = ".$GLOBALS['uid'];
        if($payStatus){
            $where .= " and pay_status = ".$payStatus;
        }
        if($channel && $appBundleId){
            $courseOrder = self::with(['course' => function($query){
                    $query->field('id,title,video_cover_image');
                }])
                ->where($where)
                ->where('channel',$channel)
                ->where('app_bundle_id',$appBundleId)
                ->field('id,course_id,order_sn,total_amount,pay_status')
                ->order('id','desc')
                ->paginate($page_num)
                ->toArray();
            if(isset($courseOrder['data'])){
                $courseOrder = $courseOrder['data'];
            }
        }
        return $courseOrder;
    }

    public static function getCourseOrderDetail($params = [])
    {
        extract($params);
        $courseOrderId = isset($course_order_id) && !empty($course_order_id) ? $course_order_id : 0;
        $courseOrderInfo = CourseOrder::with(['course' => function($query){
                $query->field('id,title,video_cover_image');
            }])
            ->where('id',$courseOrderId)
            ->field('id,course_id,order_sn,total_amount,original_price,pay_type,pay_status,pay_time')
            ->find();
        if(!empty($courseOrderInfo)){
            $courseOrderInfo['preferential_amount'] = $courseOrderInfo['original_price'] > $courseOrderInfo['total_amount'] ? $courseOrderInfo['original_price'] - $courseOrderInfo['total_amount'] : '0.00';
            $courseOrderInfo['pay_time'] = !empty($courseOrderInfo['pay_time']) ? date('Y年m月d日 H:i:s',$courseOrderInfo['pay_time']) : '';
        }
        return $courseOrderInfo;
    }

    public function getPayTypeAttr($value, $data)
    {
        switch($value){
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
        return $this->belongsTo('app\model\api\fortunecat\Course','course_id','id')->removeOption('soft_delete');
    }
}