<?php

namespace app\lib\api\plan;

use app\model\api\VivoConfig;
use app\model\api\VivoDataOrigin;
use app\lib\api\http\Http;
use laytp\library\Http as HttpCurl;
use think\facade\Cache;

class VivoPlanDetailApi
{
    use Http;

    protected $apiRefreshTokenUrl = 'https://marketing-api.vivo.com.cn/openapi/v1/oauth2/refreshToken';//刷新tokenURL
    protected $apiPlanDetailQueryUrl = 'https://marketing-api.vivo.com.cn/openapi/v1/adstatement/summary/query';//查询广告效果数据

    //包名
    protected $appBundleIds = [
        'com.example.businessvideotwo',
        'com.example.businessquxue',
        'com.houhan.suxuepy',
        'com.xuao.suxue',
        'com.dashugan.kuaixueps',
        'com.yuluojishu.kuaixue',
        'com.yuluojishu.lexue',
        'com.houhan.quxuepr',
        'com.dashugan.kuaixuepr',
        'com.dazhiya.kaidian'
    ];

    public function getVivoPlanDetail($app_bundle_id,$days,$lastId,$type = 'HOUR')
    {
        $data = [];
        $vivoDataOrigin = VivoDataOrigin::where('app_bundle_id', $app_bundle_id)->find();
        if(!empty($vivoDataOrigin)){
            $vivoConfig = VivoConfig::where('id',$vivoDataOrigin['vid'])->find();
            if(!empty($vivoConfig)){
                $formatData = $this->commonVivoFormat($app_bundle_id,$days,$vivoConfig,$lastId,$type);
                $data = json_decode($this->json_post($formatData['apiUrl'], $formatData['data']), true);
            }
        }
        return $data;
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
        $ret = json_decode(HttpCurl::get($this->apiRefreshTokenUrl .'?'. $urlParams), true);
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
        if ($vivoConfig['token_date'] - time() < 10) {
            return $this->refreshToken($vivoConfig);
        }
        return $vivoConfig['access_token'];
    }

    //组装请求参数
    public function commonVivoFormat($appBundleId,$days = 1,$vivoConfig = [],$lastId = '',$type = 'HOUR')
    {
        if($type == 'HOUR') {
            $vivoPlanHour = Cache::get('vivo_plan_hour');
            if ($vivoPlanHour) {
                $vivoPlanHourArr = json_decode($vivoPlanHour, true);
                if ($vivoPlanHourArr['timestamp'] + 250 > time() && $vivoPlanHourArr['hour'] == '00') {
                    $startDate = $endDate = date('Ymd', strtotime('-' . $days . ' day'));
                } else {
                    $startDate = $endDate = date('Ymd');
                }
            } else {
                $startDate = $endDate = date('Ymd');
            }
        }else{
            $startDate = $endDate = date('Ymd', strtotime('-' . $days . ' day'));
        }
        $timestamp = getMillisecond();
        $data['startDate'] = $startDate;
        $data['endDate'] = $endDate;
        if(!empty($lastId)){
            $data['lastId'] = $lastId;
        }
        $data['pageSize'] = 200;
        $data['summaryType'] = $type;
        $data['level'] = 'CREATIVE';
        if($appBundleId == 'yqh5_vivo' || $appBundleId == 'yqh5_vivo2_com'){
            $token = $vivoConfig['access_token'];
        }else{
            $token = $this->getExpToken($vivoConfig);
        }
        $apiParsms = [
            'access_token' => $token,
            'timestamp' => $timestamp,
            'nonce' => createUniqueRandomStr(),
        ];
        $apiUrl = $this->apiPlanDetailQueryUrl .'?'. http_build_query($apiParsms);
        $paramArr = compact('data', 'apiUrl');
        return $paramArr;
    }
}