<?php

namespace app\lib\api\advertiser;

use app\model\api\AdvertiserCallbackRecord;
use think\facade\Config;
use think\facade\Event;
use think\facade\Db;
use app\lib\api\http\Http;
use app\model\api\Channel;
use app\model\admin\ForFlow;
use app\model\api\Thread;
use app\model\api\H5OppoAdvertiserCallback;

//oppo广告主回传
class H5OppoAdvertiser
{
    use Http;
    private $ownerId;
    private $apiId;
    private $apiKey;

    //private $apiOppoUrl = 'http://sapi.ads-test.wanyol.com/v1/clue/sendData';
    private $apiOppoUrl = 'https://sapi.ads.oppomobile.com/v1/clue/sendData';

    public function h5OppoAdvertiserCollback($data = [])
    {
        file_put_contents('h5_oppo412.txt',json_encode($data));
        $redis = get_redis();
        $redisKey = env('FLOW.H5_FLOW_COLLBACK_OPPO_PARAM_KEY') . $data['for_flow_id'];
        $redis->set($redisKey,json_encode($data));
        $isNeedPhone = ForFlow::where('id',$data['for_flow_id'])->value('is_need_phone');
        $threadInfo = Thread::where('flow_id',$data['for_flow_id'])->where('is_test',0)->order('id desc')->find();
        $lbid = $data['lbid'] ?? '';
        if(!empty($threadInfo)){
            H5OppoAdvertiserCallback::create([
                'thread_id' => $threadInfo->id,
                'for_flow_id' => $threadInfo->flow_id,
                'channel' => $threadInfo->channel,
                'pageId' => $data['pageId'] ?? '',
                'tid' => $data['tid'] ?? '',
                'lbid' => $lbid
            ]);
        }
        if($isNeedPhone == 1) {
            $dateYm = date('Ym');
            $tableName = "advertiser_callback_record_{$dateYm}";
            $tableNamePrefix = "lt_advertiser_callback_record_{$dateYm}";
            $table = Db::query('SHOW TABLES LIKE"' . $tableNamePrefix . '"');
            $data['dataType'] = 'submit';
            $data['channel'] = Channel::where('id', $data['channel'])->value('channel_name');
//            if ($table) {
//                $checkCount = Db::name($tableName)->where('channel', $data['channel'])
//                    ->where('cvType', $data['dataType'])
//                    ->count();
//            } else {
//                $checkCount = AdvertiserCallbackRecord::where('channel', $data['channel'])
//                    ->where('cvType', $data['dataType'])
//                    ->count();
//            }

            $limitKey = 'callback:oppo:h5:limitHash';
            if (! $redis->hGet($limitKey, $lbid)) {
                $formatData = $this->commonOppoFormat($data);
                $ret = json_decode($this->json_post($this->apiOppoUrl, json_encode($formatData['data']), $formatData['header']), true);
                if (isset($ret['code']) && $ret['code'] == 0) {
                    $redis->hSet($limitKey, $lbid, date('Y-m-d H:i:s'));
                    $recordData = [
                        'channel' => 'h5_oppo',
                        'channel_name' => $data['channel'],
                        'app_bundle_id' => $data['app_bundle_id'] ?? '',
                        'cvType' => 'submit',
                        'oaid' => $data['oaid'] ?? '',
                        'ascribeType' => isset($formatData['data']['ascribeType']) ? 1 : 0,
                        'source' => $data['source'] ?? 1,
                        'oaid_two' => $data['oaid'] ?? ''
                    ];
                    event('InsertRecordData', $recordData);
                }
                return $ret;
            }
        }
        return [];
    }

