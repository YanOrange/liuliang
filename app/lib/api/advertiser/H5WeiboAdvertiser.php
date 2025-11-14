<?php

namespace app\lib\api\advertiser;

use app\model\api\Channel;
use app\lib\api\http\Http;
use app\model\api\WeiboConfig;

//360广告主回传
class H5WeiboAdvertiser
{
    private $AppId = '20230325619835100';
    private $SecretKey = '92ac4785ffc469d2f3139802cc1c41a';
    private $webUrl = 'flowh5.xuaoshangwu.com';
    private $apiWeiboCallbackUrl = 'https://api.biz.weibo.com/v3/track/activate';
    private $apiWeiboRefreshTokenUrl = 'https://api.biz.weibo.com/oauth/token';

    public function h5WeiboAdvertiserCollback($data = [])
    {
        $redis = get_redis();
        $redisKey = env('FLOW.H5_FLOW_COLLBACK_WEIBO_PARAM_KEY') . $data['for_flow_id'];
        $redis->set($redisKey,json_encode($data));
        $data['dataType'] = 'submit';
        $data['channel'] = Channel::where('id',$data['channel'])->value('channel_name');
        $WeiboData = WeiboConfig::where('channel', $data['channel'])->find();
        $token = $this->getExpToken($WeiboData);
        if(!empty($WeiboData)) {
            $formatData = $this->commonOppoFormat($data,$WeiboData);
            $formatData = $this->curlGet($this->apiWeiboCallbackUrl,$formatData['data'], $formatData['header']);
            $ret = json_decode($formatData, true);
            if (isset($ret['code']) && $ret['code'] == 0) {
                $recordData = [
                    'channel' => 'weibo',
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

    public function h5WeiboAdvertiserQrcodeCollback($forFlowId)
    {
        $redis = get_redis();
        $redisKey = env('FLOW.H5_FLOW_COLLBACK_WEIBO_PARAM_KEY') . $forFlowId;
        $data = $redis->get($redisKey);
        if (!empty($data)) {
            $data = json_decode($data,true);
            $data['dataType'] = 'wechat';
            $data['channel'] = Channel::where('id', $data['channel'])->value('channel_name');
            $WeiboData = WeiboConfig::where('channel', $data['channel'])->find();
            if (!empty($WeiboData)) {
                $formatData = $this->commonOppoFormat($data, $WeiboData);
                $formatData = $this->curlGet($this->apiWeiboCallbackUrl, $formatData['data'], $formatData['header']);
                $ret = json_decode($formatData, true);
                if (isset($ret['code']) && $ret['code'] == 0) {
                    $recordData = [
                        'channel' => 'weibo',
                        'channel_name' => $data['channel'],
                        'app_bundle_id' => $data['app_bundle_id'] ?? '',
                        'cvType' => 'wechat',
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
    }

    //刷新token
    public function refreshToken($weiboConfig = [])
    {
        $apiParsms = [
            'client_id' => $weiboConfig['app_id'],
            'grant_type' => 'refresh_token',
            'refresh_token' => $weiboConfig['refresh_token'],
        ];
        //$urlParams = http_build_query($apiParsms);
        $ret = json_decode($this->curlGet($this->apiWeiboRefreshTokenUrl,$apiParsms), true);
        if (isset($ret['access_token']) && !empty($ret['access_token'])) {
            $weiboConfig->access_token = $ret['access_token'];
            $weiboConfig->token_expires_in = time() + $ret['expires_in'];
            $weiboConfig->save();
            return $ret['access_token'];
        }
    }

    //获取有效token
    public function getExpToken($weiboConfig = [])
    {
        if ($weiboConfig['token_expires_in'] - time() < 100) {
            return $this->refreshToken($weiboConfig);
        }
        return $weiboConfig['access_token'];
    }

    //组装请求参数1
    public function commonOppoFormat($params = [],$WeiboData)
    {
        $TOKEN = $this->getExpToken($WeiboData);
        $time = getMillisecond();
        $data = [
            'time' => (int)$time,
            'behavior' => $params['dataType'] == 'wechat' ? 1004 : 1001,
            'mark_id' => $params['mark_id'] ?? '',
            'host' => $this->webUrl
        ];

        //设置header头请求参数
        $header = [
            "Authorization: Bearer {$TOKEN}",
            "Accept: application/json,application/text+gw2.0"
        ];
        $paramArr = compact('data', 'header');
        return $paramArr;
    }

    //组装请求参数
    public function curlGet($url,$params = [],$header=[])
    {
        $urlParams = http_build_query($params);
        $link = $url .'?'. $urlParams;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        return $output;
    }
}