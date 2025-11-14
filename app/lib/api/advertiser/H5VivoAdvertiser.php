<?php

namespace app\lib\api\advertiser;
use app\model\admin\ForFlow;
use laytp\library\Http as HttpCurl;
use app\model\api\VivoConfig;
use app\model\api\VivoDataOrigin;
use app\model\api\ReceiveMonitorData;
use app\model\api\AdvertiserCallbackRecord;
use app\model\api\Channel;
use think\facade\Db;
use app\lib\api\http\Http;
//vivo广告主回传
class H5VivoAdvertiser
{
    use Http;
    private $apiVivoGetTokenUrl = 'https://marketing-api.vivo.com.cn/openapi/v1/oauth2/token';//获取tokenURL
    private $apiVivoRefreshTokenUrl = 'https://marketing-api.vivo.com.cn/openapi/v1/oauth2/refreshToken';//刷新tokenURL
    private $apiVivoUploadUrl = 'https://marketing-api.vivo.com.cn/openapi/v1/advertiser/behavior/upload';//上传用户行为URL

    //用户行为
    private $vivoCvType = [
        'active' => 'ACTIVATION', //激活
        'register' => 'REGISTER', //注册
        'submit' => 'SUBMIT', //表单提交
        'pay' => 'PAY', //应用付费
    ];
    //vivo数据回传
    public function h5VivoAdvertiseCollback($data = [])
    {
        try {
            $redis = get_redis();
            $redisKey = env('FLOW.H5_FLOW_COLLBACK_VIVO_PARAM_KEY') . $data['for_flow_id'];
            $redis->set($redisKey,json_encode($data));
            $isNeedPhone = ForFlow::where('id',$data['for_flow_id'])->value('is_need_phone');
            if($isNeedPhone == 1) {
                $data['dataType'] = 'submit';
                $channel = Channel::where('id', $data['channel'])->value('channel_name');
                $vivoDataOrigin = VivoDataOrigin::where('app_bundle_id', $channel)->find();
                if (!empty($vivoDataOrigin)) {
                    $vivoConfig = VivoConfig::where('id', $vivoDataOrigin['vid'])->find();
                    if (!empty($vivoConfig)) {
                        $formatData = $this->commonVivoFormat($data, $vivoDataOrigin, $vivoConfig);
                        $ret = json_decode($this->json_post($formatData['apiUrl'], $formatData['data']), true);
                        if ($ret['code'] == 0) {
                            $recordData = [
                                'channel' => 'vivo',
                                'channel_name' => $channel,
                                'app_bundle_id' => $data['app_bundle_id'] ?? '',
                                'cvType' => $data['dataType'],
                                'oaid' => $data['oaid'] ?? '',
                                'md5_oaid' => isset($data['oaid']) ? md5($data['oaid']) : '',
                                'oaid_two' => isset($data['oaid']) ? md5($data['oaid']) : '',
                                'ascribeType' => (isset($formatData['data']['dataList']['requestId']) && !empty($formatData['data']['dataList']['requestId']) && isset($formatData['data']['dataList']['creativeId']) && !empty($formatData['data']['dataList']['creativeId'])) ? 1 : 0,
                                'source' => $data['source'] ?? 1
                            ];
                            event('InsertRecordData', $recordData);
                        }
                        return $ret;
                    }
                }
            }
        }catch(\Exception $e){
           return;
        }
    }

