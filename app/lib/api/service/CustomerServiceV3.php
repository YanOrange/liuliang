<?php

namespace app\lib\api\service;
use app\lib\api\service\WeightService;
use app\model\admin\GatherUserInfo;
use app\model\api\Customer;
use app\model\api\UserList;
use app\model\api\Merchant;

//课程报名客服分配
class CustomerServiceV3
{
    public function getCustomerServiceId($merchantId = 0, $uid = 0)
    {
        $uid = !empty($uid) ? $uid : $GLOBALS['uid'];
        $userInfo = UserList::where('id',$uid)->field('id,custom_fields')->find();
        $customField = explode(',',$userInfo['custom_fields']);
        $serviceId = 0;
        $userCustomFieldCid = 1;
        foreach($customField as $val){
            $gatherInfoData = $this->inArrayKey($this->gatherUserList(),$val,'pid_cid_key');
            if(isset($gatherInfoData[0])) {
                $userCustomFieldCid = isset($gatherInfoData[0]['cid']) ? $gatherInfoData[0]['cid'] : 1;
            }
        }
        $levels = [];
        //一级销售：1万以下、1-5万
        //二级销售：1万以下、1-5万、5-10万、10-20万
        //三级销售：1万以下、1-5万、5-10万、10-20万、20万以上
        //四级销售：5-10万、10-20万、20万以上
        if($userCustomFieldCid == 7) $userCustomFieldCid = 1;
        if($userCustomFieldCid == 1) {
            $levels = [1,2,3];
        }else if($userCustomFieldCid > 1 && $userCustomFieldCid <= 3) {
            $levels = [2,3,4];
        }else if($userCustomFieldCid > 3){
            $levels = [3,4];
        }
        $redisKey = env('redis.customer_redis_key') . $merchantId;
        $redis = get_redis();
        if (!$redis->exists($redisKey)) {  //初始化赋值123
            $serviceId = $this->initCustomerServiceData($redis, $redisKey, $merchantId, $levels);
        } else {
            $list = $redis->hGetAll($redisKey);
            $data = [];
            $sumResidueThreadNum = 0;
            $sumPeriodThreadNum = 0;
            foreach ($list as $key => $val) {
                $arr = json_decode($val, true);
                $customerInfo = Customer::where('id',$arr['id'])->field('level,source_ids,yuluo_daily_intake_limit_nums')->find();
                $arr['level'] = $customerInfo['level'];
                $arr['source_ids'] = isset($customerInfo['source_ids']) ? explode(',',$customerInfo['source_ids']) : [];
                $arr['yuluo_daily_intake_limit_nums'] = $customerInfo['yuluo_daily_intake_limit_nums'];
                $arr['yuluo_residue_thread_num'] = isset($arr['yuluo_residue_thread_num']) ? $arr['yuluo_residue_thread_num'] : $customerInfo['yuluo_daily_intake_limit_nums'];
                foreach($arr['source_ids'] as $key1 => $val1){
                    if($val1 === '0'){
                        $arr['source_ids'][$key1] = 100;
                    }
                }
                if($arr['status'] == 1) {
                    if ($arr['yuluo_residue_thread_num'] > 0 && $arr['period_thread_num'] > 0 && in_array(100,$arr['source_ids'])) {
                        if (in_array($arr['level'], $levels)) {
                            $data[] = ['id' => $key, 'weight' => $arr['weight']];
                        }
                        $sumPeriodThreadNum += $arr['period_thread_num'];
                        $sumResidueThreadNum += $arr['yuluo_residue_thread_num'];
                    }
                }
            }
            if($sumPeriodThreadNum <= 0 && $sumResidueThreadNum <= 0) {
                $serviceId = $this->initCustomerServiceData($redis, $redisKey, $merchantId,$levels);
            }else {
                if (!empty($data)) {
                    $serviceId = (new WeightService)->initData($data);//根据权重匹配数据
                } else{
                    $serviceId = $this->initCustomerServiceDataV2($redis, $redisKey, $merchantId,$levels,$sumPeriodThreadNum);
                }
            }
        }
        return $serviceId;
    }

