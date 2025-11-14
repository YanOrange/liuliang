<?php

namespace app\lib\api\advertiser;

use app\lib\service\external\ZyxService;
use app\model\api\Channel;
use app\lib\api\http\Http;
use app\model\api\Thread;
use think\facade\Db;

//oppo广告主回传
class H5DouyinAdvertiser
{
    use Http;
    private $apiUcUrl = 'https://analytics.oceanengine.com/api/v2/conversion';

    public function h5DouyinAdvertiserCollback($data = [])
    {
        file_put_contents('h5_douyin409.txt',json_encode($data));
        $userInfo = Thread::with(['user' => function($query){
                $query->field('id,phone');
            }])
            ->where('flow_id',$data['for_flow_id'])
            ->where('channel_id',$data['channel'])
            ->field('id,uid')
            ->order('id desc')
            ->find();
        $phone = $userInfo->user->phone ?? '';
        $redis = get_redis();
        $redisKey = env('FLOW.H5_FLOW_COLLBACK_DOUYIN_PARAM_KEY')
            .$phone. $data['for_flow_id'];
        $redis->set($redisKey,json_encode($data));
        $expireTime = mktime('23',59,59, date('m'),date('d'),date('Y'));
        $redis->expireAt($redisKey, $expireTime);
        $data['channel'] = Channel::where('id', $data['channel'])->value('channel_name');
        $formatData = $this->commonFormat($data);
        $ret = json_decode(curlPost($this->apiUcUrl, json_encode($formatData['data']), $formatData['header']), true);
        if (isset($ret['code']) && $ret['code'] == 0) {
            $recordData = [
                'channel' => 'h5_douyin',
                'channel_name' => $data['channel'] ?? '',
                'app_bundle_id' => $data['app_bundle_id'] ?? '',
                'cvType' => 'submit',
                'oaid' => $data['oaid'] ?? '',
                'clickid' => $data['clickid'] ?? '',
                'ascribeType' => isset($formatData['data']['ascribeType']) ? 1 : 0,
                'source' => $data['source'] ?? 1,
                'oaid_two' => $data['oaid'] ?? ''
            ];
            event('InsertRecordData', $recordData);

            $redisRecordKey = 'callback:douyin:submitRecord';
            $redisField = $data['thread_id'] ?? date('Y-m-d H:i:s') . '_' . rand(10, 99);
            $redis->hSet($redisRecordKey, $redisField, json_encode($data));
        }
        return $ret;
    }

    public function h5DouyinAdvertiserQrcodeCollback($forFlowId,$phone = '')
    {
        $redis = get_redis();
        $redisKey = env('FLOW.H5_FLOW_COLLBACK_DOUYIN_PARAM_KEY') . $phone.$forFlowId;
        $data = json_decode($redis->get($redisKey),true);
        if(!empty($data)) {
            $data['channel'] = Channel::where('id', $data['channel'])->value('channel_name');
            $data['event_type'] = 'work_wechat_added';
            $formatData = $this->commonFormat($data);
            $ret = json_decode(curlPost($this->apiUcUrl, json_encode($formatData['data']), $formatData['header']), true);
            if (isset($ret['code']) && $ret['code'] == 0) {
                $recordData = [
                    'channel' => 'h5_douyin',
                    'channel_name' => $data['channel'] ?? '',
                    'app_bundle_id' => $data['app_bundle_id'] ?? '',
                    'cvType' => 'wechat',
                    'oaid' => $data['oaid'] ?? '',
                    'clickid' => $data['clickid'] ?? '',
                    'ascribeType' => isset($formatData['data']['ascribeType']) ? 1 : 0,
                    'source' => $data['source'] ?? 1,
                    'oaid_two' => $data['oaid'] ?? ''
                ];
                event('InsertRecordData', $recordData);
            }
            return $ret;
        }
    }

    # 高潜回传(成交回传)
    public function dealCallback()
    {
        $redis      = get_redis();
        $redisKey   = 'callback:douyin:dealThreadLimit:';

        $threadIds = ZyxService::getDealThreadIds([
            'merchant_id'   => '271,272',
            'source_id'     => 114,
            'start_time'    => time() - 3600,
            'end_time'      => time(),
        ]);

        $result = [];
        foreach ($threadIds as $threadId) {

            $key = $redisKey . $threadId;
            if ($redis->get($key)) {
                continue;
            }

            $redisRecordKey = 'callback:douyin:submitRecord';
            $requestInfo = json_decode($redis->hGet($redisRecordKey, $threadId), true);

            $clickid = $requestInfo['clickid'] ?? '';
            if (! $clickid) {
                continue;
            }

            $formatData = $this->commonFormat([
                'event_type' => 'clue_high_intention',
                'clickid' => $clickid
            ]);

            $ret = json_decode(curlPost($this->apiUcUrl, json_encode($formatData['data']), $formatData['header']), true);
            if (isset($ret['code']) && $ret['code'] == 0) {
                $redis->setex($key, 86400 * 5,  json_encode(array_merge($formatData, ['result' => $ret, 'time' =>time()])));
                $result[] = $threadId;
            }
        }
        return $result;
    }

    // 组装请求参数
    public function commonFormat($params = [])
    {
        $data = [
            'event_type'    => $params['event_type'] ?? 'form',
            'context'       => [
                'ad' => [
                    'callback' => $params['clickid'] ?? '',
                    'match_type' => 0
                ],
            ],
            'timestamp' => getMillisecond()
        ];

        //设置header头请求参数
        $header = [
            'Content-Type : application/json'
        ];

        file_put_contents('h5_douyin_param.txt',json_encode($data).date('Y-m-d H:i:s')."\r\n",FILE_APPEND);

        return compact('data', 'header');
    }

}