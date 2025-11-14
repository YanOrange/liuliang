<?php

namespace app\lib\api\advertiser;

use app\model\api\Channel;
use think\facade\Config;
use think\facade\Event;
use think\facade\Db;
use app\model\api\UserList;
use app\model\api\AsoActionLog;
use app\model\api\AsoIdfaCallback;
use app\lib\api\city\IpCity;

//oppo广告主回传
class AsoAdvertiser
{
    protected $appChannelId = [
        'xbxyhxh_ios' => '6447632374',
        'lmgdyq_ios' => '1670854850',
        'yqzwzx_ios' => '6450792799',
        'yqzwcz_ios' => '6450885988',
        'nnzwyh_ios' => '6451143961',
        'yqzwgj_ios' => '6451038770',
        'bjgdyq_ios' => '6451968517',
        'yxyhzq_ios' => '1637902295',
    ];

    public function queryIdfa($params = [])
    {
        extract($params);
        $asoIdfaInfo = AsoIdfaCallback::where('idfa',$idfa)
            ->where('appid',$appid)
            ->field('id,idfa')
            ->find();
        $data = [$idfa => 0];
        if(!empty($asoIdfaInfo)) $data = [$idfa => 1];
        AsoActionLog::create(['request_body' => json_encode($params)]);
        return $data;
    }

    //组装请求参数
    public function clickCallbackIdfa($params = [])
    {
        extract($params);
        $asoIdfaInfo = AsoIdfaCallback::where('idfa',$idfa)
            ->where('appid',$appid)
            ->where('callback',$callback)
            //->whereDay('create_time')
            ->find();
        if(isset($ip) && !empty($ip)){
            $cityInfo = IpCity::getIpToCity($ip);
        }
        if(!empty($asoIdfaInfo)){
            $asoIdfaInfo->callback = $callback;
            $asoIdfaInfo->save();
        }else{
            (new AsoIdfaCallback())->save([
                'appid' => $appid,
                'source' => $source ?? '',
                'idfa' => $idfa,
                'ip' => $ip ?? '',
                'callback' => $callback,
                'province' => $cityInfo['province_name'] ?? '',
                'city' => $cityInfo['city_name'] ?? '',
            ]);
        }
        AsoActionLog::create(['request_body' => json_encode($params)]);
        return [];
    }

    public function AsoAdvertiserCollback($data = [])
    {
        $dateYm = date('Ym');
        $tableName = "advertiser_callback_record_{$dateYm}";
        $data['user']['idfa'] = $data['user']['idfa'] ?? '';
        $appId = $this->appChannelId[$data['user']['channel']];
        $checkCount = Db::name($tableName)->where('idfa', $data['user']['idfa'])
            ->where('channel',$data['user']['channel'])
            ->where('cvType', $data['dataType'])
            ->count();
        if(!$checkCount || !$data['user']['idfa']) {
            $asoIdfaInfo = AsoIdfaCallback::where('idfa', $data['user']['idfa'])
                ->where('appid',$appId)
                ->field('id,appid,idfa,callback')
                ->find();
            if(empty($asoIdfaInfo) && $appId == '1670854850'){
                $asoIdfaCollbackNo = AsoIdfaCallback::where('is_callback', 0)
                    ->where('appid',$appId)
                    ->whereDay('create_time')
                    ->count();
                $asoIdfaCollbackCount = AsoIdfaCallback::whereDay('create_time')->count();
                if($asoIdfaCollbackCount > 0) {
                    $asoIdfaCollbackRate = $asoIdfaCollbackNo < $asoIdfaCollbackCount ? $asoIdfaCollbackNo / $asoIdfaCollbackCount * 100 : 0;
                    if($asoIdfaCollbackRate >= 70){
                        $asoIdfaInfo = AsoIdfaCallback::where('is_callback',0)
                            ->where('appid',$appId)
                            ->field('id,appid,idfa,callback')
                            ->order('id desc')
                            ->find();
                    }
                }
            }
            if (!empty($asoIdfaInfo)) {
                if($asoIdfaInfo->appid == '1670854850' || $asoIdfaInfo->appid == '6447632374'){
                    $asoIdfaInfo->callback = urldecode($asoIdfaInfo->callback);
                }
                $formatData = $this->curlGet($asoIdfaInfo->callback);
                $ret = json_decode($formatData, true);
                if ((isset($ret['errno']) && $ret['errno'] == 0) || (isset($ret['code']) && $ret['code'] == 0)) {
                    $recordData = [
                        'channel' => 'ios',
                        'channel_name' => $data['user']['channel'],
                        'app_bundle_id' => $data['user']['app_bundle_id'] ?? '',
                        'cvType' => $data['dataType'],
                        'appid' => $asoIdfaInfo->appid ?? '',
                        'oaid' => $data['user']['oaid'] ?? '',
                        'idfa' => $asoIdfaInfo->idfa ?? '',
                        'source' => 1,
                    ];
                    event('InsertRecordData', $recordData);
                    $asoIdfaInfo->is_callback = 1;
                    $asoIdfaInfo->save();
                }
            }else{
                $recordData = [
                    'channel' => 'ios',
                    'channel_name' => $data['user']['channel'],
                    'app_bundle_id' => $data['user']['app_bundle_id'] ?? '',
                    'cvType' => $data['dataType'],
                    'oaid' => $data['user']['oaid'] ?? '',
                    'appid' => $this->appChannelId[$data['user']['channel']] ?? '',
                    'idfa' => $asoIdfaInfo->idfa ?? $data['user']['idfa'],
                    'source' => 1,
                    'is_callback' => 0
                ];
                event('InsertRecordData', $recordData);
            }
            return $ret ?? [];
        }
    }

    //组装请求参数
    public function curlGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        return $output;
    }

}