<?php

namespace app\lib\api\service;
use app\lib\api\service\WeightService;
use app\model\api\Customer;
use app\model\api\UserList;
use app\model\api\Merchant;

//课程报名客服分配
class CustomerServiceV2
{
    public function getCustomerServiceId($merchantId = 0, $uid = 0)
    {
        $uid = !empty($uid) ? $uid : $GLOBALS['uid'];
        $userInfo = UserList::where('id',$uid)->field('id,age_range_id')->find();
        $merchantInfo = Merchant::where('id',$merchantId)->field('id,is_source')->find();
        $serviceId = 0;
        $redisKey = env('redis.customer_redis_key') . $merchantId;
        $redis = get_redis();
        if (!$redis->exists($redisKey)) {  //初始化赋值
            $serviceId = $this->initCustomerServiceData($redis, $redisKey, $merchantId, $userInfo, $merchantInfo);
        } else {
            $list = $redis->hGetAll($redisKey);
            $data = [];
            $sumPeriodThreadNum = 0;
            foreach ($list as $key => $val) {
                $arr = json_decode($val, true);
                if($merchantInfo->is_source == 1){
                    $arr['is_under_eighteen_thread'] = isset($arr['is_under_eighteen_thread']) && !empty($arr['is_under_eighteen_thread']) ? $arr['is_under_eighteen_thread'] : Customer::where('id',$arr['id'])->value('is_under_eighteen_thread');
                    if($userInfo->age_range_id > 1){
                        if ($arr['residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0 && $arr['is_under_eighteen_thread'] == 0) {
                            $data[] = ['id' => $key, 'weight' => $arr['weight']];
                        }
                    }else{
                        if ($arr['residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0 && $arr['is_under_eighteen_thread'] > 0) {
                            $data[] = ['id' => $key, 'weight' => $arr['weight']];
                        }
                    }
                    $sumPeriodThreadNum += $arr['period_thread_num'];
                }
            }

            $data = !empty($eighteenData) ? $eighteenData : $data;
            if ($sumPeriodThreadNum <= 0) { //当所有的落地页的曝光都为0时,重新初始化
                $serviceId = $this->initCustomerServiceData($redis, $redisKey, $merchantId, $userInfo);
            } else {
                if (!empty($data)) {
                    $serviceId = (new WeightService)->initData($data);//根据权重匹配数据
                }else{
                    $serviceId = $this->initCustomerServiceData($redis, $redisKey, $merchantId, $userInfo);
                }
            }
        }
        return $serviceId;
    }

    //初始化客服分配的线索数量
    public function initCustomerServiceData($redis, $redisKey, $merchantId, $userInfo)
    {
        $customerServiceList = Customer::where('merchant_id', $merchantId)->field('id,daily_intake_limit_nums,thread_status,weight,is_under_eighteen_thread')->select();
        $serviceId = 0;
        if (!empty($customerServiceList)) {
            foreach ($customerServiceList as $val) {
                $redisData = $redis->hget($redisKey, $val['id']);
                if (!$redis->exists($redisKey) || empty($redisData)) {
                    $redis->hSet($redisKey, $val['id'], json_encode([
                        'id' => $val['id'],
                        'weight' => $val['weight'],
                        'period_thread_num' => $val['weight'],
                        'status' => $val['thread_status'],
                        'total_thread_num' => $val['daily_intake_limit_nums'],
                        'residue_thread_num' => $val['daily_intake_limit_nums'],
                        'is_under_eighteen_thread' => $val['is_under_eighteen_thread']
                    ]));
                } else {
                    $redisData = json_decode($redisData, true);
                    if ($redisData['residue_thread_num'] > 0) {
                        $redisData['status'] = $val['thread_status'];
                        $redisData['weight'] = $val['weight'];
                        $redisData['period_thread_num'] = $redisData['residue_thread_num'] >= $val['weight'] ? $val['weight'] : $redisData['residue_thread_num'];
                        $redis->hSet($redisKey, $val['id'], json_encode($redisData));
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
                if($userInfo->age_range_id > 1){
                    if ($arr['residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0 && $arr['is_under_eighteen_thread'] == 0) {
                        $data[] = ['id' => $key, 'weight' => $arr['weight']];
                    }
                }else{
                    if ($arr['residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0 && $arr['is_under_eighteen_thread'] > 0) {
                        $data[] = ['id' => $key, 'weight' => $arr['weight']];
                    }
                }
            }
            if (empty($data)) {
                if (!empty($customerServiceList)) {
                    foreach ($customerServiceList as $item) {
                        $redis->hSet($redisKey, $item['id'], json_encode([
                            'id' => $item['id'],
                            'weight' => $item['weight'],
                            'period_thread_num' => $item['weight'],
                            'status' => $item['thread_status'],
                            'total_thread_num' => $item['daily_intake_limit_nums'],
                            'residue_thread_num' => $item['daily_intake_limit_nums'],
                            'is_under_eighteen_thread' => $item['is_under_eighteen_thread']
                        ]));
                    }
                    $expireTime = mktime('23',59,59, date('m'),date('d'),date('Y'));
                    $redis->expireAt($redisKey, $expireTime);
                    $list = $redis->hGetAll($redisKey);
                    $data = [];
                    foreach ($list as $key => $val) {
                        $arr = json_decode($val, true);
                        if ($userInfo->age_range_id > 1) {
                            if ($arr['residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0 && $arr['is_under_eighteen_thread'] == 0) {
                                $data[] = ['id' => $key, 'weight' => $arr['weight']];
                            }
                        } else {
                            if ($arr['residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0 && $arr['is_under_eighteen_thread'] > 0) {
                                $data[] = ['id' => $key, 'weight' => $arr['weight']];
                            }
                        }
                        if ($arr['residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0) {
                            $dataAll[] = ['id' => $key, 'weight' => $arr['weight']];
                        }
                    }
                }
            }
            $data = !empty($data) ? $data : $dataAll;
            $serviceId = (new WeightService)->initData($data);

        }
        return $serviceId;
    }
    //减少当日客户的线索分配数量
    public function setResidueThreadNum($redis, $redisKey, $serviceId = 0)
    {
        $exposeInfo = $redis->hget($redisKey, $serviceId);
        if (!empty($exposeInfo)) {
            $exposeInfo = json_decode($exposeInfo, true);
            if ($exposeInfo['residue_thread_num'] > 0) {
                $exposeInfo['residue_thread_num'] -= 1;
            }
            if ($exposeInfo['period_thread_num'] > 0) {
                $exposeInfo['period_thread_num'] -= 1;
            }
            $redis->hSet($redisKey, $serviceId, json_encode($exposeInfo));
        }
    }
}