    //vivo数据回传加微
    public function h5VivoAdvertiseQrcodeCollback($forFlowId = 0)
    {
        try {
            $redis = get_redis();
            $redisKey = env('FLOW.H5_FLOW_COLLBACK_VIVO_PARAM_KEY') . $forFlowId;
            $data = $redis->get($redisKey);
            if(!empty($data)) {
                $data = json_decode($data,true);
                $data['dataType'] = 'submit';
                $channel = Channel::where('id', $data['channel'])->value('channel_name');
                $vivoDataOrigin = VivoDataOrigin::where('app_bundle_id', $channel)->find();
                if (!empty($vivoDataOrigin)) {
                    $vivoConfig = VivoConfig::where('id', $vivoDataOrigin['vid'])->find();
                    if (!empty($vivoConfig)) {
                        $formatData = $this->commonVivoFormat($data, $vivoDataOrigin, $vivoConfig);
                        $ret = json_decode($this->json_post($formatData['apiUrl'], $formatData['data']), true);
                        if ($ret['code'] == 0) {
                            $recordData = [
                                'channel' => 'vivo',
                                'channel_name' => $channel,
                                'app_bundle_id' => $data['app_bundle_id'] ?? '',
                                'cvType' => $data['dataType'],
                                'oaid' => $data['oaid'] ?? '',
                                'md5_oaid' => isset($data['oaid']) ? md5($data['oaid']) : '',
                                'oaid_two' => isset($data['oaid']) ? md5($data['oaid']) : '',
                                'ascribeType' => (isset($formatData['data']['dataList']['requestId']) && !empty($formatData['data']['dataList']['requestId']) && isset($formatData['data']['dataList']['creativeId']) && !empty($formatData['data']['dataList']['creativeId'])) ? 1 : 0,
                                'source' => $data['source'] ?? 1
                            ];
                            event('InsertRecordData', $recordData);
                        }
                        return $ret;
                    }
                }
            }
        }catch(\Exception $e){
            return;
        }
    }

    //获取token
    public function getToken($config)
    {
        $apiParsms = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['secret'],
            'grant_type' => 'code',
            'code' => '08bde13ff44be39f80f78de1ed9fb93911e1bc249cf5d69ca8344e4500ea56d7',
        ];
        $urlParams = http_build_query($apiParsms);
        return HttpCurl::get($this->apiVivoGetTokenUrl .'?'. $urlParams);
    }
    //刷新token
    public function refreshToken($vivoConfig = [])
    {
        $apiParsms = [
            'client_id' => $vivoConfig['client_id'],
            'client_secret' => $vivoConfig['secret'],
            'refresh_token' => $vivoConfig['refresh_token'],
        ];
        $urlParams = http_build_query($apiParsms);
        $ret = json_decode(HttpCurl::get($this->apiVivoRefreshTokenUrl .'?'. $urlParams), true);
        if ($ret['code'] == 0) {
            $vivoConfig->access_token = $ret['data']['access_token'];
            $vivoConfig->refresh_token = $ret['data']['refresh_token'];
            $vivoConfig->token_date = bcdiv($ret['data']['token_date'], 1000, 0);
            $vivoConfig->refresh_token_date = bcdiv($ret['data']['refresh_token_date'], 1000, 0);
            $vivoConfig->save();
            return $ret['data']['access_token'];
        }
    }

    //组装请求参数
    public function commonVivoFormat($params = [], $vivoDataOrigin = [], $vivoConfig = [])
    {
        $timestamp = getMillisecond();
        $res = strpos($params['requestid'], '#');
        if($res){
            $params['requestid'] = substr($params['requestid'],0,strrpos($params['requestid'],"#"));
        }
        $data['srcType'] = 'web';
        $data['srcId'] = $vivoDataOrigin['src_id'];
        $data['pageUrl'] = env('FLOW.LINK');
        $data['dataList'] = [
            'cvType' => $this->vivoCvType[$params['dataType']],
            'cvTime' => $timestamp,
            'requestId' => $params['requestid'],
            'creativeId' => $params['adid'],
        ];
        $apiParsms = [
            'access_token' => $vivoConfig['access_token'],
            'timestamp' => $timestamp,
            'nonce' => createUniqueRandomStr(),
        ];
        $apiUrl = $this->apiVivoUploadUrl .'?'. http_build_query($apiParsms);
        $paramArr = compact('data', 'apiUrl');
        return $paramArr;
    }
}
