<?php

namespace app\lib\api\advertiser;

use app\model\admin\thread\ThreadFollowAction;
use app\model\admin\ThreadExternal;
use app\model\api\Channel;
use laytp\library\Http;
use app\lib\api\http\Http as HttpPost;
use app\model\api\Thread;
use think\facade\Db;

//广点通广告主回传
class FlowGdtAdvertiser
{
    protected $token = '364065871700617407';
    protected $secret = 'c000ad015a2c5e5ab70e24e914e80284';
    protected $tokenSecret = [
        'yqh5_gdt3' => [
            'token' => '364065871700617407',
            'secret' => 'c000ad015a2c5e5ab70e24e914e80284'
        ],
        'yqh5_gdt2' => [
            'token' => '364283521700740993',
            'secret' => '34c3d4bbc2413ba4345716472f33c893'
        ]
    ];
    protected $gdtCallbackUrl = 'https://leads.qq.com/api/mv1/leads/report';

    public function gdtCallBackCon($params = [])
    {
        try {
            file_put_contents('gdt_callback.txt',json_encode($params).'--'.date('Y-m-d H:i:s')."\r\n",FILE_APPEND);
            $threadId = $params['thread_id'];
            $currentActionIds = explode(',',$params['current_action_id']);
            $threadExternalInfo = ThreadExternal::where('id',$threadId)->field('channel,leads_id')->find();
            $threadLeadsId = $threadExternalInfo->leads_id;
            $channel = $threadExternalInfo->channel;
            $dataAll = [];
            if($threadLeadsId && !empty($currentActionIds)){
//                if(in_array($currentActionId,[788,789,790,791])) {
//                    $dataAll[] = [
//                        'leads_id' => $threadLeadsId,
//                        'leads_convert_type' => $this->leadsConvertType($currentActionId)
//                    ];
//                }
//                if(in_array($currentActionId,[793,794,795,796,797,789])) {
//                    $dataAll[] = [
//                        'leads_id' => $threadLeadsId,
//                        'leads_ineffect_reason' => $this->leadsIneffectReason($currentActionId)
//                    ];
//                }
//                if(in_array($currentActionId,[800,801,802,803,804,805])) {
//                    $dataAll[] = [
//                        'leads_id' => $threadLeadsId,
//                        'leads_intention_score' => $this->leadsIntentionScore($currentActionId)
//                    ];
//                }
                $leadsConvertType = [];
                $leadsIneffectReason = [];
                $leadsIntentionScore = [];
                foreach($currentActionIds as $currentActionId){
                    $currentActionName = ThreadFollowAction::where('id',$currentActionId)->value('title');
                    $leadsId = ['leads_id' => $threadLeadsId];
                    if(in_array($currentActionName,['已成单','高意向客户','潜在客户','无效线索']))
                        $leadsConvertType = ['leads_convert_type' => $this->leadsConvertType($currentActionName)];
                    if(in_array($currentActionName,['无意向','未接通','重复数据','不是逾期需求','非本人','其他']))
                        $leadsIneffectReason = ['leads_ineffect_reason' => $this->leadsIneffectReason($currentActionName)];
                    if(in_array($currentActionName,['0-0','0-20','20-40','40-60','60-80','80-100']))
                        $leadsIntentionScore = ['leads_intention_score' => $this->leadsIntentionScore($currentActionName)];
                }
                $dataAll[] = array_merge($leadsId,$leadsConvertType,$leadsIneffectReason,$leadsIntentionScore);
                if(!empty($dataAll)){
                    $res = json_decode($this->gdtCallbackCurl($dataAll,$channel),true);
                    file_put_contents('gdt_callback.txt',json_encode($res).'--'.date('Y-m-d H:i:s')."\r\n",FILE_APPEND);
                }
                return $res['msg'] ?? '';
            }
        }catch(\Exception $e){
            file_put_contents('gdt_callback.txt',$e->getMessage().'--'.$e->getLine()."\r\n",FILE_APPEND);
        }
    }

