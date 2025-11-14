<?php

namespace app\lib\api\plan;

use app\model\api\VivoConfig;
use app\model\api\VivoDataOrigin;
use laytp\library\Http as HttpCurl;
use think\facade\Cache;
use app\lib\api\http\Http;

class VivoPlanFinanceQuery
{
    use Http;
    protected $apiRefreshTokenUrl = 'https://marketing-api.vivo.com.cn/openapi/v1/oauth2/refreshToken';//刷新tokenURL
    protected $apiFinanceQueryUrl = 'https://marketing-api.vivo.com.cn/openapi/v1/finance/funds/queryFounds';//账号余额

    public function getVivoAmountQuery($app_bundle_id,$advertiserId,$days)
    {
        $vivoDataOrigin = VivoDataOrigin::where('app_bundle_id', $app_bundle_id)->find();
        if(!empty($vivoDataOrigin)){
            if($app_bundle_id == 'yqh5_vivo') {
                $vivoConfig = VivoConfig::where('id',69)->find();
            }else if($app_bundle_id == 'yqh5_vivo2_com'){
                $vivoConfig = VivoConfig::where('id',67)->find();
            }else if($app_bundle_id == 'yqh5_vivo3'){
                $vivoConfig = VivoConfig::where('id',68)->find();
            }else{
                $vivoConfig = VivoConfig::where('id',$vivoDataOrigin['vid'])->find();
            }
            if(!empty($vivoConfig)){
                $formatData = $this->commonVivoFormat($vivoConfig,$advertiserId,$days);
                $data = json_decode($this->json_post($formatData['apiUrl'], json_encode($formatData['data'])), true);
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
        $ret = json_decode(HttpCurl::get($this->apiFinanceQueryUrl .'?'. $urlParams), true);
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
    public function commonVivoFormat($vivoConfig = [],$advertiserId = '',$days = 1)
    {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d',strtotime('-'.$days.' day'));
        $timestamp = getMillisecond();
        $data['type'] = 'all';
        $data['startDate'] = $startDate;
        $data['endDate'] = $endDate;
        $data['pageSize'] = 100;
        if($advertiserId == '0b61705fdef2cedbc401' || $advertiserId == 'a3b3a0db7b25abb9eac2' || $advertiserId == 'eda536f5643c1d48345b'){
            $token = $vivoConfig['access_token'];
        }else{
            $token = $this->getExpToken($vivoConfig);
        }
        $apiParsms = [
            'access_token' => $token,
            'timestamp' => $timestamp,
            'nonce' => createUniqueRandomStr(),
            'advertiser_id' => $advertiserId
        ];
        $apiUrl = $this->apiFinanceQueryUrl .'?'. http_build_query($apiParsms);
        $paramArr = compact('data', 'apiUrl');
        return $paramArr;
    }
}