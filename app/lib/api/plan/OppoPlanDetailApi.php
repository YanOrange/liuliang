<?php

namespace app\lib\api\plan;

use think\facade\Cache;
use think\facade\Config;
class OppoPlanDetailApi
{
    private $oppoDataUrl = 'https://sapi.ads.heytapmobi.com/v3/data/common/query/queryAdData';
    private $oppoTotalUrl = 'https://sapi.ads.heytapmobi.com/v3/data/common/total/queryAdData';
    private $oppoCustomBillUrl = 'https://sapi.ads.heytapmobi.com/v2/communal/finance/billHis';
    private $oppoBalanceUrl = 'https://sapi.ads.heytapmobi.com/v2/communal/owner/balance';

    public function getOppoPlanDetail($ownerId, $page = 1, $days = 1, $dayType = 1,$type = 'HOUR')
    {
        $beginTime = $endTime = date('Ymd');
        if($dayType == 2){
            $beginTime = $endTime = date('Ymd',strtotime('-'.$days.' day'));
        }
        $header = [
            'Content-Type:application/json',
            'Authorization:Bearer '.$this->getToken($ownerId)
        ];
        $data = [
            'beginTime' => $beginTime,
            'endTime' => $endTime,
            'timeLevel' => $type,
            'page' => $page,
            'pageCount' => 500,
            'orderByColumns' => 'ftime',
            'paraMap' => ['filter_zero' => 0,'groupByColumn' => 'ad_id']
        ];
        $data = $this->curlPost($this->oppoDataUrl,$header,json_encode($data));
        $data = json_decode($data,true);
        return $data;
    }

    //财务账户流水
    public function getOppoPlanCustomBill($ownerId, $page = 1, $days = 1)
    {
        $startTime = date('Y-m-d',strtotime('-'.$days.' day'));
        $endTime = date('Y-m-d');
        $header = [
            'Content-Type:application/x-www-form-urlencoded',
            'Authorization:Bearer '.$this->getToken($ownerId)
        ];
        $data = [
            'startTime' => strtotime($startTime),
            'endTime' => strtotime($endTime),
            'page' => $page,
            'pageCount' => 50,
            'loginType' => 3
        ];
        $data = $this->curlPost($this->oppoCustomBillUrl,$header,http_build_query($data));
        $data = json_decode($data,true);
        return $data;
    }

    //子客户余额查询
    public function getOppoPlanBalance($ownerId)
    {
        $header = [
            'Content-Type:application/x-www-form-urlencoded',
            'Authorization:Bearer '.$this->getToken($ownerId)
        ];
        $data = [];
        $data = $this->curlPost($this->oppoBalanceUrl,$header,json_encode($data));
        $data = json_decode($data,true);
        return $data;
    }

    public function getOppoTotalData($ownerId, $days, $dayType = 1, $type = 'HOUR')
    {
        $beginTime = $endTime = date('Ymd');
        if($dayType == 2){
            $beginTime = $endTime = date('Ymd',strtotime('-'.$days.' day'));
        }
        $header = [
            'Content-Type:application/json',
            'Authorization:Bearer '.$this->getToken($ownerId)
        ];
        $data = [
            'beginTime' => $beginTime,
            'endTime' => $endTime,
            'timeLevel' => $type,
            'orderByColumns' => 'ftime',
            'paraMap' => ['filter_zero' => 0,'groupByColumn' => 'ad_id']
        ];
        $data = $this->curlPost($this->oppoTotalUrl,$header,json_encode($data));
        $data = json_decode($data,true);
        return $data;
    }

    public function getToken($ownerId)
    {
        $timestamp = time();
        $oppoConfig = Config::load('extra/oppo','extra');
        $sign = sha1($oppoConfig['ownerId_'.$ownerId]['api_id'].$oppoConfig['ownerId_'.$ownerId]['api_key'].$timestamp);
        $token = base64_encode($ownerId.",".$oppoConfig['ownerId_'.$ownerId]['api_id'].",".$timestamp.",".$sign);
        return $token;
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
            echo 'Curl error: ' . curl_error($curl);
        }else{

            return $data;
        }

    }

}