<?php
namespace app\lib\api\advertiser;

use laytp\library\Http;
use app\model\api\AdvertiserCallbackRecord;
use app\model\api\XiaomiConfig;
use think\facade\Db;
use app\model\api\Channel;
//xiaomi广告主回传
class H5XiaomiAdvertiser
{
    private $appId;
    private $customerId;
    private $signKey;
    private $appSecret;
    private $apiH5XiaomiUrl = 'http://site.e.mi.com/conversionLog';

    //用户行为
    private $xiaomiCvType = [
        'active' => 'APP_ACTIVE',//激活
        'register' => 'APP_REGISTER',//注册
        'submit' => 'WEB_FORM_SUBMIT'//表单
    ];

    public function h5XiaomiAdvertiserCollBack($data = [])
    {
        $data['dataType'] = 'submit';
        $channelName = Channel::where('id',$data['channel'])->value('channel_name');
        //$xiaomiConfig = XiaomiConfig::where('app_bundle_id', $channelName)->where('conv_type',$data['dataType'])->find();
        $formatData = $this->commonXiaomiFormat($data);
        $res = json_decode(Http::get($formatData['callbackUrl']), true);
        if ($res['code'] == 1) {
            $recordData = [
                'channel' => 'xiaomi',
                'channel_name' => $channelName,
                'app_bundle_id' => $data['app_bundle_id'] ?? '',
                'cvType' => $data['dataType'],
                'oaid' => $data['oaid'] ?? '',
                'source' => $data['source'] ?? 1,
                'oaid_two' => $data['oaid'] ?? ''
            ];
            event('InsertRecordData', $recordData);
        }
        return $res;

    }

    //xor（按位异或），最后对结果进行 base64 编码
    public function encryptXor($data, $encrypt,$base64Str = '')
    {
        $dataLen = strlen($data);
        $encryptLen = strlen($encrypt);
        for($i = 0; $i < $dataLen; $i ++) {
            $j = $i % $encryptLen;
            $base64Str .= ($data[$i]) ^ ($encrypt[$j]);
        }
        return base64_encode($base64Str);
    }

    //组装请求参数
    public function commonXiaomiFormat($params = [])
    {
        $clientTime = getMillisecond();
        $callbackUrl = $this->apiH5XiaomiUrl.'?conversionId='.$params['conversionId'].'&eventType=form&clientTime='. $clientTime.'&logExtra='.$params['logExtra'].'&webConversionId='.$params['webConversionId'].'&convType='.$this->xiaomiCvType[$params['dataType']];
        $paramArr = ['info'=>[],'callbackUrl'=>$callbackUrl];
        return $paramArr;
    }

}