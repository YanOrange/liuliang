<?php
/**
 * 账户明细表模型
 */

namespace app\model\api\vestbag;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\ExceptionStd;

class VestUserAccountDetail extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'vest_user_account_detail';

    //获取账户明细列表
    public static function getAccountDetailList($param = [])
    {
        extract($param);
        if ($type == 'all') {
            $where = ' 1 = 1';
        } else {
            $where = ' type = ' . $type;
        }
        $time = strtotime(isset($month) && !empty($month) ? date('Y') . '-' . $month . '-01' : date('Y-m') . '-01');
        // echo date('Y-m-d',$time);
        //支出金额
        $disbursementAmount = self::where('uid', $GLOBALS['uid'])->where('create_time', '>=', $time)->where('type', 1)->sum('amount');
        //收入金额
        $incomeAmount = self::where('uid', $GLOBALS['uid'])->where('create_time', '>=', $time)->where('type', 2)->sum('amount');
        $accountDetailList  = self::field('amount,icon_id,account_id,type,create_time')->with(['icon','account'])->where('uid', $GLOBALS['uid'])->where('create_time', '>=', $time)->where($where)->order('id','desc')->select()->toArray();
        $data = [];
        foreach ($accountDetailList as $item => $value) {
            $data[$value['create_time']][] = $value;
        }
        $accountDetailListData = [];

        $weekarray = ["星期日","星期一","星期二","星期三","星期四","星期五","星期六"];
        foreach ($data as $key => &$val) {
            $todayDisbursement = 0;
            $todayIncome = 0;
            foreach ($val as $v) {
                if ($v['type'] == 1) {
                    $todayDisbursement += $v['amount'];
                } else{
                    $todayIncome += $v['amount'];
                }
            }
            $accountDetailListData[] = [
                'date' => date("m月d日",strtotime($key)),
                'week' => $weekarray[date("w",strtotime($key))],
                'today_disbursement' => (string)$todayDisbursement,
                'today_income' => (string)$todayIncome,
                'account_detail_data' => $val,
            ];

        }
        return [
            'disbursement_amount' => $disbursementAmount > 0 ? (string)$disbursementAmount : '0.00',
            'income_amount' => $incomeAmount > 0 ? (string)$incomeAmount : '0.00',
            'account_detail_list_data' => $accountDetailListData,
        ];
        //dump($accountDetailList);
    }
    //设置本月预算
    public static function setCurrentMonthBudget($param = [])
    {
        extract($param);
        $redis = get_redis();
        $redis->set('current_month_budget_' . $GLOBALS['uid'], $current_month_budget_amount);
        return self::getCurrentMonthBudget();
    }
    //获取本月预算
    public static function getCurrentMonthBudget($param = [])
    {
        extract($param);
        $redis = get_redis();
        //本月预算
        $currentMonthBudget = $redis->get('current_month_budget_' . $GLOBALS['uid']);
        //本月支出
        $currentMonthDisbursement = self::where('uid', $GLOBALS['uid'])->whereTime('create_time', 'month')->where('type', 1)->sum('amount');
        $currentMonthBudget = $currentMonthBudget > 0 ? $currentMonthBudget : '0.00';
        $currentMonthDisbursement = $currentMonthDisbursement > 0 ? $currentMonthDisbursement : '0.00';
        return [
            'current_month_budget' => (string)$currentMonthBudget,
            'current_month_disbursement' => (string)$currentMonthDisbursement,
            'residue_current_month_disbursement' => (string)($currentMonthBudget - $currentMonthDisbursement),
        ];

    }
    public function getCreateTimeAttr($value)
    {
        return !empty($value) ? date('Y-m-d', $value) : '';
    }
    //添加账户
    public static  function addAccountDetail($param = [])
    {
        extract($param);
        $ret = self::create([
            'icon_id' => $icon_id,
            'type' => $type,
            'amount' => $account_remaining,
            'account_id' => $account_id,
            'uid' => $GLOBALS['uid'],
        ]);
        $vestUserAccount = VestUserAccount::where('id', $account_id)->find();
        if ($type == 1) {
            $vestUserAccount->account_remaining = $vestUserAccount->account_remaining-$account_remaining;
        }
        if ($type == 2) {
            $vestUserAccount->account_remaining = $vestUserAccount->account_remaining+$account_remaining;
        }
        $vestUserAccount->save();
        if (!$ret) {
            new ExceptionStd('添加明细失败');
        }
    }
    public function icon()
    {
        return $this->belongsTo('app\model\api\vestbag\VestAccountIcon','icon_id','id')->field('id,icon_name,icon_bright_url,icon_dark_url')->removeOption('soft_delete');
    }
    public function account()
    {
        return $this->belongsTo('app\model\api\vestbag\VestUserAccount','account_id','id')->field('id,account_name')->removeOption('soft_delete');
    }

}
