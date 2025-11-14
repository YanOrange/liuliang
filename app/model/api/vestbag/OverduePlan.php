<?php
/**
 * 计划提醒
 */

namespace app\model\api\vestbag;

use app\lib\api\exception\Exception;
use laytp\BaseModel;
use app\lib\api\exception\ExceptionStd;
class OverduePlan extends BaseModel
{
    //模型名
    protected $name = 'overdue_plan';
    public function getPlanTimeAttr($value, $data)
    {
        return isset($data['plan_time']) && !empty($data['plan_time']) ? date('Y/m/d H:i', $data['plan_time']) : '';
    }
    public function getStatusAttr($value, $data)
    {
        $nowTime = time();
        if (isset($data['status']) && isset($data['plan_time'])) {
            if ($data['status'] != 1) {
                if ($data['plan_time'] > $nowTime) {
                    return 2;
                }
                return 3;
            }
            return $data['status'];
        }
    }
    //添加计划
    public static function createPlan($params = [])
    {
        extract($params);
        $planTime = strtotime(date('Ym') . $due_date . $reminder_time);
        $ret = self::create(array_merge($params,['uid' => $GLOBALS['uid'], 'plan_time' => $planTime]));
        if (!$ret) {
            new Exception('添加失败');
        }
        return;
    }
    //更改计划状态
    public static function savePlanStatus($params = [])
    {
        extract($params);
        $planInfo = self::find($plan_id);
        if (empty($planInfo)) {
            new Exception('计提醒划不存在');
        }
        $planInfo->status = 1;
        $planInfo->save();
    }
    public static function getMyNewPlanList($params = [])
    {
        $myPlanList = self::field('id,uid,plan_type,title,plan_time,status')->where('uid', $GLOBALS['uid'])->order('plan_time','desc')->limit(3)->select()->toArray();
        return $myPlanList;
    }
    //我的计划
    public static function myPlanList($params = [])
    {
        extract($params);
        $date = isset($date) && !empty($date) ? $date : date("Y-m");
        $checkMonthList = self::whereMonth('plan_time', $date)->where('uid', $GLOBALS['uid'])->group('due_date')->column('due_date');
        $myPlanList = self::field('id,uid,plan_type,title,plan_time,status')->where('uid', $GLOBALS['uid'])->whereMonth('plan_time', $date)->order('plan_time','desc')->paginate(!isset($pageSize) ? 10 : $pageSize)->toArray();
        $myPlanList['checkMonthList'] = $checkMonthList;
        return $myPlanList;
    }
}
