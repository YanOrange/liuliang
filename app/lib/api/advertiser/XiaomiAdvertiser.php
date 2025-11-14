<?php
namespace app\lib\api\advertiser;

use laytp\library\Http;
use app\model\api\AdvertiserCallbackRecord;
use app\model\api\XiaomiConfig;
use think\facade\Db;
//xiaomi广告主回传
trait XiaomiAdvertiser
{
    private $appId = [
//        'zy_ljgdyq_xiaomi'  => 1590741,       // 没投放了
        'zy_yqlksa_xiaomi'  => 1594987,
        'zy_yqzwzx_xiaomi'  => 1596171,
        'zy_yqmfzx_xiaomi'  => 1595723,
        'zy_ljgdyq_xiaomi'  => 1590741,
    ];
    private $customerId = [
//        'zy_ljgdyq_xiaomi'  => 624192,        // 没投放了
        'zy_yqlksa_xiaomi'  => 641982,
        'zy_yqzwzx_xiaomi'  => 648472,
        'zy_yqmfzx_xiaomi'  => 693362,
        'zy_ljgdyq_xiaomi'  => 694512,
    ];
    private $signKey;
    private $appSecret;
    private $apiXiaomiUrl = 'http://trail.e.mi.com/global/log';

    //用户行为
    private $xiaomiCvType = [
        'active' => 'APP_ACTIVE',//激活
        'register' => 'APP_REGISTER',//注册
        'pay' => 'APP_PAY',//付费
        'new_active' => 'APP_ACTIVE_NEW',//新增激活
        'key_behavior' => 'APP_ADDICTION',//关键行为
    ];

    public function xiaomiAdvertiserCallBack($data = [])
    {
        $dateYm = date('Ym');
        $tableName = "advertiser_callback_record_{$dateYm}";
        $tableNamePrefix = "lt_advertiser_callback_record_{$dateYm}";
        $table = Db::query('SHOW TABLES LIKE"'.$tableNamePrefix.'"');
        if($table){
            $checkCount = Db::name($tableName)->where('oaid', $data['user']['oaid'])
                ->where('app_bundle_id',$data['user']['app_bundle_id'])
                ->where('cvType', $data['dataType'])
                ->count();
        }else {
            $checkCount = AdvertiserCallbackRecord::where('oaid', $data['user']['oaid'])
                ->where('app_bundle_id', $data['user']['app_bundle_id'])
                ->where('cvType', $data['dataType'])
                ->count();
        }
        if (!$checkCount) {

            $formatData = $this->commonXiaomiFormat($data);
            if (!$formatData) {
                return [];
            }
            $res = json_decode(Http::get($formatData['callbackUrl']), true);
            if ($res['code'] == 1) {
//                    AdvertiserCallbackRecord::create([
//                        'channel' => 'xiaomi',
//                        'channel_name' => $data['user']['channel'],
//                        'app_bundle_id' => $data['user']['app_bundle_id'],
//                        'cvType' => $data['dataType'],
//                        'oaid' => $data['user']['oaid'],
//                        'source' => $data['source'] ?? 1,
//                        'oaid_two' => $data['user']['oaid']
//                    ]);
                $recordData = [
                    'channel' => 'xiaomi',
                    'channel_name' => $data['user']['channel'],
                    'app_bundle_id' => $data['user']['app_bundle_id'],
                    'cvType' => $data['dataType'],
                    'oaid' => $data['user']['oaid'],
                    'source' => $data['source'] ?? 1,
                    'oaid_two' => $data['user']['oaid']
                ];
                event('InsertRecordData', $recordData);
            }
            return $res;
        }
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
        $config = XiaomiConfig::where('customer_id', $this->customerId[$params['user']['channel']] ?? '')
            ->where('conv_type', $params['dataType'])
            ->where('app_id', $this->appId[$params['user']['channel']] ?? '')->find();
        if (!$config) {
            return [];
        }
        $this->appId = $config->app_id ?? 0;
        $this->customerId = $config->customer_id ?? 0;
        $this->signKey = $config->sign_key ?? '';
        $this->appSecret = $config->app_secret ?? '';
        $timestamp = getMillisecond();
        $params['oaid'] = $params['user']['oaid'];
        $params['conv_time'] = $timestamp;
        $params['client_ip'] = request()->ip();
        $dataStr = 'oaid='.$params['oaid'].'&conv_time='.$params['conv_time'].'&client_ip='.$params['client_ip'];
        $sign = md5($this->signKey.'&'.urlencode($dataStr));
        $base_data = $dataStr.'&sign='.urlencode($sign);
        $info = $this->encryptXor($base_data,$this->appSecret);
        if($this->xiaomiCvType[$params['dataType']] == 'APP_ADDICTION'){
            $callbackUrl = $this->apiXiaomiUrl.'?appId='.urlencode($this->appId).'&info='.urlencode($info).'&conv_type='.urlencode($this->xiaomiCvType[$params['dataType']]).'&customer_id='.urlencode($this->customerId).'&key_action_target='.urlencode(1).'&key_action_reach='.urlencode(1);
        }else{
            $callbackUrl = $this->apiXiaomiUrl.'?appId='.urlencode($this->appId).'&info='.urlencode($info).'&conv_type='.urlencode($this->xiaomiCvType[$params['dataType']]).'&customer_id='.urlencode($this->customerId);
        }
        $paramArr = ['info'=>$info,'callbackUrl'=>$callbackUrl];
        return $paramArr;
    }

}