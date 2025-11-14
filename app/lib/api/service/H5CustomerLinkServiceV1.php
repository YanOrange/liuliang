<?php

namespace app\lib\api\service;

use app\model\admin\ForFlow;
use app\model\api\CustomerClickLog;

class H5CustomerLinkServiceV1
{
    # 获客链接配置
    const CUSTOMER_LINK_CONFIG = [
//        1 => ['id' => 1, 'name' => '章',        'merchant_id' => 271, 'weight' => 1, 'num' => 0, 'link' => 'https://work.weixin.qq.com/ca/cawcde0459e29f880c'],
//        2 => ['id' => 2, 'name' => '苏',        'merchant_id' => 271, 'weight' => 1, 'num' => 0, 'link' => 'https://work.weixin.qq.com/ca/cawcded673c46dfc7f'],
//        3 => ['id' => 3, 'name' => '章(浩铭鑫)', 'merchant_id' => 252, 'weight' => 1, 'num' => 0, 'link' => 'https://work.weixin.qq.com/ca/cawcde2edd1623d753'],
//        4 => ['id' => 4, 'name' => '苏(律之云)', 'merchant_id' => 266, 'weight' => 1, 'num' => 0, 'link' => 'https://work.weixin.qq.com/ca/cawcde7c1a84b293a4'],
//        5 => ['id' => 5, 'name' => '肖(浩铭鑫)', 'merchant_id' => 252, 'weight' => 1, 'num' => 0, 'link' => 'https://work.weixin.qq.com/ca/cawcde8ca4ece0c262'],

//        6 => ['id' => 6, 'name' => '肖(浩铭鑫)', 'merchant_id' => 252, 'weight' => 1, 'num' => 0, 'link' => 'https://work.weixin.qq.com/ca/cawcde2edd1623d753'],
//        7 => ['id' => 7, 'name' => '肖(律之云)', 'merchant_id' => 266, 'weight' => 1, 'num' => 0, 'link' => 'https://work.weixin.qq.com/ca/cawcde7c1a84b293a4'],

        8 => ['id' => 8, 'name' => '客服(臻尚)', 'merchant_id' => 271, 'weight' => 1, 'num' => 0, 'link' => 'https://work.weixin.qq.com/ca/cawcde294f6150655a'],
    ];

    # 特殊指定
    const PAGE_SPECIFY_CUSTOMER_LINK = [
//        21 => [3],   # key: for_flow_id  value: customer_link_config_key
//        24 => [4],
//        25 => [5],
    ];

    public static function getCustomerServiceId($params = [])
    {
        $forFlowId  = $params['for_flow_id'] ?? 0;
        $redisKey   = 'H5:v_lianlu:customer:weight_' . date('md');

        $wecomLink   = ForFlow::where('id', $forFlowId)->value('wecom_link');
        if ($wecomLink) {
            $wecomLinkData = explode(',', $wecomLink);

            $info = [
                'id'    => 0,
                'link'  => $wecomLinkData[array_rand($wecomLinkData)],
            ];
        } else {
            $info = self::planA($forFlowId, $redisKey);
        }

        $logId = 0;
        if ($info) {
            $redis = get_redis();
            $redis->hIncrBy($redisKey, $info['id'], 1);
            $redis->expire($redisKey, 86400);

            # 记录日志
            $log = CustomerClickLog::create([
                'channel_id'    => $params['channel'] ?? 0,
                'for_flow_id'   => $params['for_flow_id'] ?? 0,
                'customer_id'   => $info['id'],
                'click_id'      => $params['h5_uid'] ?? session_id(),
                'type'          => 1,
                'customer_link' => $info['link'],
                'request'       => json_encode($params),
                'response'      => json_encode($info),
                'header'        => json_encode(request()->header()),
            ]);

            $logId = $log->id;
        }

        $link = $info['link'] . "?customer_channel=click_log:{$logId}";
        return ['id' => $info['id'], 'link' => $link];
    }

    protected static function planA($forFlowId, $redisKey)
    {
        $customerLinks  = self::CUSTOMER_LINK_CONFIG;
        $specifyLinkIds = self::PAGE_SPECIFY_CUSTOMER_LINK[$forFlowId] ?? [];

        $redis = get_redis();

        $totalNum       = 0;   # 总进量数
        $totalWeight    = 0;   # 总权重
        foreach ($customerLinks as $k => &$v) {

            if ($specifyLinkIds && !in_array($k, $specifyLinkIds)) {
                unset($customerLinks[$k]);
                continue;
            }

            $v['num'] = (int) $redis->hGet($redisKey, $v['id']);
            $totalNum       += $v['num'];
            $totalWeight    += $v['weight'];
        }

        # 根据权重和进量数 计算进量平均差值  重新排序
        if ($totalWeight > 0) {
            foreach ($customerLinks as &$row) {
                $inputNumRate   = $totalNum <= 0 ? 0 : bcdiv($row['num'], $totalNum, 8);
                $inputBaseRate  = bcdiv($row['weight'], $totalWeight, 8);
                $row['weight_value'] = bcsub( $inputNumRate, $inputBaseRate,8);
            }
            array_multisort(array_column($customerLinks, 'weight_value'), SORT_ASC, $customerLinks);
        }

        return $customerLinks[0] ?? [];
    }

}