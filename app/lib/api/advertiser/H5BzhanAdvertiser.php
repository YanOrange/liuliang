<?php

namespace app\lib\api\advertiser;

use app\model\api\Channel;
use app\lib\api\http\Http;
use app\model\api\Thread;
use think\facade\Db;

//oppo广告主回传
class H5BzhanAdvertiser
{
    use Http;
    public $apiUcUrl = 'https://cm.bilibili.com/conv/api/conversion/ad/cb/v1';

    public function callback($data = [])
    {
        file_put_contents('h5_bzhan731.txt', json_encode($data));

        $userInfo = Thread::with(['user' => function ($query) {
            $query->field('id,phone');
        }])
            ->where('flow_id', $data['for_flow_id'])
            ->where('channel_id', $data['channel'])
            ->field('id,uid')
            ->order('id desc')
            ->find();
        $phone = $userInfo->user->phone ?? '';
        $redis = get_redis();
        $redisKey = "h5_flow_callback_bzhan_param_{$phone}_{$data['for_flow_id']}";
        if ( $redis->get($redisKey) ) {
            return '重复回传';
        }
        $redis->set($redisKey, json_encode($data));
        $expireTime = mktime('23', 59, 59, date('m'), date('d'), date('Y'));
        $redis->expireAt($redisKey, $expireTime);
        $data['channel'] = Channel::where('id', $data['channel'])->value('channel_name');
        $formatData = $this->commonFormat($data);
        $ret = json_decode(file_get_contents($this->apiUcUrl . '?' . http_build_query($formatData['data'])), true);
        if (isset($ret['code']) && $ret['code'] == 0) {
            $recordData = [
                'channel' => 'h5_bzhan',
                'channel_name' => $data['channel'] ?? '',
                'app_bundle_id' => $data['app_bundle_id'] ?? '',
                'cvType' => 'submit',
                'oaid' => $data['oaid'] ?? '',
                'clickid' => $data['track_id'] ?? '',
                'ascribeType' => isset($formatData['data']['ascribeType']) ? 1 : 0,
                'source' => $data['source'] ?? 1,
                'oaid_two' => $data['oaid'] ?? ''
            ];
            event('InsertRecordData', $recordData);
        }
       return $ret;
    }

    // 组装请求参数
    public function commonFormat($params = [])
    {
        $data = [
            'conv_type' => $params['conv_type'] ?? 'FORM_SUBMIT',
            'track_id'  => $params['track_id'],
            'conv_time' => getMillisecond()
        ];

        //设置header头请求参数
        $header = [
            'Content-Type : application/json'
        ];

        file_put_contents('h5_bzhan_param.txt', json_encode($data) . date('Y-m-d H:i:s') . "\r\n", FILE_APPEND);

        return compact('data', 'header');
    }

}