<?php

namespace app\lib\api\service;
use app\model\api\Customer;
use app\lib\api\service\WeightService;
use app\model\api\Merchant;
use app\lib\api\service\CustomerServiceV2;
use app\lib\api\service\CustomerServiceV3;
use app\lib\api\service\CustomerServiceV4;
//课程报名客服分配
class CustomerServiceV5
{
    const MERCHANT_LIST_ID = [177,251];
    public function getCustomerServiceId($merchantId = 0, $uid = 0,$channel = null)
    {
        /*$customerList = $this->getCustomerList($merchantId);
        var_dump($customerList);die;*/
        $serviceId = 0;
        $redisKey = env('redis.yuanhua_customer_redis_key') . '_app_class_id_10';
        $redis = get_redis();
        if (!$redis->exists($redisKey)) {  //初始化赋值
            $serviceId = $this->initCustomerServiceData($redis, $redisKey, $merchantId);
        } else {
            $list = $redis->hGetAll($redisKey);
            $data = [];
            $sumPeriodThreadNum = 0;
            $customerList = $this->getCustomerList($merchantId);
            $merchantList = $this->getMerchantList();
            foreach ($list as $key => $val) {
                $arr = json_decode($val, true);
                if (isset($customerList[$key]) && $customerList[$key] && in_array($arr['merchant_id'], self::MERCHANT_LIST_ID) && in_array($arr['merchant_id'], $merchantList) && $arr['status'] && $arr['period_thread_num'] > 0) {
                //if ($arr['residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0) {
                    $data[] = ['id' => $key, 'weight' => $arr['weight']];
                    $sumPeriodThreadNum += $arr['period_thread_num'];
                }
            }
           // var_dump($data);
            if ($sumPeriodThreadNum <= 0) { //当所有的落地页的曝光都为0时,重新初始化
                $serviceId = $this->initCustomerServiceData($redis, $redisKey, $merchantId);
            } else {
                if (!empty($data)) {
                    $serviceId = (new WeightService)->initData($data);//根据权重匹配数据
                    //$this->setResidueThreadNum($redis, $redisKey, $serviceId);
                }
            }
        }
        return $serviceId;
    }
    
    
    public function getCustomerList($merchantId = 0)
    {
        $customerList = Customer::whereIn('merchant_id', self::MERCHANT_LIST_ID)->field('id,thread_status')->select()->toArray();
        return array_column($customerList, 'thread_status', 'id');
    }
    public function getMerchantList()
    {
        return  Merchant::where('is_register', 1)->whereIn('id', self::MERCHANT_LIST_ID)->column('id');
    }
    //初始化客服分配的线索数量
    public function initCustomerServiceData($redis, $redisKey, $merchantId)
    {
        //$customerServiceList = Customer::where('merchant_id', $merchantId)->where('register_intake_limit_nums','>',0)->field('id,daily_intake_limit_nums,thread_status,weight,register_intake_limit_nums')->select();
        $customerServiceList = Customer::whereIn('merchant_id', self::MERCHANT_LIST_ID)->field('id,merchant_id,daily_intake_limit_nums,thread_status,weight,register_intake_limit_nums')->select();
        $customerList = $this->getCustomerList($merchantId);
        $merchantList = $this->getMerchantList();
        $serviceId = 0;
        if (!empty($customerServiceList)) {
            foreach ($customerServiceList as $val) {
                $redisData = $redis->hget($redisKey, $val['id']);
                if (!$redis->exists($redisKey) || empty($redisData)) {
                    $redis->hSet($redisKey, $val['id'], json_encode([
                        'id' => $val['id'],
                        'merchant_id' => $val['merchant_id'],
                        'weight' => $val['weight'],
                        'period_thread_num' => 1,//$val['weight'],
                        'status' => $val['thread_status'],
                        'total_thread_num' => $val['register_intake_limit_nums'],
                        'residue_thread_num' => $val['register_intake_limit_nums'],
                    ]));
                } else {
                    $redisData = json_decode($redisData, true);
                    if ($redisData['residue_thread_num'] > 0) {
                        $redisData['status'] = $val['thread_status'];
                        $redisData['weight'] = $val['weight'];
                        $redisData['period_thread_num'] = 1;//$redisData['residue_thread_num'] >= $val['weight'] ? $val['weight'] : $redisData['residue_thread_num'];
                        $redis->hSet($redisKey, $val['id'], json_encode($redisData));
                    }
                }
            }
            $expireTime = mktime('23',59,59, date('m'),date('d'),date('Y'));
            $redis->expireAt($redisKey, $expireTime);
            $list = $redis->hGetAll($redisKey);
            $data = [];
            foreach ($list as $key => $val) {
                $arr = json_decode($val, true);
                if (isset($customerList[$key]) && $customerList[$key] && in_array($arr['merchant_id'], self::MERCHANT_LIST_ID) && in_array($arr['merchant_id'], $merchantList) && $arr['status'] && $arr['period_thread_num'] > 0) {
                //.if ($arr['residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0) {
                    $data[] = ['id' => $key, 'weight' => $arr['weight']];
                }
            }
            if (empty($data)) {
                if (!empty($customerServiceList)) {
                    foreach ($customerServiceList as $item) {
                        $redis->hSet($redisKey, $item['id'], json_encode([
                            'id' => $item['id'],
                            'merchant_id' => $item['merchant_id'],
                            'weight' => $item['weight'],
                            'period_thread_num' => 1,//$item['weight'],
                            'status' => $item['thread_status'],
                            'total_thread_num' => $item['register_intake_limit_nums'],
                            'residue_thread_num' => $item['register_intake_limit_nums'],
                        ]));
                    }
                    $expireTime = mktime('23',59,59, date('m'),date('d'),date('Y'));
                    $redis->expireAt($redisKey, $expireTime);
                    $list = $redis->hGetAll($redisKey);
                    $data = [];
                    foreach ($list as $key => $val) {
                        $arr = json_decode($val, true);
                        if (isset($customerList[$key]) && $customerList[$key]  && in_array($arr['merchant_id'], self::MERCHANT_LIST_ID) && in_array($arr['merchant_id'], $merchantList) && $arr['status'] && $arr['period_thread_num'] > 0) {
                        //if ($arr['residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0) {
                            $data[] = ['id' => $key, 'weight' => $arr['weight']];
                        }
                    }
                }
            }
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