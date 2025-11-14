<?php

namespace app\controller\admin;

use app\model\admin\login\Log;
use app\model\admin\thread\ThreadTag;
use app\service\admin\AuthServiceFacade;
use app\service\admin\UserServiceFacade;
use app\validate\admin\merchant\Merchant as MerchantValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use laytp\library\Str;
use laytp\library\Tree;
use think\facade\Config;
use think\facade\Db;
use app\model\admin\Merchant as MerchantModel;
use app\model\api\Merchant as MerchantObj;
use app\model\admin\Thread;
use app\model\api\MerchantInputSwitchTimerLog;
use app\model\admin\GatherUserInfo;
use \app\model\admin\UserList;

/**
 * 后台商户控制器
 */
class Merchant extends Backend
{

    protected $noNeedAuth = ['getListByAppClassId','ageRangeListRate'];

    protected $model;//当前模型对象

    protected function _initialize()
    {
        $this->model = new \app\model\admin\Merchant();
    }

    public function getListByAppClassId(){
        $app_class_id = $this->request->param('app_class_id');
        $merchantList = $this->model->field('id as value,merchant_name as name,is_source')->where('app_class_id',$app_class_id)->select()->toArray();
        if(!empty($merchantList)){
            foreach ($merchantList as &$val){
                $source = '站外';
                if($val['is_source'] == 1){
                    $source = '站内';
                }
                $val['name'] = $val['name'].'-'.$source ;
            }
        }
        return $this->success('数据获取成功', $merchantList);
    }

    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);
        $whereCon = " 1 = 1";
        /*   if ($loginId != 1 && in_array(6,$roleIds)) {
               $whereCon .= " AND FIND_IN_SET({$loginId},admin_ids)";
           }*/
        $isManyOrganization = $this->request->param('is_many_organization');
        if (is_numeric($isManyOrganization)) {
            $whereCon .= " AND FIND_IN_SET({$isManyOrganization},is_many_organization)";
        }

        // 客户权限 chenlele 2022-09-03
        $isCustomer = false;
        if ($loginId != 1) {
            // 8 - 干事：管理自己负责的客户
            if (in_array(env('ROLE.GANSHI'), $roleIds)) {
                $where[] = ['admin_ids', '=', $loginId];
            }

            // 10 - 客服主管
            if (in_array(env('ROLE.CUSTOMERLEADER'), $roleIds)) {
                $isCustomer = true;
            }
        }
        $data = $this->model->withCount(['totalThreadNum','appThreadNum','validThreadNum','registerThreadNum','customerAssignThreadNum', 'customerThreadNum', 'natureThreadNum', 'customerFormThreadNum'])->where($where)->where($whereCon)->with(['appClass'])->order($order);
        $allData = $this->request->param('all_data');

        if ($allData) {
            $data = $data->select();
            foreach ($data as &$val) {
                $val['isCustomer'] = $isCustomer;   // chenlele 0903
                $val['merchant_name_source'] = $val['merchant_name'] . ' - 站外';
                if ($val['is_source'] == 1) {
                    $val['merchant_name_source'] = $val['merchant_name'] . ' - 站内';
                }
            }
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
            foreach ($data['data'] as &$val) {
                $val['admin_role_id'] = 0;
                $val['isCustomer'] = $isCustomer;   // chenlele 0903
                if (in_array(6, $roleIds)) {
                    $val['admin_role_id'] = 6;
                }
                //$val['current_today_thread_nums'] = Thread::where('merchant_id',$val['id'])->whereDay('create_time')->count();
                $threadModel = new Thread();
                $val['is_has_computer_rate_limit'] = $val['is_has_computer_rate_limit'] > 0 ? $val['is_has_computer_rate_limit'] . '%' : '-';
                $val['merchant_name_source'] = $val['merchant_name'] . ' - 站外';
                if ($val['is_source'] == 1) {
                    $val['merchant_name_source'] = $val['merchant_name'] . ' - 站内';
                }
            }
        }

        return $this->success('数据获取成功', $data);
    }

    public function merchantList()
    {
        $isSource = $this->request->param('is_source');
        $isSource = !empty($isSource) ? $isSource : 1;
        $data = $this->model->where('is_source', $isSource)->order('id asc');
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //年龄段
    public function ageRangeList()
    {
        $gatherInfoJson = GatherUserInfo::where('id', 1)->value('gather_info_json');
        $data['age_range_list'] = json_decode($gatherInfoJson, true);

        // 商务主管、干事 隐藏：线索转化设置 2022.09.02
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);

        $isShowTransform = 'block';
        if ($loginId != 1) {
            // 6 - 商务团队 || 8 - 干事：管理自己负责的客户
            if (in_array(6, $roleIds) || in_array(env('ROLE.GANSHI'), $roleIds)) {
                $isShowTransform = 'none';
            }
        }
        $data['is_show_transform'] = $isShowTransform;

        return $this->success('数据获取成功', $data);
    }

    //年龄段
    public function ageRangeListRate()
    {
        $gatherInfoJson = GatherUserInfo::where('id', 1)->value('gather_info_json');
        $data = json_decode($gatherInfoJson, true);
        return $this->success('数据获取成功', $data);
    }

    //添加
    public function add()
    {
        $post = $this->request->post();
        $ageRangeArr = $post['age_range_weight_json']['age_range'];
        $weighteArr = $post['age_range_weight_json']['weight'];
        $ageRangeWeightJson = [];
        for ($i = 0; $i < count($ageRangeArr); $i++) {
            $ageRangeWeightJson[$ageRangeArr[$i]] = $weighteArr[$i];
        }
        $ageRangeIds = $post['age_range_rate_json']['age_ids'];
        $ageRates = $post['age_range_rate_json']['age_rates'];
        if(count($ageRangeIds) != count($ageRates)){
            return $this->error('年龄分配率参数错误');
        }
        $ageRangeRateJson = [];
        for ($i = 0; $i < count($ageRangeIds); $i++) {
            if(!empty($ageRangeIds[$i])){
                if($ageRates[$i] <= 0){
                    return $this->error('年龄分配率必须大于0');
                }else{
                    $ageRangeRateJson[] = ['id' => $ageRangeIds[$i],'rate' => $ageRates[$i]];
                }
            }
        }
        $post['age_range_rate_json'] = json_encode($ageRangeRateJson);
        $post['age_range_weight_json'] = json_encode($ageRangeWeightJson, JSON_UNESCAPED_UNICODE);
        $post['customer_qrcode_explain'] = json_encode($post['customer_qrcode_explain'], JSON_UNESCAPED_UNICODE);
        $post['customer_qrcode_explain_single'] = json_encode($post['customer_qrcode_explain_single'], JSON_UNESCAPED_UNICODE);
        $post['part_mini_official'] = json_encode($post['part_mini_official'], JSON_UNESCAPED_UNICODE);
        $post['live_mini_official'] = json_encode($post['live_mini_official'], JSON_UNESCAPED_UNICODE);
        $post['cultivate_mini_official'] = json_encode($post['cultivate_mini_official'], JSON_UNESCAPED_UNICODE);
        $post['manual_assign_ratio'] = json_encode($post['manual_assign_ratio'], JSON_UNESCAPED_UNICODE);
        $post['capital_ratio_update_time'] = time();
        $validate = new MerchantValidate();

        if (isset($post['is_customer'])) {
            $ck = $validate->checkTenImCustomer($post['app_class_id'], $post['is_customer'], $post['customer_consult_icon']);

            if(!$ck['status']) {
                return $this->error($ck['msg']);
            }
        }
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $post['pwd'] = Str::createPassword($post['pwd']);
            $saveRes = $this->model->save($post);
            if (!$saveRes) throw new \Exception('数据库异常，操作失败');
            //20220801--start
            //添加成功后 把ID追加到thread_tag表中的merchant_ids
            event('AppendMerchantIdToThreadTag', ['merchant_id' => $this->model->id]);
            //20220801--end
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('数据库异常，操作失败');
        }
    }

    //查看详情
    public function info()
    {
        $id = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        $gatherUserInfo = GatherUserInfo::where('id',1)->find();
        $ageRangeList = json_decode($gatherUserInfo['gather_info_json'],true);
        $ageRangeArr = [];
        foreach($ageRangeList as $item){
            $ageRangeArr[$item['name']] = 10;
        }
        $ageRangeWeightJson = json_decode($info['age_range_weight_json'], true);
        foreach($ageRangeWeightJson as $key => $weight){
            if(isset($ageRangeArr[$key])){
                $ageRangeArr[$key] = $weight;
            }
        }
        $info['age_range_weight_json'] = $ageRangeArr;
        $thread_input_period_string = '';
        if (!empty($info['thread_input_period_json'])) {
            $thread_input_period_data = json_decode($info['thread_input_period_json'], true);
            foreach ($thread_input_period_data as $val) {
                if ($val['time'] === "") {
                    $thread_input_period_string .= $val['week'] . "(暂未设置),";
                } else {
                    $thread_input_period_string .= $val['week'] . "({$val['time']}点自动开启进量),";
                }
            }
        } else {
            $thread_input_period_string = "暂未设置进量周期";
        }
        $info['thread_input_period_string'] = $thread_input_period_string;
        $info['customer_qrcode_explain'] = json_decode($info['customer_qrcode_explain'], true);
        $info['customer_qrcode_explain_single'] = json_decode($info['customer_qrcode_explain_single'], true);
        $info['part_mini_official'] = json_decode($info['part_mini_official'], true);
        $info['live_mini_official'] = json_decode($info['live_mini_official'], true);
        $info['cultivate_mini_official'] = json_decode($info['cultivate_mini_official'], true);
        $info['manual_assign_ratio'] = json_decode($info['manual_assign_ratio'], true);
        $info['age_range_rate_json'] = !empty($info['age_range_rate_json']) ? json_decode($info['age_range_rate_json'], true) : [];
        for($i = 0;$i < 5; $i++){
            if(!isset($info['age_range_rate_json'][$i])){
                $data = ['id' => '','rate' => 0];
                array_push($info['age_range_rate_json'],$data);
            }
        }
        $info['is_show_qrcode_single'] = 0;
        if (strpos($info['is_many_organization'], '4') !== false) {
            $info['is_show_qrcode_single'] = 1;
        }

        // 商务主管、干事 隐藏：线索转化设置 2022.09.02
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);

        // 客户权限 chenlele 2022-09-03
        $isCustomer = 'block';

        $isShowTransform = 'block';
        if ($loginId != 1) {
            // 6 - 商务团队 || 8 - 干事：管理自己负责的客户
            if (in_array(6, $roleIds) || in_array(env('ROLE.GANSHI'), $roleIds)) {
                $isShowTransform = 'none';
            }

            // 10 - 客服主管
            if (in_array(env('ROLE.CUSTOMERLEADER'), $roleIds)) {
                $isCustomer = 'none';
            }
        }
        $info['is_tenim_customer'] = $info['is_customer'];
        $info['is_customer'] = $isCustomer;
        $info['is_show_transform'] = $isShowTransform;

        return $this->success('获取成功', $info);
    }

    public function setIsSwitchInput()
    {
        $post = $this->request->post();

        $merchant = $this->model->find($post['id']);
        if (!$merchant) {
            return $this->error('商户不存在');
        }
        if (!empty($post['thread_input_period_json'])) {
            $threadInputPeriodJson = json_decode($post['thread_input_period_json'], true);
            foreach ($threadInputPeriodJson as $key => &$val) {
                if ($val['time'] == 'null') {
                    $val['time'] = '';
                }
                if (empty($val)) {
                    $val = getDefaultWeek($key);
                }
            }
            $post['thread_input_period_json'] = json_encode($threadInputPeriodJson);
        }

        // @chenlele 22-1015 商户需求量改变重新计算带分配条数 start
        $merchantArr = $merchant->toArray();
        $flag = false;
        if ($post['totay_thread_limit_nums'] != $merchantArr['totay_thread_limit_nums']) {
          $flag = true;
        }
        $res = $merchant->allowField(['totay_thread_limit_nums', 'thread_input_period_json'])->save($post);
        if ($flag) {
            $merchantArr = $this->model->findOrEmpty($post['id'])->toArray();
            $merchantArr['create_time'] = strtotime($merchantArr['create_time']);
            $customerTotal = Db::name('customer_distribute_quality')
                ->field('id, days')
                ->where('merchant_id', $post['id'])
                ->value('days');
            CustomerDistribute::setDaysCompute($merchantArr, (int)$customerTotal);
        }
        // @chenlele 22-1015 商户需求量改变重新计算带分配条数 end

        if ($res !== false) {
            return $this->success('操作成功');
        } else {
            return $this->error('操作失败');
        }

    }

    //编辑
    public function edit()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $roleIds = AuthServiceFacade::getAuthUserRole($loginUserInfo['id']);
        $post = $this->request->post();
        $ageRangeArr = $post['age_range_weight_json']['age_range'];
        $weighteArr = $post['age_range_weight_json']['weight'];
        $ageRangeWeightJson = [];
        for ($i = 0; $i < count($ageRangeArr); $i++) {
            $ageRangeWeightJson[$ageRangeArr[$i]] = $weighteArr[$i];
        }
        $ageRangeIds = $post['age_range_rate_json']['age_ids'];
        $ageRates = $post['age_range_rate_json']['age_rates'];
        if(count($ageRangeIds) != count($ageRates)){
            return $this->error('年龄分配率参数错误');
        }
        $ageRangeRateJson = [];
        for ($i = 0; $i < count($ageRangeIds); $i++) {
            if(!empty($ageRangeIds[$i])){
                if($ageRates[$i] <= 0){
                    return $this->error('年龄分配率必须大于0');
                }else{
                    $ageRangeRateJson[] = ['id' => $ageRangeIds[$i],'rate' => $ageRates[$i]];
                }
            }
        }
        $post['age_range_rate_json'] = json_encode($ageRangeRateJson);
        $post['age_range_weight_json'] = json_encode($ageRangeWeightJson, JSON_UNESCAPED_UNICODE);
        $post['customer_qrcode_explain'] = json_encode($post['customer_qrcode_explain'], JSON_UNESCAPED_UNICODE);
        $post['customer_qrcode_explain_single'] = json_encode($post['customer_qrcode_explain_single'], JSON_UNESCAPED_UNICODE);
        $post['part_mini_official'] = json_encode($post['part_mini_official'], JSON_UNESCAPED_UNICODE);
        $post['live_mini_official'] = json_encode($post['live_mini_official'], JSON_UNESCAPED_UNICODE);
        $post['cultivate_mini_official'] = json_encode($post['cultivate_mini_official'], JSON_UNESCAPED_UNICODE);
        $post['manual_assign_ratio'] = json_encode($post['manual_assign_ratio'], JSON_UNESCAPED_UNICODE);
        $validate = new MerchantValidate();

        if (isset($post['is_customer'])) {
            $ck = $validate->checkTenImCustomer($post['app_class_id'], $post['is_customer'], $post['customer_consult_icon']);
            if (!$ck['status']) {
                return $this->error($ck['msg']);
            }
        }
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $merchant = $this->model->findOrEmpty($post['id']);
            if (!$merchant) throw new \Exception('id参数错误');

            // @chenlele 22-1015 商户需求量改变重新计算带分配条数 start
            $merchantArr = $merchant->toArray();
            $merchantArr['create_time'] = strtotime($merchantArr['create_time']);
            $flag = false;
            if ($post['totay_thread_limit_nums'] != $merchantArr['totay_thread_limit_nums']) {
                $flag = true;
            }
            $res = $merchant->allowField(['totay_thread_limit_nums', 'thread_input_period_json'])->save($post);
            if ($flag) {
                $merchantArr = $this->model->findOrEmpty($post['id'])->toArray();
                $merchantArr['create_time'] = strtotime($merchantArr['create_time']);
                $customerTotal = Db::name('customer_distribute_quality')
                    ->field('id, days')
                    ->where('merchant_id', $post['id'])
                    ->value('days');
                CustomerDistribute::setDaysCompute($merchantArr, (int)$customerTotal);
            }
            // @chenlele 22-1015 商户需求量改变重新计算带分配条数 end

            if ($post['pwd']) {
                $post['pwd'] = Str::createPassword($post['pwd']);
            } else {
                unset($post['pwd']);
                unset($post['re_pwd']);
            }
            if($post['capital_landing_page_share_merchant_ratio1'] != $merchantArr['capital_landing_page_share_merchant_ratio1'] || $post['capital_landing_page_share_merchant_ratio2'] != $merchantArr['capital_landing_page_share_merchant_ratio2']){
                $redis = get_redis();
                $threadNumRedisKey = env('redis.merchant_today_app_thread_num_redis_key') . $post['id'];
                $zeroThreadNumRedisKey = env('redis.merchant_today_app_zero_thread_num_redis_key') . $post['id'];
                $redis->set($threadNumRedisKey, 0);
                $redis->expireAt($threadNumRedisKey, mktime('23',59,59, date('m'),date('d'),date('Y')));
                $redis->set($zeroThreadNumRedisKey, 0);
                $redis->expireAt($zeroThreadNumRedisKey, mktime('23',59,59, date('m'),date('d'),date('Y')));
                $post['capital_ratio_update_time'] = time();
            }
            if (in_array(6, $roleIds)) {
                $post1['id'] = $post['id'];
                $post1['totay_thread_limit_nums'] = $post['totay_thread_limit_nums'];
                $updateRes = $merchant->update($post1);
            } else {
                $updateRes = $merchant->update($post);
            }
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }

    //删除
    public function del()
    {
        $ids = array_filter($this->request->param('ids'));
        if (!$ids) {
            return $this->error('参数ids不能为空');
        }
        try {
            if ($this->model->destroy($ids)) {
                return $this->success('数据删除成功');
            } else {
                return $this->error('数据删除失败');
            }
        } catch (\Exception $e) {
            return $this->exceptionError($e);
        }
    }

    //设置账号状态
    public function setStatus()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['status'] = $fieldVal;
        try {
            if ($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                return $this->success('操作成功');
            } else if ($updateRes === 0) {
                return $this->success('未作修改');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }

    //设置纯表单状态
    public function setIsForm()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_form'] = $fieldVal;
        try {
            if ($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                return $this->success('操作成功');
            } else if ($updateRes === 0) {
                return $this->success('未作修改');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }

    //设置跳转小程序状态
    public function setIsJumpMiniprogram()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_jump_miniprogram'] = $fieldVal;
        try {
            if ($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                return $this->success('操作成功');
            } else if ($updateRes === 0) {
                return $this->success('未作修改');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }

    //设置进量状态
    public function setIsSwitch()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $nowTime = date('Y-m-d H:i:s');
        $loginUserInfo = UserServiceFacade::getUserInfo();
        try {
            $merchant = MerchantModel::withTrashed()->find($id);
            if (empty($merchant)) {
                return $this->error('记录不存在');
            }
            if ($fieldVal) {
                $threadPriceInfo = MerchantObj::getMerchantThreadPrice($merchant);
                if ($merchant->residue_amount <= 0 || $merchant->residue_amount < $threadPriceInfo['thread_price']) {
                    return $this->error('账户可用余额不足');
                }
            }
            Db::startTrans();
            event($fieldVal == 0 ? 'MerchantCloseIsSwitch' : 'MerchantOpenIsSwitch', ['merchant' => $merchant]);
            if ($fieldVal == 0) {
                $remark = "管理员{$loginUserInfo['nickname']}于{$nowTime}成功关闭广告主{$merchant->merchant_name}进量";
            }
            if ($fieldVal == 1) {
                $remark = "管理员{$loginUserInfo['nickname']}于{$nowTime}成功开启广告主{$merchant->merchant_name}进量";
            }
            MerchantInputSwitchTimerLog::create([
                'merchant_id' => $merchant->id,
                'remark' => $remark,
            ]);
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('数据库异常，操作失败');
        }
    }

    //设置文案状态
    public function setCustomerExplainStatus()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['customer_explain_status'] = $fieldVal;
        try {
            if ($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                return $this->success('操作成功');
            } else if ($updateRes === 0) {
                return $this->success('未作修改');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }

    //是否允许设置进量状态
    public function setIsAllowSetSwitch()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_allow_set_switch'] = $fieldVal;
        try {
            if ($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                return $this->success('操作成功');
            } else if ($updateRes === 0) {
                return $this->success('未作修改');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }

    //设置落地页进量开关
    public function setLandingPageThreadSwitch()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['landing_page_thread_switch'] = $fieldVal;
        try {
            if ($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                return $this->success('操作成功');
            } else if ($updateRes === 0) {
                return $this->success('未作修改');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }

    //设置免测状态
    public function setIsFreeTry()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_free_try'] = $fieldVal;
        try {
            if ($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                return $this->success('操作成功');
            } else if ($updateRes === 0) {
                return $this->success('未作修改');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }

    //设置分配状态
    public function setIsAssign()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_assign'] = $fieldVal;
        try {
            if ($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                return $this->success('操作成功');
            } else if ($updateRes === 0) {
                return $this->success('未作修改');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }

    //设置当天线索数量限制状态
    public function setIsEditTotayThreadLimitNums()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_edit_totay_thread_limit_nums'] = $fieldVal;
        try {
            if ($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                return $this->success('操作成功');
            } else if ($updateRes === 0) {
                return $this->success('未作修改');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }


    // 逾期类目增加客服对话功能：客服功能
    public function setIsCustomer()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_customer');
        $update['is_customer'] = $fieldVal;
        try {
            // 逾期分类：只能上传客服咨询图标才能开启
            $merchant = Db::name('merchant')->where('id', $id)->field(['id', 'customer_consult_icon', 'app_class_id'])->find();
            if ($merchant['app_class_id'] != env('TENIM.YUQICLSID')) {
                return $this->error('该功能只针对于逾期类使用');
            }
            if ($fieldVal && empty($merchant['customer_consult_icon'])) {
                return $this->error('请上传客服咨询入口图标');
            }
            if ($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                return $this->success('操作成功');
            } else if ($updateRes === 0) {
                return $this->success('未作修改');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }

    //补量设置
    public function setSupplement()
    {
        $post = $this->request->post();
        $validate = new MerchantValidate();
        if (!$validate->scene('setSupplement')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $merchant = $this->model->findOrEmpty($post['id']);
            if (!$merchant) throw new \Exception('id参数错误');

            $saveRes = $this->model->update($post);
            if (!$saveRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('数据库异常，操作失败');
        }
    }

    //设置首页排名
    public function setRank()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['rank'] = $fieldVal;
        try {
            if($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                return $this->success('操作成功');
            } else if ($updateRes === 0) {
                return $this->success('未作修改');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }
    //设置注册量开关
    public function setIsRegister()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_register'] = $fieldVal;
        try {
            if($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                return $this->success('操作成功');
            } else if ($updateRes === 0) {
                return $this->success('未作修改');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }
    
    //设置获客链接
    public function setIsCustomerLink()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_customer_link'] = $fieldVal;
        try {
            if($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                return $this->success('操作成功');
            } else if ($updateRes === 0) {
                return $this->success('未作修改');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }
}