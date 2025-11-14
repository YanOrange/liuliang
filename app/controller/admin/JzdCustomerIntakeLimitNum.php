<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Event;
use think\facade\Request;
use app\validate\admin\customer\JzdCustomer as JzdCustomerValidate;
use app\model\admin\CustomerRestDay;
use app\model\admin\MerchantOrganization;
use app\model\admin\Merchant;
use app\model\admin\Thread;
use app\model\admin\thread\ThreadUserStatus;
use app\model\admin\QiyuesuoOnlineContractJzd;
use app\model\admin\ThreadExternal;
use app\model\admin\JzdCustomerAssignMode;
use app\model\admin\Customer;
use app\model\admin\TransactionList;
use app\model\admin\JzdCustomerAssignLimitNumLog;


/**
 * 教之道控制器
 */
class JzdCustomerIntakeLimitNum extends Backend
{
    protected $model;//当前模型对象
    protected $noNeedAuth = ['*'];
    protected $noNeedLogin = [];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\JzdCustomerComponentRule();
    }

    //查看1
    public function customerInputList()
    {
        $assign_mode = $this->request->post('assign_mode');
        $merchantId = $this->request->post('merchant_id');
        //$assign_mode = $assign_mode ?? 1;
        //$merchantId = 177;
        $datetime = date('Y-m-d');
        if($merchantId == 251){
            $merchantOrganizationData = [
                '218' => '销售一组组员',
            ];
        }
        if($merchantId == 177){
            $merchantOrganizationData = [
                '106' => '笔笔老师',
                '110' => '七海老师',
                '111' => '浩浩老师',
                '1909' => '卡卡老师',
                //'143' => '文老师',
            ];
        }
        $data['assign_mode'] = JzdCustomerAssignMode::where('merchant_id',$merchantId)->field('id,assign_mode,assign_rule,sale_group_nums,gross_profit_day')->find();
        $assign_mode = $assign_mode > 0 ? $assign_mode : $data['assign_mode']->assign_mode;
        if($assign_mode == 1) {
            $where1[] = ['merchant_id','=',$merchantId];
            $where1[] = ['assign_mode','=',1];
            $where1[] = ['datetime','=',$datetime];
            $merchantOrganizationCustomer1 = $this->model->with(['organization'])->where($where1)
                ->where('assign_rule',1)
                ->field('id,merchant_organization_id')
                ->group('merchant_organization_id')
                ->paginate(100)
                ->each(function ($item, $key) use ($where1) {
                    $item['new_daily_intake_limit_nums_total'] = $this->model->where($where1)
                        ->where('assign_rule',1)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->sum('new_daily_intake_limit_nums');
                    $item['old_daily_intake_limit_nums_total'] = $this->model->where($where1)
                        ->where('assign_rule',1)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->sum('old_daily_intake_limit_nums');
                    $item['customer'] = $this->model->with(['customer'])->where($where1)
                        ->where('assign_rule',1)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->field('customer_id as id,customer_id,is_new,new_daily_intake_limit_nums,old_daily_intake_limit_nums')
                        ->select()
                        ->toArray();
                    return $item;
                })
                ->toArray();
            $merchantOrganizationCustomer2 = $this->model->with(['organization'])->where($where1)
                ->where('assign_rule',2)
                ->field('id,merchant_organization_id')
                ->group('merchant_organization_id')
                ->paginate(100)
                ->each(function ($item, $key) use ($where1) {
                    $item['new_daily_intake_limit_nums_total'] = $this->model->where($where1)
                        ->where('assign_rule',2)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->sum('new_daily_intake_limit_nums');
                    $item['old_daily_intake_limit_nums_total'] = $this->model->where($where1)
                        ->where('assign_rule',2)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->sum('old_daily_intake_limit_nums');
                    $item['customer'] = $this->model->with(['customer'])->where($where1)
                        ->where('assign_rule',2)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->field('customer_id as id,customer_id,is_new,new_daily_intake_limit_nums,old_daily_intake_limit_nums')
                        ->select()
                        ->toArray();
                    return $item;
                })
                ->toArray();
            $newDailyIntakeLimitNumsTotal1 = 0;
            $oldDailyIntakeLimitNumsTotal1 = 0;
            if (!empty($merchantOrganizationCustomer1['data'])) {
                foreach ($merchantOrganizationCustomer1['data'] as $key => &$item1) {
                    unset($merchantOrganizationCustomer1['data'][$key]['organization']);
                    foreach($item1['customer'] as $ckey => $customer){
                        $newDailyIntakeLimitNumsTotal1 += $customer['new_daily_intake_limit_nums'] ?? 0;
                        $oldDailyIntakeLimitNumsTotal1 += $customer['old_daily_intake_limit_nums'] ?? 0;
                        unset($item1['customer'][$ckey]['customer']);
                    }
                    if(isset($merchantOrganizationData[$item1['merchant_organization_id']])) $item1['name'] = $merchantOrganizationData[$item1['merchant_organization_id']];
                }
            }
            $newDailyIntakeLimitNumsTotal2 = 0;
            $oldDailyIntakeLimitNumsTotal2 = 0;
            if (!empty($merchantOrganizationCustomer2['data'])) {
                foreach ($merchantOrganizationCustomer2['data'] as $key => &$item2) {
                    unset($merchantOrganizationCustomer2['data'][$key]['organization']);
                    foreach($item2['customer'] as $ckey => $customer){
                        $newDailyIntakeLimitNumsTotal2 += $customer['new_daily_intake_limit_nums'] ?? 0;
                        $oldDailyIntakeLimitNumsTotal2 += $customer['old_daily_intake_limit_nums'] ?? 0;
                        unset($item2['customer'][$ckey]['customer']);
                    }
                    if(isset($merchantOrganizationData[$item2['merchant_organization_id']])) $item2['name'] = $merchantOrganizationData[$item2['merchant_organization_id']];
                }
            }
            $data['organization_customer_gross_list'] = array_values($merchantOrganizationCustomer1['data']);
            $data['organization_customer_roi_list'] = array_values($merchantOrganizationCustomer2['data']);
            $data['new_daily_intake_limit_nums_gross_total'] = $newDailyIntakeLimitNumsTotal1;
            $data['old_daily_intake_limit_nums_gross_total'] = $oldDailyIntakeLimitNumsTotal1;
            $data['new_daily_intake_limit_nums_roi_total'] = $newDailyIntakeLimitNumsTotal2;
            $data['old_daily_intake_limit_nums_roi_total'] = $oldDailyIntakeLimitNumsTotal2;
        }else if($assign_mode == 2) {
            $where2[] = ['merchant_id','=',$merchantId];
            $where2[] = ['assign_mode','=',2];
            $where2[] = ['datetime','=',$datetime];
            $merchantOrganizationCustomer = $this->model->with(['organization'])->where($where2)
                ->field('id,merchant_organization_id')
                ->group('merchant_organization_id')
                ->paginate(100)
                ->each(function ($item, $key) use ($where2) {
                    $item['new_daily_intake_limit_nums_total'] = $this->model->where($where2)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->sum('new_daily_intake_limit_nums');
                    $item['old_daily_intake_limit_nums_total'] = $this->model->where($where2)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->sum('old_daily_intake_limit_nums');
                    $item['customer'] = $this->model->with(['customer'])->where($where2)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->field('customer_id as id,customer_id,is_new,new_daily_intake_limit_nums,old_daily_intake_limit_nums,assign_intake_limit_nums,apply_rate,register_rate')
                        ->select()
                        ->toArray();
                    return $item;
                })
                ->toArray();
            $newDailyIntakeLimitNumsTotal = 0;
            $oldDailyIntakeLimitNumsTotal = 0;
            if (!empty($merchantOrganizationCustomer['data'])) {
                foreach ($merchantOrganizationCustomer['data'] as $key => &$item) {
                    unset($merchantOrganizationCustomer['data'][$key]['organization']);
                    foreach($item['customer'] as $ckey => $customer){
                        $newDailyIntakeLimitNumsTotal += $customer['new_daily_intake_limit_nums'] ?? 0;
                        $oldDailyIntakeLimitNumsTotal += $customer['old_daily_intake_limit_nums'] ?? 0;
                        unset($item['customer'][$ckey]['customer']);
                    }
                    if(isset($merchantOrganizationData[$item['merchant_organization_id']])) $item['name'] = $merchantOrganizationData[$item['merchant_organization_id']];
                }
            }
            $data['organization_customer_gross_list'] = array_values($merchantOrganizationCustomer['data']);
            $data['organization_customer_roi_list'] = [];
            $data['new_daily_intake_limit_nums_total'] = $newDailyIntakeLimitNumsTotal;
            $data['old_daily_intake_limit_nums_total'] = $oldDailyIntakeLimitNumsTotal;
        }else if($assign_mode == 3) {
            $merchantOrganizationPids = [106,110,111,141,143];

            $organization = (new \app\model\admin\MerchantOrganization())->whereIn('id',$merchantOrganizationPids)
                ->field('id,name,daily_intake_limit_nums')
                ->select()
                ->toArray();
            if (!empty($organization)) {
                foreach ($organization as &$item) {
                    $merchantOrganizationIds = (new MerchantOrganization())->where('pid',$item['id'])->column('id');
                    array_unshift($merchantOrganizationIds,$item['id']);
                    $item['customer_num'] = (new \app\model\admin\Customer())->where('merchant_id',$merchantId)
                        ->where('thread_status',1)
                        ->whereIn('merchant_organization_id',$merchantOrganizationIds)
                        ->count();
                    if(isset($merchantOrganizationData[$item['id']])) $item['name'] = $merchantOrganizationData[$item['id']];
                }
            }
            $where3[] = ['merchant_id','=',$merchantId];
            $where3[] = ['assign_mode','=',3];
            $where3[] = ['datetime','=',$datetime];
            $merchantOrganizationCustomer1 = $this->model->with(['organization'])->where($where3)
                ->where('assign_rule',1)
                ->field('id,merchant_organization_id')
                ->group('merchant_organization_id')
                ->paginate(100)
                ->each(function ($item, $key) use ($where3) {
                    $item['new_daily_intake_limit_nums_total'] = $this->model->where($where3)
                        ->where('assign_rule',1)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->sum('new_daily_intake_limit_nums');
                    $item['old_daily_intake_limit_nums_total'] = $this->model->where($where3)
                        ->where('assign_rule',1)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->sum('old_daily_intake_limit_nums');
                    $item['customer'] = $this->model->with(['customer'])->where($where3)
                        ->where('assign_rule',1)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->field('customer_id as id,customer_id,is_new,new_daily_intake_limit_nums,old_daily_intake_limit_nums')
                        ->select()
                        ->toArray();
                    return $item;
                })
                ->toArray();
            $merchantOrganizationCustomer2 = $this->model->with(['organization'])->where($where3)
                ->where('assign_rule',2)
                ->field('id,merchant_organization_id')
                ->group('merchant_organization_id')
                ->paginate(100)
                ->each(function ($item, $key) use ($where3) {
                    $item['new_daily_intake_limit_nums_total'] = $this->model->where($where3)
                        ->where('assign_rule',2)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->sum('new_daily_intake_limit_nums');
                    $item['old_daily_intake_limit_nums_total'] = $this->model->where($where3)
                        ->where('assign_rule',2)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->sum('old_daily_intake_limit_nums');
                    $item['customer'] = $this->model->with(['customer'])->where($where3)
                        ->where('assign_rule',2)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->field('customer_id as id,customer_id,is_new,new_daily_intake_limit_nums,old_daily_intake_limit_nums')
                        ->select()
                        ->toArray();
                    return $item;
                })
                ->toArray();
            $newDailyIntakeLimitNumsTotal1 = 0;
            $oldDailyIntakeLimitNumsTotal1 = 0;
            if (!empty($merchantOrganizationCustomer1['data'])) {
                foreach ($merchantOrganizationCustomer1['data'] as $key => &$item1) {
                    unset($merchantOrganizationCustomer1['data'][$key]['organization']);
                    foreach($item1['customer'] as $ckey => $customer){
                        $newDailyIntakeLimitNumsTotal1 += $customer['new_daily_intake_limit_nums'] ?? 0;
                        $oldDailyIntakeLimitNumsTotal1 += $customer['old_daily_intake_limit_nums'] ?? 0;
                        unset($item1['customer'][$ckey]['customer']);
                    }
                    if(isset($merchantOrganizationData[$item1['merchant_organization_id']])) $item1['name'] = $merchantOrganizationData[$item1['merchant_organization_id']];
                }
            }
            $newDailyIntakeLimitNumsTotal2 = 0;
            $oldDailyIntakeLimitNumsTotal2 = 0;
            if (!empty($merchantOrganizationCustomer2['data'])) {
                foreach ($merchantOrganizationCustomer2['data'] as $key => &$item2) {
                    unset($merchantOrganizationCustomer2['data'][$key]['organization']);
                    foreach($item2['customer'] as $ckey => $customer){
                        $newDailyIntakeLimitNumsTotal2 += $customer['new_daily_intake_limit_nums'] ?? 0;
                        $oldDailyIntakeLimitNumsTotal2 += $customer['old_daily_intake_limit_nums'] ?? 0;
                        unset($item2['customer'][$ckey]['customer']);
                    }
                    if(isset($merchantOrganizationData[$item2['merchant_organization_id']])) $item2['name'] = $merchantOrganizationData[$item2['merchant_organization_id']];
                }
            }
            $data['organization'] = $organization;
            $data['organization_customer_gross_list'] = array_values($merchantOrganizationCustomer1['data']);
            $data['organization_customer_roi_list'] = array_values($merchantOrganizationCustomer2['data']);
            $data['new_daily_intake_limit_nums_gross_total'] = $newDailyIntakeLimitNumsTotal1;
            $data['old_daily_intake_limit_nums_gross_total'] = $oldDailyIntakeLimitNumsTotal1;
            $data['new_daily_intake_limit_nums_roi_total'] = $newDailyIntakeLimitNumsTotal2;
            $data['old_daily_intake_limit_nums_roi_total'] = $oldDailyIntakeLimitNumsTotal2;
        }else if($assign_mode == 4){
            $where4[] = ['merchant_id','=',$merchantId];
            $where4[] = ['assign_mode','=',4];
            $where4[] = ['datetime','=',$datetime];
            $merchantOrganizationCustomer1 = $this->model->with(['organization'])->where($where4)
                ->where('assign_rule',1)
                ->field('id,merchant_organization_id')
                ->group('merchant_organization_id')
                ->paginate(100)
                ->each(function ($item, $key) use ($where4) {
                    $item['new_daily_intake_limit_nums_total'] = $this->model->where($where4)
                        ->where('assign_rule',1)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->sum('new_daily_intake_limit_nums');
                    $item['old_daily_intake_limit_nums_total'] = $this->model->where($where4)
                        ->where('assign_rule',1)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->sum('old_daily_intake_limit_nums');
                    $item['customer'] = $this->model->with(['customer'])->where($where4)
                        ->where('assign_rule',1)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->field('customer_id as id,customer_id,is_new,new_daily_intake_limit_nums,old_daily_intake_limit_nums,assign_intake_limit_nums,apply_rate,register_rate')
                        ->select()
                        ->toArray();
                    return $item;
                })
                ->toArray();
            $merchantOrganizationCustomer2 = $this->model->with(['organization'])->where($where4)
                ->where('assign_rule',2)
                ->field('id,merchant_organization_id')
                ->group('merchant_organization_id')
                ->paginate(100)
                ->each(function ($item, $key) use ($where4) {
                    $item['new_daily_intake_limit_nums_total'] = $this->model->where($where4)
                        ->where('assign_rule',2)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->sum('new_daily_intake_limit_nums');
                    $item['old_daily_intake_limit_nums_total'] = $this->model->where($where4)
                        ->where('assign_rule',2)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->sum('old_daily_intake_limit_nums');
                    $item['customer'] = $this->model->with(['customer'])->where($where4)
                        ->where('assign_rule',2)
                        ->where('merchant_organization_id', $item['merchant_organization_id'])
                        ->field('customer_id as id,customer_id,is_new,new_daily_intake_limit_nums,old_daily_intake_limit_nums,assign_intake_limit_nums,apply_rate,register_rate')
                        ->select()
                        ->toArray();
                    return $item;
                })
                ->toArray();
            $newDailyIntakeLimitNumsTotal1 = 0;
            $oldDailyIntakeLimitNumsTotal1 = 0;
            if (!empty($merchantOrganizationCustomer1['data'])) {
                foreach ($merchantOrganizationCustomer1['data'] as $key => &$item1) {
                    unset($merchantOrganizationCustomer1['data'][$key]['organization']);
                    foreach($item1['customer'] as $ckey => $customer){
                        $newDailyIntakeLimitNumsTotal1 += $customer['new_daily_intake_limit_nums'] ?? 0;
                        $oldDailyIntakeLimitNumsTotal1 += $customer['old_daily_intake_limit_nums'] ?? 0;
                        unset($item1['customer'][$ckey]['customer']);
                    }
                    if(isset($merchantOrganizationData[$item1['merchant_organization_id']])) $item1['name'] = $merchantOrganizationData[$item1['merchant_organization_id']];
                }
            }
            $newDailyIntakeLimitNumsTotal2 = 0;
            $oldDailyIntakeLimitNumsTotal2 = 0;
            if (!empty($merchantOrganizationCustomer2['data'])) {
                foreach ($merchantOrganizationCustomer2['data'] as $key => &$item2) {
                    unset($merchantOrganizationCustomer2['data'][$key]['organization']);
                    foreach($item2['customer'] as $ckey => $customer){
                        $newDailyIntakeLimitNumsTotal2 += $customer['new_daily_intake_limit_nums'] ?? 0;
                        $oldDailyIntakeLimitNumsTotal2 += $customer['old_daily_intake_limit_nums'] ?? 0;
                        unset($item2['customer'][$ckey]['customer']);
                    }
                    if(isset($merchantOrganizationData[$item2['merchant_organization_id']])) $item2['name'] = $merchantOrganizationData[$item2['merchant_organization_id']];
                }
            }
            $data['organization_customer_gross_list'] = array_values($merchantOrganizationCustomer1['data']);
            $data['organization_customer_roi_list'] = array_values($merchantOrganizationCustomer2['data']);
            $data['new_daily_intake_limit_nums_gross_total'] = $newDailyIntakeLimitNumsTotal1;
            $data['old_daily_intake_limit_nums_gross_total'] = $oldDailyIntakeLimitNumsTotal1;
            $data['new_daily_intake_limit_nums_roi_total'] = $newDailyIntakeLimitNumsTotal2;
            $data['old_daily_intake_limit_nums_roi_total'] = $oldDailyIntakeLimitNumsTotal2;
        }
        return $this->success('数据获取成功', $data);
    }

    //重新分量
    public function initCustomerInput()
    {
        $assignMode = $this->request->post('assign_mode') ?? 1;
        $assignRule = $this->request->post('assign_rule') ?? 0;
        $merchantId = $this->request->post('merchant_id') ?? 177;
        $saleGroupNums = $this->request->post('sale_group_nums') ?? 480;
        //$datetime = date('H') < 16 ? date('Y-m-d') : date('Y-m-d',strtotime('+1 day'));
        $datetime = date('Y-m-d');
        //$merchantId = 177;
        if($merchantId == 251){
            $this->initCustomerInputV2();
            return;
        }
        $merchantOrganizationData = [
            '106' => '笔笔老师',
            '110' => '七海老师',
            '111' => '浩浩老师',
            '141' => '小仟老师',
            //'143' => '文老师',
        ];
        $merchantOrganizationPids = [106,110,111,141];
        $grossProfitDay = JzdCustomerAssignMode::where('merchant_id',$merchantId)->value('gross_profit_day') ?? 15;
        $currentMonth = date('m');
        $currentDay = date('d');
        $currentMonthRestDay = CustomerRestDay::whereIn('month',[$currentMonth - 1,$currentMonth])
            ->where('day','<=',$currentDay)
            ->where('is_rest',1)
            ->field('id,month,day')
            ->select()
            ->toArray();
        $restMonthDay = [];
        foreach($currentMonthRestDay as $date){
            if($date['month'] < 10) $date['month'] = '0'.$date['month'];
            if($date['day'] < 10) $date['day'] = '0'.$date['day'];
            $restMonthDay[] = $date['month'].'-'.$date['day'];
        }
        for($i=0;$i<$grossProfitDay;$i++){
            $thrMonthDay = date('m-d',strtotime("-{$i} day"));
            if(in_array($thrMonthDay,$restMonthDay)){
                $grossProfitDay++;
            }
        }
        try {
            if($assignMode == 3){
                $merchantOrganizationCustomer = MerchantOrganization::where('merchant_id', $merchantId)
                    ->whereIn('id',$merchantOrganizationPids)
                    ->field('id,name,daily_intake_limit_nums as component_num_total')
                    ->paginate(50)
                    ->each(function ($item, $key) use ($merchantId) {
                        $merchantOrganizationIds = (new MerchantOrganization())->where('pid',$item['id'])->column('id');
                        array_unshift($merchantOrganizationIds,$item['id']);
                        $item['customer'] = (new \app\model\admin\Customer())->where('merchant_id', $merchantId)
                            ->whereIn('merchant_organization_id', $merchantOrganizationIds)
                            //->where('thread_status', 1)
                            ->where('status',1)
                            ->field('id,nickname,month_contract_amount,month_paid_amount,create_time')
                            ->select()
                            ->toArray();
                        return $item;
                    })
                    ->toArray();
                if (!empty($merchantOrganizationCustomer['data'])) {
                    foreach ($merchantOrganizationCustomer['data'] as $key => &$item) {
                        $newDailyIntakeLimitNumsSystem = (new \app\model\admin\JzdCustomerComponentRule())
                            ->where('assign_mode',3)
                            ->where('assign_rule',1)
                            ->where('is_auto_assign',2)
                            ->where('merchant_organization_id',$item['id'])
                            ->where('datetime',date('Y-m-d'))
                            ->sum('new_daily_intake_limit_nums');
                        $item['component_num_total'] = $item['component_num_total'] - $newDailyIntakeLimitNumsSystem;
                        if (empty($item['customer'])) {
                            unset($merchantOrganizationCustomer['data'][$key]);
                        }
                        foreach($item['customer'] as $key2 => &$item2){
                            $customerComponentRule = (new \app\model\admin\JzdCustomerComponentRule())->where('customer_id',$item2['id'])
                                ->where('assign_mode',3)
                                ->where('is_auto_assign',2)
                                ->where('datetime',date('Y-m-d'))
                                ->find();
                            if(!empty($customerComponentRule)){
                                unset($item['customer'][$key2]);
                            }
                        }
                        $item['customer'] = array_values($item['customer']);
                        if(isset($merchantOrganizationData[$item['id']])) $item['name'] = $merchantOrganizationData[$item['id']];
                    }
                }
                $merchantOrganizationCustomer = array_values($merchantOrganizationCustomer['data']);
                foreach($merchantOrganizationCustomer as &$item){
                    // //月销售目标
                    // $monthContractAmountTotal = array_sum(array_column($item['customer'],'month_contract_amount'));
                    // $item['component_num_total'] = round($monthContractAmountTotal / 5.5 / $merchantInfo->peak_price / ($currentMonthDay - $currentMonthRestDay));
                    $grossProfitTotal = 0;
                    $grossProfitTotal2 = 0;
                    foreach($item['customer'] as &$item1){
                        $custime = $item1['create_time'];
                        $threadCount = Thread::where('customer_id',$item1['id'])->where('is_test',0)
                            ->whereBetween('create_time',[strtotime($custime),strtotime("$custime +7 day")])
                            ->count();
                        //销售老人/新人
                        $item1['is_new'] = 1;
                        if($threadCount > 0) $item1['is_new'] = 2;
                        if($item1['is_new'] == 2) {
                            //老人近15天销售额
                            $saleTotalAmount = QiyuesuoOnlineContractJzd::alias('qys')
                                ->join('thread_external thr', 'qys.thread_id = thr.id and qys.admin_user_id = thr.origin_customer_id and thr.create_time=thr.last_create_time','inner')
                                ->where('qys.admin_user_id',$item1['id'])
                                ->where('qys.status', 'COMPLETE')
                                ->where('qys.status_msg','<>','INVALID_CANCEL')
                                ->where('qys.source_id','<>',8)
                                ->whereBetween('qys.contract_signing_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->sum('lower_money');
                            //老人近15天本金
                            $depositTotal = TransactionList::alias('tt')
                                ->join('thread_external thr', 'tt.thread_id = thr.id and thr.customer_id ='.$item1['id'],'inner')
                                ->where('tt.collect_type',1)
                                ->where('tt.order_status',1)
                                ->where('tt.money','>',1)
                                ->whereBetween('tt.pay_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->sum('tt.money');
                            //老人近15天有效线索成本(根据线索标签判断，除去是否未成年、是否秒删)
                            $threadPriceTotal = ThreadExternal::alias('thr')
                                ->join('thread_user_status tus','tus.thread_id = thr.id and (tus.current_action_id = 372 or tus.current_action_id = 40)','left')
                                ->where('tus.id',null)
                                ->where('thr.customer_id',$item1['id'])
                                ->where('thr.is_test',0)
                                ->where('thr.allot_nums', 0)
                                ->whereBetween('thr.create_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->sum('thr.thread_price');
                            //实际上班天数
                            $threadDay = ThreadExternal::where('customer_id',$item1['id'])
                                ->where('is_test',0)
                                ->where('source_id','<>', 8)
                                ->whereBetween('create_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->field("id,FROM_UNIXTIME(create_time, '%Y-%m-%d') AS create_day")
                                ->group('create_day')
                                ->count();
                            //毛利计算公式 =  （本人近15天（不限制在本月）销售额（合同金额+定金）— 本人近15（不限制在本月）天有效线索成本（根据线索标签判断，除去是否未成年、是否秒删，这两个标签的学员数量，取当前数量）÷上班天数（统计这个销售在本月实际上班的天数，根据分配流量进行统计，统计有分配流量的天数）
                            //毛利数据处理 = 每个销售毛利开根号“√”
                            $grossProfit = 0;
                            $grossProfit2 = 0;
                            if($saleTotalAmount + $depositTotal - $threadPriceTotal > 0){
                                $grossProfit = sqrt(($saleTotalAmount + $depositTotal - $threadPriceTotal) / $threadDay);
                            }
                            //ROI计算公式=近15天个人天销售额 ÷ 近15天有效销售成本（据线索标签判断，除去是否未成年、是否秒删，这两个标签的学员数量，取当前数量）
                            //ROI数据处理 = 每个销售ROI开根号“√”
                            if($threadPriceTotal > 0){
                                $grossProfit2 = sqrt(($saleTotalAmount + $depositTotal) / $threadPriceTotal);
                            }
                            $item1['gross_profit'] = round($grossProfit);
                            $item1['gross_profit2'] = round($grossProfit2);
                            //销售组毛利总和
                            $grossProfitTotal += $grossProfit;
                            $grossProfitTotal2 += $grossProfit2;
                        }
                    }
                    $item['gross_profit_total'] = round($grossProfitTotal);
                    $item['gross_profit_total2'] = round($grossProfitTotal2);
                }
            }else if($assignMode == 4){
                $newDailyIntakeLimitNumsSystem = (new \app\model\admin\JzdCustomerComponentRule())
                    ->where('assign_mode',4)
                    ->where('assign_rule',1)
                    ->where('is_auto_assign',2)
                    ->where('datetime',date('Y-m-d'))
                    ->sum('new_daily_intake_limit_nums');
                $saleGroupNumsOrgin = $saleGroupNums;
                $saleGroupNums = $saleGroupNums - $newDailyIntakeLimitNumsSystem;
                $merchantOrganizationCustomer = MerchantOrganization::where('merchant_id', $merchantId)
                    ->whereIn('id',$merchantOrganizationPids)
                    ->field('id,name')
                    ->paginate(50)
                    ->each(function ($item, $key) use ($merchantId) {
                        $merchantOrganizationIds = (new MerchantOrganization())->where('pid',$item['id'])->column('id');
                        array_unshift($merchantOrganizationIds,$item['id']);
                        $item['customer'] = (new \app\model\admin\Customer())->where('merchant_id', $merchantId)
                            ->whereIn('merchant_organization_id', $merchantOrganizationIds)
                            //->where('thread_status', 1)
                            ->where('status', 1)
                            ->field('id,nickname,month_contract_amount,month_paid_amount,create_time')
                            ->select()
                            ->toArray();
                        return $item;
                    })
                    ->toArray();
                if (!empty($merchantOrganizationCustomer['data'])) {
                    foreach ($merchantOrganizationCustomer['data'] as $key => &$item) {
                        if (empty($item['customer'])) {
                            unset($merchantOrganizationCustomer['data'][$key]);
                        }
                        foreach($item['customer'] as $key2 => &$item2){
                            $customerComponentRule = (new \app\model\admin\JzdCustomerComponentRule())->where('customer_id',$item2['id'])
                                ->where('assign_mode',4)
                                ->where('is_auto_assign',2)
                                ->where('datetime',date('Y-m-d'))
                                ->find();
                            if(!empty($customerComponentRule)){
                                unset($item['customer'][$key2]);
                            }
                        }
                        $item['customer'] = array_values($item['customer']);
                        if(isset($merchantOrganizationData[$item['id']])) $item['name'] = $merchantOrganizationData[$item['id']];
                    }
                }
                $merchantOrganizationCustomer = array_values($merchantOrganizationCustomer['data']);
                foreach($merchantOrganizationCustomer as &$item){
                    // //月销售目标
                    // $monthContractAmountTotal = array_sum(array_column($item['customer'],'month_contract_amount'));
                    // $item['component_num_total'] = round($monthContractAmountTotal / 5.5 / $merchantInfo->peak_price / ($currentMonthDay - $currentMonthRestDay));
                    $item['component_num_total'] = $saleGroupNums;
                    $grossProfitTotal = 0;
                    foreach($item['customer'] as &$item1){
                        $custime = $item1['create_time'];
                        $threadCount = Thread::where('customer_id',$item1['id'])->where('is_test',0)
                            ->whereBetween('create_time',[strtotime($custime),strtotime("$custime +7 day")])
                            ->count();
                        //销售老人/新人
                        $item1['is_new'] = 1;
                        if($threadCount > 0) $item1['is_new'] = 2;
                        if($item1['is_new'] == 2) {
                            //老人近15天销售额(新)
                            $saleTotalAmountOld = QiyuesuoOnlineContractJzd::alias('qys')
                                ->join('lt_thread_allocated_record tar', 'tar.thread_id = qys.thread_id and qys.admin_user_id = tar.customer_id','inner')
                                ->where('qys.admin_user_id',$item1['id'])
                                ->where('qys.status', 'COMPLETE')
                                ->where('qys.status_msg','<>','INVALID_CANCEL')
                                ->where('qys.source_id','<>',8)
                                ->whereBetween('qys.contract_signing_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->sum('lower_money');
                            //老人近15天销售额(新)
                            $saleTotalAmount = QiyuesuoOnlineContractJzd::alias('qys')
                                ->join('thread_external thr', 'qys.thread_id = thr.id and qys.admin_user_id = thr.origin_customer_id and thr.create_time=thr.last_create_time','inner')
                                ->where('qys.admin_user_id',$item1['id'])
                                ->where('qys.status', 'COMPLETE')
                                ->where('qys.status_msg','<>','INVALID_CANCEL')
                                ->where('qys.source_id','<>',8)
                                ->whereBetween('qys.contract_signing_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->sum('lower_money');
                            // dump($saleTotalAmount);die;
                            //老人近15天本金
                            $depositTotal = TransactionList::alias('tt')
                                ->join('thread_external thr', 'tt.thread_id = thr.id and thr.customer_id ='.$item1['id'],'inner')
                                ->where('tt.collect_type',1)
                                ->where('tt.order_status',1)
                                ->where('tt.money','>',1)
                                ->whereBetween('tt.pay_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->sum('tt.money');
                            //老人近15天有效线索成本(根据线索标签判断，除去是否未成年、是否秒删)
                            $threadPriceTotal = ThreadExternal::alias('thr')
                                ->join('thread_user_status tus','tus.thread_id = thr.id and (tus.current_action_id = 372 or tus.current_action_id = 40)','left')
                                ->where('tus.id',null)
                                ->where('thr.customer_id',$item1['id'])
                                ->where('thr.is_test',0)
                                ->where('thr.allot_nums', 0)
                                ->whereBetween('thr.create_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->sum('thr.thread_price');
                            //实际上班天数
                            $threadDay = ThreadExternal::where('customer_id',$item1['id'])
                                ->where('is_test',0)
                                ->where('source_id','<>', 8)
                                ->whereBetween('create_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->field("id,FROM_UNIXTIME(create_time, '%Y-%m-%d') AS create_day")
                                ->group('create_day')
                                ->count();
                            $grossProfit = 0;
                            if($assignRule == 1){
                                //毛利计算公式 =  （本人近15天（不限制在本月）销售额（合同金额+定金）— 本人近15（不限制在本月）天有效线索成本（根据线索标签判断，除去是否未成年、是否秒删，这两个标签的学员数量，取当前数量）÷上班天数（统计这个销售在本月实际上班的天数，根据分配流量进行统计，统计有分配流量的天数）
                                //毛利数据处理 = 每个销售毛利开根号“√”
                                if($saleTotalAmount + $saleTotalAmountOld + $depositTotal - $threadPriceTotal > 0){
                                    $grossProfit = sqrt(($saleTotalAmount + $saleTotalAmountOld + $depositTotal - $threadPriceTotal) / $threadDay);
                                }
                            }else if($assignRule == 2){
                                //ROI计算公式=近15天个人天销售额 ÷ 近15天有效销售成本（据线索标签判断，除去是否未成年、是否秒删，这两个标签的学员数量，取当前数量）
                                //ROI数据处理 = 每个销售ROI开根号“√”
                                if($threadPriceTotal > 0){
                                    $grossProfit = sqrt(($saleTotalAmount + $saleTotalAmountOld + $depositTotal) / $threadPriceTotal);
                                }
                            }
                            $item1['gross_profit'] = $grossProfit;
                            //销售组毛利总和
                            $grossProfitTotal += $grossProfit;
                        }
                    }
                    $item['gross_profit_total'] = $grossProfitTotal;
                }
            }
            $data = [];
            $data1 = [];
            $grossProfitTotal = array_sum(array_column($merchantOrganizationCustomer,'gross_profit_total'));
            foreach($merchantOrganizationCustomer as &$item){
                $item['new_daily_intake_limit_nums_arr'] = [];
                foreach($item['customer'] as &$item1){
                    $item1['new_daily_intake_limit_nums'] = 0;
                    $item1['old_daily_intake_limit_nums'] = 0;
                    if($item1['is_new'] == 2){
                        //分配比 = 毛利数据处理结果 ÷ 毛利数据处理结果总和
                        if($assignMode == 4){
                            if($item1['gross_profit'] > 0 && $grossProfitTotal > 0){
                                $assignRate = $item1['gross_profit'] / $grossProfitTotal;
                                //每个销售分配的总数 =  小组总量 × 分配比，并且取整
                                $item1['new_daily_intake_limit_nums'] = round($item['component_num_total'] * $assignRate) >= 0 ? round($item['component_num_total'] * $assignRate) : 5;
                            }
                        }else{
                            if($item1['gross_profit'] > 0 && $item['gross_profit_total'] > 0){
                                $assignRate = $item1['gross_profit'] / $item['gross_profit_total'];
                                //每个销售分配的总数 =  小组总量 × 分配比，并且取整
                                $item1['new_daily_intake_limit_nums'] = round($item['component_num_total'] * $assignRate) >= 0 ? round($item['component_num_total'] * $assignRate) : 5;
                            }
                        }
                    }
                    $item['new_daily_intake_limit_nums_arr'][] = $item1['new_daily_intake_limit_nums'];
                }
            }
            foreach($merchantOrganizationCustomer as &$item){
                $new_daily_intake_limit_nums_max = !empty($item['new_daily_intake_limit_nums_arr']) ? max($item['new_daily_intake_limit_nums_arr']) : 0;
                foreach($item['customer'] as &$item1){
                    if($new_daily_intake_limit_nums_max > 0 && $item1['new_daily_intake_limit_nums'] > 0 && $new_daily_intake_limit_nums_max > $item1['new_daily_intake_limit_nums']){
                        $item1['old_daily_intake_limit_nums'] = ($new_daily_intake_limit_nums_max - $item1['new_daily_intake_limit_nums']) * 2;
                    }
                    $data[] = [
                        'assign_mode' => $assignMode,
                        'assign_rule' => $assignRule,
                        'merchant_id' => $merchantId,
                        'customer_id' => $item1['id'],
                        'is_new' => $item1['is_new'],
                        'is_auto_assign' => 1,
                        'datetime' => $datetime,
                        'merchant_organization_id' => $item['id'],
                        'new_daily_intake_limit_nums' => $item1['new_daily_intake_limit_nums'],
                        'old_daily_intake_limit_nums' => $item1['old_daily_intake_limit_nums'],
                    ];
                }
            }
            if($assignMode == 3){
                $this->model->where('assign_mode',$assignMode)
                    ->where('is_auto_assign',1)
                    ->where('datetime',$datetime)
                    ->update(['delete_time'=>time()]);
            }else{
                $this->model->where('assign_mode',$assignMode)
                    ->where('is_auto_assign',1)
                    ->where('assign_rule',$assignRule)
                    ->where('datetime',$datetime)
                    ->update(['delete_time'=>time()]);
            }
            $this->model->saveAll($data);
            if($assignMode == 3){
                foreach($merchantOrganizationCustomer as &$item) {
                    $item['new_daily_intake_limit_nums_arr'] = [];
                    foreach ($item['customer'] as &$item1) {
                        $item1['new_daily_intake_limit_nums'] = 0;
                        $item1['old_daily_intake_limit_nums'] = 0;
                        if ($item1['is_new'] == 2) {
                            //分配比 = 毛利数据处理结果 ÷ 毛利数据处理结果总和
                            if ($item1['gross_profit2'] > 0 && $item['gross_profit_total2'] > 0) {
                                $assignRate = $item1['gross_profit2'] / $item['gross_profit_total2'];
                                //每个销售分配的总数 =  小组总量 × 分配比，并且取整
                                $item1['new_daily_intake_limit_nums'] = round($item['component_num_total'] * $assignRate) >= 0 ? round($item['component_num_total'] * $assignRate) : 5;
                            }
                        }
                        $item['new_daily_intake_limit_nums_arr'][] = $item1['new_daily_intake_limit_nums'];
                    }
                }

                foreach($merchantOrganizationCustomer as &$item) {
                    $new_daily_intake_limit_nums_max = max($item['new_daily_intake_limit_nums_arr']);
                    foreach ($item['customer'] as &$item1) {
                        if ($new_daily_intake_limit_nums_max > 0 && $item1['new_daily_intake_limit_nums'] > 0 && $new_daily_intake_limit_nums_max > $item1['new_daily_intake_limit_nums']) {
                            $item1['old_daily_intake_limit_nums'] = ($new_daily_intake_limit_nums_max - $item1['new_daily_intake_limit_nums']) * 2;
                        }
                        $data1[] = [
                            'assign_mode' => $assignMode,
                            'assign_rule' => 2,
                            'merchant_id' => $merchantId,
                            'customer_id' => $item1['id'],
                            'is_new' => $item1['is_new'],
                            'is_auto_assign' => 1,
                            'datetime' => $datetime,
                            'merchant_organization_id' => $item['id'],
                            'new_daily_intake_limit_nums' => $item1['new_daily_intake_limit_nums'],
                            'old_daily_intake_limit_nums' => $item1['old_daily_intake_limit_nums'],
                        ];
                    }
                }
                $this->model->saveAll($data1);
            }
            if($assignMode == 4) {
                $customer = JzdCustomerAssignMode::where('merchant_id',$merchantId)->find();
                $customer->save(['sale_group_nums' => $saleGroupNumsOrgin]);
            }
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }
    
    //飞鱼
    public function initCustomerInputV2()
    {
        $assignMode = $this->request->post('assign_mode') ?? 1;
        $assignRule = $this->request->post('assign_rule') ?? 0;
        $merchantId = $this->request->post('merchant_id') ?? 251;
        $saleGroupNums = $this->request->post('sale_group_nums') ?? 480;
        //$datetime = date('H') < 16 ? date('Y-m-d') : date('Y-m-d',strtotime('+1 day'));
        $datetime = date('Y-m-d');
        //$merchantId = 177;
        $merchantOrganizationData = [
            '218' => '销售一组组员',
        ];
        $merchantOrganizationPids = [218];
        $grossProfitDay = JzdCustomerAssignMode::where('merchant_id',$merchantId)->value('gross_profit_day') ?? 15;
        $currentMonth = date('m');
        $currentDay = date('d');
        $currentMonthRestDay = CustomerRestDay::whereIn('month',[$currentMonth - 1,$currentMonth])
            ->where('day','<=',$currentDay)
            ->where('is_rest',1)
            ->field('id,month,day')
            ->select()
            ->toArray();
        $restMonthDay = [];
        foreach($currentMonthRestDay as $date){
            if($date['month'] < 10) $date['month'] = '0'.$date['month'];
            if($date['day'] < 10) $date['day'] = '0'.$date['day'];
            $restMonthDay[] = $date['month'].'-'.$date['day'];
        }
        for($i=0;$i<$grossProfitDay;$i++){
            $thrMonthDay = date('m-d',strtotime("-{$i} day"));
            if(in_array($thrMonthDay,$restMonthDay)){
                $grossProfitDay++;
            }
        }
        try {
            if($assignMode == 3){
                $merchantOrganizationCustomer = MerchantOrganization::where('merchant_id', $merchantId)
                    ->whereIn('id',$merchantOrganizationPids)
                    ->field('id,name,daily_intake_limit_nums as component_num_total')
                    ->paginate(50)
                    ->each(function ($item, $key) use ($merchantId) {
                        $merchantOrganizationIds = (new MerchantOrganization())->where('pid',$item['id'])->column('id');
                        array_unshift($merchantOrganizationIds,$item['id']);
                        $item['customer'] = (new \app\model\admin\Customer())->where('merchant_id', $merchantId)
                            ->whereIn('merchant_organization_id', $merchantOrganizationIds)
                            ->where('thread_status', 1)
                            ->field('id,nickname,month_contract_amount,month_paid_amount,create_time')
                            ->select()
                            ->toArray();
                        return $item;
                    })
                    ->toArray();
                if (!empty($merchantOrganizationCustomer['data'])) {
                    foreach ($merchantOrganizationCustomer['data'] as $key => &$item) {
                        $newDailyIntakeLimitNumsSystem = (new \app\model\admin\JzdCustomerComponentRule())
                            ->where('assign_mode',3)
                            ->where('assign_rule',1)
                            ->where('is_auto_assign',2)
                            ->where('merchant_organization_id',$item['id'])
                            ->where('datetime',date('Y-m-d'))
                            ->sum('new_daily_intake_limit_nums');
                        $item['component_num_total'] = $item['component_num_total'] - $newDailyIntakeLimitNumsSystem;
                        if (empty($item['customer'])) {
                            unset($merchantOrganizationCustomer['data'][$key]);
                        }
                        foreach($item['customer'] as $key2 => &$item2){
                            $customerComponentRule = (new \app\model\admin\JzdCustomerComponentRule())->where('customer_id',$item2['id'])
                                ->where('assign_mode',3)
                                ->where('is_auto_assign',2)
                                ->where('datetime',date('Y-m-d'))
                                ->find();
                            if(!empty($customerComponentRule)){
                                unset($item['customer'][$key2]);
                            }
                        }
                        $item['customer'] = array_values($item['customer']);
                        if(isset($merchantOrganizationData[$item['id']])) $item['name'] = $merchantOrganizationData[$item['id']];
                    }
                }
                $merchantOrganizationCustomer = array_values($merchantOrganizationCustomer['data']);
                foreach($merchantOrganizationCustomer as &$item){
                    // //月销售目标
                    // $monthContractAmountTotal = array_sum(array_column($item['customer'],'month_contract_amount'));
                    // $item['component_num_total'] = round($monthContractAmountTotal / 5.5 / $merchantInfo->peak_price / ($currentMonthDay - $currentMonthRestDay));
                    $grossProfitTotal = 0;
                    $grossProfitTotal2 = 0;
                    foreach($item['customer'] as &$item1){
                        $custime = $item1['create_time'];
                        $threadCount = Thread::where('customer_id',$item1['id'])->where('is_test',0)
                            ->whereBetween('create_time',[strtotime($custime),strtotime("$custime +7 day")])
                            ->count();
                        //销售老人/新人
                        $item1['is_new'] = 1;
                        if($threadCount > 0) $item1['is_new'] = 2;
                        if($item1['is_new'] == 2) {
                            //老人近15天销售额
                            $saleTotalAmount = QiyuesuoOnlineContractJzd::alias('qys')
                                ->join('thread_external thr', 'qys.thread_id = thr.id and qys.admin_user_id = thr.origin_customer_id and thr.create_time=thr.last_create_time','inner')
                                ->where('qys.admin_user_id',$item1['id'])
                                ->where('qys.status', 'COMPLETE')
                                ->where('qys.status_msg','<>','INVALID_CANCEL')
                                ->where('qys.source_id','<>',8)
                                ->whereBetween('qys.contract_signing_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->sum('lower_money');
                            //老人近15天本金
                            $depositTotal = TransactionList::alias('tt')
                                ->join('thread_external thr', 'tt.thread_id = thr.id and thr.customer_id ='.$item1['id'],'inner')
                                ->where('tt.collect_type',1)
                                ->where('tt.order_status',1)
                                ->where('tt.money','>',1)
                                ->whereBetween('tt.pay_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->sum('tt.money');
                            //老人近15天有效线索成本(根据线索标签判断，除去是否未成年、是否秒删)
                            $threadPriceTotal = ThreadExternal::alias('thr')
                                ->join('thread_user_status tus','tus.thread_id = thr.id and (tus.current_action_id = 372 or tus.current_action_id = 40)','left')
                                ->where('tus.id',null)
                                ->where('thr.customer_id',$item1['id'])
                                ->where('thr.is_test',0)
                                ->where('thr.allot_nums', 0)
                                ->whereBetween('thr.create_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->sum('thr.thread_price');
                            //实际上班天数
                            $threadDay = ThreadExternal::where('customer_id',$item1['id'])
                                ->where('is_test',0)
                                ->where('source_id','<>', 8)
                                ->whereBetween('create_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->field("id,FROM_UNIXTIME(create_time, '%Y-%m-%d') AS create_day")
                                ->group('create_day')
                                ->count();
                            //毛利计算公式 =  （本人近15天（不限制在本月）销售额（合同金额+定金）— 本人近15（不限制在本月）天有效线索成本（根据线索标签判断，除去是否未成年、是否秒删，这两个标签的学员数量，取当前数量）÷上班天数（统计这个销售在本月实际上班的天数，根据分配流量进行统计，统计有分配流量的天数）
                            //毛利数据处理 = 每个销售毛利开根号“√”
                            $grossProfit = 0;
                            $grossProfit2 = 0;
                            if($saleTotalAmount + $depositTotal - $threadPriceTotal > 0){
                                $grossProfit = sqrt(($saleTotalAmount + $depositTotal - $threadPriceTotal) / $threadDay);
                            }
                            //ROI计算公式=近15天个人天销售额 ÷ 近15天有效销售成本（据线索标签判断，除去是否未成年、是否秒删，这两个标签的学员数量，取当前数量）
                            //ROI数据处理 = 每个销售ROI开根号“√”
                            if($threadPriceTotal > 0){
                                $grossProfit2 = sqrt(($saleTotalAmount + $depositTotal) / $threadPriceTotal);
                            }
                            $item1['gross_profit'] = round($grossProfit);
                            $item1['gross_profit2'] = round($grossProfit2);
                            //销售组毛利总和
                            $grossProfitTotal += $grossProfit;
                            $grossProfitTotal2 += $grossProfit2;
                        }
                    }
                    $item['gross_profit_total'] = round($grossProfitTotal);
                    $item['gross_profit_total2'] = round($grossProfitTotal2);
                }
            }else if($assignMode == 4){
                $newDailyIntakeLimitNumsSystem = (new \app\model\admin\JzdCustomerComponentRule())
                    ->where('assign_mode',4)
                    ->where('assign_rule',1)
                    ->where('is_auto_assign',2)
                    ->where('datetime',date('Y-m-d'))
                    ->sum('new_daily_intake_limit_nums');
                $saleGroupNumsOrgin = $saleGroupNums;
                $saleGroupNums = $saleGroupNums - $newDailyIntakeLimitNumsSystem;
                $merchantOrganizationCustomer = MerchantOrganization::where('merchant_id', $merchantId)
                    ->whereIn('id',$merchantOrganizationPids)
                    ->field('id,name')
                    ->paginate(50)
                    ->each(function ($item, $key) use ($merchantId) {
                        $merchantOrganizationIds = (new MerchantOrganization())->where('pid',$item['id'])->column('id');
                        array_unshift($merchantOrganizationIds,$item['id']);
                        $item['customer'] = (new \app\model\admin\Customer())->where('merchant_id', $merchantId)
                            ->whereIn('merchant_organization_id', $merchantOrganizationIds)
                            ->where('thread_status', 1)
                            ->field('id,nickname,month_contract_amount,month_paid_amount,create_time')
                            ->select()
                            ->toArray();
                        return $item;
                    })
                    ->toArray();
                if (!empty($merchantOrganizationCustomer['data'])) {
                    foreach ($merchantOrganizationCustomer['data'] as $key => &$item) {
                        if (empty($item['customer'])) {
                            unset($merchantOrganizationCustomer['data'][$key]);
                        }
                        foreach($item['customer'] as $key2 => &$item2){
                            $customerComponentRule = (new \app\model\admin\JzdCustomerComponentRule())->where('customer_id',$item2['id'])
                                ->where('assign_mode',4)
                                ->where('is_auto_assign',2)
                                ->where('datetime',date('Y-m-d'))
                                ->find();
                            if(!empty($customerComponentRule)){
                                unset($item['customer'][$key2]);
                            }
                        }
                        $item['customer'] = array_values($item['customer']);
                        if(isset($merchantOrganizationData[$item['id']])) $item['name'] = $merchantOrganizationData[$item['id']];
                    }
                }
                $merchantOrganizationCustomer = array_values($merchantOrganizationCustomer['data']);
                foreach($merchantOrganizationCustomer as &$item){
                    // //月销售目标
                    // $monthContractAmountTotal = array_sum(array_column($item['customer'],'month_contract_amount'));
                    // $item['component_num_total'] = round($monthContractAmountTotal / 5.5 / $merchantInfo->peak_price / ($currentMonthDay - $currentMonthRestDay));
                    $item['component_num_total'] = $saleGroupNums;
                    $grossProfitTotal = 0;
                    foreach($item['customer'] as &$item1){
                        $custime = $item1['create_time'];
                        $threadCount = Thread::where('customer_id',$item1['id'])->where('is_test',0)
                            ->whereBetween('create_time',[strtotime($custime),strtotime("$custime +7 day")])
                            ->count();
                        //销售老人/新人
                        $item1['is_new'] = 1;
                        if($threadCount > 0) $item1['is_new'] = 2;
                        if($item1['is_new'] == 2) {
                            //老人近15天销售额(新)
                            $saleTotalAmountOld = QiyuesuoOnlineContractJzd::alias('qys')
                                ->join('lt_thread_allocated_record tar', 'tar.thread_id = qys.thread_id and qys.admin_user_id = tar.customer_id','inner')
                                ->where('qys.admin_user_id',$item1['id'])
                                ->where('qys.status', 'COMPLETE')
                                ->where('qys.status_msg','<>','INVALID_CANCEL')
                                ->where('qys.source_id','<>',8)
                                ->whereBetween('qys.contract_signing_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->sum('lower_money');
                            //老人近15天销售额(新)
                            $saleTotalAmount = QiyuesuoOnlineContractJzd::alias('qys')
                                ->join('thread_external thr', 'qys.thread_id = thr.id and qys.admin_user_id = thr.origin_customer_id and thr.create_time=thr.last_create_time','inner')
                                ->where('qys.admin_user_id',$item1['id'])
                                ->where('qys.status', 'COMPLETE')
                                ->where('qys.status_msg','<>','INVALID_CANCEL')
                                ->where('qys.source_id','<>',8)
                                ->whereBetween('qys.contract_signing_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->sum('lower_money');
                            // dump($saleTotalAmount);die;
                            //老人近15天本金
                            $depositTotal = TransactionList::alias('tt')
                                ->join('thread_external thr', 'tt.thread_id = thr.id and thr.customer_id ='.$item1['id'],'inner')
                                ->where('tt.collect_type',1)
                                ->where('tt.order_status',1)
                                ->where('tt.money','>',1)
                                ->whereBetween('tt.pay_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->sum('tt.money');
                            //老人近15天有效线索成本(根据线索标签判断，除去是否未成年、是否秒删)
                            $threadPriceTotal = ThreadExternal::alias('thr')
                                ->join('thread_user_status tus','tus.thread_id = thr.id and (tus.current_action_id = 372 or tus.current_action_id = 40)','left')
                                ->where('tus.id',null)
                                ->where('thr.customer_id',$item1['id'])
                                ->where('thr.is_test',0)
                                ->where('thr.allot_nums', 0)
                                ->whereBetween('thr.create_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->sum('thr.thread_price');
                            //实际上班天数
                            $threadDay = ThreadExternal::where('customer_id',$item1['id'])
                                ->where('is_test',0)
                                ->where('source_id','<>', 8)
                                ->whereBetween('create_time',[strtotime(date('Y-m-d',strtotime("-".$grossProfitDay." day"))), strtotime(date('Y-m-d'))])
                                ->field("id,FROM_UNIXTIME(create_time, '%Y-%m-%d') AS create_day")
                                ->group('create_day')
                                ->count();
                            $grossProfit = 0;
                            if($assignRule == 1){
                                //毛利计算公式 =  （本人近15天（不限制在本月）销售额（合同金额+定金）— 本人近15（不限制在本月）天有效线索成本（根据线索标签判断，除去是否未成年、是否秒删，这两个标签的学员数量，取当前数量）÷上班天数（统计这个销售在本月实际上班的天数，根据分配流量进行统计，统计有分配流量的天数）
                                //毛利数据处理 = 每个销售毛利开根号“√”
                                if($saleTotalAmount + $saleTotalAmountOld + $depositTotal - $threadPriceTotal > 0){
                                    $grossProfit = sqrt(($saleTotalAmount + $saleTotalAmountOld + $depositTotal - $threadPriceTotal) / $threadDay);
                                }
                            }else if($assignRule == 2){
                                //ROI计算公式=近15天个人天销售额 ÷ 近15天有效销售成本（据线索标签判断，除去是否未成年、是否秒删，这两个标签的学员数量，取当前数量）
                                //ROI数据处理 = 每个销售ROI开根号“√”
                                if($threadPriceTotal > 0){
                                    $grossProfit = sqrt(($saleTotalAmount + $saleTotalAmountOld + $depositTotal) / $threadPriceTotal);
                                }
                            }
                            $item1['gross_profit'] = $grossProfit;
                            //销售组毛利总和
                            $grossProfitTotal += $grossProfit;
                        }
                    }
                    $item['gross_profit_total'] = $grossProfitTotal;
                }
            }
            $data = [];
            $data1 = [];
            $grossProfitTotal = array_sum(array_column($merchantOrganizationCustomer,'gross_profit_total'));
            foreach($merchantOrganizationCustomer as &$item){
                $item['new_daily_intake_limit_nums_arr'] = [];
                foreach($item['customer'] as &$item1){
                    $item1['new_daily_intake_limit_nums'] = 0;
                    $item1['old_daily_intake_limit_nums'] = 0;
                    if($item1['is_new'] == 2){
                        //分配比 = 毛利数据处理结果 ÷ 毛利数据处理结果总和
                        if($assignMode == 4){
                            if($item1['gross_profit'] > 0 && $grossProfitTotal > 0){
                                $assignRate = $item1['gross_profit'] / $grossProfitTotal;
                                //每个销售分配的总数 =  小组总量 × 分配比，并且取整
                                $item1['new_daily_intake_limit_nums'] = round($item['component_num_total'] * $assignRate) >= 0 ? round($item['component_num_total'] * $assignRate) : 5;
                            }
                        }else{
                            if($item1['gross_profit'] > 0 && $item['gross_profit_total'] > 0){
                                $assignRate = $item1['gross_profit'] / $item['gross_profit_total'];
                                //每个销售分配的总数 =  小组总量 × 分配比，并且取整
                                $item1['new_daily_intake_limit_nums'] = round($item['component_num_total'] * $assignRate) >= 0 ? round($item['component_num_total'] * $assignRate) : 5;
                            }
                        }
                    }
                    $item['new_daily_intake_limit_nums_arr'][] = $item1['new_daily_intake_limit_nums'];
                }
            }
            foreach($merchantOrganizationCustomer as &$item){
                $new_daily_intake_limit_nums_max = !empty($item['new_daily_intake_limit_nums_arr']) ? max($item['new_daily_intake_limit_nums_arr']) : 0;
                foreach($item['customer'] as &$item1){
                    if($new_daily_intake_limit_nums_max > 0 && $item1['new_daily_intake_limit_nums'] > 0 && $new_daily_intake_limit_nums_max > $item1['new_daily_intake_limit_nums']){
                        $item1['old_daily_intake_limit_nums'] = ($new_daily_intake_limit_nums_max - $item1['new_daily_intake_limit_nums']) * 2;
                    }
                    $data[] = [
                        'assign_mode' => $assignMode,
                        'assign_rule' => $assignRule,
                        'merchant_id' => $merchantId,
                        'customer_id' => $item1['id'],
                        'is_new' => $item1['is_new'],
                        'is_auto_assign' => 1,
                        'datetime' => $datetime,
                        'merchant_organization_id' => $item['id'],
                        'new_daily_intake_limit_nums' => $item1['new_daily_intake_limit_nums'],
                        'old_daily_intake_limit_nums' => $item1['old_daily_intake_limit_nums'],
                    ];
                }
            }
            if($assignMode == 3){
                $this->model->where('assign_mode',$assignMode)
                    ->where('is_auto_assign',1)
                    ->where('datetime',$datetime)
                    ->update(['delete_time'=>time()]);
            }else{
                $this->model->where('assign_mode',$assignMode)
                    ->where('is_auto_assign',1)
                    ->where('assign_rule',$assignRule)
                    ->where('datetime',$datetime)
                    ->update(['delete_time'=>time()]);
            }
            $this->model->saveAll($data);
            if($assignMode == 3){
                foreach($merchantOrganizationCustomer as &$item) {
                    $item['new_daily_intake_limit_nums_arr'] = [];
                    foreach ($item['customer'] as &$item1) {
                        $item1['new_daily_intake_limit_nums'] = 0;
                        $item1['old_daily_intake_limit_nums'] = 0;
                        if ($item1['is_new'] == 2) {
                            //分配比 = 毛利数据处理结果 ÷ 毛利数据处理结果总和
                            if ($item1['gross_profit2'] > 0 && $item['gross_profit_total2'] > 0) {
                                $assignRate = $item1['gross_profit2'] / $item['gross_profit_total2'];
                                //每个销售分配的总数 =  小组总量 × 分配比，并且取整
                                $item1['new_daily_intake_limit_nums'] = round($item['component_num_total'] * $assignRate) >= 0 ? round($item['component_num_total'] * $assignRate) : 5;
                            }
                        }
                        $item['new_daily_intake_limit_nums_arr'][] = $item1['new_daily_intake_limit_nums'];
                    }
                }

                foreach($merchantOrganizationCustomer as &$item) {
                    $new_daily_intake_limit_nums_max = max($item['new_daily_intake_limit_nums_arr']);
                    foreach ($item['customer'] as &$item1) {
                        if ($new_daily_intake_limit_nums_max > 0 && $item1['new_daily_intake_limit_nums'] > 0 && $new_daily_intake_limit_nums_max > $item1['new_daily_intake_limit_nums']) {
                            $item1['old_daily_intake_limit_nums'] = ($new_daily_intake_limit_nums_max - $item1['new_daily_intake_limit_nums']) * 2;
                        }
                        $data1[] = [
                            'assign_mode' => $assignMode,
                            'assign_rule' => 2,
                            'merchant_id' => $merchantId,
                            'customer_id' => $item1['id'],
                            'is_new' => $item1['is_new'],
                            'is_auto_assign' => 1,
                            'datetime' => $datetime,
                            'merchant_organization_id' => $item['id'],
                            'new_daily_intake_limit_nums' => $item1['new_daily_intake_limit_nums'],
                            'old_daily_intake_limit_nums' => $item1['old_daily_intake_limit_nums'],
                        ];
                    }
                }
                $this->model->saveAll($data1);
            }
            if($assignMode == 4) {
                $customer = JzdCustomerAssignMode::where('merchant_id',$merchantId)->find();
                $customer->save(['sale_group_nums' => $saleGroupNumsOrgin]);
            }
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }

    //销售组列表
    public function getMerchantOrganizationCustomerList()
    {
        $merchantOrganizationId = $this->request->post('merchant_organization_id');
        $assignMode = $this->request->post('assign_mode') ?? 3;
        $merchantId = $this->request->post('merchant_id') ?? 177;
        //$merchantId = 177;
        $merchantOrganizationData = [
            '106' => '笔笔老师',
            '110' => '七海老师',
            '111' => '浩浩老师',
            '141' => '小仟老师',
            '143' => '文老师',
        ];
        $merchantOrganizationPids = $merchantOrganizationId ? [$merchantOrganizationId] : [106,110,111,141,143];
        $merchantOrganizationIds = (new MerchantOrganization())->whereIn('pid',$merchantOrganizationPids)->column('id');
        $merchantOrganizationIds = array_merge($merchantOrganizationPids,$merchantOrganizationIds);
        $merchantOrganizationCustomer = (new \app\model\admin\Customer())->where('merchant_id', $merchantId)
            ->whereIn('merchant_organization_id', $merchantOrganizationIds)
            ->where('thread_status', 1)
            ->field('id,nickname,merchant_organization_id')
            ->paginate(100)
            ->each(function ($item, $key) use($merchantOrganizationPids,$merchantOrganizationData,$assignMode) {
                if(in_array($item['merchant_organization_id'],$merchantOrganizationPids)){
                    $item['name'] = isset($merchantOrganizationData[$item['merchant_organization_id']]) ? $merchantOrganizationData[$item['merchant_organization_id']] : '';
                }else{
                    $pid = (new MerchantOrganization())->where('id',$item['merchant_organization_id'])->value('pid');
                    $item['merchant_organization_id'] = $pid;
                    $item['name'] = isset($merchantOrganizationData[$pid]) ? $merchantOrganizationData[$pid] : '';
                }
                $customerComponentRule = (new \app\model\admin\JzdCustomerComponentRule())->where('customer_id',$item['id'])
                    ->where('assign_mode',$assignMode)
                    ->where('is_auto_assign',2)
                    ->where('datetime',date('Y-m-d'))
                    ->find();
                $item['is_selected'] = 0;
                $item['new_daily_intake_limit_nums'] = 0;
                $item['old_daily_intake_limit_nums'] = 0;
                if(!empty($customerComponentRule)){
                    $item['is_selected'] = 1;
                    $item['new_daily_intake_limit_nums'] = $customerComponentRule['new_daily_intake_limit_nums'];
                    $item['old_daily_intake_limit_nums'] = $customerComponentRule['old_daily_intake_limit_nums'];
                }
            })
            ->toArray();
        return $this->success('操作成功',$merchantOrganizationCustomer);
    }

    //设置销售固定分配数量
    public function setCustomerDailyLimitNums()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $post['customer_param'] = json_decode($post['customer_param'],true);
        $validate = new JzdCustomerValidate();
        //if (!$validate->scene('setCustomerDailyLimitNums')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $data =  [];
            $data1 =  [];
            $merchantId = $post['merchant_id'];
            if(!empty($post['customer_param'])){
                foreach($post['customer_param'] as $item){
                    $data[] = [
                        'assign_mode' => $post['assign_mode'],
                        'assign_rule' => 1,
                        'merchant_id' => $merchantId,
                        'customer_id' => $item['customer_id'],
                        'is_new' => 2,
                        'is_auto_assign' => 2,
                        'datetime' => date('Y-m-d'),
                        'merchant_organization_id' => $item['merchant_organization_id'],
                        'new_daily_intake_limit_nums' => $item['new_daily_intake_limit_nums'],
                        'old_daily_intake_limit_nums' => $item['old_daily_intake_limit_nums'],
                    ];
                    $data1[] = [
                        'assign_mode' => $post['assign_mode'],
                        'assign_rule' => 2,
                        'merchant_id' => $merchantId,
                        'customer_id' => $item['customer_id'],
                        'is_new' => 2,
                        'is_auto_assign' => 2,
                        'datetime' => date('Y-m-d'),
                        'merchant_organization_id' => $item['merchant_organization_id'],
                        'new_daily_intake_limit_nums' => $item['new_daily_intake_limit_nums'],
                        'old_daily_intake_limit_nums' => $item['old_daily_intake_limit_nums'],
                    ];
                }
            }
            $dataAll = array_merge($data,$data1);
            (new \app\model\admin\JzdCustomerComponentRule())->where('assign_mode',$post['assign_mode'])->where('is_auto_assign',2)->where('datetime',date('Y-m-d'))->update(['delete_time' => time()]);
            $updateRes = (new \app\model\admin\JzdCustomerComponentRule())->saveAll($dataAll);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }

    //同步销售分量数
    public function editCustomer()
    {
        $merchantId = $this->request->post('merchant_id') ?? 177;
        $datetime = date('Y-m-d');
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $customerAssignMode = JzdCustomerAssignMode::where('merchant_id',$merchantId)->find();
        $jzdCustomerDailyLimitNum = $this->model->where('assign_mode',$customerAssignMode->assign_mode)
            ->where('assign_rule',$customerAssignMode['assign_rule'])
            ->where('datetime',$datetime)
            ->field('id,customer_id,new_daily_intake_limit_nums,old_daily_intake_limit_nums,assign_intake_limit_nums,apply_rate,register_rate')
            ->select()
            ->toArray();
        if(empty($jzdCustomerDailyLimitNum)){
            $jzdCustomerDailyLimitNum = $this->model->where('assign_mode',$customerAssignMode->assign_mode)
                ->where('assign_rule',$customerAssignMode['assign_rule'])
                ->where('datetime',date('Y-m-d',strtotime('-1 day')))
                ->field('id,customer_id,new_daily_intake_limit_nums,old_daily_intake_limit_nums,assign_intake_limit_nums,apply_rate,register_rate')
                ->select()
                ->toArray();
        }
        $customerIds = array_column($jzdCustomerDailyLimitNum,'customer_id');
        $data = [];
        $dataLog = [];
        Customer::where('merchant_id',$merchantId)->update([
            'register_intake_limit_nums' => 0,
            'assign_intake_limit_nums' => 0
        ]);
        Db::startTrans();
        try{
            if(!empty($jzdCustomerDailyLimitNum)){
                foreach($jzdCustomerDailyLimitNum as $item){
                    if($item['new_daily_intake_limit_nums'] >= 0) {
                        $dailyIntakeLimitNums = Customer::where('id',$item['customer_id'])
                            ->value('daily_intake_limit_nums');
                        $appIntakeLimitNums = 0;
                        if($item['new_daily_intake_limit_nums'] > 0) {
                            $appIntakeLimitNums = $item['apply_rate'] > 0 && $item['register_rate'] > 0 ? ceil($item['new_daily_intake_limit_nums'] / ($item['apply_rate'] + $item['register_rate']) * $item['apply_rate']) : 0;
                        }
                        $data[] = [
                            'id' => $item['customer_id'],
                            'daily_intake_limit_nums' => $appIntakeLimitNums > 0 ? $appIntakeLimitNums : $item['new_daily_intake_limit_nums'],
                            'day_allocated_num' => $item['old_daily_intake_limit_nums'],
                            'app_intake_limit_nums' => $appIntakeLimitNums > 0 ? $appIntakeLimitNums : $item['new_daily_intake_limit_nums'],
                            'register_intake_limit_nums' => $appIntakeLimitNums > 0 ? $item['new_daily_intake_limit_nums'] - $appIntakeLimitNums : 0,
                            'assign_intake_limit_nums' => $item['assign_intake_limit_nums']
                        ];
                        $dataLog[] = [
                            'merchant_id' => $merchantId,
                            'customer_id' => $item['customer_id'],
                            'daily_intake_limit_nums' => $item['new_daily_intake_limit_nums'],
                            'org_daily_intake_limit_nums' => $dailyIntakeLimitNums,
                            'datetime' => date('Y-m-d'),
                            'admin_id' => $loginId
                        ];
                    }
                }
            }
            if(!empty($data)){
                $customer = (new Customer())->saveAll($data);
                $registerIntakeLimitNums = Customer::where('merchant_id',$merchantId)->where('is_test',0)->sum('register_intake_limit_nums');
                $assignIntakeLimitNums = Customer::where('merchant_id',$merchantId)->where('is_test',0)->sum('assign_intake_limit_nums');
                Merchant::where('id',$merchantId)->update([
                    'auto_register_thread_nums' => $registerIntakeLimitNums,
                    'customer_assign_thread_nums' => $assignIntakeLimitNums
                ]);
            }
            if(!empty($dataLog)){
                $customerLog = (new JzdCustomerAssignLimitNumLog())->saveAll($dataLog);
            }
            if($customer && $customerLog){
                foreach($customerIds as $customerId) {
                    Event::trigger('CustomerEdit', [
                        'customer' => (new Customer())->find($customerId),
                    ]);
                }
            }
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }

    //设置销售组分配数量
    public function addCustomerAssignMode()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new JzdCustomerValidate();
        if (!$validate->scene('addCustomerMode')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $customer = JzdCustomerAssignMode::where('merchant_id',$post['merchant_id'])->find();
            if($post['assign_mode'] != 4) unset($post['sale_group_nums']);
            if(isset($post['gross_profit_day']) && !empty($post['gross_profit_day'])){
                $updateRes = $customer->save(['gross_profit_day' => $post['gross_profit_day']]);
            }else{
                $updateRes = $customer->save($post);
            }
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }

    //设置销售组分配数量
    public function setMerchantOrganizationNums()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new JzdCustomerValidate();
        if (!$validate->scene('setMerchantOrganizationNums')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $merchantOrganization = new MerchantOrganization();
            $customer = $merchantOrganization->findOrEmpty($post['merchant_organization_id']);
            $updateRes = $customer->save(['daily_intake_limit_nums' => $post['daily_intake_limit_nums']]);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }

    //设置新销售分配数量
    public function setNewCustomerNums()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new JzdCustomerValidate();
        if (!$validate->scene('setNewCustomerNums')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $customer = $this->model->where('assign_mode',$post['assign_mode'])
                ->where('assign_rule',$post['assign_rule'] ?? 1)
                ->where('customer_id',$post['customer_id'])
                ->where('merchant_organization_id',$post['merchant_organization_id'])
                ->where('datetime',date('Y-m-d'))
                ->find();
            if($post['is_new'] == 1) $data['new_daily_intake_limit_nums'] = $post['new_daily_intake_limit_nums'];
            if($post['is_new'] == 2) $data['old_daily_intake_limit_nums'] = $post['new_daily_intake_limit_nums'];
            $data['apply_rate'] = $post['apply_rate'];
            $data['register_rate'] = $post['register_rate'];
            $data['assign_intake_limit_nums'] = $post['assign_intake_limit_nums'];
            $updateRes = $customer->save($data);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }

    //设置销售休息天
    public function setRestDay()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new JzdCustomerValidate();
        if (!$validate->scene('setRestDay')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $customer = new CustomerRestDay();
            $restDay = $customer->where('month',$post['month'])
                ->where('day',$post['day'])
                ->find();
            if (!empty($restDay)){
                if($restDay->is_rest == 0){
                    $restDay->is_rest = 1;
                }else{
                    $restDay->is_rest = 0;
                }
                $updateRes = $restDay->save();
            }else{
                $updateRes  = $customer->save($post);
            }
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }

    //销售休息天数
    public function customerRestDay()
    {
        $post = $this->request->post();
        extract($post);
        $where[] = ['is_rest','=',1];
        if(isset($month) && !empty($month)) {
            $where[] = ['month', '=', $month];
        }else{
            $where[] = ['month','=',date('m')];
        }
        $customer = new CustomerRestDay();
        $customerDay = $customer->where($where)->column('day');
        $data['month'] = isset($month) && !empty($month) ? $month : date('m');
        $data['day'] = $customerDay;

        return $this->success('数据获取成功', $data);
    }

    //获得当前月的周末集合
    public function currentWeekend(){
        $date = $this->request->post('date');
        $strtotime = !empty($date) ? strtotime($date) : strtotime('Y-m-d');
        $month = date('m',$strtotime);
        $year = date('Y',$strtotime);
        $days = date("t", mktime(0,0,0, $month, 1, $year));//当年当月的天数
        $startweek = date("w", mktime(0,0,0, $month, 1, $year));
        $nums = $startweek;
        $datea = [];
        $weekendDay = [];
        for($i=0;$i<$days;$i++){
            $str = ($i+1 > 9)?$i+1:'0'.($i+1);
//            if($nums == 6){
//                $datea[] = "$year-$month-".$str;
//            }elseif($nums == 7){
//                $datea[] = "$year-$month-".$str;
//                $nums = 0;
//            }
            if($nums == 7){
                $weekendDay[] = ['month' => $month,'day' => $str];
                $datea[] = "$year-$month-".$str;
                $nums = 0;
            }
            $nums++;
        }
        (new CustomerRestDay())->saveAll($weekendDay);

        return $this->success('添加成功');
    }

    /**
     *计算可预约时间函数
     *@param  $create_time 创建时间
     *@param  $jiejia_date 节假日集合
     *@param  $work_date 下单时间距预约的时间(相当于三个工作日才能约)
     **/
    public function yuyue_date($create_time,$jiejia_date,$work_date,$nums=2){
        $time = $create_time;
        for($s=0;$s<$nums;$s++){
            $time = date('Y-m-d',strtotime("{$time}+$s month"));
            $array = $this->dangyue($time);
            foreach($array as $k=>$v){
                $datea[] = $v;
            }
        }
        //筛选国家法定假
        foreach($jiejia_date as $key=>$val){
            for($v=0;$v<$val;$v++){
                $dateb[] =  date('Y-m-d',strtotime("{$key}+1+$v day"));
            }
        }
        $date = array_merge($datea,$dateb);
        $n = 0;
        do {
            $create_time = date('Y-m-d',strtotime("{$create_time}+1 day"));
            if(!in_array($create_time,$date)){
                $n++;
            }
        } while ($work_date != $n);
        return $create_time;
    }

}