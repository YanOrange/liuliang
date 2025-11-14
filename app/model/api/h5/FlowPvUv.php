<?php

namespace app\model\api\h5;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\model\api\Channel;

class FlowPvUv extends BaseModel
{
    use SoftDelete;
    //表名
    protected $name = 'flow_pv_uv';

    public static function threadPvUv($params = [])
    {
        extract($params);
        $channel = $params['channel'] ?? '';
        $forFlowId = $params['for_flow_id'] ?? 0;
        $startTime = $params['start_time'] ?? 0;
        $threadId = $params['thread_id'] ?? 0;
        $nickname = $params['nickname'] ?? '';
        $phone = $params['phone'] ?? '';
        $uid = $params['uid'] ?? 0;
        $ip = request()->ip();
        $sessionId = session_id();
        if($forFlowId && $startTime) {
            //$flowPvUv = FlowPvUv::where('channel',$channel)->where('type',2)->where('ip',$ip)->find();
            $flowPvUv = [];
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
            $startTime = mb_substr($startTime,0,10);
            if(!empty($flowPvUv)){
                FlowPvUv::create([
                    'uid' => $uid,
                    'phone' => $phone,
                    'nickname' => $nickname,
                    'session_id' => $sessionId,
                    'channel' => $channel,
                    'thread_id' => $threadId,
                    'for_flow_id' => $forFlowId,
                    'duration' => isset($startTime) && !empty($startTime) ? time()-$startTime : 0,
                    'ip' => $ip,
                    'type' => 1,
                    'start_time' => $startTime
                ]);
            }else {
                FlowPvUv::create([
                    'uid' => $uid,
                    'phone' => $phone,
                    'nickname' => $nickname,
                    'session_id' => $sessionId,
                    'channel' => $channel,
                    'thread_id' => $threadId,
                    'for_flow_id' => $forFlowId,
                    'duration' => isset($startTime) && !empty($startTime) ? time()-$startTime : 0,
                    'ip' => $ip,
                    'type' => 1,
                    'start_time' => $startTime
                ]);
                FlowPvUv::create([
                    'uid' => $uid,
                    'phone' => $phone,
                    'nickname' => $nickname,
                    'session_id' => $sessionId,
                    'channel' => $channel,
                    'thread_id' => $threadId,
                    'for_flow_id' => $forFlowId,
                    'duration' => isset($startTime) && !empty($startTime) ? time()-$startTime : 0,
                    'ip' => $ip,
                    'type' => 2,
                    'start_time' => $startTime
                ]);
            }
        }
        return;
    }

    public static function setH5PvUv($params = [])
    {
        $h5Uid      = $params['h5_uid'] ?? '';
        $position   = $params['position'] ?? 'page';
        $forFlowId  = $params['for_flow_id'] ?? 0;

        $redis = get_redis();
        $redis->incrBy(self::totalH5PvKey($forFlowId, $position), 1);
        $redis->incrBy(self::dailyH5PvKey($forFlowId, $position), 1);
        $redis->hSet(self::totalH5UvKey($forFlowId, $position), $h5Uid, 1);
        $redis->hSet(self::dailyH5UvKey($forFlowId, $position), $h5Uid, 1);
    }

    public static function getH5TotalPv($forFlowId, $position)
    {
        return (int) get_redis()->get(self::totalH5PvKey($forFlowId, $position));
    }

    public static function getH5TotalUv($forFlowId, $position)
    {
        return (int)  get_redis()->hLen(self::totalH5UvKey($forFlowId, $position));
    }

    protected static function totalH5PvKey($forFlowId, $position)
    {
        return "pv:h5_{$position}:total_{$forFlowId}";
    }

    protected static function totalH5UvKey($forFlowId, $position)
    {
        return "uv:h5_{$position}:uv:total_{$forFlowId}";
    }

    protected static function dailyH5PvKey($forFlowId, $position)
    {
        return "pv:h5_{$position}:". date('Ymd') .":{$forFlowId}";
    }

    protected static function dailyH5UvKey($forFlowId, $position)
    {
        return "uv:h5_{$position}:". date('Ymd') .":{$forFlowId}";
    }
}