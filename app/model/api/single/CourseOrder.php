<?php
/**
 * 课程表模型
 */

namespace app\model\api\single;

use app\lib\api\service\MerchantService;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\lib\api\exception\ExceptionStd;
use app\model\api\single\Thread;
use app\model\api\single\SingleCourse;
use app\model\api\Channel;
use app\model\api\Merchant;
use app\model\api\UserList;
use app\model\api\PayConfig;
use app\lib\api\payapi\Alipay;
use app\lib\api\payapi\Wxpay;
use think\facade\Db;
use think\facade\Config;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;

class CourseOrder extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'course_order';

    protected $hidden = [
        'course'
    ];

    //付费报名
    public static function payApplyCourse($params = [])
    {
        extract($params);
        $courseType = isset($course_type) ? $course_type : 0;
        if(empty($courseType)){
            new ExceptionStd('报名参数错误');
        }
        if (Thread::checkApplyCourse($course_id)) {
            new ExceptionStd('该课程已报名过');
        }

        $userInfo = UserList::find($GLOBALS['uid']);
        if($courseType == 1) {
            $courseInfo = Course::find($course_id);
            if (empty($courseInfo) || $courseInfo->status == 0) {
                new ExceptionStd('该课程已结束报名');
            }
            $merchantInfo = Merchant::find($courseInfo->merchant_id);
        }
        if($courseType == 2) {
            $courseInfo = SingleCourse::find($course_id);
            if (empty($courseInfo) || $courseInfo->status == 0) {
                new ExceptionStd('该课程已结束报名');
            }
            $merchantId = (new MerchantService)->getMerchantServiceId($courseInfo->merchant_ids, $userInfo->age_range_id);
            $merchantInfo = Merchant::find($merchantId);
        }
        if ($courseInfo->entry_fee <= 0) {
            new ExceptionStd('该课程不需要支付');
        }
        if (empty($merchantInfo) || $merchantInfo->is_switch == 0) {
            new ExceptionStd('该课程已结束报名');
        }

        $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
        $ageRange = $gatherInfo['name'];
        $weightArr = isset($merchantInfo->age_range_weight_json) && !empty($merchantInfo->age_range_weight_json) ? json_decode($merchantInfo->age_range_weight_json, true) : [];
        $weight = isset($weightArr[$ageRange]) && !empty($weightArr[$ageRange]) ? $weightArr[$ageRange] : 0;
        if($weight <= 0){
            new Exception('你不满足报名要求');
        }

        $channelInfo = Channel::getChannelAppClass($userInfo->channel);
        if($channelInfo['is_many_organization'] == 3){
            $checkApplyMerchant = Thread::where('uid', $GLOBALS['uid'])->where('merchant_id', $merchantInfo->id)->where('is_many_organization',3)->count();
            if($checkApplyMerchant >= 3){
                new ExceptionStd('名额已满');
            }
        }
        $threadPriceInfo = Merchant::getMerchantThreadPrice($merchantInfo);
        Db::startTrans();
        try {
            $order_sn = create_order_sn();
            $gatherInfo = GatherUserInfoModel::getFormatGatherInfo($userInfo->age_range_id, 'age_range_id');
            $ageRange = $gatherInfo['name'];
            $courseOrderData = [
                'uid' => $GLOBALS['uid'],
                'merchant_id' => $merchantInfo->id,
                'course_id'  => $course_id,
                'order_sn' => $order_sn,
                'total_amount' => $courseInfo->entry_fee,
                'original_price' => $courseInfo->original_price ?? 0,
                'channel'      => $userInfo->channel,
                'app_bundle_id' => $app_bundle_id,
                'pay_type' => $pay_type,
                'ip' => request()->ip(),
                'thread_price' => $threadPriceInfo['thread_price'],
                'thread_price_type' => $threadPriceInfo['thread_price_type'],
                'age' => $ageRange,
                'channel_id' => $channelInfo['channel_id'],
                'app_id' => $channelInfo['app_id'],
                'app_class_id' => $channelInfo['app_class_id'],
                'landing_page_id' => $landing_page_id ?? 0
            ];
            $courseOrder = new self();
            $courseOrderInfo = $courseOrder::where('uid',$GLOBALS['uid'])
                ->where('course_id',$course_id)
                ->where('channel_id',$channelInfo['channel_id'])
                ->find();
            if(!empty($courseOrderInfo)){
                $courseOrderUpdateData = [
                    'order_sn' => $order_sn,
                    'total_amount' => $courseInfo->entry_fee,
                    'original_price' => $courseInfo->original_price ?? 0,
                    'pay_type' => $pay_type,
                    'ip' => request()->ip(),
                    'thread_price' => $threadPriceInfo['thread_price'],
                    'thread_price_type' => $threadPriceInfo['thread_price_type'],
                    'create_time' => time()
                ];
                $courseOrder->where('id',$courseOrderInfo->id)->save($courseOrderUpdateData);
            }else {
                $courseOrder->save($courseOrderData);
            }
            $orderParams = [
                'total_amount' => $courseInfo->entry_fee,
                'order_sn' => $order_sn,
                'desc' => '商品订单' . $order_sn,
                'app_bundle_id' => $app_bundle_id,
                'system_type' => $system_type ?? ''
            ];
            Db::commit();
            //return ['merchant' => self::getPayParams($pay_type, $orderParams)];
            return ['merchant_id' => $merchantInfo->id,'is_jump_miniprogram' => $merchantInfo->is_jump_miniprogram,'courseOrder' => self::getPayParams($pay_type, $orderParams)];
        } catch (\Exception $e) {
            Db::rollback();
            new ExceptionStd($e->getMessage());
        }
    }

    //获取支付参数
    public static function getPayParams($payType = null, $orderParams = [])
    {
        if ($payType == 'alipay') {
            $payParams = Alipay::aliAppPay($orderParams);
            return ['pay_params' => $payParams];
        }
        if ($payType == 'wxpay') {
            $payParams = Wxpay::wxAppPay($orderParams);
            return $payParams;
        }
    }

    public function course()
    {
        return $this->belongsTo('app\model\api\single\SingleCourse','course_id','id')
            ->bind(['title','video_cover_image'])
            ->removeOption('soft_delete');
    }
}
