<?php

namespace app\lib\api\service;
use app\model\api\Customer;
use app\lib\api\service\WeightService;
use app\model\api\Merchant;
use app\lib\api\service\CustomerServiceV2;
use app\lib\api\service\CustomerServiceV3;
use app\lib\api\service\CustomerServiceV4;
//课程报名客服分配
class CustomerServiceBaidu
{
    protected $merchantCustomerIds = [
        '142' => [1495,1524,1883,1911],//黄倩、谭玲玲、谢明君、杨艳
        '195' => [2091,2272,2610,2648],//韩勤、郑瑜、余倩、郭旺
        '229' => [2568,2624,2682,2777],//杨庆宇、敖朝鹏、李应康、殷涛
    ];

    public function getCustomerServiceId($merchantId = 0)
    {
        $serviceId = 0;
        $redisKey = env('redis.baidu_customer_redis_key') . $merchantId;
        $redis = get_redis();
        if (!$redis->exists($redisKey)) {  //初始化赋值
            $serviceId = $this->initCustomerServiceData($redis, $redisKey, $merchantId);
        } else {
            $list = $redis->hGetAll($redisKey);
            $data = [];
            $sumPeriodThreadNum = 0;
            foreach ($list as $key => $val) {
                $arr = json_decode($val, true);
                $arr['status'] = Customer::where('id',$arr['id'])->value('thread_status');
                if ($arr['residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0) {
                    $data[] = ['id' => $key, 'weight' => $arr['weight']];
                    $sumPeriodThreadNum += $arr['period_thread_num'];
                }
            }
            if ($sumPeriodThreadNum <= 0) { //当所有的落地页的曝光都为0时,重新初始化
                $serviceId = $this->initCustomerServiceData($redis, $redisKey, $merchantId);
            } else {
                if (!empty($data)) {
                    $serviceId = (new WeightService)->initData($data);//根据权重匹配数据
                    $this->setResidueThreadNum($redis, $redisKey, $serviceId);
                }
            }
        }
        if(empty($serviceId)){
            $customerData = Customer::where('merchant_id',$merchantId)->where('thread_status',1)->field('id,weight')->select()->toArray();
            $serviceId = (new WeightService)->initData($customerData);//根据权重匹配数据
        }
        return $serviceId;
    }

    //初始化客服分配的线索数量
    public function initCustomerServiceData($redis, $redisKey, $merchantId)
    {
        $customerIds = $this->merchantCustomerIds[$merchantId];
        $customerServiceList = Customer::whereIn('id', $customerIds)->where('thread_status',1)->field('id,daily_intake_limit_nums,thread_status,weight')->select();
        $serviceId = 0;
        if (!empty($customerServiceList)) {
            foreach ($customerServiceList as $val) {
                $redisData = $redis->hget($redisKey, $val['id']);
                if (!$redis->exists($redisKey) || empty($redisData)) {
                    $redis->hSet($redisKey, $val['id'], json_encode([
                        'id' => $val['id'],
                        'weight' => 1,
                        'period_thread_num' => 1,
                        'status' => $val['thread_status'],
                        'total_thread_num' => $val['daily_intake_limit_nums'],
                        'residue_thread_num' => $val['daily_intake_limit_nums'],
                    ]));
                } else {
                    $redisData = json_decode($redisData, true);
                    if ($redisData['residue_thread_num'] > 0) {
                        $redisData['status'] = $val['thread_status'];
                        $redisData['weight'] = $val['weight'];
                        $redisData['period_thread_num'] = 1;
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
                if ($arr['residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0) {
                    $data[] = ['id' => $key, 'weight' => $arr['weight']];
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
                        ]));
                    }
                    $expireTime = mktime('23',59,59, date('m'),date('d'),date('Y'));
                    $redis->expireAt($redisKey, $expireTime);
                    $list = $redis->hGetAll($redisKey);
                    $data = [];
                    foreach ($list as $key => $val) {
                        $arr = json_decode($val, true);
                        if ($arr['residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0) {
                            $data[] = ['id' => $key, 'weight' => $arr['weight']];
                        }
                    }
                }
            }
            $serviceId = (new WeightService)->initData($data);
            if (!empty($serviceId)) {
                $this->setResidueThreadNum($redis, $redisKey, $serviceId);
            }
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