<?php

namespace app\controller\api\h5;

use app\controller\api\BaseApi;
use app\model\api\h5\FlowPvUv;
use app\model\api\UserList;

class Flow extends BaseApi
{
    protected $noNeedLogin = ['*'];
    protected $noNeedCheckSign = ['*'];

    //浏览量
    public function addPvUv()
    {
        $params = $this->request->post();
        extract($params);
        $this->commonApiValidate($params, 'app\validate\api\h5\Flow', 'addPvUv');
        $channel = $params['channel'] ?? '';
        $forFlowId = $params['for_flow_id'] ?? 0;
        $startTime = $params['start_time'] ?? 0;
        $ip = $this->request->ip();
        if($channel && $forFlowId && $startTime) {
            $flowPvUv = FlowPvUv::where('channel',$channel)->where('type',1)->where('ip',$ip)->order('id desc')->find();
            $duration = isset($startTime) && !empty($startTime) ? time() - $startTime : 0;
            if(!empty($flowPvUv)){
                $flowPvUv->save(['duration' => $duration,'start_time' => $startTime]);
            }
        }
        return $this->success('记录成功',[]);
    }

    //浏览量
    public function addPvUv1()
    {
        $params = $this->request->post();
        extract($params);
        $this->commonApiValidate($params, 'app\validate\api\h5\Flow', 'addPvUv');
        $channel = $params['channel'] ?? '';
        $forFlowId = $params['for_flow_id'] ?? 0;
        $startTime = $params['start_time'] ?? 0;
        $ip = $this->request->ip();
        $sessionId = session_id();
        if($channel && $forFlowId && $startTime) {
            $flowPvUv = FlowPvUv::where('channel',$channel)->where('type',2)->where('ip',$ip)->find();
            $nickname = FlowPvUv::where('ip', $ip)->order('id desc')->value('nickname');
            if(empty($nickname)){
                $nickname = FlowPvUv::whereLike('nickname','匿名%')->order('id desc')->value('nickname');
                if(!empty($nickname)){
                    $number = (int)mb_substr($nickname, 2);
                    $number = $number+1;
                    $nickname = '匿名'.$number;
                }else{
                    $nickname = '匿名1';
                }
            }
            if(!empty($flowPvUv)){
                $flowPvUvCt = FlowPvUv::where('channel',$channel)
                    ->where('type',1)
                    ->where('ip',$ip)
                    ->whereDay('create_time')
                    ->count();
                if($flowPvUvCt <= 0) {
                    FlowPvUv::create([
                        'nickname' => $nickname,
                        'session_id' => $sessionId,
                        'channel' => $channel,
                        'for_flow_id' => $forFlowId,
                        'duration' => isset($startTime) && !empty($startTime) ? time() - $startTime : 0,
                        'ip' => $ip,
                        'type' => 1,
                        'start_time' => $startTime
                    ]);
                }
            }else {
                FlowPvUv::create([
                    'nickname' => $nickname,
                    'session_id' => $sessionId,
                    'channel' => $channel,
                    'for_flow_id' => $forFlowId,
                    'duration' => isset($startTime) && !empty($startTime) ? time()-$startTime : 0,
                    'ip' => $ip,
                    'type' => 1,
                    'start_time' => $startTime
                ]);
                FlowPvUv::create([
                    'nickname' => $nickname,
                    'session_id' => $sessionId,
                    'channel' => $channel,
                    'for_flow_id' => $forFlowId,
                    'duration' => isset($startTime) && !empty($startTime) ? time()-$startTime : 0,
                    'ip' => $ip,
                    'type' => 2,
                    'start_time' => $startTime
                ]);
            }
        }
        return $this->success('记录成功',[]);
    }
}