<?php

namespace app\lib\api\advertiser;

use app\model\api\AdvertiserCallbackRecord;
use app\model\api\TodayReceiveMonitorData;
use laytp\library\Http;
use think\facade\Db;

//rongyao广告主回传
class RongyaoAdvertiser
{
    private $apiRongyaoUrl = 'https://ads-drcn.platform.hihonorcloud.com/api/ad-tracking/v1/conversion';

    //用户行为
    private $rongyaoCvType = [
        'active' => 10001,//激活
        'register' => 10002,//注册
        'pay' => 10004,//应用付费
        'submit' => 20001,//表单提交
        'place_order' => 10007//用户下单
    ];

    public function rongyaoAdvertiserCallBack($data = [])
    {
        $dateYm = date('Ym');
        $tableName = "advertiser_callback_record_{$dateYm}";
        $tableNamePrefix = "advertiser_callback_record_{$dateYm}";
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
            $formatData = $this->commonRongyaoFormat($data);
            if(!empty($formatData['trackId']) && !empty($formatData['advertiserId'])) {
                $rongyaoCallbackUrl = http_build_query($formatData);
                $ret = json_decode(Http::get($this->apiRongyaoUrl.'?'.$rongyaoCallbackUrl),true);
                if ($ret['code'] == 0) {
                    $recordData = [
                        'channel' => 'rongyao',
                        'channel_name' => $data['user']['channel'],
                        'app_bundle_id' => $data['user']['app_bundle_id'],
                        'cvType' => $data['dataType'],
                        'oaid' => $data['user']['oaid'],
                        'oaid_two' => $data['user']['oaid'],
                    ];
                    event('InsertRecordData', $recordData);
                }
            }else{
                $recordData = [
                    'channel' => 'rongyao',
                    'channel_name' => $data['user']['channel'],
                    'app_bundle_id' => $data['user']['app_bundle_id'],
                    'cvType' => $data['dataType'],
                    'oaid' => $data['user']['oaid'],
                    'oaid_two' => $data['user']['oaid'],
                    'is_callback' => 0
                ];
                event('InsertRecordData', $recordData);
            }
            return $ret ?? [];
        }
    }

    //组装请求参数
    public function commonRongyaoFormat($params = [])
    {
        $timestamp = getMillisecond();
        $data['trackId'] = '';
        $data['conversionId'] = $this->rongyaoCvType[$params['dataType']];
        $data['conversionTime'] = $timestamp;
        $data['advertiserId'] = '';
        $data['oaid'] = $params['user']['oaid'];
        $rongyaoMonitorData = TodayReceiveMonitorData::where('oaid', $params['user']['oaid'])
            ->where('channel', $params['user']['channel'])
            ->where('app_bundle_id', $params['user']['app_bundle_id'])
            ->order('id desc')
            ->field('id,track_id,advertiser_id')
            ->find();
        if ($rongyaoMonitorData) {
            $data['trackId'] = $rongyaoMonitorData->track_id;
            $data['advertiserId'] = $rongyaoMonitorData->advertiser_id;
        }
        return $data;
    }

}