    //初始化客服分配的线索数量1
    public function initCustomerServiceData($redis, $redisKey, $merchantId,$levels)
    {
        $customerServiceList = Customer::where('merchant_id', $merchantId)
            ->field('id,daily_intake_limit_nums,yuluo_daily_intake_limit_nums,youshang_daily_intake_limit_nums,thread_status,weight,youshang_weight,is_under_eighteen_thread,level,source_ids,media_weight,media_daily_intake_limit_nums')
            ->select();
        $serviceId = 0;
        if (!empty($customerServiceList)) {
            foreach ($customerServiceList as $val) {
                $redisData = $redis->hget($redisKey, $val['id']);
                if (!$redis->exists($redisKey) || empty($redisData)) {
                    $redis->hSet($redisKey, (string)$val['id'], json_encode([
                        'id' => $val['id'],
                        'weight' => $val['weight'],
                        'youshang_weight' => $val['youshang_weight'],
                        'media_weight' => $val['media_weight'],
                        'period_thread_num' => $val['weight'],
                        'youshang_period_thread_num' => $val['youshang_weight'],
                        'media_period_thread_num' => $val['media_weight'],
                        'status' => $val['thread_status'],
                        'total_thread_num' => $val['daily_intake_limit_nums'],
                        'yuluo_total_thread_num' => $val['yuluo_daily_intake_limit_nums'],
                        'youshang_total_thread_num' => $val['youshang_daily_intake_limit_nums'],
                        'media_total_thread_num' => $val['media_daily_intake_limit_nums'],
                        'residue_thread_num' => $val['daily_intake_limit_nums'],
                        'yuluo_residue_thread_num' => $val['yuluo_daily_intake_limit_nums'],
                        'youshang_residue_thread_num' => $val['youshang_daily_intake_limit_nums'],
                        'media_residue_thread_num' => $val['media_daily_intake_limit_nums'],
                        'is_under_eighteen_thread' => $val['is_under_eighteen_thread'],
                        'level' => $val['level'],
                        'source_ids' => $val['source_ids'],
                    ]));
                } else {
                    $redisData = json_decode($redisData, true);
                    if ($redisData['yuluo_residue_thread_num'] > 0) {
                        $redisData['status'] = $val['thread_status'];
                        $redisData['weight'] = $val['weight'];
                        $redisData['period_thread_num'] = $redisData['yuluo_residue_thread_num'] >= $val['weight'] ? $val['weight'] : $redisData['yuluo_residue_thread_num'];
                        $redisData['level'] = $val['level'];
                        $redisData['source_ids'] = $val['source_ids'];
                        $redis->hSet($redisKey, (string)$val['id'], json_encode($redisData));
                    }
                }
            }
            $expireTime = mktime('23',59,59, date('m'),date('d'),date('Y'));
            $redis->expireAt($redisKey, $expireTime);
            $list = $redis->hGetAll($redisKey);
            $data = [];
            $dataAll = [];
            foreach ($list as $key => $val) {
                $arr = json_decode($val, true);
                $arr['source_ids'] = isset($arr['source_ids']) ? explode(',',$arr['source_ids']) : [];
                foreach($arr['source_ids'] as $key1 => $val1){
                    if($val1 === '0'){
                        $arr['source_ids'][$key1] = 100;
                    }
                }
                if ($arr['yuluo_residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0 && in_array(100,$arr['source_ids'])) {
                    if (in_array($arr['level'], $levels)) {
                        $data[] = ['id' => $key, 'weight' => $arr['weight']];
                    }
                }
            }
            //$data = !empty($data) ? $data : $dataAll;
            if (empty($data)) {
                if (!empty($customerServiceList)) {
                    foreach ($customerServiceList as $item) {
                        $redisData = $redis->hget($redisKey, $item['id']);
                        if (!$redis->exists($redisKey) || empty($redisData)) {
                            $redis->hSet($redisKey, (string)$item['id'], json_encode([
                                'id' => $item['id'],
                                'weight' => $item['weight'],
                                'youshang_weight' => $item['youshang_weight'],
                                'media_weight' => $val['media_weight'],
                                'period_thread_num' => $item['weight'],
                                'youshang_period_thread_num' => $item['youshang_weight'],
                                'media_period_thread_num' => $val['media_weight'],
                                'status' => $item['thread_status'],
                                'total_thread_num' => $item['daily_intake_limit_nums'],
                                'yuluo_total_thread_num' => $item['yuluo_daily_intake_limit_nums'],
                                'media_total_thread_num' => $val['media_daily_intake_limit_nums'],
                                'youshang_total_thread_num' => $item['youshang_daily_intake_limit_nums'],
                                'residue_thread_num' => $item['daily_intake_limit_nums'],
                                'yuluo_residue_thread_num' => $item['yuluo_daily_intake_limit_nums'],
                                'youshang_residue_thread_num' => $item['youshang_daily_intake_limit_nums'],
                                'media_residue_thread_num' => $val['media_daily_intake_limit_nums'],
                                'is_under_eighteen_thread' => $item['is_under_eighteen_thread'],
                                'level' => $item['level'],
                                'source_ids' => $item['source_ids'],
                            ]));
                        }else{
                            $redisDataAll = json_decode($redisData,true);
                            $redisDataAll['status'] = $item['thread_status'];
                            $redisDataAll['weight'] = $item['weight'];
                            $redisDataAll['period_thread_num'] = $item['weight'];
                            $redisDataAll['yuluo_total_thread_num'] = $item['yuluo_daily_intake_limit_nums'];
                            $redisDataAll['yuluo_residue_thread_num'] = $item['yuluo_daily_intake_limit_nums'];
                            $redisDataAll['level'] = $item['level'];
                            $redisDataAll['source_ids'] = $item['source_ids'];
                            $redis->hSet($redisKey, (string)$item['id'], json_encode($redisDataAll));
                        }
                    }
                    $expireTime = mktime('23',59,59, date('m'),date('d'),date('Y'));
                    $redis->expireAt($redisKey, $expireTime);
                    $list = $redis->hGetAll($redisKey);
                    $data = [];
                    foreach ($list as $key => $val) {
                        $arr = json_decode($val, true);
                        $arr['source_ids'] = isset($arr['source_ids']) ? explode(',',$arr['source_ids']) : [];
                        foreach($arr['source_ids'] as $key1 => $val1){
                            if($val1 === '0'){
                                $arr['source_ids'][$key1] = 100;
                            }
                        }
                        if ($arr['yuluo_residue_thread_num'] > 0 && $arr['status']  && $arr['period_thread_num'] > 0 && in_array(100,$arr['source_ids'])) {
                            if (in_array($arr['level'], $levels)) {
                                $data[] = ['id' => $key, 'weight' => $arr['weight']];
                            }else{
                                $dataAll[] = ['id' => $key, 'weight' => $arr['weight']];
                            }
                        }
                    }
                }
            }
            $data = !empty($data) ? $data : $dataAll;
            $serviceId = (new WeightService)->initData($data);

        }
        return $serviceId;
    }

    //初始化客服分配的线索数量1
    public function initCustomerServiceDataV2($redis, $redisKey, $merchantId,$levels,$sumPeriodThreadNum)
    {
        $customerServiceList = Customer::where('merchant_id', $merchantId)
            ->field('id,daily_intake_limit_nums,yuluo_daily_intake_limit_nums,youshang_daily_intake_limit_nums,thread_status,weight,youshang_weight,is_under_eighteen_thread,level,source_ids')
            ->select();
        $serviceId = 0;
        if (!empty($customerServiceList)) {
            foreach ($customerServiceList as $val) {
                $redisData = $redis->hget($redisKey, $val['id']);
                if (!$redis->exists($redisKey) || empty($redisData)) {
                    $redis->hSet($redisKey, (string)$val['id'], json_encode([
                        'id' => $val['id'],
                        'weight' => $val['weight'],
                        'youshang_weight' => $val['youshang_weight'],
                        'media_weight' => $val['media_weight'],
                        'period_thread_num' => $val['weight'],
                        'youshang_period_thread_num' => $val['youshang_weight'],
                        'media_period_thread_num' => $val['media_weight'],
                        'status' => $val['thread_status'],
                        'total_thread_num' => $val['daily_intake_limit_nums'],
                        'yuluo_total_thread_num' => $val['yuluo_daily_intake_limit_nums'],
                        'youshang_total_thread_num' => $val['youshang_daily_intake_limit_nums'],
                        'media_total_thread_num' => $val['media_daily_intake_limit_nums'],
                        'residue_thread_num' => $val['daily_intake_limit_nums'],
                        'yuluo_residue_thread_num' => $val['yuluo_daily_intake_limit_nums'],
                        'youshang_residue_thread_num' => $val['youshang_daily_intake_limit_nums'],
                        'media_residue_thread_num' => $val['media_daily_intake_limit_nums'],
                        'is_under_eighteen_thread' => $val['is_under_eighteen_thread'],
                        'level' => $val['level'],
                        'source_ids' => $val['source_ids'],
                    ]));
                } else {
                    $redisData = json_decode($redisData, true);
                    if ($redisData['yuluo_residue_thread_num'] > 0 && $redisData['period_thread_num'] <= 0) {
                        if(in_array($redisData['level'],$levels)) {
                            $redisData['status'] = $val['thread_status'];
                            $redisData['weight'] = $val['weight'];
                            $redisData['period_thread_num'] = $redisData['residue_thread_num'] >= $val['weight'] ? $val['weight'] : $redisData['residue_thread_num'];
                            $redisData['level'] = $val['level'];
                            $redisData['source_ids'] = $val['source_ids'];
                            $redis->hSet($redisKey, (string)$val['id'], json_encode($redisData));
                        }else{
                            if($sumPeriodThreadNum <= 0) {
                                $redisData['status'] = $val['thread_status'];
                                $redisData['weight'] = $val['weight'];
                                $redisData['period_thread_num'] = $redisData['residue_thread_num'] >= $val['weight'] ? $val['weight'] : $redisData['residue_thread_num'];
                                $redisData['level'] = $val['level'];
                                $redisData['source_ids'] = $val['source_ids'];
                                $redis->hSet($redisKey, (string)$val['id'], json_encode($redisData));
                            }
                        }
                    }
                }
            }
            $expireTime = mktime('23',59,59, date('m'),date('d'),date('Y'));
            $redis->expireAt($redisKey, $expireTime);
            $list = $redis->hGetAll($redisKey);
            $data = [];
            $dataAll = [];
            foreach ($list as $key => $val) {
                $arr = json_decode($val, true);
                $arr['source_ids'] = isset($arr['source_ids']) ? explode(',',$arr['source_ids']) : [];
                foreach($arr['source_ids'] as $key1 => $val1){
                    if($val1 === '0'){
                        $arr['source_ids'][$key1] = 100;
                    }
                }
                if ($arr['yuluo_residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0 && in_array(100,$arr['source_ids'])) {
                    if (in_array($arr['level'], $levels)) {
                        $data[] = ['id' => $key, 'weight' => $arr['weight']];
                    }else{
                        $dataAll[] = ['id' => $key, 'weight' => $arr['weight']];
                    }
                }
            }
            $data = !empty($data) ? $data : $dataAll;
            $serviceId = (new WeightService)->initData($data);

        }
        return $serviceId;
    }

    //减少当日客户的线索分配数量
    public function setResidueThreadNum($redis, $redisKey, $serviceId = 0, $isMedia = 0)
    {
        $exposeInfo = $redis->hget($redisKey, $serviceId);
        if (!empty($exposeInfo)) {
            $exposeInfo = json_decode($exposeInfo, true);
            if($isMedia == 1){
                if ($exposeInfo['media_residue_thread_num'] > 0) {
                    $exposeInfo['media_residue_thread_num'] -= 1;
                }
                if ($exposeInfo['media_period_thread_num'] > 0) {
                    $exposeInfo['media_period_thread_num'] -= 1;
                }
            }else {
                if ($exposeInfo['yuluo_residue_thread_num'] > 0) {
                    $exposeInfo['yuluo_residue_thread_num'] -= 1;
                }
                if ($exposeInfo['period_thread_num'] > 0) {
                    $exposeInfo['period_thread_num'] -= 1;
                }
            }
            $redis->hSet($redisKey, (string)$serviceId, json_encode($exposeInfo));
        }
    }

    public function gatherUserList()
    {
        $gatherUserArr = [];
        $gatherUserList = GatherUserInfo::where('id',19)->select()->toArray();
        if(!empty($gatherUserList)) {
            foreach ($gatherUserList as $item){
                $gatherInfoJson = json_decode($item['gather_info_json'],true);
                $gatherInfoData = [];
                foreach($gatherInfoJson as $val){
                    $gatherInfoData[] = [
                        'id' => $item['id'],
                        'field' => $item['field'],
                        'pid' => $item['id'],
                        'cid' => $val['id'],
                        'pid_cid_key' => $item['id'].'='.$val['id'],
                        'name' => $val['name']
                    ];
                }
                $gatherUserArr[] = $gatherInfoData;
            }
            foreach($gatherUserArr as $item){
                foreach($item as $val){
                    $gatherUserAll[] = $val;
                }
            }
        }
        return $gatherUserAll;
    }

    public function inArrayKey($array, $inarray, $field){
        if(!is_array($inarray)){
            $inarray = explode(',', $inarray);
        }
        $arr = [];
        foreach($array as $key=>$value){
            if(in_array($value[$field], $inarray)){
                $arr[] = $value;
            }
        }
        return $arr;
    }
}