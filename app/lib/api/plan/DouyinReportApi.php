<?php

namespace app\lib\api\plan;

use think\facade\Cache;
use app\lib\api\http\Http as HttpRequest;
class DouyinReportApi
{
    use HttpRequest;
    protected $advertiserId = '1750731839891469';
    protected $accessTokenUrl = 'https://ad.oceanengine.com/open_api/oauth2/access_token/';
    protected $refreshTokenUrl = 'https://ad.oceanengine.com/open_api/oauth2/refresh_token/';
    protected $reportUrl = 'https://ad.oceanengine.com/open_api/2/report/advertiser/get/';
    protected $billReportUrl = 'https://ad.oceanengine.com/open_api/2/advertiser/fund/daily_stat/';
    protected $adReportUrl = 'https://ad.oceanengine.com/open_api/2/report/ad/get/';

    /**
     * Send GET request
     * @param $json_str : Args in JSON format
     * @return bool|string : Response in JSON format
     */
    public function getDouyinReport($days = 1)
    {
        $data = [];
        $data['end_date'] = date('Y-m-d',strtotime("-{$days} day"));
        $data['start_date'] = date('Y-m-d',strtotime("-{$days} day"));
        $data['advertiser_id'] = $this->advertiserId;
        $data['group_by_list'] = ['STAT_GROUP_BY_FIELD_STAT_TIME'];
        $data['group_by'] = json_encode($data['group_by_list']);
        $data['time_granularity'] = 'STAT_TIME_GRANULARITY_DAILY';
        $data['page'] = 1;
        $data['page_size'] = 20;
        $data['remark'] = '计划消耗';

        $my_args = sprintf(json_encode($data));
        $accessToken = $this->getAccessToken();
        $PATH = $this->reportUrl;
        $curl = curl_init();

        $args = json_decode($my_args, true);

        foreach ($args as $key => $value) {
            $args[$key] = is_string($value) ? $value : json_encode($value);
        }

        $url = $PATH . "?" . http_build_query($args);

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Access-Token: " . $accessToken,
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    //查询账户日流水
    public function getDouyinDailyBill($days)
    {
        $data = [];
        $data['end_date'] = date('Y-m-d',strtotime("-{$days} day"));
        $data['start_date'] = date('Y-m-d',strtotime("-{$days} day"));
        $data['advertiser_id'] = $this->advertiserId;

        $my_args = sprintf(json_encode($data));
        $accessToken = $this->getAccessToken();
        $PATH = $this->billReportUrl;
        $curl = curl_init();

        $args = json_decode($my_args, true);

        foreach ($args as $key => $value) {
            $args[$key] = is_string($value) ? $value : json_encode($value);
        }

        $url = $PATH . "?" . http_build_query($args);

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Access-Token: " . $accessToken,
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    //查询账户日流水
    public function getDouyinAdReport($days)
    {
        $data = [];
        $data['end_date'] = date('Y-m-d',strtotime("-{$days} day"));
        $data['start_date'] = date('Y-m-d',strtotime("-{$days} day"));
        $data['advertiser_id'] = $this->advertiserId;
        $data['group_by'] = json_encode(['STAT_GROUP_BY_FIELD_STAT_TIME','STAT_GROUP_BY_FIELD_ID']);

        $my_args = sprintf(json_encode($data));
        $accessToken = $this->getAccessToken();
        $PATH = $this->adReportUrl;
        $curl = curl_init();

        $args = json_decode($my_args, true);

        foreach ($args as $key => $value) {
            $args[$key] = is_string($value) ? $value : json_encode($value);
        }

        $url = $PATH . "?" . http_build_query($args);

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Access-Token: " . $accessToken,
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    //获取accesstoken
    public function getAccessToken()
    {
        $accessToken = Cache::get('douyin_access_token');
        if(!empty($accessToken)){
            return $accessToken;
        }else{
            $res = $this->refreshToken();
            $res = json_decode($res,true);
            if($res['code'] == 0){
                Cache::set('douyin_access_token',$res['data']['access_token'],$res['data']['expires_in'] - 400);
                Cache::set('douyin_refresh_token',$res['data']['refresh_token'],$res['data']['refresh_token_expires_in'] - 400);
            }
            return $res['data']['access_token'];
        }
    }

    public function refreshToken()
    {
        $refreshToken = Cache::get('douyin_refresh_token');
        $header = [
            'Content-Type' => 'application/json'
        ];
        $params = [
            "app_id" => "1752086152545320",
            "secret" => "b375730e90c1cbcc2624c2652100b72cac3a4b72",
            "grant_type" => "auth_code",
            "refresh_token" => $refreshToken
        ];
        $res = self::json_post($this->refreshTokenUrl,$params,$header);
        return $res;
    }
}