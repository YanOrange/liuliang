<?php

namespace app\lib\api\advertiser;

use app\model\api\Channel;
use think\facade\Config;
use think\facade\Event;
use think\facade\Db;
use app\model\api\UserList;
use app\model\api\AsoActionLog;
use app\model\api\AsoIdfaCallback;

//oppo广告主回传
class IMoneyAdvertiser
{
    public function queryIdfa($params = [])
    {
        extract($params);
        $asoIdfaInfo = AsoIdfaCallback::where('idfa',$idfa)
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
            ->where('callback',$callback)
            //->whereDay('create_time')
            ->find();
        if(!empty($asoIdfaInfo)){
            $asoIdfaInfo->callback = $callback;
            $asoIdfaInfo->save();
        }else{
            (new AsoIdfaCallback())->save([
                'appid' => $appid,
                'source' => $source,
                'idfa' => $idfa,
                'callback' => $callback
            ]);
        }
        AsoActionLog::create(['request_body' => json_encode($params)]);
        return [];
    }

    public function AsoAdvertiserCollback($data = [])
    {
        $dateYm = date('Ym');
        $tableName = "advertiser_callback_record_{$dateYm}";
        $checkCount = Db::name($tableName)->where('idfa', $data['user']['idfa'])
            ->where('app_bundle_id',$data['user']['app_bundle_id'])
            ->where('cvType', $data['dataType'])
            ->count();
        if(!$checkCount) {
            $asoIdfaInfo = AsoIdfaCallback::where('idfa', $data['user']['idfa'])
                ->field('id,idfa,callback')
                ->find();
            if (!empty($asoIdfaInfo)) {
                $formatData = $this->curlGet($asoIdfaInfo->callback);
                $ret = json_decode($formatData, true);
                if ($ret['errno'] == 0) {
                    $recordData = [
                        'channel' => 'ios',
                        'channel_name' => $data['user']['channel'],
                        'app_bundle_id' => $data['user']['app_bundle_id'] ?? '',
                        'cvType' => $data['dataType'],
                        'oaid' => $data['user']['oaid'] ?? '',
                        'idfa' => $data['user']['idfa'] ?? '',
                        'source' => 1,
                    ];
                    event('InsertRecordData', $recordData);
                }
                return $ret;
            }
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