<?php

namespace app\model\api\h5;

use app\model\api\Channel;
use app\model\api\h5\GatherUserInfo;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use think\facade\Config;
use app\model\api\h5\Thread;
use app\model\api\h5\HorseRaceLamp;
use app\model\api\h5\FlowPvUv;
use app\model\admin\FlowInfoRule;

class ForFlow extends BaseModel
{
    use SoftDelete;
    //表名
    protected $name = 'for_flow';

    protected $hidden = [
        'merchant_ids',
        'h5_link_json',
        'status',
        'create_time',
        'update_time',
        'delete_time'
    ];

    public static function getForFlowDetail($params = [])
    {
        extract($params);
        $forFlowId = isset($for_flow_id) && !empty($for_flow_id) ? $for_flow_id : 0;
        if(!empty($forFlowId)){
            $forFlowInfo = self::where('id',$forFlowId)->where('status',1)->find();
            if(!empty($forFlowInfo)){
                $forFlowInfo = $forFlowInfo->toArray();
                //$forFlowInfo['gather_info_set_data'] = self::getGatherInfoList($forFlowInfo['gather_info_set_json']);
                $gather_info_set_data = [];
                $gatherUserInfoIds = Channel::getFieldById($channel,'gather_user_info_ids');
                $appId = Channel::getFieldById($channel,'app_id');
                if(!empty($gatherUserInfoIds)){
                    $gather_info_set_data = self::getGatherInfoList($gatherUserInfoIds, $appId);
                }
                $forFlowInfo['gather_info_set_data'] = $gather_info_set_data;
                $forFlowInfo['other_info_set_json'] = !empty($forFlowInfo['other_info_set_json']) ? json_decode($forFlowInfo['other_info_set_json'],true) : [];
                $forFlowInfo['thread_user'] = [];
                $horseRaceLamp = HorseRaceLamp::field('nickname,phone,times')->order('times','asc')->select();
                if(!empty($horseRaceLamp)){
                    foreach($horseRaceLamp as $val){
                        $phone_xing = substr($val->phone,4,4);  //获取手机号中间四位
                        $forFlowInfo['thread_user'][] = [
                            'nickname' => self::subNickname($val->nickname),
                            'phone' => str_replace($phone_xing,'****',$val->phone),  //用****进行替换
                            'times' => $val->times.'分钟前',
                        ];
                    }
                }
                $forFlowInfo['show_page_detail_images'] = !empty($forFlowInfo['show_page_detail_images']) ? explode(',',$forFlowInfo['show_page_detail_images']) : [];
                $forFlowInfo['content'] = !empty($forFlowInfo['content']) ? json_decode($forFlowInfo['content'],true) : [];
                unset($forFlowInfo['gather_info_set_json']);
                $channelName = Channel::where('id',$channel)->value('channel_name');
                $channelInfo = Channel::getChannelAppClass($channelName);
                $flowInfoRule = \app\model\admin\PersonalTransferAgreement::whereFindInSet('channel_ids',$channel)->value('content');
                if(empty($flowInfoRule) && $channelInfo['app_class_id'] == 9){
                    $flowInfoRule = FlowInfoRule::where('id',3)->value('content');
                    $flowInfoRule = str_replace("名称", "债务调解服务", $flowInfoRule);
                }elseif (in_array($channelInfo['app_id'], [10, 13])) {
                    $flowInfoRule = str_replace("《动态获取公司名称》", "浙江臻尚律师事务所", $flowInfoRule);
                }
                $forFlowInfo['flow_info_rule'] = $flowInfoRule;
            }
        }
        FlowPvUv::threadPvUv(['channel' => $channel,'for_flow_id' => $for_flow_id,'start_time' => time()]);

        FlowPvUv::setH5PvUv(['h5_uid' => $params['h5_uid'] ?? '', 'for_flow_id' => $for_flow_id]);
        return $forFlowInfo ?? new \stdClass();
    }

    public static function getGatherInfoSetData($gatherUserInfoIds){
        $gather_info_set_data = [];
        $gatherUserInfoArr=json_decode($gatherUserInfoIds,true);
        if(!empty($gatherUserInfoArr)) {
            foreach ($gatherUserInfoArr as $key => $val) {
                $gatherInfo = GatherUserInfo::where('id', $val['pid'])->find()->toArray();
                $gatherInfoJsonArr = json_decode($gatherInfo['gather_info_json'], true);
                $cidArr = explode(',', $val['cid']);
                foreach ($gatherInfoJsonArr as $key1 => $val1) {
                    if (!in_array($val1['id'], $cidArr)) {
                        unset($gatherInfoJsonArr[$key1]);
                    }
                }
                $gather_info_set_data[] = [
                    'id' => $gatherInfo['id'],
                    'field' => $gatherInfo['field'],
                    'title' => $gatherInfo['title'],
                    'select_id' => 0,
                    'gather_info_json' => $gatherInfoJsonArr
                ];
            }
        }
        return $gather_info_set_data;
    }


