<?php

namespace app\model\api\vestbag;

use app\lib\api\exception\Exception;
use app\model\admin\GatherUserInfo;
use app\model\api\UserList;
use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\ExceptionStd;
use think\facade\Db;
class OverdueDebt extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'overdue_debt';

    //添加案件
    public static function createOverdueDebt($params = [])
    {
        extract($params);
        $ret = self::create(array_merge($params, ['uid' => $GLOBALS['uid']]));
        if (!$ret) {
            new Exception('案件添加失败');
        }
        return ['id' => $ret->id];
    }
//    public function getCreateTimeAttr($value, $data)
//    {
//        $weekData = ["日", "一", "二", "三", "四", "五", "六"];
//        return isset($data['create_time']) && !empty($data['create_time']) ? date('H:i', $data['create_time']) .' 星期'. $weekData[date("w", $data['create_time'])] : '';
//    }
    //获取我的案件列表
    public static function getMyOverdueDebtList($params = [])
    {
        extract($params);
        $data = self::field('id,debt_platform,debt_amount,zw_mold, create_time')->where('uid', $GLOBALS['uid'])->select()->toArray();

        $zwMoldList = self::zwMoldList();
        foreach ($data as &$v){
            $v['zw_mold_desc'] =  isset($zwMoldList[$v['zw_mold']]) ? $zwMoldList[$v['zw_mold']] : '';
        }
        return $data;
    }
    //案件详情
    public static function getMyOverdueDebtDetail($params = [])
    {
        extract($params);
        $debtInfo = self::field('id,debt_platform,debt_amount,total_periods,residue_periods,is_overdue,create_time')->where('uid',$GLOBALS['uid'] )->where('id', $debt_id)->find();
        if (empty($debtInfo)) {
            new ExceptionStd('案件不存在');
        }
        $phone = UserList::where('id', $GLOBALS['uid'])->value('phone');
        $handlingProgress = '';
        if (!empty($phone)) {
            $uid = Db::name('user_list_external')->where('phone', $phone)->value('id');
            if (!empty($id)) {
                $threadId = Db::name('thread_external')->where('uid', $uid)->value('id');
                if (!empty($threadId)) {
                    $collectionOrderFeeInfo = Db::name('collection_order_fee')->field('handling_progress,windup_content')->where('thread_id', $threadId)->find();
                }
            }
        }
        $handlingProgress = isset($collectionOrderFeeInfo['handling_progress']) && !empty($collectionOrderFeeInfo['handling_progress']) ? explode(',', $collectionOrderFeeInfo['handling_progress']) : [];
        $debtInfo = $debtInfo->toArray();
        $debtInfo['create_time'] = strtotime($debtInfo['create_time']);
        $progressList = [
            ['progress_time' => date('Y-m-d H:i',($debtInfo['create_time'] + 7200)), 'progress_desc' => '法务接收案件中' , 'selected' => 1],
            ['progress_time' => date('Y-m-d H:i',($debtInfo['create_time'] + 7200 + (86400*2))), 'progress_desc' => '法务正在处理', 'selected' => in_array(1,$handlingProgress) ? 1 : 0],
            ['progress_time' => date('Y-m-d H:i',($debtInfo['create_time'] + 7200 + (86400*4))), 'progress_desc' => '法务准备结案资料', 'selected' => in_array(6,$handlingProgress) ? 1 : 0],
            ['progress_time' => date('Y-m-d H:i',($debtInfo['create_time'] + 7200 + (86400*7))), 'progress_desc' => '结案', 'selected' => in_array(8,$handlingProgress) ? 1 : 0],
        ];
        $progressStr = '法务接收案件中';
        $windupContent = isset($collectionOrderFeeInfo['windup_content']) && !empty($collectionOrderFeeInfo['windup_content']) ? $collectionOrderFeeInfo : '等待法务结案';
        foreach ($progressList as $val) {
            if ($val['selected'] == 1) {
                $progressStr = $val['progress_desc'];
            }
        }
        $debtInfo['progress_list'] = $progressList;
        $debtInfo['progress'] = $progressStr;
        $debtInfo['settlement_plan'] = $windupContent;
        return $debtInfo;
    }
    // 案件详情
    public static function getMyOverdueDebtDetailV1($params = [])
    {
        extract($params);
        $debtInfo = self::field('id,debt_platform,debt_amount,zw_mold,total_periods,residue_periods,is_overdue,create_time')->where('uid',$GLOBALS['uid'] )->where('id', $debt_id)->find();
        if (empty($debtInfo)) {
            new ExceptionStd('案件不存在');
        }
        $debtInfo = $debtInfo->toArray();
        $debtInfo['create_time'] = strtotime($debtInfo['create_time']);

        $progressList = [
            ['progress_title' => '提交平台委托', 'progress_desc' => '您已成功提交平台委托，您迈出的这一步将会为您带来更舒适的体验。平台会根据用户提交的委托信息和历史数据，为用户分配合适的法务老师。', 'progress_time' => $debtInfo['create_time'], 'selected' => 1],
            ['progress_title' => '等待法务接受案件', 'progress_desc' => '请等待法务老师接收案件，这需要一些时间进行系统通知，同时会告知法务老师准备资料，以给您更好的咨询感受，让您对负债不再担忧。', 'progress_time' => $debtInfo['create_time'] + 1800, 'selected' => 1],
            ['progress_title' => '成功委托咨询', 'progress_desc' => '这将对您提高债务处理效率和服务质量带来帮助，请等待法务为您安排合理解决都具有重要的意义。正在沟通平台最优政策，个性化的债务管理建议，帮助您更好地规划财务。', 'progress_time' => $debtInfo['create_time'] + 3600, 'selected' => 1],
        ];
        $zwMoldList = self::zwMoldList();
        $progress = '';
        foreach ($progressList as $key => &$val) {
            if ($val['selected'] == 1) {
                $progress = $key;
                $val['progress_time'] = date('m-d H:i', $val['progress_time']);
            } else {
                $val['progress_time'] = '';
            }
        }
        return [
            'id'            => $debtInfo['id'],
            'debt_platform' => $debtInfo['debt_platform'],
            'debt_amount'   => $debtInfo['debt_amount'],
            'zw_mold_desc'  => isset($zwMoldList[$debtInfo['zw_mold']]) ? $zwMoldList[$debtInfo['zw_mold']] : '',
            'progress_list' => $progressList,
            'progress'      => $progress,
        ];
    }

    private static function zwMoldList()
    {
        $zwMoldList = [];
        $gatherInfoJson = GatherUserInfo::where('field','zw_mold')->value('gather_info_json');
        if(!empty($gatherInfoJson)) {
            $zwMoldList = json_decode($gatherInfoJson, true);
            $zwMoldList = array_column($zwMoldList, 'name', 'id');
        }
        return $zwMoldList;
    }
}
