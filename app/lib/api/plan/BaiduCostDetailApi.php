<?php

namespace app\lib\api\plan;

use app\model\api\VivoConfig;
use app\model\api\VivoDataOrigin;
use laytp\library\Http as HttpCurl;
use app\lib\api\http\Http;
use think\facade\Cache;

class BaiduCostDetailApi
{
    protected $apiRefreshTokenUrl = 'https://u.baidu.com/oauth/refreshToken';//刷新tokenURL
    protected $apiReportDataUrl = 'https://api.baidu.com/json/sms/service/OpenApiReportService/getReportData';//查询广告效果数据
    protected $accessToken = 'eyJhbGciOiJIUzM4NCJ9.eyJzdWIiOiJhY2MiLCJhdWQiOiLmlbDmja7miqXlkYrmiZPpgJrlupTnlKgiLCJ1aWQiOjQ5Mzg3MzcyLCJhcHBJZCI6ImI5ZmU5ODVjMzljMWJkNWE3NzNiZGM2ODBjYzJiNzJmIiwiaXNzIjoi5ZWG5Lia5byA5Y-R6ICF5Lit5b-DIiwicGxhdGZvcm1JZCI6IjQ5NjAzNDU5NjU5NTg1NjE3OTQiLCJleHAiOjQxMDI0MTYwMDAsImp0aSI6Ijc3NzE1NTAzMjM0NjkzOTM5MzkifQ.iK949SQaUkuxyHUWC7-VvgbE-w4mQFIvQacikW2dEwHs1UOPCaag788ZWO6Ws8jb';

    public function getReportData($userName,$days)
    {
        $formatData = $this->commonBaiduFormat($userName,$days);
        $data = json_decode($this->curlPost($this->apiReportDataUrl, $formatData['header'], json_encode($formatData['data'])), true);
        return $data;
    }

    //组装请求参数
    public function commonBaiduFormat($userName,$days = 1)
    {
        $startDate = $endDate = date('Y-m-d',strtotime('-'.$days.' day'));
        $data['header'] = [
            'userName' => $userName,
            'accessToken' => $this->accessToken
        ];
        $data['body'] = [
            'reportType' => 2149145,
            'timeUnit' => 'DAY',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'columns' => [ "date", "userName", "impression", "click", "cost", "ctr", "cpc" ],
            'startRow' => 0,
            'rowCount' => 200,
            'needSum' => false
        ];
        $header = [
            'Accept-Encoding' => 'gzip, deflate',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];


        $paramArr = compact('data', 'header');
        return $paramArr;
    }

    public function curlPost($url,$headers,$body)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_TIMEOUT,3);//超时设置
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);//设置请求体
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');//使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。(这个加不加没啥影响)
        $data = curl_exec($curl);
        if($data === false){
            return false;
        }else{
            return $data;
        }

    }

}