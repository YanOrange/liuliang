<?php
namespace app\lib\api\advertiser;

use app\model\api\AdvertiserCallbackRecord;
use think\facade\Db;

//c360广告主回传
trait C360Advertiser
{
    private $Key = 'chbkifhu3ovnqmlwqyy5';
    private $Secret = 'l8oly7p6iuhiul2j6atotdfhj8tpbrgp';
    private $apiC360Url = 'https://convert.dop.360.cn/uploadConvert';

    //用户行为
    private $c360CvType = [
        'active' => 'ACTIVATE',//激活
        'register' => 'REGISTER',//注册
    ];

    public function c360AdvertiserCallBack($data = [])
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
            $formatData = $this->commonC360Format($data);
            $res = json_decode($this->json_post($this->apiC360Url, json_encode($formatData['data']), $formatData['header']), true);
            if ($res['errno'] == 0) {
                AdvertiserCallbackRecord::create([
                    'channel' => 'c360',
                    'channel_name' => $data['user']['channel'],
                    'app_bundle_id' => $data['user']['app_bundle_id'],
                    'cvType' => $data['dataType'],
                    'oaid' => $data['user']['oaid'],
                    'source' => $data['source'] ?? 1
                ]);
                $recordData = [
                    'channel' => 'c360',
                    'channel_name' => $data['user']['channel'],
                    'app_bundle_id' => $data['user']['app_bundle_id'],
                    'cvType' => $data['dataType'],
                    'oaid' => $data['user']['oaid'],
                    'source' => $data['source'] ?? 1
                ];
                event('InsertRecordData', $recordData);
            }
            return $res;
        }
    }

    public function commonC360Format($params = [])
    {
        $data['data'] = [
            'pid_type' => 'imei',
            'data_industry' => 'ocpc_convert',
            'data_detail' => [
                'pid' => '',
                'oaid_md5' => md5($params['user']['oaid']),
                'event' => $this->c360CvType[$params['dataType']],
                'device_info' => [
                    'platform' => 'android'
                ],
            ],
        ];
        $header = [
            'App-key:'.$this->Key,
            'App-Sign:'.md5($this->Secret.json_encode($data)),
            'Content-Type: application/json;charset=utf-8'
        ];
        $paramArr = compact('data', 'header');
        return $paramArr;
    }
}