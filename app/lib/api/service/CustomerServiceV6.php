<?php

namespace app\lib\api\service;
use app\model\api\Customer;
use app\lib\api\service\WeightService;
use app\model\api\Merchant;
use app\lib\api\service\CustomerServiceV2;
use app\lib\api\service\CustomerServiceV3;
use app\lib\api\service\CustomerServiceV4;
use app\model\admin\JzdCustomerVolumeAssign;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

//课程报名客服分配
class CustomerServiceV6
{
    public function getCustomerServiceId($merchantId = 0, $uid = 0,$channel = null)
    {
        $serviceId = 0;
        $redisKey = env('redis.assign_thread_customer_redis_key') . $merchantId;
        $redis = get_redis();
        if (!$redis->exists($redisKey)) {  //初始化赋值
            $serviceId = $this->initCustomerServiceData($redis, $redisKey, $merchantId);
        } else {
            $list = $redis->hGetAll($redisKey);
            $data = [];
            $sumPeriodThreadNum = 0;
            foreach ($list as $key => $val) {
                $arr = json_decode($val, true);
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
                    //$this->setResidueThreadNum($redis, $redisKey, $serviceId);
                }
            }
        }
        return $serviceId;
    }

    //初始化客服分配的线索数量
    public function initCustomerServiceData($redis, $redisKey, $merchantId)
    {
        $customerServiceList = Customer::where('merchant_id', $merchantId)->where('assign_intake_limit_nums','>',0)->field('id,daily_intake_limit_nums,thread_status,weight,assign_intake_limit_nums')->select();
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
                        'total_thread_num' => $val['assign_intake_limit_nums'],
                        'residue_thread_num' => $val['assign_intake_limit_nums'],
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
                            'total_thread_num' => $item['assign_intake_limit_nums'],
                            'residue_thread_num' => $item['assign_intake_limit_nums'],
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
        }
        return $serviceId;
    }

    //销售业绩排名

    /**
     * @throws DataNotFoundException
     * @throws \RedisException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function assignCustomerServiceData($merchantId)
    {
        //获取redis队列
        $date = date("Ymd");
        $list_key =  "customer:assign:sort:{$merchantId}:{$date}";
        $redis = get_redis();
        $serviceId = $redis->lPop($list_key);
        //echo $serviceId;exit();
        if(empty($serviceId)) {
            $customerVolumeAssign = JzdCustomerVolumeAssign::with(['customer'])->where('merchant_id', $merchantId)
                ->field('id,customer_id,customer_total_contract_amount,customer_total_deposit_amount,customer_total_receipt_amount')
                ->select()
                ->toArray();
            $num1 = $num = round(count($customerVolumeAssign) * 0.6);
            $customerData = [];
            if (!empty($customerVolumeAssign)) {
                foreach ($customerVolumeAssign as $item) {
                    if ($item['thread_status']) {
                        $customerData[] = [
                            'customer_id' => $item['customer_id'],
                            'customer_performance_amount' => $item['customer_total_contract_amount'] + $item['customer_total_deposit_amount'] + $item['customer_total_receipt_amount']
                        ];
                    }
                }
            }
            $performanceData = [];
            // 提取需要排序的字段作为关联数组
            foreach ($customerData as $key => &$item) {
                $performanceData[$key] = $item['customer_performance_amount'];
            }
            unset($item); // 释放引用变量
            $res = [];
            array_multisort($performanceData, SORT_DESC, $customerData);
            $customerRankData = [];
            if ($num > 0 && !empty($customerData)) {
                for ($i = 0; $i < $num; $i++) {
                    if($i!=0){
                        $res[]= $redis->rPush($list_key,$customerData[$i]['customer_id']);
                    }else{
                        $serviceId = $customerData[$i]['customer_id'];
                    }
//                    $customerRankData[] = [
//                        'id' => $customerData[$i]['customer_id'],
//                        'weight' => $num1--
//                    ];
                }
            }
            $expireTime = mktime('23',59,59, date('m'),date('d'),date('Y'));
            $redis->expireAt($list_key, $expireTime);
        }
        //$serviceId = (new WeightService())->initData($customerRankData);
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