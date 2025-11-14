<?php

namespace app\lib\api\http;
/**
 * Httpjson post json
 */
trait Http
{
    public function json_post($url, $data = NULL , $header = [], $contentTypeJson = true)
    {

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if(!$data){
            return 'data is null';
        }
        if ($contentTypeJson) {
            if(is_array($data)){
                $data = json_encode($data);
            }
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);

        if ($contentTypeJson) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array_merge(array(
                'Content-Type: application/json; charset=utf-8',
                'Accept: application/json',
                'Cache-Control: no-cache',
                'Pragma: no-cache'
            ), $header));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        $errorno = curl_errno($curl);
        if ($errorno) {
            return $errorno;
        }
        curl_close($curl);
        return $res;
    }
}
