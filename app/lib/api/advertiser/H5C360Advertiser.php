<?php

namespace app\lib\api\advertiser;

use app\model\api\Channel;
use app\lib\api\http\Http;

//360广告主回传
class H5C360Advertiser
{
    use Http;
    private $AppKey = 'g1k0atw21pcyn6j7hyw8';
    private $SecretKey = 'hc8ozpwe5z2l72hrguv1sqfw778yqnt6';
    private $apiUcUrl = 'https://convert.dop.360.cn/uploadWebConvert';

    public function h5C360AdvertiserCollback($data = [])
    {
        $data['dataType'] = 'submit';
        $data['channel'] = Channel::where('id',$data['channel'])->value('channel_name');
        $formatData = $this->commonOppoFormat($data);
        $formatData = $this->json_post($this->apiUcUrl, $formatData['data'], $formatData['header']);
        $ret = json_decode($formatData, true);
        if (isset($ret['errno']) && $ret['errno'] == 0) {
            $recordData = [
                'channel' => 'c360',
                'channel_name' => $data['channel'],
                'app_bundle_id' => $data['app_bundle_id'] ?? '',
                'cvType' => 'submit',
                'oaid' => $data['oaid'] ?? '',
                'ascribeType' => isset($formatData['data']['ascribeType']) ? 1 : 0,
                'source' => $data['source'] ?? 1,
                'oaid_two' => $data['oaid'] ?? ''
            ];
            event('InsertRecordData', $recordData);
        }
        return $ret;

    }

    //组装请求参数1
    public function commonOppoFormat($params = [])
    {
        $data['data'] = [
            'data_industry' => 'ocpc_ps_convert',
            'data_detail' => [
                'qhclickid' => $params['qhclickid'],
                'trans_id' => $params['trans_id'],
                'event' => 'SUBMIT',
                'jzqs' => 1217955
            ]
        ];

        //设置header头请求参数
        $header = [
            'App-Key:'.$this->AppKey,
            'App-Sign:'.md5($this->SecretKey .json_encode($data)),
            'Content-Type:application/json;charset=utf-8'
        ];
        $paramArr = compact('data', 'header');
        return $paramArr;
    }

}