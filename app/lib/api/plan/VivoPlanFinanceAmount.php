<?php

namespace app\lib\api\plan;

use app\model\api\VivoConfig;
use app\model\api\VivoDataOrigin;
use laytp\library\Http as HttpCurl;
use think\facade\Cache;
use laytp\library\Http;

class VivoPlanFinanceAmount
{
    protected $apiRefreshTokenUrl = 'https://marketing-api.vivo.com.cn/openapi/v1/oauth2/refreshToken';//刷新tokenURL
    protected $apiAmountUrl = 'https://marketing-api.vivo.com.cn/openapi/v1/account/fetch/account';//账号余额

    public function getVivoAmountAmount($app_bundle_id,$advertiserId)
    {
        $vivoDataOrigin = VivoDataOrigin::where('app_bundle_id', $app_bundle_id)->find();
        if(!empty($vivoDataOrigin)){
            $vivoConfig = VivoConfig::where('id',$vivoDataOrigin['vid'])->find();
            if(!empty($vivoConfig)){
                $formatData = $this->commonVivoFormat($vivoConfig,$advertiserId);
                $data = json_decode(Http::get($formatData['apiUrl'], $formatData['data']), true);
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
        $ret = json_decode(HttpCurl::get($this->apiAmountUrl .'?'. $urlParams), true);
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
        $timestamp = getMillisecond();
        $data = [];
        $token = $this->getExpToken($vivoConfig);
        $apiParsms = [
            'access_token' => $token,
            'timestamp' => $timestamp,
            'nonce' => createUniqueRandomStr(),
            'advertiser_id' => $advertiserId
        ];
        $apiUrl = $this->apiAmountUrl .'?'. http_build_query($apiParsms);
        $paramArr = compact('data', 'apiUrl');
        return $paramArr;
    }
}