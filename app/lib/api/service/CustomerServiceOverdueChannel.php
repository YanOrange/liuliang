<?php

namespace app\lib\api\service;
use app\model\api\Customer;
use app\lib\api\service\WeightService;
use app\model\api\Merchant;
use app\model\admin\OverdueAppCustomer;

//逾期渠道客服分配
class CustomerServiceOverdueChannel
{
    public function getCustomerServiceId($channelId = 0)
    {
        $serviceId = 0;
        $redisKey = env('redis.overdue_channel_customer_redis_key') . $channelId;
        $redis = get_redis();
        if (!$redis->exists($redisKey)) {  //初始化赋值
            $serviceId = $this->initCustomerServiceData($redis, $redisKey, $channelId);
        } else {
            $list = $redis->hGetAll($redisKey);
            $data = [];
            $sumPeriodThreadNum = 0;
            $appstoreDailyIntakeLimitNums = 0;
            foreach ($list as $key => $val) {
                $arr = json_decode($val, true);
                $customer = Customer::where('id',$arr['id'])->field('id,thread_status,appstore_daily_intake_limit_nums,appstore_daily_intake_limit_nums_01,appstore_daily_intake_limit_nums_02')->find();
                $arr['status'] = $customer['thread_status'];
                if ($arr['residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0) {
                    if(time() > strtotime(date('Y-m-d 01:00:00')) && time() <= strtotime(date('Y-m-d 13:00:00'))){
                        $appstoreDailyIntakeLimitNums = $customer['appstore_daily_intake_limit_nums'];
                    } else if(time() > strtotime(date('Y-m-d 13:00:00')) && time() <= strtotime(date('Y-m-d 19:30:00'))){
                        $appstoreDailyIntakeLimitNums = $customer['appstore_daily_intake_limit_nums_01'];
                    } else if(time() > strtotime(date('Y-m-d 19:30:00')) && time() <= strtotime(date('Y-m-d 01:00:00',strtotime(' +1 day')))){
                        $appstoreDailyIntakeLimitNums = $customer['appstore_daily_intake_limit_nums_02'];
                    }
                    if($appstoreDailyIntakeLimitNums > 0) {
                        $data[] = ['id' => $key, 'weight' => $arr['weight']];
                    }
                    $sumPeriodThreadNum += $arr['period_thread_num'];
                }
            }
            if ($sumPeriodThreadNum <= 0) { //当所有的落地页的曝光都为0时,重新初始化
                $serviceId = $this->initCustomerServiceData($redis, $redisKey, $channelId);
            } else {
                if (!empty($data)) {
                    $serviceId = (new WeightService)->initData($data);//根据权重匹配数据
                    $this->setResidueThreadNum($redis, $redisKey, $serviceId);
                }else{
                    $serviceId = $this->initCustomerServiceData($redis, $redisKey, $channelId);
                }
            }
        }
        return ['customer_id' => $serviceId,'merchant_id' => Customer::where('id',$serviceId)->value('merchant_id') ?? 0];
    }

    //初始化客服分配的线索数量
    public function initCustomerServiceData($redis, $redisKey, $channelId)
    {

        $customerChannelList = OverdueAppCustomer::where('channel_id', $channelId)->find();
        $customerIds = explode(',', $customerChannelList->customer_ids);
        $customerServiceList = Customer::whereIn('id', $customerIds)
            ->field('id,daily_intake_limit_nums,thread_status,weight')
            ->select();
        $serviceId = 0;
        $appstoreDailyIntakeLimitNums = 0;
        if (!empty($customerServiceList)) {
            foreach ($customerServiceList as $val) {
                $redisData = $redis->hget($redisKey, $val['id']);
                if (!$redis->exists($redisKey) || empty($redisData)) {
                    $redis->hSet($redisKey, $val['id'], json_encode([
                        'id' => $val['id'],
                        'weight' => 1,
                        'period_thread_num' => 1,
                        'status' => $val['thread_status'],
                        'total_thread_num' => 1,
                        'residue_thread_num' => 1,
                    ]));
                } else {
                    $redisData = json_decode($redisData, true);
                    $redisData['period_thread_num'] = 1;
                    $redis->hSet($redisKey, $val['id'], json_encode($redisData));
                }
            }

            $expireTime = mktime('23', 59, 59, date('m'), date('d'), date('Y'));
            $redis->expireAt($redisKey, $expireTime);
            $list = $redis->hGetAll($redisKey);
            $data = [];
            foreach ($list as $key => $val) {
                $arr = json_decode($val, true);
                $customer = Customer::where('id', $arr['id'])->field('id,thread_status,appstore_daily_intake_limit_nums,appstore_daily_intake_limit_nums_01,appstore_daily_intake_limit_nums_02')->find();
                $arr['status'] = $customer['thread_status'];
                if(time() > strtotime(date('Y-m-d 01:00:00')) && time() <= strtotime(date('Y-m-d 13:00:00'))){
                    $appstoreDailyIntakeLimitNums = $customer['appstore_daily_intake_limit_nums'];
                } else if(time() > strtotime(date('Y-m-d 13:00:00')) && time() <= strtotime(date('Y-m-d 19:30:00'))){
                    $appstoreDailyIntakeLimitNums = $customer['appstore_daily_intake_limit_nums_01'];
                } else if(time() > strtotime(date('Y-m-d 19:30:00')) && time() <= strtotime(date('Y-m-d 01:00:00',strtotime(' +1 day')))){
                    $appstoreDailyIntakeLimitNums = $customer['appstore_daily_intake_limit_nums_02'];
                }
                if ($arr['residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0) {
                    if ($appstoreDailyIntakeLimitNums > 0) {
                        $data[] = ['id' => $key, 'weight' => $arr['weight']];
                    }
                }
            }
            if (empty($data)) {
                if (!empty($customerServiceList)) {
                    foreach ($customerServiceList as $item) {
                        $redis->hSet($redisKey, $item['id'], json_encode([
                            'id' => $item['id'],
                            'weight' => 1,
                            'period_thread_num' => 1,
                            'status' => $item['thread_status'],
                            'total_thread_num' => 1,
                            'residue_thread_num' => 1,
                        ]));
                    }
                    $expireTime = mktime('23', 59, 59, date('m'), date('d'), date('Y'));
                    $redis->expireAt($redisKey, $expireTime);
                    $list = $redis->hGetAll($redisKey);
                    $data = [];
                    foreach ($list as $key => $val) {
                        $arr = json_decode($val, true);
                        $customer = Customer::where('id', $arr['id'])->field('id,thread_status,appstore_daily_intake_limit_nums,appstore_daily_intake_limit_nums_01,appstore_daily_intake_limit_nums_02')->find();
                        $arr['status'] = $customer['thread_status'];
                        if(time() > strtotime(date('Y-m-d 01:00:00')) && time() <= strtotime(date('Y-m-d 13:00:00'))){
                            $appstoreDailyIntakeLimitNums = $customer['appstore_daily_intake_limit_nums'];
                        } else if(time() > strtotime(date('Y-m-d 13:00:00')) && time() <= strtotime(date('Y-m-d 19:30:00'))){
                            $appstoreDailyIntakeLimitNums = $customer['appstore_daily_intake_limit_nums_01'];
                        } else if(time() > strtotime(date('Y-m-d 19:30:00')) && time() <= strtotime(date('Y-m-d 01:00:00',strtotime(' +1 day')))){
                            $appstoreDailyIntakeLimitNums = $customer['appstore_daily_intake_limit_nums_02'];
                        }
                        if ($arr['residue_thread_num'] > 0 && $arr['status'] && $arr['period_thread_num'] > 0) {
                            if ($appstoreDailyIntakeLimitNums > 0) {
                                $data[] = ['id' => $key, 'weight' => $arr['weight']];
                            }
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
            if ($exposeInfo['period_thread_num'] > 0) {
                $exposeInfo['period_thread_num'] -= 1;
            }
            $redis->hSet($redisKey, $serviceId, json_encode($exposeInfo));
        }
    }
}