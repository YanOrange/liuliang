<?php

namespace app\lib\api\service;
use app\lib\api\service\WeightService;
use app\model\api\LandingPage;
use app\model\api\Course;
use app\model\api\UserList;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;
use think\facade\Config;
use app\model\api\Thread;
use app\model\api\Merchant;
use app\model\api\App;
use app\model\api\Channel;
use app\model\api\AppClass;
use think\facade\Db;

class PartMerchantService
{

    //获取兼职可报名商户
    public static function getPartMerchantId($course_id = 0,$channelInfo)
    {
        $courseInfo = Course::find($course_id);
        $merchantService = new MerchantServiceJob();
        $merchantList = $merchantService::getMerchantIsPayCount($channelInfo);
        extract($merchantList);
        if(strstr($channelInfo['channel_name'],'ios')){
            $allMerchantListData = !empty($tempPayMerchantData) ? $tempPayMerchantData : $tempFreeMerchantData;
        }else {
            if ($courseInfo->entry_fee > 0) {
                $allMerchantListData = $tempPayMerchantData ?? $tempFreeMerchantData;
            } else {
                $allMerchantListData = $tempFreeMerchantData ?? $tempPayMerchantData;
            }
        }
        //$allMerchantListData = array_merge($tempFreeMerchantData, $tempPayMerchantData);
        $allMerchantIds = array_column($allMerchantListData,'id');
        $threadMerchantIds = Thread::whereIn('merchant_id',$allMerchantIds)
            ->where('uid',$GLOBALS['uid'])->column('merchant_id');
        foreach($allMerchantListData as $key => $item){
            if(in_array($item['id'],$threadMerchantIds)){
                unset($allMerchantListData[$key]);
            }
        }
        $allMerchantListData = array_values($allMerchantListData);
        $threadModel =  new \app\model\api\Thread();
        $name = $threadModel->getName();
        $tableName = env('database.prefix') . $name;
        if(!empty($allMerchantListData)){
            $merchantId = (new WeightService)->initData($allMerchantListData);
            $threadMerchant = Thread::where('course_id|part_course_id',$course_id)->where('uid',$GLOBALS['uid'])->field('id,merchant_id')->find();
            if(!empty($threadMerchant)){
                $data = ['is_apply' => 1,'merchant_id' => $threadMerchant->merchant_id];
            }else{
                $data = ['is_apply' => 0,'merchant_id' => $merchantId];
            }
        }else{
            $threadMerchant = Thread::where('course_id|part_course_id',$course_id)->where('uid',$GLOBALS['uid'])->field('id,merchant_id')->find();
            if(!empty($threadMerchant)){
                $data = ['is_apply' => 1,'merchant_id' => $threadMerchant->merchant_id];
            }else{
                $threadCount = $threadModel->where('uid',$GLOBALS['uid'])->where('is_discern_qrcode',0)->count();
                if($threadCount > 0){
                    $merchantId = $threadModel->whereExists(function ($query) use ($tableName) {
                        $merchantTableName = (new \app\model\api\Merchant())->getName();
                        $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' .   $tableName . '.merchant_id');
                        $query->where('is_source', 2);
                        return $query;
                    })
                        ->where('uid',$GLOBALS['uid'])
                        ->where('is_discern_qrcode',0)
                        ->order('id desc')
                        ->value('merchant_id');
                    $data = ['is_apply' => 1,'merchant_id' => $merchantId];
                }else{
                    $merchantId = $threadModel->whereExists(function ($query) use ($tableName) {
                        $merchantTableName = (new \app\model\api\Merchant())->getName();
                        $query = $query->table(env('database.prefix') . $merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' .   $tableName . '.merchant_id');
                        $query->where('is_source', 2);
                        return $query;
                    })
                        ->where('uid',$GLOBALS['uid'])
                        ->order('id desc')
                        ->value('merchant_id');
                    $data = ['is_apply' => 1,'merchant_id' => $merchantId];
                }
            }
        }
        return $data;
    }
}