    public function gdtCallbackCurl($data,$channel = null)
    {
        //设置header头请求参数
        $headers = [
            'X-Signature: '.$this->getsignature($channel),
            'Accept: application/json',
            'Content-Type: application/json'
        ];

        // $data = [
        //     [
        //         'leads_id' => 382990066,
        //         'leads_convert_type' => 'LEADS_CONVERT_STATUS_DEPRECATED',
        //         'leads_ineffect_reason' => 'LEADS_INEFFECT_REASION_TEL_NOT_CONNECTED'
        //     ]
        // ];
        $dataAll['list'] = $data;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->gdtCallbackUrl);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($dataAll));//设置请求体1
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');//使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。(这个加不加没啥影响)
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        $data = curl_exec($curl);
        if ($data === false) {
            return false;
        } else {
            return $data;
        }
    }

    public function getsignature($channel = null)
    {
        $signAlgorithm = '<signAlgorithm>'; //签名加密算法：SHA1/SHA256
        $token = $this->tokenSecret[$channel]['token'] ?? $this->token;
        $secret = $this->tokenSecret[$channel]['secret'] ?? $this->secret;
        //$token = $this->token;
        //$secret = $this->secret;
        $nonce = getRandomStr(24); // 不超过 32 个字符，由调⽤⽅⾃⾏⽣成，每个请求都不重复
        $timestamp = time(); // 即当前的秒级时间戳
        $signature = base64_encode($token . "," . $timestamp. "," . $nonce . "," . sha1($token.".".$timestamp.".".$secret));
        // 如果使⽤的是sha256加密算法，则使⽤下⾯这条注释的语句
        // $signature = base64_encode($token . "," . $timestamp. "," . $nonce . "," .sha256($token.".".$timestamp.".".$secret));
        return $signature;
    }

    public function leadsConvertType($title)
    {
        $actionTagName = '';
        if($title == '无效线索') $actionTagName = 'LEADS_CONVERT_STATUS_DEPRECATED';
        if($title == '潜在客户') $actionTagName = 'LEADS_CONVERT_STATUS_POTENTIAL_CUSTOMER';
        if($title == '高意向客户') $actionTagName = 'LEADS_CONVERT_STATUS_HIGH_INTENTION_CUSTOMER';
        if($title == '已成单') $actionTagName = 'LEADS_CONVERT_STATUS_TRANS_COMPLETED';
        return $actionTagName;
    }

    public function leadsIneffectReason($title)
    {
        $actionTagName = '';
        if($title == '无意向') $actionTagName = 'LEADS_INEFFECT_REASON_NO_INTENTION';
        if($title == '未接通') $actionTagName = 'LEADS_INEFFECT_REASON_TEL_NOT_CONNECTED';
        if($title == '重复数据') $actionTagName = 'LEADS_INEFFECT_REASON_DATA_DUPLICATION';
        if($title == '不是逾期需求') $actionTagName = 'LEADS_INEFFECT_REASON_REGION_MISMATCHED';
        if($title == '非本人') $actionTagName = 'LEADS_INEFFECT_REASON_IDENTITY_MISMATCHED';
        if($title == '其他') $actionTagName = 'LEADS_INEFFECT_REASON_UNKOWN';
        return $actionTagName;
    }

    public function leadsIntentionScore($title)
    {
        $actionTagName = '';
        if($title == '0-0') $actionTagName = 'LEADS_INTENTION_SCORE_LEVEL_1';
        if($title == '0-20') $actionTagName = 'LEADS_INTENTION_SCORE_LEVEL_2';
        if($title == '20-40') $actionTagName = 'LEADS_INTENTION_SCORE_LEVEL_3';
        if($title == '40-60') $actionTagName = 'LEADS_INTENTION_SCORE_LEVEL_4';
        if($title == '60-80') $actionTagName = 'LEADS_INTENTION_SCORE_LEVEL_5';
        if($title == '80-100') $actionTagName = 'LEADS_INTENTION_SCORE_LEVEL_6';
        return $actionTagName;
    }

}