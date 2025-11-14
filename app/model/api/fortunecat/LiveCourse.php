<?php

namespace app\model\api\fortunecat;

use app\lib\api\other\CommonCourse;
use app\lib\api\service\MerchantServiceJob;
use app\lib\api\service\PartMerchantService;
use app\model\api\UserList;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use laytp\BaseModel;
use app\model\api\fortunecat\PartClass;
use app\model\api\fortunecat\TrainCourse;
use app\model\api\Channel;
use think\facade\Config;
use think\model\concern\SoftDelete;
use app\lib\api\other\UserCity;

class LiveCourse extends BaseModel
{
    use SoftDelete;
    protected $name = 'course';

    protected $hidden = [
        'merchant_id',
        'live_start_time',
        'live_end_time',
        'tag_ids'
    ];

    protected $append = [
        'live_status',
        'live_time_between'
    ];

    /**
     * 培训直播课程
     */
    public static function getLiveCourseList($params = [])
    {
        extract($params);
        $partClassId = isset($part_class_id) && !empty($part_class_id) ? $part_class_id : 0;
        $channel = isset($channel) && !empty($channel) ? $channel : UserList::where('id', $GLOBALS['uid'])->value('channel');
        $channelInfo = Channel::getChannelAppClass($channel);
        $data = [];
        $limit_num = 10;
        $recommendCourse = [];
        if($channel) {
            $where = " status = 1";
            $where .= " and course_type = 3";
            //$where .= " and app_class_id = {$channelInfo['app_class_id']}";
            $where .= " and find_in_set({$channelInfo['channel_id']},channel_ids)";
            if(!empty($partClassId)){
                $where .= " and find_in_set({$partClassId}, part_class_ids)";
            }
            $merchantData = MerchantServiceJob::getMerchantIsPayCount($channelInfo);
            if($merchantData['outsideMerchantCount'] > 0){
                if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
                    $where .= " and entry_fee > 0";
                }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
                    $where .= " and entry_fee = 0";
                }
            }else{
                if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
                    $where .= " and entry_fee > 0";
                }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
                    $where .= " and entry_fee = 0";
                }
            }

            $courseModel =  new self();
            $bannerLiveCourse  = $courseModel->where($where)
                ->field('id,title,merchant_id,video_cover_image,entry_fee,virtual_apply_nums,live_btn_desc,live_start_time,live_end_time')
                ->order(['sort'=>'desc','id'=>'desc'])
                ->find();

            //  if(!empty($bannerLiveCourse)){
            $whereCon = " status = 1";
            $whereCon .= " and course_type = 2";
            //$whereCon .= " and app_class_id = {$channelInfo['app_class_id']}";
            $whereCon .= " and find_in_set({$channelInfo['channel_id']},channel_ids)";
            if(!empty($partClassId)) {
                $whereCon .= " and find_in_set({$partClassId}, part_class_ids)";
            }
            if($merchantData['outsideMerchantCount'] > 0){
                if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
                    $whereCon .= " and entry_fee > 0";
                }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
                    $whereCon .= " and entry_fee = 0";
                }
            }else{
                if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
                    $whereCon .= " and entry_fee > 0";
                }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
                    $whereCon .= " and entry_fee = 0";
                }
            }
            $recommendCourse = $courseModel->with(['merchant' => function ($query) {
                $query->field('id,is_switch,company_name');
            },
                'courseTag' => function($query){
                    $query->field('id,tag_name,tag_color');
                }
            ])
                ->where($whereCon)
                ->field('id,title,sort,merchant_id,video_cover_image,entry_fee,virtual_apply_nums,tag_ids,live_start_time,live_end_time')
                ->order(['sort' => 'desc', 'id' => 'desc'])
                ->paginate($limit_num)
                ->toArray();
            //   }
            if(!empty($bannerLiveCourse)){
                $bannerLiveCourse = $bannerLiveCourse->toArray();
                $bannerLiveCourse['merchant'] = ['id' => 0,'company_name' => ''];
                $data['banner_live_course'] = $bannerLiveCourse;
                // $data['recommend_course_list'] = [];
            }
            if(isset($recommendCourse['data']) && !empty($recommendCourse['data'])){
                foreach($recommendCourse['data'] as &$val){
                    $isSwitch[] = isset($val['merchant']['is_switch']) ? $val['merchant']['is_switch'] : 0;
                    $val['courseTag'] = $val['courseTag'] ?? new \stdClass();
                    if ($val['entry_fee'] > 0 &&  UserCity::checkCity($channel)) {
                        $val['entry_fee'] = '0.00'; 
                    }
                }
                //array_multisort($isSwitch, SORT_DESC,array_column($recommendCourse['data'], 'sort'),SORT_DESC,array_column($recommendCourse['data'], 'id'),SORT_DESC, $recommendCourse['data']);
                $data['recommend_course_list'] = $recommendCourse['data'];
            } else {
                $data['recommend_course_list'] = [];
            }
        }
        return !empty($data) ? $data : new \stdClass();
    }

    /**
     * 直播课程类目
     */
    public static function getPartClassList($params = [])
    {
        extract($params);
        $partClassIds = [];
        $channelInfo = Channel::getChannelAppClass($channel);
        $courseList = Course::where('status',1)
            ->where('course_type',3)
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            ->field('id,part_class_ids')
            ->select();
        if(!empty($courseList)){
            foreach($courseList as $item){
                $coursePartClassIds = !empty($item['part_class_ids']) ? explode(',',$item['part_class_ids']) : [];
                if(!empty($coursePartClassIds)){
                    foreach($coursePartClassIds as $pcid){
                        $partClassIds[] = $pcid;
                    }
                }
            }
        }
        $partClassList = PartClass::field('id,part_class_name')
            ->whereIn('id',$partClassIds)
            ->where('class_type',2)
            ->order('id asc')
            ->select()
            ->toArray();
        return $partClassList;
    }

    /**
     * 直播课程列表
     */
    public static function getMoreLiveCourseList($params = [])
    {
        extract($params);
        $partClassId = isset($part_class_id) && !empty($part_class_id) ? $part_class_id : 0;
        $channel = isset($channel) && !empty($channel) ? $channel : '';
        $channelInfo = Channel::getChannelAppClass($channel);
        $data = [];
        $limit_num = 10;
        if($channel) {
            $where = " status = 1";
            $where .= " and course_type = 3";
            //$where .= " and app_class_id = {$channelInfo['app_class_id']}";
            $where .= " and find_in_set({$channelInfo['channel_id']},channel_ids)";
            if(!empty($partClassId)) {
                $where .= " and find_in_set({$partClassId}, part_class_ids)";
            }
            $merchantData = MerchantServiceJob::getMerchantIsPayCount($channelInfo);
            if($merchantData['outsideMerchantCount'] > 0){
                if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
                    $where .= " and entry_fee > 0";
                }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
                    $where .= " and entry_fee = 0";
                }
            }else{
                if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
                    $where .= " and entry_fee > 0";
                }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
                    $where .= " and entry_fee = 0";
                }
            }

            $courseModel =  new self();
            $liveCourse = $courseModel->with(['merchant' => function ($query) {
                $query->field('id,is_switch,company_name');
            }])
                ->where($where)
                ->field('id,title,sort,merchant_id,video_cover_image,entry_fee,virtual_apply_nums,live_btn_desc,live_start_time,live_end_time')
                ->order(['sort' => 'desc', 'id' => 'desc'])
                ->paginate($limit_num)
                ->toArray();

            if(isset($liveCourse['data']) && !empty($liveCourse['data'])){
                foreach($liveCourse['data'] as $val){
                    $isSwitch[] = isset($val['merchant']['is_switch']) ? $val['merchant']['is_switch'] : 0;
                }
                //array_multisort($isSwitch, SORT_DESC,array_column($liveCourse['data'], 'sort'),SORT_DESC,array_column($liveCourse['data'], 'id'),SORT_DESC, $liveCourse['data']);
                $data = $liveCourse['data'];
            }
        }
        return $data;
    }

    /**
     * 直播课程详情
     */
    public static function getLiveCourseDetail($params = [])
    {
        extract($params);
        $course_id = isset($course_id) && !empty($course_id) ? $course_id : 0;
        $channel = isset($channel) && !empty($channel) ? $channel : '';
        $data = [];
        $userInfo = UserList::find($GLOBALS['uid']);
        /*$channelInfo= Channel::getChannelAppClass($userInfo->channel);
        $partMerchantService = new PartMerchantService();
        $applyMerchant = $partMerchantService->getPartMerchantId($course_id,$channelInfo);*/
        if($course_id && $channel){
            $courseInfo = self::where('id',$course_id)
                ->field('id,title,merchant_id,video_cover_image,video_url,virtual_apply_nums,entry_fee,original_price,content,live_btn_desc,course_ids,live_start_time,live_end_time,part_course_bottom_desc')
                ->find();
//            $merchantService = new MerchantServiceJob();
//            $merchantList = $merchantService::getMerchantIsPayCount($channelInfo);
//            $merchantId = $merchantService::sortMerchantList($merchantList,$channelInfo,$courseInfo->entry_fee);
            //  $merchantInfo = Merchant::where('id',$applyMerchant['merchant_id'])->field('id,is_switch,merchant_logo,company_name,is_jump_miniprogram')->find();
            //   $courseInfo['merchant'] = !empty($merchantInfo) ? $merchantInfo->toArray() : [];
            //   $goodCourseId = Course::where('merchant_id',$applyMerchant['merchant_id'])->where('course_type',0)->value('id');
            if(!empty($courseInfo)){
                $course = CommonCourse::getCommonCourseToCourseId($course_id, isset($channel) && !empty($channel) ? $channel : \app\model\api\fortunecat\UserList::where('id', $GLOBALS['uid'])->value('channel'));
                $courseInfo['part_course_id'] = $course_id;
                $merchantInfo = Merchant::where('id',$course['merchant_id'])->field('id,is_switch,merchant_logo,company_name,is_jump_miniprogram')->find();
                $merchantInfo['is_jump_miniprogram'] = $course['is_jump_miniprogram'];
                $courseInfo['merchant'] = !empty($merchantInfo) ? $merchantInfo->toArray() : [];
                //$courseInfo['merchant'] = $merchantInfo;
                $courseInfo['is_apply'] = $course['is_apply'];//$applyMerchant['is_apply'] ?? 0;
                $courseInfo['part_course_bottom_desc'] = $courseInfo['part_course_bottom_desc'] ?? '';
                $courseInfo['preferential_amount'] = $courseInfo['original_price'] > $courseInfo['entry_fee'] ? $courseInfo['original_price'] - $courseInfo['entry_fee'] : '0.00';
                if($courseInfo['live_start_time'] > time()){
                    $day = floor(($courseInfo['live_start_time'] - time())/86400);
                    $hour = floor(($courseInfo['live_start_time'] - time())%86400/3600);
                    $minute = floor(($courseInfo['live_start_time'] - time())%86400/60/60);
                    $second = floor(($courseInfo['live_start_time'] - time())%86400%60);
                    $courseInfo['live_status_desc'] = '距离开播时间：'.$day.'天'.$hour.'时'.$minute.'分'.$second.'秒';
                }
                if($courseInfo['live_start_time'] < time() && $courseInfo['live_end_time']){
                    $courseInfo['live_status_desc'] = '直播已开始，请添加老师微信把你添加到直播间中';
                }
                if($courseInfo['live_end_time'] < time()){
                    $courseInfo['live_status_desc'] = '直播已结束，如想看回放请添加老师微信';
                }
                /*$threadCount = Thread::where('course_id|part_course_id',$course_id)->where('uid',$GLOBALS['uid'])->count();
                if($threadCount > 0){
                    $courseInfo['is_apply'] = 1;
                    //$courseInfo['btn_desc'] = self::getLiveBtnDesc($courseInfo);
                }*/
                $courseInfo['is_under_eighteen_apply'] = self::getIsUnderEighteenApply($channel);
                $courseIds = explode(',',$courseInfo['course_ids']);
                $partCourse = TrainCourse::with(['merchant' => function($query){
                    $query->field('id,company_name');
                }])
                    ->whereIn('id',$courseIds)
                    ->field('id,title,merchant_id,compensation,compensation_type,btn_desc,tag_ids')
                    ->select();
                $courseInfo['id'] = $course['course_id'];
                $courseInfo['merchant_id'] = $course['merchant_id'];
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

    public static function getIsUnderEighteenApply($channel)
    {
        $isUnderEighteenApply = 1;
        if (!empty($channel)) {
            $isUnderEighteenApply = Channel::where('channel_name', $channel)->value('is_under_eighteen_apply');
        }
        return $isUnderEighteenApply ?? 0;
    }

    public static function getContentAttr($value, $data)
    {
        return richText($value);
    }

    public function getLiveStatusAttr($value, $data)
    {
        $liveStatus = '';
        if(!empty($data['live_start_time']) && !empty($data['live_start_time'])) {
            if ($data['live_start_time'] > time()) {
                $liveStatus = '待直播';
            }
            if ($data['live_start_time'] < time() && $data['live_end_time'] > time()) {
                $liveStatus = '直播中';
            }
            if ($data['live_end_time'] < time()) {
                $liveStatus = '已结束';
            }
        }
        return $liveStatus;
    }

    public function getLiveBtnDescAttr($value, $data)
    {
        $liveBtnDesc = '';
        if(!empty($data['live_start_time']) && !empty($data['live_end_time'])) {
            $liveBtnDescArr = json_decode($data['live_btn_desc'], true);
            $liveBtnDescArr = array_column($liveBtnDescArr, 'btn_desc', 'btn_status');
            if ($data['live_start_time'] > time()) {
                $liveBtnDesc = $liveBtnDescArr['wait_live'];
            }
            if ($data['live_start_time'] < time() && $data['live_end_time'] > time()) {
                $liveBtnDesc = $liveBtnDescArr['in_live'];
            }
            if ($data['live_end_time'] < time()) {
                $liveBtnDesc = $liveBtnDescArr['finish_live'];
            }
        }
        return $liveBtnDesc;
    }

    public function getLiveTimeBetweenAttr($value, $data)
    {
        $liveTimeBetween = '';
        if(!empty($data['live_start_time']) && !empty($data['live_start_time'])) {
            $liveStartTime = date('m/d H:i', $data['live_start_time']);
            if(date('m/d',$data['live_start_time']) == date('m/d',$data['live_end_time'])){
                $liveEndTime = date('H:i', $data['live_end_time']);
            }else {
                $liveEndTime = date('m/d H:i', $data['live_end_time']);
            }
            $liveTimeBetween = $liveStartTime . ' ~ ' . $liveEndTime;
        }
        return $liveTimeBetween;
    }

    public function merchant()
    {
        return $this->belongsTo('app\model\api\fortunecat\Merchant','merchant_id','id')->removeOption('soft_delete');
    }

    public function courseTag()
    {
        return $this->belongsTo('app\model\admin\part\PartCourseTag','tag_ids','id')->removeOption('soft_delete');
    }

}