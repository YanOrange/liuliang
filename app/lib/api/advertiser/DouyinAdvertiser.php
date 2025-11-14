<?php

namespace app\lib\api\advertiser;

use app\model\api\AdvertiserCallbackRecord;
use app\model\api\TodayReceiveMonitorData;
use think\facade\Event;
use think\facade\Db;
use app\lib\api\http\Http;

//oppo广告主回传
class DouyinAdvertiser
{
    use Http;
    private $apiDouyinUrl = 'https://analytics.oceanengine.com/api/v2/conversion';
    //用户行为
    private $oppoCvType = [
        'active' => 'active',//激活
        'register' => 'active_register',//注册
        'pay' => 'active_pay',//付费
        'submit' => 'game_addiction',//关键行为
    ];

    public function douyinAdvertiserCallBack($data = [])
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
            $formatData = $this->commonOppoFormat($data);
            $ret = json_decode($this->json_post($this->apiDouyinUrl, $formatData['data'], $formatData['header']), true);
            if ($ret['code'] == 0) {
                $recordData = [
                    'channel' => 'douyin',
                    'channel_name' => $data['user']['channel'],
                    'app_bundle_id' => $data['user']['app_bundle_id'],
                    'cvType' => $data['dataType'],
                    'oaid' => $data['user']['oaid'],
                    'ascribeType' => isset($formatData['data']['ascribeType']) ? 1 : 0,
                    'source' => $data['source'] ?? 1,
                    'oaid_two' => $data['user']['oaid']
                ];
                event('InsertRecordData', $recordData);
            }
            return $ret;
        }
    }

    //组装请求参数
    public function commonOppoFormat($params = [])
    {
        $callback = TodayReceiveMonitorData::where('channel',$params['user']['channel'])
            ->where('store','douyin')
            ->where('oaid',$params['user']['oaid'])
            ->order('id desc')
            ->value('callback');
        $timestamp = getMillisecond();
        $data['event_type'] = $this->oppoCvType[$params['dataType']];
        $data['context'] = [
            'ad' => [
                'callback' => $callback,
                'match_type' => 0
            ],
            'device' => [
                'oaid' => $params['user']['oaid']
            ]
        ];
        $data['timestamp'] = $timestamp;
        //设置header头请求参数
        $header = [
            'Content-Type : application/json'
        ];
        $paramArr = compact('data', 'header');
        return $paramArr;
    }

}