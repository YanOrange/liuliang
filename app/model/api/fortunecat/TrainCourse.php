<?php

namespace app\model\api\fortunecat;

use app\lib\api\other\CommonCourse;
use app\lib\api\service\MerchantServiceJob;
use app\lib\api\service\PartMerchantService;
use app\model\api\Channel;
use laytp\BaseModel;
use think\facade\Config;
use app\lib\api\other\UserCity;

class TrainCourse extends BaseModel
{
    protected $name = 'course';

    protected $hidden = [
        'merchant_id'
    ];

    /**
     * 培训课程详情
     */
    public static function getTrainCourseDetail($params = [])
    {
        extract($params);
        $course_id = isset($course_id) && !empty($course_id) ? $course_id : 0;
        $channel = isset($channel) && !empty($channel) ? $channel : UserList::where('id', $GLOBALS['uid'])->value('channel');
        $data = [];
       // $userInfo = UserList::find($GLOBALS['uid']);
        $channelInfo= Channel::getChannelAppClass($channel);
        //$partMerchantService = new PartMerchantService();
      //  $applyMerchant = $partMerchantService->getPartMerchantId($course_id,$channelInfo);
        if($course_id && $channel){
            $courseInfo = self::where('id',$course_id)
                ->field('id,title,merchant_id,video_cover_image,video_url,virtual_apply_nums,entry_fee,original_price,content,btn_desc,course_ids,part_course_bottom_desc')
                ->find();
//            $merchantService = new MerchantServiceJob();
//            $merchantList = $merchantService::getMerchantIsPayCount($channelInfo);
//            $merchantId = $merchantService::sortMerchantList($merchantList,$channelInfo,$courseInfo->entry_fee);

          //  $merchantInfo = Merchant::where('id',$applyMerchant['merchant_id'])->field('id,is_switch,merchant_logo,company_name,is_jump_miniprogram')->find();
         //   $courseInfo['merchant'] = !empty($merchantInfo) ? $merchantInfo->toArray() : [];
         //   $goodCourseId = Course::where('merchant_id',$applyMerchant['merchant_id'])->where('course_type',0)->value('id');
            if(!empty($courseInfo)){
                $course = CommonCourse::getCommonCourseToCourseId($course_id, isset($channel) && !empty($channel) ? $channel : UserList::where('id', $GLOBALS['uid'])->value('channel'));
                $merchantInfo = Merchant::where('id',$course['merchant_id'])->field('id,is_switch,merchant_logo,company_name,is_jump_miniprogram')->find();
                $merchantInfo = $merchantInfo->toArray();
                $merchantInfo['is_jump_miniprogram'] = $course['is_jump_miniprogram'];
                //$courseInfo['merchant'] = !empty($merchantInfo) ? $merchantInfo->toArray() : [];
                $courseInfo['merchant'] = $merchantInfo;
                $courseInfo['part_course_id'] = $course_id;
                $thread = Thread::whereDay('create_time')->where('uid',$GLOBALS['uid'])->field('id')->order('id desc')->find();
                $courseInfo['thread_id'] = !empty($thread) ? $thread->id : 0;
              //  $courseInfo['is_apply'] = $applyMerchant['is_apply'] ?? 0;
                $courseInfo['section'] = rand(1,15);
                $courseInfo['is_apply'] = !empty($thread) ? 1 : 0;
                if (!empty($thread)) {
                    $courseInfo['btn_desc'] = '开始学习';
                }
               // $courseInfo['is_apply'] = $course['is_apply'];

                /*$threadCount = Thread::where('course_id|part_course_id',$course_id)->where('uid',$GLOBALS['uid'])->count();
                if($threadCount > 0){
                    $courseInfo['is_apply'] = 1;
                    $courseInfo['btn_desc'] = '开始学习';
                }*/
                $courseInfo['part_course_bottom_desc'] = $courseInfo['part_course_bottom_desc'] ?? '';
                $courseInfo['is_under_eighteen_apply'] = self::getIsUnderEighteenApply($channel);
                $courseIds = explode(',',$courseInfo['course_ids']);
                $partCourse = self::with(['merchant' => function($query){
                    $query->field('id,company_name');
                }])
                    ->whereIn('id',$courseIds)
                    ->field('id,title,merchant_id,compensation,compensation_type,btn_desc,tag_ids')
                    ->select();
                $courseInfo['id'] = $course['course_id'];
                $courseInfo['merchant_id'] = $course['merchant_id'];
                if ($courseInfo['entry_fee'] > 0 && UserCity::checkCity($channel)) {
                    $courseInfo['entry_fee'] = '0.00';
                }
                $data['courseInfo'] = $courseInfo;
                $data['course'] = $course;
                if(!empty($partCourse)){
                    foreach($partCourse as &$val){
                        $partTagIds = !empty($val['tag_ids']) ? explode(',',$val['tag_ids']) : [];
                        $val['part_tag_names'] = PartCourseTag::whereIn('id',$partTagIds)->column('tag_name');
                    }
                    $data['partCourse'] = $partCourse;
                }
                
            }
        }
        return $data;
    }