    public function h5OppoAdvertiserQrcodeCollback($forFlowId)
    {
        $redis = get_redis();
        $redisKey = env('FLOW.H5_FLOW_COLLBACK_OPPO_PARAM_KEY') . $forFlowId;
        $data = $redis->get($redisKey);
        if (!empty($data)) {
            $data = json_decode($data,true);
            $data['dataType'] = 'submit';
            $data['channel'] = Channel::where('id', $data['channel'])->value('channel_name');
            $formatData = $this->commonOppoFormat($data);
            $ret = json_decode($this->json_post($this->apiOppoUrl, json_encode($formatData['data']), $formatData['header']), true);
            if (isset($ret['code']) && $ret['code'] == 0) {
                $recordData = [
                    'channel' => 'h5_oppo',
                    'channel_name' => $data['channel'],
                    'app_bundle_id' => $data['app_bundle_id'] ?? '',
                    'cvType' => 'submit',
                    'oaid' => $data['oaid'] ?? '',
                    'ascribeType' => isset($formatData['data']['ascribeType']) ? 1 : 0,
                    'source' => $data['source'] ?? 1,
                    'oaid_two' => $data['oaid'] ?? ''
                ];
                event('InsertRecordData', $recordData);
            }
            return $ret;
        }
        return true;
    }

    public function h5OppoAdvertiserContractCollback($threadId)
    {
        $data = H5OppoAdvertiserCallback::where('thread_id',$threadId)->order('id desc')->find();
        if (!empty($data)) {
            $data['dataType'] = 'effective_consult';
            //$data['channel'] = Channel::where('channel', $data['channel'])->value('channel_name');
            $formatData = $this->commonOppoFormat($data);
            $ret = json_decode($this->json_post($this->apiOppoUrl, json_encode($formatData['data']), $formatData['header']), true);
            if (isset($ret['code']) && $ret['code'] == 0) {
                $recordData = [
                    'channel' => 'h5_oppo',
                    'channel_name' => $data['channel'],
                    'app_bundle_id' => $data['app_bundle_id'] ?? '',
                    'cvType' => 'effective_consult',
                    'oaid' => $data['oaid'] ?? '',
                    'ascribeType' => isset($formatData['data']['ascribeType']) ? 1 : 0,
                    'source' => $data['source'] ?? 1,
                    'oaid_two' => $data['oaid'] ?? ''
                ];
                event('InsertRecordData', $recordData);
            }
            return $ret;
        }
        return true;
    }

    //组装请求参数1
    public function commonOppoFormat($params = [])
    {
        $oppoConfig = config::load('extra/oppo','extra');
        $ownerInfo = $oppoConfig['h5OwnerIds'];
        if(isset($ownerInfo['flow_id_'.$params['for_flow_id']]) && !empty($ownerInfo['flow_id_'.$params['for_flow_id']]))
        {
            if(in_array($params['for_flow_id'],[112,113])) {
                $ownerId = $ownerInfo['flow_id_' . $params['for_flow_id']][$params['channel']]['ownerId'];
                $apiId = $ownerInfo['flow_id_' . $params['for_flow_id']][$params['channel']]['api_id'];
                $apiKey = $ownerInfo['flow_id_' . $params['for_flow_id']][$params['channel']]['api_key'];
            }else {
                $ownerId = $ownerInfo['flow_id_' . $params['for_flow_id']]['ownerId'];
                $apiId = $ownerInfo['flow_id_' . $params['for_flow_id']]['api_id'];
                $apiKey = $ownerInfo['flow_id_' . $params['for_flow_id']]['api_key'];
            }
        }else{
            if(isset($ownerInfo[$params['channel']]) && !empty($ownerInfo[$params['channel']]))
            {
                $ownerId = $ownerInfo[$params['channel']]['ownerId'];
                $apiId = $ownerInfo[$params['channel']]['api_id'];
                $apiKey = $ownerInfo[$params['channel']]['api_key'];
            }else {
                $ownerId = $this->ownerId;
                $apiId = $this->apiId;
                $apiKey = $this->apiKey;
            }
        }
        $timestamp = time();
        $sign = sha1($apiId.$apiKey.$timestamp);
        $token = base64_encode($ownerId.",".$apiId.",".$timestamp.",".$sign);
        $data['pageId'] = $params['pageId'];
        $data['ownerId'] = $ownerId;
        $data['ip'] = '127.0.0.1';
        $data['tid'] = $params['tid'];
        $data['lbid'] = $params['lbid'];
        $data['transformType'] = $params['dataType'] == 'effective_consult' ? 103 : 101;
        //设置header头请求参数
        $header = [
            'Content-Type:application/json',
            'Authorization:Bearer '.$token
        ];
        $paramArr = compact('data', 'header');
        return $paramArr;
    }

}