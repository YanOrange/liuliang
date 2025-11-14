<?php

namespace app\model\api\infoflow;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\api\Channel;
use think\facade\Config;

class ForFlow extends BaseModel
{
    use SoftDelete;

    protected $name = 'for_flow';

    protected $hidden = [
        'create_time',
        'update_time',
        'delete_time'
    ];

    public static function getForFlowList($params = [])
    {
        extract($params);
        $channel = isset($channel) && !empty($channel) ? $channel : '';
        $data = [];
        if(!empty($channel)) {
            $channelInfo = Channel::getChannelAppClass($channel);
            $appFlowInfo = self::where('status',1)
                ->where('type',2)
                ->whereFindInSet('app_ids',$channelInfo['app_id'])
                ->order('id','desc')
                ->find();
            if(!empty($appFlowInfo)){
                $index = 0;
                $flowList = [];
                $data['id'] = $appFlowInfo->id;
                $data['title'] = $appFlowInfo->for_flow_title;
                $data['avator'] = $appFlowInfo->system_avatar;
                $data['info_desc'] = $appFlowInfo->hint_official;
                $data['btn_desc'] = $appFlowInfo->btn_desc;
                $data['flowList'] = [];
                $appFlowInfo->header_official_json = !empty($appFlowInfo->header_official_json) ? json_decode($appFlowInfo->header_official_json,true) : [];
                $appFlowInfo->gather_info_set_json = !empty($appFlowInfo->gather_info_set_json) ? json_decode($appFlowInfo->gather_info_set_json,true) : [];
                $appFlowInfo->custom_problem_json = !empty($appFlowInfo->custom_problem_json) ? json_decode($appFlowInfo->custom_problem_json,true) : [];
                $userConfig = Config::load("extra/user", "extra");
                if(!empty($appFlowInfo->header_official_json)) {
                    foreach($appFlowInfo->header_official_json as $val) {
                        $flowList[] = [
                            'index' => $index,
                            'sort' => (int)$val['sort'],
                            'info' => $val['content'],
                            'type' => 0,
                            'readTime' => $val['times'],
                            'param' => '',
                            'params' => []
                        ];
                    }
                }
                if(!empty($appFlowInfo->gather_info_set_json)) {
                    foreach ($appFlowInfo->gather_info_set_json as $val){
                        if (isset($val['is_nickname']) && $val['is_nickname'] == 1) {
                            $flowList[] = [
                                'index' => $index,
                                'sort' => (int)$val['sort'],
                                'info' => '你的昵称',
                                'type' => 1,
                                'readTime' => 0,
                                'param' => 'nickname',
                                'params' => []
                            ];
                        }
                        if (isset($val['is_age']) && $val['is_age'] == 1) {
                            $flowList[] = [
                                'index' => $index,
                                'sort' => (int)$val['sort'],
                                'info' => '你的年龄',
                                'type' => 2,
                                'readTime' => 0,
                                'param' => 'age_range_id',
                                'params' => changeKeys($userConfig['age_range_list'], ['age_range'], ['name'])
                            ];
                        }
                        if (isset($val['is_identity']) && $val['is_identity'] == 1) {
                            $flowList[] = [
                                'index' => $index,
                                'sort' => (int)$val['sort'],
                                'info' => '你的身份',
                                'type' => 2,
                                'readTime' => 0,
                                'param' => 'identity_id',
                                'params' => changeKeys($userConfig['identity_list'], ['identity'], ['name'])
                            ];
                        }
                        if (isset($val['is_education']) && $val['is_education'] == 1) {
                            $flowList[] = [
                                'index' => $index,
                                'sort' => (int)$val['sort'],
                                'info' => '你的学历',
                                'type' => 2,
                                'readTime' => 0,
                                'param' => 'education_id',
                                'params' => changeKeys($userConfig['education_list'], ['education'], ['name'])
                            ];
                        }
                        if (isset($val['is_study_demand']) && $val['is_study_demand'] == 1) {
                            $flowList[] = [
                                'index' => $index,
                                'sort' => (int)$val['sort'],
                                'info' => '学习需求',
                                'type' => 2,
                                'readTime' => 0,
                                'param' => 'study_goal_id',
                                'params' => changeKeys($userConfig['study_goal_list'], ['is_has_computer'], ['name'])
                            ];
                        }
                        if (isset($val['is_has_computer']) && $val['is_has_computer'] == 1) {
                            $flowList[] = [
                                'index' => $index,
                                'sort' => (int)$val['sort'],
                                'info' => '是否有电脑',
                                'type' => 2,
                                'readTime' => 0,
                                'param' => 'is_has_computer',
                                'params' => changeKeys($userConfig['is_has_computer_list'], ['is_has_computer'], ['name'])
                            ];
                        }
                    }
                }
                if(!empty($appFlowInfo->custom_problem_json)) {
                    foreach ($appFlowInfo->custom_problem_json as $val) {
                        $param = 'custom_answer';
                        if($val['type'] == 2){
                            $param = 'answer_id';
                        }
                        $paramName = [];
                        if(isset($val['name']) && !empty($val['name'])){
                            foreach($val['name']  as $k => $v){
                                $paramName[] = ['id' => $k+1,'name' => $v['text']];
                            }
                        }
                        $flowList[] = [
                            'index' => $index,
                            'sort' => (int)$val['sort'],
                            'info' => $val['content'],
                            'type' => $val['type'],
                            'readTime' => 0,
                            'param' => $param,
                            'params' => $paramName
                        ];
                    }
                }
                if(!empty($flowList)) {
                    $sort = array_column(array_values($flowList), 'sort');
                    array_multisort($sort, SORT_ASC, $flowList);
                    foreach ($flowList as $k => $v) {
                        $index++;
                        $flowList[$k]['index'] = $index;
                        unset($flowList[$k]['sort']);
                        $data['flowList'][] = $flowList[$k];
                    }
                }
            }
        }
        return $data;
    }


}