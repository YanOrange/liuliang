<?php

namespace app\lib\api\advertiser;

use app\model\api\Channel;
use laytp\library\Http;

//oppo广告主回传
class H5UcAdvertiser
{
    private $h5WebUrl = 'http://kh.hnqjdyf.com';
    private $apiUcUrl = 'https://huichuan.uc.cn/callback/webapi';

    public function h5UcAdvertiserCollback($data = [])
    {
        $data['dataType'] = 'submit';
        $data['channel'] = Channel::where('id',$data['channel'])->value('channel_name');
        $formatData = $this->curlGet($data);
        $ret = json_decode($formatData, true);
        if (isset($ret['status']) && $ret['status'] == 0) {
            $recordData = [
                'channel' => 'uc',
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

    //组装请求参数
    public function curlGet($params = [])
    {
        $timestamp = intval(getMillisecond());
        $res = strpos($params['uctrackid'], '#');
        if($res){
            $params['uctrackid'] = substr($params['uctrackid'],0,strrpos($params['uctrackid'],"#"));
        }
        $this->h5WebUrl = $params['link'] ?? $this->h5WebUrl;
        $eventType = $params['event_type'] ?? 5;
        $link = $this->apiUcUrl."?link={$this->h5WebUrl}?uctrackid={$params['uctrackid']}&event_type={$eventType}&event_time={$timestamp}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        return $output;
    }

}