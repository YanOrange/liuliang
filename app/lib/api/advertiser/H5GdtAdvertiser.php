<?php

namespace app\lib\api\advertiser;

use app\model\api\Channel;
use laytp\library\Http;
use app\lib\api\http\Http as HttpPost;
use app\model\api\Thread;
use app\model\api\TodayReceiveMonitorData;
use app\model\api\H5GdtAdvertiserCallback;

//oppo广告主回传
class H5GdtAdvertiser
{
    use HttpPost;
    private $h5WebUrl = 'flowh5.jiaozhidao.cc';
    private $apiUcUrl = 'http://tracking.e.qq.com/conv/web';

    public function h5GdtAdvertiserCollback($data = [])
    {
        $pathParams = 'clickid='.$data['qz_gdt'].'&action_time='.time().'&action_type=RESERVATION&link='.urlencode($this->h5WebUrl);
        $this->apiUcUrl = $this->apiUcUrl.'?'.$pathParams;
        $data['dataType'] = 'submit';
        $data['channel'] = Channel::where('id',$data['channel'])->value('channel_name');
        $threadInfo = Thread::where('flow_id',$data['for_flow_id'])->order('id desc')->find();
        H5GdtAdvertiserCallback::create([
            'thread_id' => $threadInfo->id,
            'channel' => $data['channel'],
            'clickid' => $data['qz_gdt'],
        ]);
        $ageRangeId = $threadInfo->age_range_id;
        if($ageRangeId > 1) {
            $http = new Http();
            $ret = json_decode($http->get($this->apiUcUrl), true);
            if (isset($ret['code']) && $ret['code'] == 0) {
                $recordData = [
                    'channel' => 'gdt',
                    'channel_name' => $data['channel'] ?? '',
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

    public function h5GdtAdvertiserOverdueCollback($data = [],$actionType = 'RESERVATION')
    {
        $gdtMonitorData = TodayReceiveMonitorData::where('channel',$data['channel'])->order('id desc')->find();
        $threadInfo = Thread::where('uid',$data['id'])->where('channel',$data['channel'])->order('id desc')->find();
        if(!empty($gdtMonitorData) && !empty($gdtMonitorData->callback)){
            $gdtUrl = urldecode($gdtMonitorData->callback);
            $header = [
                'Content-Type: application/json'
            ];
            $gdtData = [
                'actions' => [[
                    'action_time' => time(),
                    'action_type' => $actionType
                ]]
            ];
            $ret = json_decode(curlPost($gdtUrl, json_encode($gdtData), $header), true);
            if (isset($ret['code']) && $ret['code'] == 0) {
                $recordData = [
                    'channel' => 'gdt',
                    'channel_name' => $data['channel'] ?? '',
                    'app_bundle_id' => $data['app_bundle_id'] ?? '',
                    'cvType' => $actionType == 'SCANCODE' ? 'wechat' : 'submit',
                    'oaid' => $data['oaid'] ?? '',
                    'ascribeType' => isset($formatData['data']['ascribeType']) ? 1 : 0,
                    'source' => $data['source'] ?? 1,
                    'oaid_two' => $data['oaid'] ?? ''
                ];
                event('InsertRecordData', $recordData);
            }
            H5GdtAdvertiserCallback::create([
                'thread_id' => $threadInfo->id,
                'channel' => $data['channel'],
                'callbcak' => $gdtMonitorData->callback,
            ]);
            return $ret;
        }
    }

    public function h5GdtAdvertiserContractCollback($threadId)
    {
        $actionType = 'COMPLETE_ORDER';
        $data = H5GdtAdvertiserCallback::where('thread_id',$threadId)->order('id desc')->find();
        if (!empty($data)) {
            if(!empty($data['clickid'])){
                $pathParams = 'clickid='.$data['clickid'].'&action_time='.time().'&action_type='.$actionType.'&link='.urlencode($this->h5WebUrl);
                $this->apiUcUrl = $this->apiUcUrl.'?'.$pathParams;
                $data['dataType'] = 'order';
                $data['channel'] = Channel::where('id',$data['channel'])->value('channel_name');
                $http = new Http();
                $ret = json_decode($http->get($this->apiUcUrl), true);
                if (isset($ret['code']) && $ret['code'] == 0) {
                    $recordData = [
                        'channel' => 'gdt',
                        'channel_name' => $data['channel'] ?? '',
                        'app_bundle_id' => $data['app_bundle_id'] ?? '',
                        'cvType' => 'order',
                        'oaid' => $data['oaid'] ?? '',
                        'ascribeType' => isset($formatData['data']['ascribeType']) ? 1 : 0,
                        'source' => $data['source'] ?? 1,
                        'oaid_two' => $data['oaid'] ?? ''
                    ];
                    event('InsertRecordData', $recordData);
                }
                return $ret;
            }
            if(!empty($data['callback'])){
                $gdtUrl = urldecode($data['callback']);
                $header = [
                    'Content-Type: application/json'
                ];
                $gdtData = [
                    'actions' => [[
                        'action_time' => time(),
                        'action_type' => $actionType
                    ]]
                ];
                $ret = json_decode(curlPost($gdtUrl, json_encode($gdtData), $header), true);
                if (isset($ret['code']) && $ret['code'] == 0) {
                    $recordData = [
                        'channel' => 'gdt',
                        'channel_name' => $data['channel'] ?? '',
                        'app_bundle_id' => $data['app_bundle_id'] ?? '',
                        'cvType' => 'order',
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
        return true;
    }

    //组装请求参数
    public function commonOppoFormat($params = [])
    {
        $data['click_id'] = '5p76exycaaapu6n3tpqq';
        $data['click_time'] = time();
        $data['action_type'] = 'ACTIVATE_APP';
        $data['link'] = urlencode($this->h5WebUrl);
        return $data;
    }

}