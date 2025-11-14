<?php

namespace app\model\api\fortunecat;

use laytp\BaseModel;
use app\model\api\fortunecat\PartClass;
use app\model\api\fortunecat\Thread;
use app\model\api\fortunecat\UserList;
use app\model\api\fortunecat\PartCourseTag;
use app\model\api\fortunecat\Collect;
use app\model\api\Channel;
use app\lib\api\service\PartMerchantService;
use app\lib\api\other\CommonCourse;
use app\lib\api\other\UserCity;
use app\model\api\Thread as ThreadModel;

class Course extends BaseModel
{
    protected $hidden = [
        'merchant_id',
        'course_ids',
        //'part_class_ids',
        'tag_ids'
    ];

    protected $append = [
        'part_course_tag_names',
    ];

    public static function getPartCourseDetail($params = [])
    {
        extract($params);
        $course_id = isset($course_id) && !empty($course_id) ? $course_id : 0;
        $channel = isset($channel) && !empty($channel) ? $channel : UserList::where('id', $GLOBALS['uid'])->value('channel');
        $channelInfo = Channel::getChannelAppClass($channel);
        /*$partMerchantService = new PartMerchantService();
        $applyMerchant = $partMerchantService->getPartMerchantId($course_id,$channelInfo);*/
        if($course_id && $channel){
            $courseInfo = self::where('id',$course_id)
                ->where('course_type',1)
                ->field('id,merchant_id,btn_desc,title,entry_fee,is_allow_apply,video_url,compensation,part_class_ids,tag_ids,course_ids,virtual_apply_nums,content,share_desc,share_image,flow_desc,confirm_copy_desc,compensation_type,confirm_btn_desc,part_course_bottom_desc,is_allow_apply')
                ->find();

            if(!empty($courseInfo)){
                $course = CommonCourse::getCommonCourseToCourseId($course_id, $channel);
                $courseInfo['part_course_id'] = $course_id;
                $courseInfo['is_apply'] = $course['is_apply'];
                $courseInfo['is_collect'] = 0;
                $courseInfo['user_avatar_list'] = [];
                $courseInfo['part_course_tag_names'] = [];
                $courseInfo['user_avatar_list'] = [];
                $courseInfo['course_job_training_list'] = [];
                $courseInfo['course_recommend_list'] = [];
                $courseInfo['part_course_bottom_desc'] = $courseInfo['part_course_bottom_desc'] ?? '';
                $courseInfo['confirm_copy_desc'] = !empty($courseInfo['confirm_copy_desc']) ? json_decode($courseInfo['confirm_copy_desc'],true) : [];
                $courseInfo['flow_desc'] = !empty($courseInfo['flow_desc']) ? json_decode($courseInfo['flow_desc'],true) : [];
                $merchantInfo = Merchant::where('id',$course['merchant_id'])->field('id,is_switch,merchant_logo,company_name,is_jump_miniprogram')->find();
                $merchantInfo = $merchantInfo->toArray();
               // var_dump($merchantInfo);
                //var_dump($course);
                $merchantInfo['is_jump_miniprogram'] = $course['is_jump_miniprogram'];
              //  var_dump($merchantInfo);
                //$courseInfo['merchant'] = !empty($merchantInfo) ? $merchantInfo->toArray() : [];
                $courseInfo['merchant'] = $merchantInfo;

                $courseInfo['is_apply'] = $course['is_apply'];
                if ($courseInfo['is_allow_apply'] === 0) {
                    $courseInfo['is_apply'] = 0;
                    $course['is_apply'] = 0;
                    
                }
                if ($course['is_apply']) {
                    $courseInfo['btn_desc'] = $course['is_jump_miniprogram'] == 1 ? '立即添加微信' : '在线沟通';
                }
                /*if($threadCount > 0){
                    $courseInfo['is_apply'] = 1;
                    $courseInfo['btn_desc'] = isset($courseInfo->merchant->is_jump_miniprogram) && $courseInfo->merchant->is_jump_miniprogram == 1 ? '加微信' : '';
                }*/
                $courseInfo['course'] = $course;
                $collect = Collect::where('uid',$GLOBALS['uid'])->where('course_id',$course_id)->count();
                if($collect > 0){
                    $courseInfo['is_collect'] = 1;
                }
                $courseInfo['part_course_tag_names'] = PartCourseTag::getPartCourseTagNames($courseInfo['tag_ids']);
                $threadUids = Thread::where('course_id',$course_id)->group('uid')->limit(10)->column('uid');
                $courseInfo['user_avatar_list'] = UserList::whereIn('id',$threadUids)->where('avatar','<>','')->column('avatar');
                $courseIds = explode(',',$courseInfo['course_ids']);
                $courseInfo['course_job_training_list'] = self::whereIn('id',$courseIds)
                    ->field('id,title,video_cover_image,virtual_apply_nums,entry_fee')
                    ->select()
                    ->toArray();
                $courseInfo['id'] = $course['course_id'];
                $courseInfo['merchant_id'] = $course['merchant_id'];
                $courseInfo['is_under_eighteen_apply'] = self::getIsUnderEighteenApply($channel);
                $courseInfo['customer_link'] = $course['is_apply'] ? ThreadModel::getCustomerLink(['course_id' => $course['course_id']])['customer_link'] : '';

                $courseRecommendList = self::with(['merchant' => function($query){
                    $query->field('id,company_name');
                }])
                    ->where('course_type',1)
                    ->whereFindInSet('channel_ids', $channelInfo['channel_id'])
                    ->where('id', '<>', $course_id)
                    ->where('status', 1)
                    ->where('entry_fee', $courseInfo['entry_fee'] > 0 ? '>' : '=', 0)
                    ->field('id,title,merchant_id,compensation,tag_ids,btn_desc,compensation_type')
                    ->orderRaw('rand()')->limit(3)
                    ->select()
                    ->toArray();
                $courseInfo['course_recommend_list'] = !empty($courseRecommendList) ? $courseRecommendList : [];
                if ($courseInfo['is_allow_apply'] === 0 || ($courseInfo['entry_fee'] > 0 && UserCity::checkCity($channel))) {
                    $courseInfo['entry_fee'] = '0.00';
                }
            }
            //dump($courseInfo->toArray());
            return $courseInfo;
        }
    }