    public static function getTrainCourseDetailV2($params = [])
    {
        extract($params);
        $course_id = isset($course_id) && !empty($course_id) ? $course_id : 0;
        $channel = isset($channel) && !empty($channel) ? $channel : UserList::where('id', $GLOBALS['uid'])->value('channel');
        $data = [];
        if($course_id && $channel){
            $courseInfo = self::where('id',$course_id)
                ->field('id,title,video_cover_image,video_url,virtual_apply_nums,entry_fee,original_price,content,btn_desc,course_ids,part_course_bottom_desc')
                ->find();
            if(!empty($courseInfo)){
                $course = CommonCourse::getCommonCourseToCourseId($course_id, isset($channel) && !empty($channel) ? $channel : UserList::where('id', $GLOBALS['uid'])->value('channel'));
                $merchantInfo = Merchant::where('id',$course['merchant_id'])->field('id,is_switch,merchant_logo,company_name,is_jump_miniprogram')->find();
                $merchantInfo = $merchantInfo->toArray();
                $merchantInfo['is_jump_miniprogram'] = $course['is_jump_miniprogram'];
                $courseInfo['merchant'] = $merchantInfo;
                $courseInfo['part_course_id'] = $course_id;
                $thread = Thread::whereDay('create_time')->where('uid',$GLOBALS['uid'])->field('id')->order('id desc')->find();
                $courseInfo['thread_id'] = !empty($thread) ? $thread->id : 0;
                $courseInfo['section'] = rand(1,15);
                $courseInfo['is_apply'] = !empty($thread) ? 1 : 0;
                if (!empty($thread)) {
                    $courseInfo['btn_desc'] = '开始学习';
                }
                $courseInfo['part_course_bottom_desc'] = $courseInfo['part_course_bottom_desc'] ?? '';
                $courseInfo['is_under_eighteen_apply'] = self::getIsUnderEighteenApply($channel);
            }
        }
        return $courseInfo ?? [];
    }

    public static function getContentAttr($value, $data)
    {
        return richText($value);

    }

    public static function getIsUnderEighteenApply($channel)
    {
        $isUnderEighteenApply = 1;
        if (!empty($channel)) {
            $isUnderEighteenApply = Channel::where('channel_name', $channel)->value('is_under_eighteen_apply');
        }
        return $isUnderEighteenApply ?? 0;
    }

    public function getCompensationAttr($value, $data)
    {
        $manyorganization = Config::load('extra/manyorganization','extra');
        $manyorganization = array_column($manyorganization['compensation_type_list'],'name','value');
        $compensation = isset($manyorganization[$data['compensation_type']]) && !empty($manyorganization[$data['compensation_type']]) ? '￥'.$value.'元/'.$manyorganization[$data['compensation_type']] : '￥'.$value.'元/其他';
        return $compensation;
    }

    public function merchant()
    {
        return $this->belongsTo('app\model\api\fortunecat\Merchant','merchant_id','id')->removeOption('soft_delete');
    }

}