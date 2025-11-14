<?php

namespace app\lib\api\advertiser;

use app\model\api\AdvertiserCallbackRecord;
use app\model\api\TodayReceiveMonitorData;
use app\model\api\HuaweiToken;
use app\lib\api\http\Http;
use think\facade\Config;
use think\facade\Db;

//oppo广告主回传
class HuaweiAdvertiser
{
    use Http;

    //private $appId = '108502709';
    private $appIds = [
        'zy_ljgdyq_huawei' => 111075101,
        'zy_wwls_huawei' => 111075101,
        'zy_yqzwzx_huawei' => 111333031,
        'zy_bnclyq_huawei' => 111478803,
    ];
    //private $clientId = '1218835014674828736';
    private $clientIds = [
        'zy_ljgdyq_huawei'  => '1438398037184510272',
        'zy_wwls_huawei'    => '1438398037184510272',
        'zy_yqzwzx_huawei'  => '1461591138304536000',
        'zy_bnclyq_huawei'  => '1473934552081644160',
    ];
    //private $clientSecret = '4864EBBB739A62B01DEBA4F48949E9E5AE8EF3F1C078F0259FBADA00B97DC2B1';
    private $clientSecrets = [
        'zy_ljgdyq_huawei'  => '19F642D839986B562A4CE969E884502864F098AA569F9F1B03D7760B2AAC4835',
        'zy_wwls_huawei'    => '19F642D839986B562A4CE969E884502864F098AA569F9F1B03D7760B2AAC4835',
        'zy_yqzwzx_huawei'  => 'FA903E25C0D568ACFF69DF41A6A2CFAFF8836199FE59ABA683F77CE9B43E0B2F',
        'zy_bnclyq_huawei'  => '0CFD174AE5A2FE59335F48CB8A2DC0407C98AEBEE2AA7981E6B8D067A9408F60',
    ];
    private $apiTokenUrl = 'https://connect-api.cloud.huawei.com/api/oauth2/v1/token';
    private $apiHuaweiUrl = 'https://connect-api.cloud.huawei.com/api/datasource/v1/track/activate';

    //用户行为
    private $huaweiCvType = [
        'active' => 1,//激活
        'register' => 7,//注册
        'pay' => 4,//应用付费
        'submit' => 5,//应用付费
        'key_behavior' => 101//关键行为
    ];

    public function huaweiAdvertiserCallBack($data = [])
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
        if (!$checkCount) {
            $formatData = $this->commonHuaweiFormat($data);
            if(isset($formatData['data']['callBack']) && !empty($formatData['data']['callBack'])) {
                $ret = json_decode($this->json_post($this->apiHuaweiUrl, json_encode($formatData['data']), $formatData['header']), true);
                if (isset($ret['code']) && $ret['code'] == 0) {
                    $recordData = [
                        'channel' => 'huawei',
                        'channel_name' => $data['user']['channel'],
                        'app_bundle_id' => $data['user']['app_bundle_id'],
                        'cvType' => $data['dataType'],
                        'oaid' => $data['user']['oaid'],
                        'oaid_two' => $data['user']['oaid'],
                    ];
                    event('InsertRecordData', $recordData);
                }
            }else{
                $recordData = [
                    'channel' => 'huawei',
                    'channel_name' => $data['user']['channel'],
                    'app_bundle_id' => $data['user']['app_bundle_id'],
                    'cvType' => $data['dataType'],
                    'oaid' => $data['user']['oaid'],
                    'oaid_two' => $data['user']['oaid'],
                    'is_callback' => 0
                ];
                event('InsertRecordData', $recordData);
            }
            return $ret ?? [];
        }
    }

    //获取token
    public function getToken($channel)
    {
        $accessToken = '';
        $huaweiToken = HuaweiToken::where('channel',$channel)->find() ?? new HuaweiToken();
        if(!empty($huaweiToken)){
            if(!empty($huaweiToken) && !empty($huaweiToken->access_token) && time() - (int)$huaweiToken->create_time < $huaweiToken->expires_in){
                $accessToken = $huaweiToken->access_token;
            }
        }

        if(isset($this->clientIds[$channel]) && isset($this->clientSecrets[$channel])){
            $apiParsms = [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientIds[$channel],
                'client_secret' => $this->clientSecrets[$channel],
            ];
            $res = json_decode($this->json_post($this->apiTokenUrl, json_encode($apiParsms), []), true);
            $huaweiToken->access_token = $res['access_token'] ?? '';
            $huaweiToken->expires_in = $res['expires_in'] ?? '';
            $huaweiToken->create_time = time();$huaweiToken->save();
            $accessToken = $res['access_token'] ?? '';
        }
        return $accessToken;
    }

    //组装请求参数
    public function commonHuaweiFormat($params = [])
    {
        $timestamp = getMillisecond();
        $data['appId'] = $this->appIds[$params['user']['channel']] ?? '';
        $data['deviceIdType'] = 'OAID';
        $data['deviceId'] = $params['user']['oaid'];
        $data['actionType'] = $this->huaweiCvType[$params['dataType']];
        $data['actionTime'] = $timestamp;
        $data['callBack'] = '';
        $callback = TodayReceiveMonitorData::where('oaid', $params['user']['oaid'])
            ->where('channel', $params['user']['channel'])
            ->where('app_bundle_id', $params['user']['app_bundle_id'])
            ->order('id desc')
            ->value('callback');
        if ($callback) {
            $data['callBack'] = $callback;
        }
        $clientId = $this->clientIds[$params['user']['channel']] ?? '';
        //设置header头请求参数
        $header = [
            'client_id: ' . $clientId,
            'Authorization:Bearer ' . $this->getToken($params['user']['channel'])
        ];
        $paramArr = compact('data', 'header');
        return $paramArr;
    }

}