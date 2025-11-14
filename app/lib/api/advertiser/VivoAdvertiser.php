<?php

namespace app\lib\api\advertiser;
use laytp\library\Http as HttpCurl;
use app\model\api\VivoConfig;
use app\model\api\VivoDataOrigin;
use app\model\api\ReceiveMonitorData;
use app\model\api\AdvertiserCallbackRecord;
use think\facade\Db;
//vivo广告主回传
trait VivoAdvertiser
{

    private $apiVivoGetTokenUrl = 'https://marketing-api.vivo.com.cn/openapi/v1/oauth2/token';//获取tokenURL
    private $apiVivoRefreshTokenUrl = 'https://marketing-api.vivo.com.cn/openapi/v1/oauth2/refreshToken';//刷新tokenURL
    private $apiVivoUploadUrl = 'https://marketing-api.vivo.com.cn/openapi/v1/advertiser/behavior/upload';//上传用户行为URL
    //用户行为
    private $vivoCvType = [
        'active' => 'ACTIVATION', //激活
        'register' => 'REGISTER', //注册
        'submit' => 'SUBMIT', //表单提交
        'pay' => 'PAY', //应用付费
        'other' => 'OTHER', //其他
    ];
    //vivo数据回传
    public function vivoAdvertiserCallBack($data = [])
    {
        $dateYm = date('Ym');
        $tableName = "advertiser_callback_record_{$dateYm}";
        $tableNamePrefix = "lt_advertiser_callback_record_{$dateYm}";
        $table = Db::query('SHOW TABLES LIKE"'.$tableNamePrefix.'"');
        if($table){
            $checkCount = Db::name($tableName)->where('oaid', $data['user']['oaid'])
                ->where('app_bundle_id',$data['user']['app_bundle_id'])
                ->where('cvType', $data['dataType'])
                ->count();
        }else {
            $checkCount = AdvertiserCallbackRecord::where('oaid', $data['user']['oaid'])
                ->where('app_bundle_id', $data['user']['app_bundle_id'])
                ->where('cvType', $data['dataType'])
                ->count();
        }
        if($data['user']['channel'] == 'lmgdyq_nubiya'){
            $recordData = [
                'channel' => 'vivo',
                'channel_name' => $data['user']['channel'],
                'app_bundle_id' => $data['user']['app_bundle_id'],
                'cvType' => $data['dataType'],
                'oaid' => $data['user']['oaid'],
                'md5_oaid' => md5($data['user']['oaid']),
                'oaid_two' => md5($data['user']['oaid']),
                'ascribeType' => (isset($formatData['data']['dataList']['requestId']) && !empty($data['data']['dataList']['requestId'])) ? 1 : 0,
                'source' => $data['source'] ?? 1,
                'is_callback' => 0
            ];
            event('InsertRecordData', $recordData);
            return;
        }
        if (!$checkCount) {
            $vivoDataOrigin = VivoDataOrigin::where('app_bundle_id', $data['user']['app_bundle_id'])->find();
            if (!empty($vivoDataOrigin)) {
                $vivoConfig = VivoConfig::where('id', $vivoDataOrigin['vid'])->find();
                if (!empty($vivoConfig)) {
                    $formatData = $this->commonVivoFormat($data, $vivoDataOrigin, $vivoConfig);
                    $ret = json_decode($this->json_post($formatData['apiUrl'], $formatData['data']), true);
                    if ($ret['code'] == 0) {
//                        AdvertiserCallbackRecord::create([
//                            'channel' => 'vivo',
//                            'channel_name' => $data['user']['channel'],
//                            'app_bundle_id' => $data['user']['app_bundle_id'],
//                            'cvType' => $data['dataType'],
//                            'oaid' => $data['user']['oaid'],
//                            'md5_oaid' => md5($data['user']['oaid']),
//                            'oaid_two' => md5($data['user']['oaid']),
//                            'ascribeType' => (isset($formatData['data']['dataList']['requestId']) && !empty($formatData['data']['dataList']['requestId']) && isset($formatData['data']['dataList']['creativeId']) && !empty($formatData['data']['dataList']['creativeId'])) ? 1 : 0,
//                            'source' => $data['source'] ?? 1
//                        ]);
                        $recordData = [
                            'channel' => 'vivo',
                            'channel_name' => $data['user']['channel'],
                            'app_bundle_id' => $data['user']['app_bundle_id'],
                            'cvType' => $data['dataType'],
                            'oaid' => $data['user']['oaid'],
                            'md5_oaid' => md5($data['user']['oaid']),
                            'oaid_two' => md5($data['user']['oaid']),
                            'ascribeType' => (isset($formatData['data']['dataList']['requestId']) && !empty($formatData['data']['dataList']['requestId']) && isset($formatData['data']['dataList']['creativeId']) && !empty($formatData['data']['dataList']['creativeId'])) ? 1 : 0,
                            'source' => $data['source'] ?? 1
                        ];
                        event('InsertRecordData', $recordData);
                    }
                    return $ret;
                }
            }
        }
    }
    //获取token
    public function getToken($config)
    {
        $apiParsms = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['secret'],
            'grant_type' => 'code',
            'code' => '0cde3e717bfe057f8e1e081d4a3e7df40392b2786050dcbd101a1fa6d5c43fde',
        ];
        $urlParams = http_build_query($apiParsms);
        $ret = json_decode(HttpCurl::get($this->apiVivoGetTokenUrl .'?'. $urlParams), true);
        if ($ret['code'] == 0) {
            $config->access_token = $ret['data']['access_token'];
            $config->refresh_token = $ret['data']['refresh_token'];
            $config->token_date = bcdiv($ret['data']['token_date'], 1000, 0);
            $config->refresh_token_date = bcdiv($ret['data']['refresh_token_date'], 1000, 0);
            $config->save();
            return $ret['data']['access_token'];
        }
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

    //获取有效token
    public function getExpToken($vivoConfig = [])
    {
        if (!$vivoConfig['access_token']) {
            return $this->getToken($vivoConfig);
        }
        if ($vivoConfig['token_date'] - time() < 10) {
            return $this->refreshToken($vivoConfig);
        }
        return $vivoConfig['access_token'];
    }
    //组装请求参数
    public function commonVivoFormat($params = [], $vivoDataOrigin = [], $vivoConfig = [])
    {
        $timestamp = getMillisecond();
        $data['srcType'] = 'APP';
        $data['pkgName'] = $params['user']['app_bundle_id'];
        $data['srcId'] = $vivoDataOrigin['src_id'];
        $data['dataList'] = [
            'userIdType' => 'OAID_MD5',
            'userId' => md5($params['user']['oaid']),
            'cvType' => $this->vivoCvType[$params['dataType']],
            'cvTime' => $timestamp,
            'cvCustom' => $params['user']['channel']
        ];
        $vivoData = ReceiveMonitorData::where('oaid',md5($params['user']['oaid']))
            ->where('channel',$params['user']['channel'])
            ->where('app_bundle_id',$params['user']['app_bundle_id'])
            ->field('request_id,creative_id')
            ->find();
        if($vivoData['request_id'] && $vivoData['creative_id']){
            $data['dataList']['requestId'] = $vivoData['request_id'];
            $data['dataList']['creativeId'] = $vivoData['creative_id'];
        }
        $token = $this->getExpToken($vivoConfig);
        $apiParsms = [
            'access_token' => $token,
            'timestamp' => $timestamp,
            'nonce' => createUniqueRandomStr(),
        ];
        $apiUrl = $this->apiVivoUploadUrl .'?'. http_build_query($apiParsms);
        $paramArr = compact('data', 'apiUrl');
        return $paramArr;
    }
}
