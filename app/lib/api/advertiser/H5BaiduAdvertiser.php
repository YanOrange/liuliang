<?php

namespace app\lib\api\advertiser;

use app\model\admin\ThreadExternal;
use app\model\admin\UserListExternal;
use app\model\api\BaiduMarketingClue;

//百度线索状态回传
class H5BaiduAdvertiser
{
    private $userName = '刘律TZ004';
    private $apiBaiduUrl = 'https://api.baidu.com/json/sms/service/FmLeadsUpdateService/addMark';
    private $accessToken = 'eyJhbGciOiJIUzM4NCJ9.eyJzdWIiOiJhY2MiLCJhdWQiOiLpgL7mnJ_ljY_llYblpITnkIYiLCJ1aWQiOjUxMDA0MDM2LCJhcHBJZCI6IjFlNWRjYTFjYTdhMGJiYTkwMmY2MWIyODAxYjhlZTkyIiwiaXNzIjoi5ZWG5Lia5byA5Y-R6ICF5Lit5b-DIiwicGxhdGZvcm1JZCI6IjQ5NjAzNDU5NjU5NTg1NjE3OTQiLCJleHAiOjQxMDI0MTYwMDAsImp0aSI6IjgxNjA0MjczODI2ODgyMjczODUifQ.BBrzgHvIFDopgxHuZrD-OPzyaSOe6768NLwMi5Odq4TLeiaF2Ah2Fym-9mZRE8Ow';

    public function callback($params = [])
    {
        $threadId = $params['thread_id'] ?? 0;
        $currentActionId = $params['current_action_id'] ?? '';
        $threadInfo = ThreadExternal::where('id',$threadId)
            ->field('id,uid')
            ->find();
        $phone = UserListExternal::where('id',$threadInfo->uid)->value('phone');
        $clueId = BaiduMarketingClue::where('cluePhoneNumber',$phone)->value('clueId');
        if($clueId){
            $header = ['Content-Type:application/json;charset:utf-8'];
            $data['header'] = [
                'userName'=> $this->userName,
                'accessToken' => $this->accessToken
            ];
            $data['body'] = [
                'data' => [[
                    'clueId' => $clueId,
                    'markType' => $this->markType($currentActionId)
                ]]
            ];
            $res = json_decode(curlPost($this->apiBaiduUrl,json_encode($data),$header),true);
            if(isset($res['header']['status']) && $res['header']['status'] == 0){
                return 'success';
            }else{
                return 'fail';
            }
        }
    }

    public function markType($type)
    {
        $data = [
            2003 => '回访-电话接通',
            2004 => '回访-信息确认',
            2005 => '回访-发现意向',
            2006 => '回访-高潜成交',
            2007 => '回访-成单客户',
            1003 => '空错号（无效线索）',
            1001 => '恶意/辱骂（无效线索）',
            1004 => '联系不上（无效线索）',
            1005 => '非本人（无效线索）',
            1006 => '无意愿（无效线索）',
        ];
        return array_search($type, $data);
    }

}