    //收集信息
    public static function getGatherInfoList($gatherUserInfoIds, $appId = null)
    {
        $gatherUserInfoList = [];
        if(!empty($gatherUserInfoIds)){
            $gatherUserInfoData = json_decode($gatherUserInfoIds, true);
            $gatherInfoArrIds = array_column($gatherUserInfoData, 'pid');
            $gatherUserInfoList = GatherUserInfo::field('id,field,title,gather_info_json,sort,select_type')->whereIn('id', $gatherInfoArrIds)->order('sort asc, id asc')->select()->toArray();
            $gatherUserInfoList = self::getMysqlDataInSort($gatherInfoArrIds, $gatherUserInfoList, $gatherUserInfoData);
            foreach ($gatherUserInfoList as &$value) {
                $value['selected_id'] = 0;

                if ($appId == 10) {
                    switch ($value['field']) {
                        case "zhaiwu_monney";
                            $value['title'] = "逾期金额【5万以下不受理，请勿填写】";
                        break;
                        case "zw_mold";
                            $value['title'] = "债务类型【不做贷款，请勿填写】";
                            foreach ($value['gather_info_json'] as &$v) {
                                $v['name'] .= "逾期";
                            }
                            break;
                        case "zhaiwu_zhuangtai";
                            $value['title'] = "逾期时长【已被起诉，可以受理】";
                            break;
                    }
                }
            }
        }
        return $gatherUserInfoList;
    }

    //解决mysql in排序问题
    public static function getMysqlDataInSort($inData = [], $data = [], $gatherUserInfoData = [])
    {
        $list = [];
        if (!empty($inData) && !empty($data) && !empty($gatherUserInfoData)) {
            $gatherUserInfoData = array_column($gatherUserInfoData, null, 'pid');
            $tempArr = array_column($data, null, 'id');
            foreach ($inData as $val) {
                if (isset($tempArr[$val]) && !empty($tempArr[$val]) && isset($gatherUserInfoData[$val]) && !empty($gatherUserInfoData[$val])) {
                    $cidStr = $gatherUserInfoData[$val]['cid'];
                    if (!empty($cidStr)) {
                        $cidArr = explode(',', $cidStr);
                        $gatherInfoArrList = json_decode($tempArr[$val]['gather_info_json'], true);
                        $gatherInfoArrList = array_column($gatherInfoArrList, null, 'id');
                        $tempGatherData = [];
                        foreach ($cidArr as $v) {
                            $tempGatherData[] = $gatherInfoArrList[$v];
                        }
                        $key = array_column(array_values($tempGatherData), 'sort');
                        array_multisort($key, SORT_DESC, $tempGatherData);
                        $tempArr[$val]['gather_info_json'] = $tempGatherData;
                    }
                    $list[] = $tempArr[$val];
                }
            }
        }
        array_multisort(array_column($list, 'sort'), SORT_ASC, array_column($list, 'id'), SORT_ASC, $list);
        return $list;
    }

    public static function subNickname($user_name){
        $strlen     = mb_strlen($user_name, 'utf-8'); //获取字符长度
        $firstStr     = mb_substr($user_name, 0, 1, 'utf-8');  //查找字符第一个
        $str=$firstStr . str_repeat('*', $strlen - 1);  //拼接第一个+把字符串 "* " 重复 $strlen - 1 次：
        return $str;
    }

    public static function getTimes($thrTime)
    {
        $timeStr = '';
        $curTime = time();
        $day = floor(($curTime-$thrTime)/86400);//日
        $hour = floor(($curTime-$thrTime)%86400/3600);//时
        $second = floor(($curTime-$thrTime)%86400/60);//分
        $minute = floor(($curTime-$thrTime)%86400%60);//秒
        if($second > 0){
            $timeStr = $second.'秒前';
        }
        if($minute > 0){
            $timeStr = $minute.'分钟前';
        }
        if($hour > 0){
            $timeStr = $hour.'小时前';
        }
        if($day > 0){
            $timeStr = $day.'天前';
        }
        return $timeStr;
    }

    public static function getFlowPage($params = [])
    {
        extract($params);
        $h5Link = '';
        $forFlowId = isset($for_flow_id) && !empty($for_flow_id) ? $for_flow_id : 0;
        $channelId = isset($channel) && !empty($channel) ? $channel : 0;
        $forFlowInfo = self::where('id',$forFlowId)->find();
        $h5LinkList = json_decode($forFlowInfo['h5_link_json'],true);
        if(!empty($h5LinkList)) {
            foreach ($h5LinkList as $item) {
                if ($item['channel_id'] == $channelId) {
                    $h5Link = $item['link'];
                    break;
                }
            }
        }
        $data = ['link' => $h5Link, 'page_type' => $forFlowInfo['page_type'] ?? 1];
        return $data;
    }
}