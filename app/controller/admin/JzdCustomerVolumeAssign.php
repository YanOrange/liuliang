<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Event;
use think\facade\Request;
use app\validate\admin\customer\JzdCustomer as JzdCustomerValidate;
use app\model\admin\MerchantOrganization;
use app\model\admin\Merchant;
use app\model\admin\Thread;
use app\model\admin\ThreadExternal;
use app\model\admin\Customer;
use app\model\admin\JzdCustomerAssignLimitNumLog;
use app\model\admin\JzdCustomerVolumeAssignLog;


/**
 * 教之道控制器
 */
class JzdCustomerVolumeAssign extends Backend
{
    protected $model;//当前模型对象
    protected $noNeedAuth = ['*'];
    protected $noNeedLogin = [];
    protected $customerThreadTime = [
        177 => [
            1772 => '2022-11-15 13:03:10',
            2130 => '2023-03-10 07:22:52',
            2348 => '2023-04-18 10:46:29',
            2370 => '2023-04-21 13:20:41',
            2714 => '2023-07-15 13:35:49',
            2755 => '2023-07-29 10:19:55',
            2901 => '2023-09-08 13:41:08',
            2938 => '2023-09-19 12:53:26',
            3193 => '2023-11-26 12:59:19',
            3219 => '2023-12-02 13:03:26',
            3236 => '2023-12-05 10:23:09',
            3264 => '2023-12-12 13:34:27',
            3273 => '2024-01-22 13:54:38',
            3729 => '2024-03-01 13:11:16',
            3780 => '2024-03-07 13:47:22',
            3943 => '2024-04-04',
        ],
        251 => [
            3281 => '2023-12-18 13:01:48',
            3280 => '2023-12-18 13:02:41',
            3427 => '2024-01-12 09:17:01',
            3467 => '2024-01-20 10:30:13',
            3573 => '2024-02-21 09:33:57',
            3575 => '2024-02-21 09:38:15',
            3574 => '2024-02-22 09:15:06',
            3603 => '2024-02-23 09:31:59',
            3604 => '2024-02-23 10:11:06',
            3610 => '2024-02-23 14:59:32',
            3714 => '2024-02-29 10:35:59',
            3713 => '2024-02-29 10:44:51',
            3712 => '2024-02-29 10:45:04',
            3711 => '2024-02-29 10:55:33',
            3724 => '2024-03-01 09:18:48',
            3762 => '2024-03-05 09:49:23',
            3761 => '2024-03-05 09:49:54',
            3764 => '2024-03-05 10:03:26',
            3763 => '2024-03-05 10:05:18',
            3829 => '2024-03-12 09:13:56',
            3828 => '2024-03-12 09:20:35',
            3827 => '2024-03-12 09:25:34',
            3816 => '2024-03-12 13:46:19',
            3871 => '2024-03-15 17:23:02',
            3889 => '2024-03-18 18:28:03',
            3890 => '2024-03-18 18:28:02',
            3898 => '2024-03-21 17:59:02',
        ]
    ];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\JzdCustomerVolumeAssign();
    }

    protected  function getCustomerThreadTime($merchantId,$customerId)
    {
        $redis = get_redis();
        $redis_key = "customer:thread:time:{$merchantId}";
        $customerThreadData = $redis->get($redis_key);
        if(empty($customerThreadData)){
            $arr = Thread::where('merchant_id',$merchantId)->where('is_assign',0)->where('is_test',0)
                ->where('apply_register_type',1)->group('customer_id')
                ->column('FROM_UNIXTIME(MIN(create_time)) as create_time','customer_id');
            $threadStartTime = !empty($arr[$customerId])?$arr[$customerId]:'';
            $redis->set($redis_key,json_encode($arr));
        }else{
            $arr = json_decode($customerThreadData,true);
            $arr_key = array_keys($arr);
            $threadStartTime = !empty($arr[$customerId])?$arr[$customerId]:'';
            if(empty($threadStartTime)){
                $start_time = empty($arr[$arr_key[count($arr_key)-1]])?'':strtotime($arr[$arr_key[count($arr_key)-1]]);
                $arr1 = Thread::where('merchant_id',$merchantId)->where('is_assign',0)->where('is_test',0)
                    ->where('apply_register_type',1)
                    ->where('customer_id','>',$arr_key[count($arr_key)-1])
                    ->where('create_time','>',$start_time)
                    ->group('customer_id')
                    ->column('FROM_UNIXTIME(MIN(create_time)) as create_time','customer_id');
                $threadStartTime = !empty($arr1[$customerId])?$arr1[$customerId]:'';
                if(!empty($arr1)){
                    foreach ($arr1 as $key=>$val){
                        $arr[$key] = !empty($val)?$val:'';
                    }
                }
                $redis->set($redis_key,json_encode($arr));
            }
        }
        return $threadStartTime;
    }

    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->where($where)->with(['customer','organization'])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    public function customerThreadNum()
    {
        $hmAppIntakeLimitNums = $this->model->where('merchant_id',177)->sum('app_intake_limit_nums');
        $hmAssignIntakeLimitNums = $this->model->where('merchant_id',177)->sum('assign_intake_limit_nums');
        $fyAppIntakeLimitNums = $this->model->where('merchant_id',251)->sum('app_intake_limit_nums');
        $fyAssignIntakeLimitNums = $this->model->where('merchant_id',251)->sum('assign_intake_limit_nums');
        return $this->success('数据获取成功', [
            'hmAppIntakeLimitNums' => $hmAppIntakeLimitNums,
            'hmAssignIntakeLimitNums' => $hmAssignIntakeLimitNums,
            'fyAppIntakeLimitNums' => $fyAppIntakeLimitNums,
            'fyAssignIntakeLimitNums' => $fyAssignIntakeLimitNums,
        ]);
    }

    //商户列表
    public function merchantList()
    {
        $data = Merchant::where('id', 'in',[177,251])->order('id asc');
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //组织列表
    public function merchantOrganizationList()
    {
        $data = MerchantOrganization::where('merchant_id', 'in',[177,251])->order('id asc');
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //重新分量
    public function initCustomerInput()
    {
        set_time_limit(0);
        $merchantId = $this->request->post('merchant_id') ?? 177;
        $AppApplyPerformanceWeight = $this->request->post('app_apply_performance_weight') ?? 0;
        $AppApplyReceiptsWeight = $this->request->post('app_apply_receipts_weight') ?? 0;
        $customerPerformanceWeight = $this->request->post('customer_performance_weight') ?? 0;
        $customerReceiptsWeight = $this->request->post('customer_receipts_weight') ?? 0;
        $appAverageQuantity = $this->request->post('app_average_quantity') ?? 0;
        $FiveDayAppAverageQuantity = $this->request->post('five_day_app_average_quantity') ?? 0;
        $customerPresetQuantity = $this->request->post('customer_preset_quantity') ?? 0;
        $customerFrontThreeDayNum = $this->request->post('customer_front_three_day_num') ?? 5;
        $customerAfterFourDayNum = $this->request->post('customer_after_four_day_num') ?? 10;
        $customerPresetDay = $this->request->post('customer_preset_day') ?? 15;
        $customerAddDay = $this->request->post('customer_add_day') ?? 15;
        $AppPresetDay = $this->request->post('app_preset_day') ?? 15;
        $newCustomerThreadNum = 0;
        $customerList = Customer::where('merchant_id',$merchantId)
            ->where('thread_status',1)
            ->where('is_test',0)
            ->where('status',1)
            ->field('id,merchant_id,nickname,login_mobiles,thread_status,status,day_night_shift,merchant_organization_id,increase_intake_limit_nums,create_time')
            ->select()
            ->toArray();
        try {
            $customerRateData = [];
            $appCustomerRateSum = 0;
            $assignCustomerRateSum = 0;
            $oldCustomerNum = 0;
            if(!empty($customerList)){
                $AppThreadPresetDay = $AppPresetDay - 1;
                $customerThreadPresetDay = $customerPresetDay - 1;
                $endtime = date('Y-m-d 23:59:59',strtotime("-1 days"));
                $starttime = date("Y-m-d 00:00:00", strtotime($endtime . " -$AppThreadPresetDay days"));
                $customerEndtime = date('Y-m-d 23:59:59',strtotime("-1 days"));
                $customerStarttime = date("Y-m-d 00:00:00", strtotime($endtime . " -$customerThreadPresetDay days"));
                $customerIds = array_column($customerList,'id');
                $contractDepositPaidData = $this->contractDepositPaidSql($merchantId,$customerIds,(1-$customerPresetDay),(1-$AppPresetDay));
                foreach($customerList as &$item){
                    $appEffectiveThreadNums = 0;
                    $customerEffectiveThreadNums = 0;
                    $totalContractAmount = 0;
                    $totalDepositAmount = 0;
                    $totalReceiptAmount = 0;
                    $appTotalPerformanceAmount = 0;
                    $appCustomerRate = 0;
                    $customerTotalPerformanceAmount = 0;
                    $assignCustomerRate = 0;
                    $isNewCustomer = 1;
                    //$threadStartTime = Thread::where('customer_id',$item['id'])->where('is_register',0)->order('id asc')->value('create_time');
                    $customerThreadNum = Thread::where('customer_id',$item['id'])
                        ->where('merchant_id',$merchantId)
                        ->where('app_class_id',10)
                        ->where('is_register',0)
                        ->where('create_time','>=',1668488590)
                        ->field("FROM_UNIXTIME(create_time,'%Y-%m-%d') as create_time1")
                        ->group('create_time1')
                        ->count();

                    $customerThreadNum = $customerThreadNum ? $customerThreadNum + 1 : $customerThreadNum;
                    $threadStartTime = $this->getCustomerThreadTime($merchantId,$item['id']);
                    //$threadStartTime = !empty($this->customerThreadTime[$merchantId][$item['id']])?$this->customerThreadTime[$merchantId][$item['id']]:'';
                    $threadEndTime = date('Y-m-d H:i:s',strtotime($threadStartTime." +7 days"));
                    if($customerThreadNum > 0 && $customerThreadNum <= 3){
                        $newCustomerThreadNum = $customerFrontThreeDayNum;
                    }else if($customerThreadNum > 3 && $customerThreadNum <= 7){
                        $newCustomerThreadNum = $customerAfterFourDayNum;
                    }
                    //成交单数
                    $contractNumSql = "SELECT
                      mn1.`NAME` AS sale_name,
                      qys.admin_user_id,
                      cu.nickname AS nickname,
                      COUNT(qys.id) AS contract_num,
                      SUM(qys.lower_money) AS contract_amount
                    FROM
                      lt_qiyuesuo_online_contract_jzd AS qys
                      LEFT JOIN lt_thread_external AS thr ON thr.id = qys.thread_id
                      LEFT JOIN lt_customer AS cu ON cu.id = qys.admin_user_id
                      LEFT JOIN lt_merchant_organization AS mn ON mn.id = cu.merchant_organization_id
                      LEFT JOIN lt_merchant_organization AS mn1 ON mn1.id = mn.pid
                      LEFT JOIN lt_merchant AS mer ON mer.id = thr.merchant_id
                      LEFT JOIN lt_channel_delivery_mode AS ce ON ce.channel_id = thr.channel_id
                      LEFT JOIN lt_delivery_mode de ON ce.delivery_mode_id1 = de.id
                      LEFT JOIN lt_delivery_mode de2 ON ce.delivery_mode_id2 = de2.id
                    WHERE
                      qys.contract_signing_time >= UNIX_TIMESTAMP('{$threadStartTime}')
                      AND thr.merchant_id = {$merchantId}
                      AND thr.channel_id>0
                      AND qys.admin_user_id = {$item['id']}
                      AND qys.`status` = 'COMPLETE'
                      AND qys.status_msg <> 'INVALID_CANCEL'
                      AND qys.delete_time = 0
                      AND qys.source_id NOT IN (8, 48)
                                        AND thr.apply_register_type=1
                                        AND thr.is_assign=0 # 排除分配量
                                        AND cu.nickname NOT like '%测试%'";
                    $totalContractNum = Db::query($contractNumSql);
                    if(($customerThreadNum < 7 && $totalContractNum[0]['contract_num'] >= 2) || ($customerThreadNum >= 7 && $totalContractNum[0]['contract_num'] >= 2)) {
                        $isNewCustomer = 0;
                        $oldCustomerNum++;
                        //app有效线索量
                        $appEffectiveThreadNums = Thread::where('customer_id', $item['id'])->where('is_test', 0)
                            ->where('is_register', 0)
                            ->where('is_assign', 0)
                            ->whereBetween('create_time', [strtotime($starttime), strtotime($endtime)])
                            ->count();
                        //客服量有效线索量
                        $customerEffectiveThreadNums = Thread::where('customer_id', $item['id'])->where('is_test', 0)
                            ->where('is_assign','>', 0)
                            ->whereBetween('create_time', [strtotime($customerStarttime), strtotime($customerEndtime)])
                            ->count();

                        $totalContractAmount1 = $contractDepositPaidData['totalContractAmount'][$item['id']] ?? 0;
                        $totalDepositAmount1 = $contractDepositPaidData['totalDepositAmount'][$item['id']] ?? 0;
                        $totalReceiptAmount1 = $contractDepositPaidData['totalReceiptAmount'][$item['id']] ?? 0;
                        $customerTotalContractAmount1 = $contractDepositPaidData['customerTotalContractAmount'][$item['id']] ?? 0;
                        $customerTotalDepositAmount1 = $contractDepositPaidData['customerTotalDepositAmount'][$item['id']] ?? 0;
                        $customerTotalReceiptAmount1 = $contractDepositPaidData['customerTotalReceiptAmount'][$item['id']] ?? 0;
                        $appTotalPerformanceAmount = $appEffectiveThreadNums ? round(($totalContractAmount1 + $totalDepositAmount1) / $appEffectiveThreadNums,2) : 0;
                        $appCustomerRate = round(sqrt($appTotalPerformanceAmount) * $AppApplyPerformanceWeight + pow($totalReceiptAmount1, 1 / 3) * $AppApplyReceiptsWeight,2);
                        $customerTotalPerformanceAmount = $customerEffectiveThreadNums ? round(($customerTotalContractAmount1 + $customerTotalDepositAmount1) / $customerEffectiveThreadNums,2) : 0;
                        $assignCustomerRate = round(sqrt($customerTotalPerformanceAmount) * $customerPerformanceWeight + pow($customerTotalReceiptAmount1, 1 / 3) * $customerReceiptsWeight,2);
                    }
                    $customerRateData[] = [
                        'customer_id' => $item['id'],
                        'merchant_id' => $merchantId,
                        'merchant_organization_id' => $item['merchant_organization_id'],
                        'app_effective_thread_nums' => $appEffectiveThreadNums,
                        'customer_effective_thread_nums' => $customerEffectiveThreadNums,
                        'app_total_contract_amount' => $totalContractAmount1 ?? 0,
                        'app_total_deposit_amount' => $totalDepositAmount1 ?? 0,
                        'app_total_receipt_amount' => $totalReceiptAmount1 ?? 0,
                        'customer_total_contract_amount' => $customerTotalContractAmount1 ?? 0,
                        'customer_total_deposit_amount' => $customerTotalDepositAmount1 ?? 0,
                        'customer_total_receipt_amount' => $customerTotalReceiptAmount1 ?? 0,
                        'app_total_performance_amount' => $appTotalPerformanceAmount,
                        'app_customer_rate' => $appCustomerRate,
                        'customer_total_performance_amount' => $customerTotalPerformanceAmount,
                        'assign_customer_rate' => $assignCustomerRate,
                        'is_new' => $isNewCustomer,
                        'datetime' => date('Y-m-d'),
                        'new_customer_thread_num' => $newCustomerThreadNum,
                        'org_increase_intake_limit_nums' => $item['increase_intake_limit_nums']
                    ];
                    $appCustomerRateSum += $appCustomerRate;
                    $assignCustomerRateSum += $assignCustomerRate;
                }
                if(!empty($customerRateData)){
                    foreach($customerRateData as &$item){
                        //$customerIncreaseThreadNum = Thread::where('customer_id',$item['customer_id'])->where('is_origin',4)->where('is_test',0)->whereDay('create_time')->count();
                        //$customerIncreaseThreadNum = 0;
                        //$IncreaseIntakeLimitNums = $item['org_increase_intake_limit_nums'] - $customerIncreaseThreadNum;
                        $customerRate = $appCustomerRateSum > 0 ? $item['app_customer_rate'] / $appCustomerRateSum : 0;
                        $assignCustomerRate = $assignCustomerRateSum > 0 ? $item['assign_customer_rate'] / $assignCustomerRateSum : 0;
                        $item['app_intake_limit_nums'] = !$item['is_new'] ? round($customerRate * $appAverageQuantity * $oldCustomerNum) : $item['new_customer_thread_num'];
                        $item['assign_intake_limit_nums'] = !$item['is_new'] ? round($assignCustomerRate * $customerPresetQuantity) : 0;
                        $item['increase_intake_limit_nums'] = 0;
                    }
                    $this->model->where('merchant_id',$merchantId)->where('delete_time',0)->update(['delete_time' => time()]);
                    $this->model->saveAll($customerRateData);
                }
            }
            JzdCustomerVolumeAssignLog::create([
                'merchant_id' => $merchantId,
                'app_apply_performance_weight' => $AppApplyPerformanceWeight,
                'app_apply_receipts_weight' => $AppApplyReceiptsWeight,
                'customer_performance_weight' => $customerPerformanceWeight,
                'customer_receipts_weight' => $customerReceiptsWeight,
                'app_average_quantity' => $appAverageQuantity,
                'five_day_app_average_quantity' => $FiveDayAppAverageQuantity,
                'customer_preset_quantity' => $customerPresetQuantity,
                'customer_preset_day' => $customerPresetDay,
                'customer_add_day' => $customerAddDay,
                'app_preset_day' => $AppPresetDay,
            ]);
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }

    public function contractDepositPaidSql($merchantId,$customerIds = [],$day = -14,$app_day=-14)
    {
        $customerIdStr = implode(',',$customerIds);
        $endtime = date('Y-m-d 23:59:59',strtotime("-1 days"));
        $starttime = date("Y-m-d 00:00:00", strtotime($endtime . " {$day} days"));
        $app_starttime = date("Y-m-d 00:00:00", strtotime($endtime . " {$app_day} days"));//app计算分量
        //合同金额
        $contractSql = "SELECT
          mn1.`NAME` AS sale_name,
          qys.admin_user_id,
          cu.nickname AS nickname,
          COUNT(qys.id) AS contract_num,
          SUM(qys.lower_money) AS contract_amount
        FROM
          lt_qiyuesuo_online_contract_jzd AS qys
          LEFT JOIN lt_thread_external AS thr ON thr.id = qys.thread_id
          LEFT JOIN lt_customer AS cu ON cu.id = qys.admin_user_id
          LEFT JOIN lt_merchant_organization AS mn ON mn.id = cu.merchant_organization_id
          LEFT JOIN lt_merchant_organization AS mn1 ON mn1.id = mn.pid
          LEFT JOIN lt_merchant AS mer ON mer.id = thr.merchant_id
          LEFT JOIN lt_channel_delivery_mode AS ce ON ce.channel_id = thr.channel_id
          LEFT JOIN lt_delivery_mode de ON ce.delivery_mode_id1 = de.id
          LEFT JOIN lt_delivery_mode de2 ON ce.delivery_mode_id2 = de2.id
        WHERE
          qys.contract_signing_time >= UNIX_TIMESTAMP('{$app_starttime}')
          AND qys.contract_signing_time <= UNIX_TIMESTAMP('{$endtime}')
          AND thr.merchant_id = {$merchantId}
          AND thr.channel_id>0
          AND qys.admin_user_id in ({$customerIdStr})
          AND qys.`status` = 'COMPLETE'
          AND qys.status_msg <> 'INVALID_CANCEL'
          AND qys.delete_time = 0
          AND qys.source_id NOT IN (8, 48)
          AND thr.apply_register_type=1
          AND thr.is_assign=0 # 排除分配量
          AND cu.nickname NOT like '%测试%'
        GROUP BY qys.admin_user_id";
        $totalContractAmount = Db::query($contractSql);
        $totalContractAmount = array_column($totalContractAmount,'contract_amount','admin_user_id');
        //定金金额
        $depositSql = "SELECT
                mn1.NAME AS sale_name,
                tt.charge_customer AS customer_id,
                cu.nickname as nickname,
                COUNT(tt.id ) AS deposit_num,
                SUM( tt.money ) AS deposit_amount 
        FROM
                lt_transaction_list AS tt
                INNER JOIN lt_thread_external AS thr ON thr.id = tt.thread_id
                LEFT JOIN lt_customer AS cu ON cu.id = tt.charge_customer
                LEFT JOIN lt_merchant_organization AS mn ON mn.id = cu.merchant_organization_id
                LEFT JOIN lt_merchant_organization AS mn1 ON mn1.id = mn.pid
                LEFT JOIN lt_merchant AS mer ON mer.id = thr.merchant_id
                LEFT JOIN lt_channel_delivery_mode ce ON thr.channel_id = ce.channel_id
                LEFT JOIN lt_delivery_mode de ON ce.delivery_mode_id1 = de.id
                LEFT JOIN lt_delivery_mode de2 ON ce.delivery_mode_id2 = de2.id 
        WHERE
                tt.pay_time >= UNIX_TIMESTAMP( '{$app_starttime}' ) 
                AND tt.pay_time <= UNIX_TIMESTAMP( '{$endtime}' ) 
                AND tt.collect_type = 1 
                AND tt.order_status = 1 
                AND tt.delete_time = 0 
                AND tt.source_id NOT IN ( 8, 48 ) 
                AND tt.money > 1 
                AND thr.merchant_id = {$merchantId}
                AND thr.customer_id in ({$customerIdStr})
                AND thr.apply_register_type = 1 
                AND thr.is_assign = 0 # 排除分配量
                AND thr.channel_id>0
                AND cu.nickname NOT LIKE '%测试%'
        group by thr.customer_id";
        $totalDepositAmount = Db::query($depositSql);
        $totalDepositAmount = array_column($totalDepositAmount,'deposit_amount','customer_id');
        //一期实收
        $receiptsSql = "SELECT
              thr.customer_id AS sale_id,
              sum(
                CASE
                  WHEN current_stage_num = 1 THEN paid_money
                  ELSE 0
                END
              ) AS paid_money,
              sum(paid_money) AS total_paid_money
            FROM
              `lt_jzd_order_ledger_details` AS js
              LEFT JOIN lt_jzd_order_ledger AS jr ON js.ledger_id = jr.id
              LEFT JOIN lt_thread_external AS thr ON thr.id = jr.thread_id
              LEFT JOIN lt_customer AS cu ON cu.id = thr.customer_id
              LEFT JOIN lt_merchant_organization AS mn ON mn.id = cu.merchant_organization_id
              LEFT JOIN lt_merchant_organization AS mn1 ON mn.pid_1 = mn1.id
              LEFT JOIN lt_merchant AS mer ON mer.id = thr.merchant_id
              LEFT JOIN lt_channel_delivery_mode cd ON thr.channel_id = cd.channel_id
              LEFT JOIN lt_delivery_mode de ON cd.delivery_mode_id1 = de.id
              LEFT JOIN lt_delivery_mode de2 ON cd.delivery_mode_id2 = de2.id
            WHERE
              js.pay_time >= UNIX_TIMESTAMP('{$app_starttime}')
              AND js.pay_time <= UNIX_TIMESTAMP(ADDDATE('{$endtime}', 1))
              AND thr.merchant_id = {$merchantId}
    		  AND thr.customer_id in ({$customerIdStr})
    		  AND thr.apply_register_type = 1
    		  AND thr.is_assign = 0
              AND thr.source_id NOT IN (8, 48)
    					AND cd.delivery_mode_id2 != 58
              AND js.deleted_time = 0
              AND js.cancel_time = 0
              AND jr.deleted_time = 0
              AND jr.is_disuse = 0
             group by thr.customer_id";
        //   var_dump($receiptsSql);die;
        $totalReceiptAmount = Db::query($receiptsSql);
        $totalReceiptAmount = array_column($totalReceiptAmount,'paid_money','sale_id');
        //合同金额
        $custometContractSql = "SELECT
          mn1.`NAME` AS sale_name,
          qys.admin_user_id,
          cu.nickname AS nickname,
          COUNT(qys.id) AS contract_num,
          SUM(qys.lower_money) AS contract_amount
        FROM
          lt_qiyuesuo_online_contract_jzd AS qys
          LEFT JOIN lt_thread_external AS thr ON thr.id = qys.thread_id
          LEFT JOIN lt_customer AS cu ON cu.id = qys.admin_user_id
          LEFT JOIN lt_merchant_organization AS mn ON mn.id = cu.merchant_organization_id
          LEFT JOIN lt_merchant_organization AS mn1 ON mn1.id = mn.pid
          LEFT JOIN lt_merchant AS mer ON mer.id = thr.merchant_id
          LEFT JOIN lt_channel_delivery_mode AS ce ON ce.channel_id = thr.channel_id
          LEFT JOIN lt_delivery_mode de ON ce.delivery_mode_id1 = de.id
          LEFT JOIN lt_delivery_mode de2 ON ce.delivery_mode_id2 = de2.id
        WHERE
          qys.contract_signing_time >= UNIX_TIMESTAMP('{$starttime}')
          AND qys.contract_signing_time <= UNIX_TIMESTAMP('{$endtime}')
          AND thr.merchant_id = {$merchantId}
          AND thr.is_assign = 3
          AND thr.channel_id>0
          AND qys.admin_user_id in ({$customerIdStr})
          AND qys.`status` = 'COMPLETE'
          AND qys.status_msg <> 'INVALID_CANCEL'
          AND qys.delete_time = 0
          AND qys.source_id NOT IN (8, 48)
          AND cu.nickname NOT like '%测试%'
        group by qys.admin_user_id";
        $customerTotalContractAmount = Db::query($custometContractSql);
        $customerTotalContractAmount = array_column($customerTotalContractAmount,'contract_amount','admin_user_id');
        //定金金额
        $customerDepositSql = "SELECT
                mn1.NAME AS sale_name,
                tt.charge_customer AS customer_id,
                cu.nickname as nickname,
                COUNT(tt.id ) AS deposit_num,
                SUM( tt.money ) AS deposit_amount 
        FROM
                lt_transaction_list AS tt
                INNER JOIN lt_thread_external AS thr ON thr.id = tt.thread_id
                LEFT JOIN lt_customer AS cu ON cu.id = tt.charge_customer
                LEFT JOIN lt_merchant_organization AS mn ON mn.id = cu.merchant_organization_id
                LEFT JOIN lt_merchant_organization AS mn1 ON mn1.id = mn.pid
                LEFT JOIN lt_merchant AS mer ON mer.id = thr.merchant_id
                LEFT JOIN lt_channel_delivery_mode ce ON thr.channel_id = ce.channel_id
                LEFT JOIN lt_delivery_mode de ON ce.delivery_mode_id1 = de.id
                LEFT JOIN lt_delivery_mode de2 ON ce.delivery_mode_id2 = de2.id 
        WHERE
                tt.pay_time >= UNIX_TIMESTAMP( '{$starttime}' ) 
                AND tt.pay_time <= UNIX_TIMESTAMP( '{$endtime}' ) 
                AND tt.collect_type = 1 
                AND tt.order_status = 1 
                AND tt.delete_time = 0 
                AND tt.source_id NOT IN ( 8, 48 ) 
                AND tt.money > 1 
                AND thr.merchant_id = {$merchantId}
                AND thr.customer_id in ({$customerIdStr})
                AND thr.is_assign = 3
                AND thr.channel_id>0
                AND cu.nickname NOT LIKE '%测试%'
         group by thr.customer_id";
        $customerTotalDepositAmount = Db::query($customerDepositSql);
        $customerTotalDepositAmount = array_column($customerTotalDepositAmount,'deposit_amount','customer_id');
        //一期实收
        $customerReceiptsSql = "SELECT
          FROM_UNIXTIME(sh.pay_time, '%Y-%m-%d') AS date_day,
          CASE
            WHEN cu.merchant_organization_id = 0 THEN '未分组'
            ELSE mn1.`NAME`
          END AS `销售团队`,
          thr.origin_customer_id AS customer_id,
          cu.nickname AS `销售`,
          cu.real_name AS `销售姓名`,
          mer.merchant_name AS `商户`,
          COUNT(sh.id) AS `实收笔数`,
          sum(paid_money) AS paid_money
        FROM
          lt_jzd_order_ledger_details AS sh
          LEFT JOIN lt_jzd_order_ledger AS jr ON jr.id = sh.ledger_id
          LEFT JOIN lt_thread_external AS thr ON thr.id = jr.thread_id
          LEFT JOIN lt_customer AS cu ON thr.origin_customer_id = cu.id
          LEFT JOIN lt_merchant_organization AS mn ON cu.merchant_organization_id = mn.id
          LEFT JOIN lt_merchant_organization AS mn1 ON mn1.id = mn.pid
          LEFT JOIN lt_merchant AS mer ON mer.id = thr.merchant_id
          LEFT JOIN lt_channel_delivery_mode AS ce ON thr.channel_id = ce.channel_id
          LEFT JOIN lt_delivery_mode de ON ce.delivery_mode_id1 = de.id
          LEFT JOIN lt_delivery_mode de2 ON ce.delivery_mode_id2 = de2.id
        WHERE
          sh.pay_time >= UNIX_TIMESTAMP('{$starttime}')
          AND sh.pay_time <= UNIX_TIMESTAMP('{$endtime}')
          AND thr.source_id NOT IN (8, 48)
          AND sh.deleted_time = 0
          AND jr.deleted_time = 0
          AND jr.is_disuse = 0
          AND thr.is_assign = 3
		   AND sh.current_stage_num=1
		   AND thr.apply_register_type=1
		   AND thr.channel_id>0
		   AND cu.nickname NOT like '%测试%'
		   AND thr.customer_id in ({$customerIdStr})
		group by customer_id";
        //   var_dump($receiptsSql);die;
        $customerTotalReceiptAmount = Db::query($customerReceiptsSql);
        $customerTotalReceiptAmount = array_column($customerTotalReceiptAmount,'paid_money','customer_id');
        $data = [
            'totalContractAmount' => $totalContractAmount,
            'totalDepositAmount' => $totalDepositAmount,
            'totalReceiptAmount' => $totalReceiptAmount,
            'customerTotalContractAmount' => $customerTotalContractAmount,
            'customerTotalDepositAmount' => $customerTotalDepositAmount,
            'customerTotalReceiptAmount' => $customerTotalReceiptAmount,
        ];
        return $data;
    }

    //同步销售分量数
    public function editCustomer()
    {
        $merchantId = $this->request->post('merchant_id') ?? 177;
        $datetime = date('Y-m-d');
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $jzdCustomerDailyLimitNum = $this->model->where('merchant_id',$merchantId)
            ->where('datetime',$datetime)
            ->field('id,customer_id,app_intake_limit_nums,old_daily_intake_limit_nums,assign_intake_limit_nums,register_intake_limit_nums,increase_intake_limit_nums')
            ->select()
            ->toArray();
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
                    if($item['app_intake_limit_nums'] >= 0) {
                        $dailyIntakeLimitNums = Customer::where('id',$item['customer_id'])
                            ->value('daily_intake_limit_nums');
                        $data[] = [
                            'id' => $item['customer_id'],
                            'daily_intake_limit_nums' => $item['app_intake_limit_nums'],
                            'day_allocated_num' => $item['old_daily_intake_limit_nums'],
                            'app_intake_limit_nums' => $item['app_intake_limit_nums'],
                            'register_intake_limit_nums' => $item['register_intake_limit_nums'],
                            'assign_intake_limit_nums' => $item['assign_intake_limit_nums'],
                            //'increase_intake_limit_nums' => $item['increase_intake_limit_nums'],
                        ];
                        $dataLog[] = [
                            'merchant_id' => $merchantId,
                            'customer_id' => $item['customer_id'],
                            'daily_intake_limit_nums' => $item['app_intake_limit_nums'],
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
            // if($customer && $customerLog){
            //     foreach($customerIds as $customerId) {
            //         Event::trigger('CustomerEdit', [
            //             'customer' => (new Customer())->find($customerId),
            //         ]);
            //     }
            // }
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }

    //设置新销售分配数量
    public function setAppCustomerNums()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $data = [];
        try {
            $customer = $this->model->where('id',$post['id'])->find();
            if(isset($post['app_intake_limit_nums']) && !empty($post['app_intake_limit_nums'])){
                $data['app_intake_limit_nums'] = $post['app_intake_limit_nums'];
            }else if(isset($post['assign_intake_limit_nums']) && !empty($post['assign_intake_limit_nums'])){
                $data['assign_intake_limit_nums'] = $post['assign_intake_limit_nums'];
            }else if(isset($post['register_intake_limit_nums']) && !empty($post['register_intake_limit_nums'])){
                $data['register_intake_limit_nums'] = $post['register_intake_limit_nums'];
            }else if(isset($post['old_daily_intake_limit_nums']) && !empty($post['old_daily_intake_limit_nums'])){
                $data['old_daily_intake_limit_nums'] = $post['old_daily_intake_limit_nums'];
            }
            if(!empty($data)){
                $updateRes = $customer->save($data);
                if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            }
            return $this->success('操作成功');
        } catch (\Exception $e) {
            return $this->exceptionError($e);
        }
    }

}
