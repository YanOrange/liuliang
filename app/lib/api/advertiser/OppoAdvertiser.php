<?php

namespace app\lib\api\advertiser;

use app\model\api\AdvertiserCallbackRecord;
use app\model\api\TodayReceiveMonitorData;
use think\facade\Config;
use think\facade\Event;
use think\facade\Db;

//oppo广告主回传
trait OppoAdvertiser
{
    private $salt = 'e0u6fnlag06lc3pl';
    private $base64Key = 'XGAXicVG5GMBsx5bueOe4w==';
    private $apiOppoUrl = 'https://api.ads.heytapmobi.com/api/uploadActiveData';
    //用户行为
    private $oppoCvType = [
        'active' => 1,//激活
        'register' => 2,//注册
        'pay' => 7,//应用付费
        'secondary_retention' => 4//次留
    ];
    //包名
    private $oppoAppBundleId = [
        'com.example.businessvideobailing',
        'com.houhan.suxuepy',
        'com.dashugan.kuaixuepr',
        'com.yuluojishu.kuaixue',
        'com.xuao.suxuepr',
        'com.houhan.quxuepr',
        'com.dazhiya.yxyh',
        'com.yuluojishu.lexue',
        'com.example.xiangxue',
        'com.yuluo.xsjzdq',
        'com.kuanghua.msgdyq',
        'com.kuanghua.lmgdyq',
        'com.jzd.xchmh',
        'com.jzd.xbhmh',
        'com.yuluo.lxbc',
        'com.yuluo.kxbc',
        'com.xuao.yxyhmh',
        'com.yuluo.xsfydq',
        'com.xuao.gxjz',
        'com.xuao.jsgmc',
    ];

    public function oppoAdvertiserCallBack($data = [])
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
            $advertiserCollbackChannel = Config::load('extra/oppo', 'extra');
            if(isset($formatData['data']['ascribeType']) || in_array($data['user']['channel'],$advertiserCollbackChannel['advertiserCollbackChannel'])) {
                $ret = json_decode($this->json_post($this->apiOppoUrl, $formatData['data'], $formatData['header']), true);
                if ($data['user']['channel'] == 'xbxzjz_oppo' && $data['dataType'] == 'pay') {
                    file_put_contents('./hc7.txt', $ret);
                }
                if ($ret['ret'] == 0) {
                    if ($data['user']['channel'] == 'kuaixue_oppo' && $data['user']['app_bundle_id'] == 'com.yuluojishu.kuaixue') {
                        $source = TodayReceiveMonitorData::where('oaid', $data['user']['oaid'])
                            ->where('channel', $data['user']['channel'])
                            ->where('app_bundle_id', $data['user']['app_bundle_id'])
                            ->order('id desc')
                            ->value('source');
                        if (!empty($source)) {
                            $data['source'] = $source == 2 ? 4 : 3;
                        }
                    }
                    $recordData = [
                        'channel' => 'oppo',
                        'channel_name' => $data['user']['channel'],
                        'app_bundle_id' => $data['user']['app_bundle_id'],
                        'cvType' => $data['dataType'],
                        'oaid' => $data['user']['oaid'],
                        'ascribeType' => isset($formatData['data']['ascribeType']) ? 1 : 0,
                        'source' => $data['source'] ?? 1,
                        'oaid_two' => $data['user']['oaid'],
                    ];
                    event('InsertRecordData', $recordData);
                }
            }else{
                $recordData = [
                    'channel' => 'oppo',
                    'channel_name' => $data['user']['channel'],
                    'app_bundle_id' => $data['user']['app_bundle_id'],
                    'cvType' => $data['dataType'],
                    'oaid' => $data['user']['oaid'],
                    'ascribeType' => 0,
                    'source' => $data['source'] ?? 1,
                    'oaid_two' => $data['user']['oaid'],
                    'is_callback' => 0
                ];
                event('InsertRecordData', $recordData);
            }
            return $ret ?? [];
        }
    }
    //aes加密
    public function encrypt($input, $base64Key)
    {
        return openssl_encrypt($input, 'AES-128-ECB',base64_decode($base64Key),0,'');
    }
    //组装请求参数
    public function commonOppoFormat($params = [])
    {

        $timestamp = getMillisecond();
        $data['dataType'] = $this->oppoCvType[$params['dataType']];
        $data['pkg'] = $params['user']['app_bundle_id'];
        $data['ouId'] = $this->encrypt($params['user']['oaid'], $this->base64Key);
        $data['channel'] = 1;
        $data['type'] = 2;
        $data['appType'] = 1;
        $data['clientIp'] = '127.0.0.1';
        $data['timestamp'] = $timestamp;
        //if(in_array($params['user']['app_bundle_id'],$this->oppoAppBundleId)) {
            $adid = TodayReceiveMonitorData::where('oaid', $params['user']['oaid'])
                ->where('channel', $params['user']['channel'])
                ->where('app_bundle_id', $params['user']['app_bundle_id'])
                ->order('id desc')
                ->value('adid');
            if ($adid) {
                $data['ascribeType'] = 1;
                $data['adId'] = $adid;
            }
        //}
        $jsonStr = json_encode($data);
        $sign = md5($jsonStr . $timestamp . $this->salt);
        //设置header头请求参数
        $header = [
            'signature: ' . $sign,
            'timestamp: ' . $timestamp
        ];
        $paramArr = compact('data', 'header');
        return $paramArr;
    }

}