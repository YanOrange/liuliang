<?php

namespace app\model\api;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\api\Channel;
use app\model\api\v2\Show;

class Advertising extends BaseModel
{
    use SoftDelete;

    protected $name = 'advertising';

    public static function getPageAdvertising($params = [])
    {
        extract($params);
        $channel = isset($channel) && !empty($channel) ? $channel : '';
        $merchantId = isset($merchant_id) && !empty($merchant_id) ? $merchant_id : 0;
        $data = [];
        $where[] = ['adv.status','=',1];
        if(!empty($merchantId)){
            $where[] = ['adv.merchant_id','=',$merchantId];
        }
        $channelInfo = Channel::getChannelAppClass($channel);
        if(!empty($channelInfo)){
//            if($channelInfo['is_many_organization'] == 3){
//                $where[] = ['app_id','find in set',$channelInfo['app_id']];
//            }else{
//                if(!$merchantId){
//                    $redis = get_redis();
//                    $merchantId = $redis->get(env('redis.user_landing_page_redis_key') . $GLOBALS['uid']);
//                }
//                $where[] = ['merchant_id','=',$merchantId];
//            }
            $advertising = self::alias('adv')
                ->join('thread thr','thr.uid = '.$GLOBALS['uid'].' and thr.merchant_id = adv.merchant_id','left')
                ->where($where)
                ->whereNull('thr.id')
                ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
                ->where('adv.is_many_organization',$channelInfo['is_many_organization'])
                ->field('adv.id,adv.merchant_id,title,image,page_id,open_mode,jump_mode,jump_mode_json,jump_url')
                ->order(['sort'=>'desc','id'=>'desc'])
                ->find();
            if(!empty($advertising)){
                $advertising = $advertising->toArray();
                $advertising['jump_mode_json'] = !empty($advertising['jump_mode_json']) ? json_decode($advertising['jump_mode_json'],true) : [];
                $advertising['jump_mode_json'] = [
                    'module_id' => 1,
                    'course_id' => Course::where('merchant_id',$advertising['merchant_id'])->value('id') ?? 0,
                    'merchant_id' => $advertising['merchant_id']
                ];
                $data = $advertising;
            }
        }
        return $data;
    }
    public static function getPageAdvertisingV2($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $advertisingData = self::field('image,open_mode')->whereFindInSet('channel_ids', $channelInfo['channel_id'])->where('status', 1)->order('id desc')->find();
        return $advertisingData;
    }
}