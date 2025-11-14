<?php

namespace app\model\api\fortunecat;

use app\lib\api\service\MerchantServiceJob;
use app\model\api\Channel;
use laytp\BaseModel;
use think\model\concern\SoftDelete;

class PartClass extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'part_class';

    public static function getPartClassList($params = [])
    {
        extract($params);
        $partClassIds = [];
        //$firstPartClass = ['id' => 0,'part_class_name' => '今日兼职'];
        $channelInfo = Channel::getChannelAppClass($channel);
        $merchantData = MerchantServiceJob::getMerchantIsPayCount($channelInfo);
        $where[] = ['status','=',1];
        $where[] = ['course_type','=',1];
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

        $courseList = Course::where($where)
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
        $partClassList = self::field('id,part_class_name,class_image')
            ->whereIn('id',$partClassIds)
            ->where('class_type',1)
            ->order('id asc')
            ->select()
            ->toArray();
        //array_unshift($partClassList,$firstPartClass);
        return $partClassList;
    }
}