    public static function getContentAttr($value, $data)
    {
        return !empty($value) ? richText($value) : '';
    }

    public static function getIsUnderEighteenApply($channel)
    {
        $isUnderEighteenApply = 1;
        if (!empty($channel)) {
            $isUnderEighteenApply = Channel::where('channel_name', $channel)->value('is_under_eighteen_apply');
        }
        return $isUnderEighteenApply ?? 0;

    }

    public function getPartCourseTagNamesAttr($value, $data)
    {

        $partTagIds = !empty($data['tag_ids']) ? explode(',',$data['tag_ids']) : [];
        $partTagNames = PartCourseTag::whereIn('id',$partTagIds)->column('tag_name');
        return $partTagNames;
    }

    public function getCompensationAttr($value, $data)
    {
        switch($data['compensation_type']){
            case 1;
                $unit = '日';
                break;
            case 2;
                $unit = '次';
                break;
            case 3;
                $unit = '个';
                break;
            case 4;
                $unit = '张';
                break;
            case 5;
                $unit = '小时';
                break;
            case 6;
                $unit = '其他';
                break;
            default:
                $unit = '日';
        }
        return !empty($value) ? '￥'.$value.'元/'.$unit : '';
    }

//    public function getEntryFeeAttr($value, $data)
//    {
//        return !empty($value) && $value > 0 ? $value : '免费';
//    }

    public function merchant()
    {
        return $this->belongsTo('app\model\api\fortunecat\Merchant','merchant_id','id')->removeOption('soft_delete');
    }

    public function courseTag()
    {
        return $this->belongsTo('app\model\api\fortunecat\PartCourseTag','tag_ids','id')->removeOption('soft_delete');
    }

}