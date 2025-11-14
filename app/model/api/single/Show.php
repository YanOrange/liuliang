<?php

namespace app\model\api\single;

use app\model\api\UserList;
use app\model\api\v2\GatherUserInfo;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\api\Channel;
use app\model\api\single\Merchant;

class Show extends BaseModel
{
    use SoftDelete;
    //表名
    protected $name = 'single_course';

    //精选课程
    public static function choiceCourse($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        //$merchantList = Merchant::getMerchantIds($channelInfo);
        $merchantData = Merchant::getMerchantIdsV2($channelInfo);
        $where[] = ['status','=',1];
        if($merchantData['outsideMerchantCount'] > 0){
            if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
                $where[] = ['entry_fee','>',0];
            }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
                $where[] = ['entry_fee','=',0];
            }
        }else{
            if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
                $where[] = ['entry_fee','>',0];
            }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
                $where[] = ['entry_fee','=',0];
            }
        }
        $courseList = [];
//        if(!empty($merchantList)){
//            foreach($merchantList as $val) {
//                $courseList[] = self::where($where)
//                    //->whereFindInSet('merchant_ids', $val['id'])
//                    ->whereFindInSet('course_type', 1)
//                    ->whereFindInSet('app_ids', $channelInfo['app_id'])
//                    ->field('id')
//                    ->select();
//            }
//        }
//        $courseIds = [];
//        foreach($courseList as $key=>$item){
//            if(empty($item)) {
//                unset($courseList[$key]);
//            }else{
//                foreach($item as $val){
//                    $courseIds[] = $val['id'];
//                }
//            }
//        }
        $courseList = self::with(['courseTag' => function ($query) {
                $query->field('id,tag_name,tag_color');
            }])
            ->where($where)
            ->whereFindInSet('course_type', 1)
            ->whereFindInSet('app_ids', $channelInfo['app_id'])
            //->whereIn('id',$courseIds)
            ->field('id,title,video_cover_image,virtual_apply_nums,entry_fee,tag_id,course_desc')
            ->order(['sort' => 'desc', 'id' => 'desc'])
            ->select();
        return $courseList;

    }

    //精选课程
    public static function recommendCourse($params = [])
    {
        extract($params);
        $sortId = isset($sort_id) && !empty($sort_id) ? $sort_id : 1;
        $channelInfo = Channel::getChannelAppClass($channel);
        //$merchantList = Merchant::getMerchantIds($channelInfo);
        $merchantData = Merchant::getMerchantIdsV2($channelInfo);
        $where[] = ['status','=',1];
        if($merchantData['outsideMerchantCount'] > 0){
            if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
                $where[] = ['entry_fee','>',0];
            }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
                $where[] = ['entry_fee','=',0];
            }
        }else{
            if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
                $where[] = ['entry_fee','>',0];
            }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
                $where[] = ['entry_fee','=',0];
            }
        }
        $order = ['sort' => 'desc', 'id' => 'desc'];
        if($sortId == 2) $order = 'virtual_apply_nums desc';
        if($sortId == 3) $order = 'evaluate_nums desc';
        if($sortId == 4) $where[] = ['entry_fee','<=',0];
        if($sortId == 5) $where[] = ['entry_fee','>',0];
        $courseList = [];
//        if(!empty($merchantList)){
//            foreach($merchantList as $val) {
//                $courseList[] = self::where($where)
//                    //->whereFindInSet('merchant_ids', $val['id'])
//                    ->whereFindInSet('course_type', 2)
//                    ->whereFindInSet('app_ids', $channelInfo['app_id'])
//                    ->field('id')
//                    ->order($order)
//                    ->select();
//            }
//        }
//        $courseIds = [];
//        foreach($courseList as $key=>$item){
//            if(empty($item)) {
//                unset($courseList[$key]);
//            }else{
//                foreach($item as $val){
//                    $courseIds[] = $val['id'];
//                }
//            }
//        }
        $courseList = self::where($where)
            ->whereFindInSet('course_type', 2)
            ->whereFindInSet('app_ids', $channelInfo['app_id'])
            //->whereIn('id',$courseIds)
            ->field('id,title,video_cover_image,virtual_apply_nums,entry_fee,course_desc')
            ->order($order)
            ->select();
        return $courseList;

    }

    //排序列表
    public static function getSortList($params = [])
    {
        extract($params);
        $channel = $channel ?? '';
        $channelInfo = Channel::getChannelAppClass($channel);
        $merchantData = Merchant::getMerchantIdsV2($channelInfo);
        if($merchantData['payMerchantNums'] > 0 && $merchantData['freeMerchantNums'] == 0){
            $data = [
                ['id' => 1,'name' => '综合'],
                ['id' => 2,'name' => '人气'],
                ['id' => 3,'name' => '好评'],
                ['id' => 5,'name' => '付费'],
            ];
        }else if($merchantData['freeMerchantNums'] > 0 && $merchantData['payMerchantNums'] == 0){
            $data = [
                ['id' => 1,'name' => '综合'],
                ['id' => 2,'name' => '人气'],
                ['id' => 3,'name' => '好评'],
                ['id' => 4,'name' => '免费'],
            ];
        }else{
            $data = [
                ['id' => 1,'name' => '综合'],
                ['id' => 2,'name' => '人气'],
                ['id' => 3,'name' => '好评'],
                ['id' => 4,'name' => '免费'],
                ['id' => 5,'name' => '付费'],
            ];
        }
        return $data;
    }

    public function getCourseTagAttr($value, $data)
    {
        if(isset($data['courseTag']) && !empty($data['courseTag'])){
            return $data['courseTag'];
        }
        return [];
    }

    public function courseTag()
    {
        return $this->belongsTo('app\model\api\single\PartCourseTag','tag_id','id')->removeOption('soft_delete');
    }
}