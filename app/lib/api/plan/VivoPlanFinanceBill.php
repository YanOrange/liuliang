<?php

namespace app\lib\api\plan;

use app\model\api\VivoConfig;
use app\model\api\VivoDataOrigin;
use app\lib\api\http\Http;
use laytp\library\Http as HttpCurl;
use think\facade\Cache;

class VivoPlanFinanceBill
{
    use Http;

    protected $apiRefreshTokenUrl = 'https://marketing-api.vivo.com.cn/openapi/v1/oauth2/refreshToken';//刷新tokenURL
    protected $apiAmountBillUrl = 'https://marketing-api.vivo.com.cn/openapi/v1/finance/transfer/queryTransfers';//二代账号流水

    public function getVivoAmountBill($app_bundle_id,$days = 1)
    {
        $vivoDataOrigin = VivoDataOrigin::where('app_bundle_id', $app_bundle_id)->find();
        if(!empty($vivoDataOrigin)){
            $vivoConfig = VivoConfig::where('id',$vivoDataOrigin['vid'])->find();
            $advertiserId= $vivoConfig['advertiser_id'];
            if(!empty($vivoConfig)){
                $formatData = $this->commonVivoFormat($vivoConfig,$advertiserId,$days);
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
    public function commonVivoFormat($vivoConfig = [],$advertiserId = '',$days = 1)
    {
        $startDate = $endDate = date('Y-m-d',strtotime('-'.$days.' day'));
        $timestamp = getMillisecond();
        $data['startDate'] = $startDate;
        $data['endDate'] = $endDate;
        $data['pageSize'] = 100;
        $token = $this->getExpToken($vivoConfig);
        $apiParsms = [
            'access_token' => $token,
            'timestamp' => $timestamp,
            'nonce' => createUniqueRandomStr(),
            'advertiser_id' => $advertiserId
        ];
        $apiUrl = $this->apiAmountBillUrl .'?'. http_build_query($apiParsms);
        $paramArr = compact('data', 'apiUrl');
        return $paramArr;
    }
}