<?php

namespace app\controller\admin;

use app\model\admin\ThreadExternal;
use app\model\admin\UserListExternal;
use app\service\admin\UserServiceFacade;
use app\validate\admin\app\App as AppValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use app\model\admin\ClueMessage;
use app\model\admin\Thread;
use app\model\admin\Merchant;
use app\lib\api\wx\Robot;
use app\model\admin\MerchantRechargeDetail;
/**
 * 后台应用控制器
 */
class ClueFeedbackTwo extends Backend
{
    protected $model;//当前模型对象

    protected $noNeedAuth = [''];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\ClueFeedback();
    }
    //查看
    public function index()
    {
        //$where = $this->buildSearchParams();
        $order = $this->buildOrder();
        //$where[] = ['feedback_status','in',[20,21]];
        $data = $this->buildSearch()->with(['threadExternal','userExternal','merchant','subMerchant','subCustomer','problem','auditorOne','auditorTwo'])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->each(function($item){
                if($item['submitter_terminal'] == 1){
                    $item['subMerchant'] = [
                        'merchant_name' => $item['subCustomer']['nickname']
                    ];
                }
                $item['auditor_one_time'] = !empty($item['auditor_one_time']) ? date('Y-m-d H:i:s', $item['auditor_one_time']) : '';
                $item['auditor_two_time'] = !empty($item['auditor_two_time']) ? date('Y-m-d H:i:s', $item['auditor_two_time']) : '';

            })->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    // 构建查询条件
    private function buildSearch()
    {
        $filter = $this->request->param('search_param');
        $filter = !empty($filter) ? $filter : [];
        extract($filter);
        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;

        if (isset($feedback_status) && !empty($feedback_status)) {
            $this->model = $this->model->where($tableName . '.feedback_status', '=', $feedback_status);
        }else{
            $this->model = $this->model->where($tableName . '.feedback_status', '=', 20);
        }

        if (isset($nickname) && !empty($nickname)) {
            $uid = UserListExternal::where('nickname',$nickname)->value('id');
            $this->model = $this->model->where($tableName . '.thread_external_uid', '=', $uid);
        }
        if (isset($wx_nickname) && !empty($wx_nickname)) {
            $uid = UserListExternal::where('wx_nickname',$wx_nickname)->value('id');
            $this->model = $this->model->where($tableName . '.thread_external_uid', '=', $uid);
        }
        if (isset($phone) && !empty($phone)) {
            $uid = UserListExternal::where('phone',$phone)->value('id');
            $this->model = $this->model->where($tableName . '.thread_external_uid', '=', $uid);
        }
        if (isset($clue_problem_id) && !empty($clue_problem_id)) {
            $this->model = $this->model->where($tableName . '.clue_problem_id', '=', $clue_problem_id);
        }

        if (isset($thread_create_time) && !empty($thread_create_time)) {
            $this->model = $this->model->withJoin(['threadExternal'], 'inner');
            list($startTime, $endTime) = explode(' - ', $thread_create_time);
            $this->model = $this->model->where('threadExternal.create_time', 'between', strtotime($startTime) . ',' . strtotime($endTime));
        }

        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $this->model = $this->model->where($tableName . '.create_time', 'between', $startTime . ',' . $endTime);
        }

        return $this->model;
    }

    //查看详情
    public function info()
    {
        $id   = $this->request->param('id');
        $info = $this->model->with(['threadExternal','userExternal','subMerchant','subCustomer','problem'])->findOrEmpty($id)->toArray();
        if($info['submitter_terminal'] == 1){
            $info['subMerchant']['merchant_name'] = $info['subCustomer']['nickname'];
        }
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        //$validate = new AppValidate();
        //if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $feedback = $this->model->with(['userExternal'])->findOrEmpty($post['id']);
            if (!$feedback) throw new \Exception('id参数错误');
            $threadExternalInfo = ThreadExternal::find($feedback->thread_external_id);
            if (empty($threadExternalInfo)) {
                throw new \Exception('记录不存在');
            }
            $threadInfo = Thread::find($threadExternalInfo->inside_thread_id);
            if (empty($threadInfo)) {
                throw new \Exception('记录不存在');
            }
           // $merchantName = Merchant::where('id', $threadExternalInfo->merchant_id)->value('merchant_name');
            
            $post['auditor_two'] = $loginId;
            $post['auditor_two_time'] = time();
            $updateRes  = $feedback->update($post);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            $message = $post['feedback_status'] == 30 ? '已通过' : '未通过';
            ClueMessage::create([
                'thread_external_id' => $feedback->thread_external_id,
                'merchant_id' => $feedback->merchant_id,
                'admin_id' => $loginId,
                'content' => '您提交的无效线索，线索手机号：'.$feedback->userExternal->phone.'管理员已审核'.$message,
                'recipient_id' => $feedback->submitter,
                'submitter_terminal' => $feedback->submitter_terminal
            ]);
            ThreadExternal::where('id',$feedback->thread_external_id)->update(['feedback_status' => $post['feedback_status'] == 30 ? 2 : 3]);
            if ($post['feedback_status'] == 30) {
                $merchant = Merchant::find($threadInfo->merchant_id);
                $redis = get_redis();
                $redisKey = env('redis.merchant_amount_redis_v2_key') . $threadInfo->merchant_id;
                if (!$redis->exists($redisKey)) {
                    $redis->set($redisKey, floatToInt($merchant->residue_amount));
                }
                $redis->watch($redisKey);
                $redis->multi();
                $redis->incrBy($redisKey, floatToInt($threadInfo->thread_price));
                $result = $redis->exec();
                if ($result) {
                    $merchant->total_amount += $threadInfo->thread_price;
                    $merchant->residue_amount += $threadInfo->thread_price;
                   
                    //教之道/飞鱼只计算明天预补量字段
                    if (($merchant->id == 177 && date('H') >= 15) || ($merchant->id == 177 && $merchant->is_switch == 0) || ($merchant->id == 251 && date('H') >= 14) || ($merchant->id == 251 && $merchant->is_switch == 0)) {
                        if (!$threadInfo->is_register) {
                            $merchant->tomorrow_customer_supplement +=1;
                        }
                    }
                    if ($threadInfo->is_register) {
                        $merchant->register_supplement +=1;
                    }
                    if (!$threadInfo->is_register) {
                        //飞鱼当天补量
                        if (($merchant->id == 251 && date('H') < 14 && $merchant->is_switch == 1) || ($merchant->id == 177 && date('H') < 15 && $merchant->is_switch == 1)) {
                            $threadInfo->is_valid = 0;
                            $threadInfo->save();
                            $redisKey = env('redis.customer_redis_key') . $merchant->id;
                            $redisInfo = $redis->hget($redisKey, $threadInfo->customer_id);
                            if (!empty($redisInfo)) {
                                $redisInfo = json_decode($redisInfo, true);
                                $redisInfo['residue_thread_num'] = $redisInfo['residue_thread_num'] + 1;
                                $redisInfo['total_thread_num'] = $redisInfo['total_thread_num'] + 1;
                                $redisInfo['period_thread_num'] = $redisInfo['period_thread_num'] + 1;
                                $redis->hSet($redisKey, $threadInfo->customer_id, json_encode($redisInfo));
                            }
                        }   
                    }
                    //$merchant->tomorrow_customer_supplement +=1;
                    $ret = $merchant->save();
                    if (!$ret) {
                        $redis->decrBy($redisKey, floatToInt($threadInfo->thread_price));
                        return $this->error('数据库异常，操作失败');
                    }
                    $loginUserInfo = UserServiceFacade::getUserInfo();
                    MerchantRechargeDetail::create([
                        'recharge_amount' => $threadInfo->thread_price,
                        'operator_id' => $loginUserInfo['id'],
                        'merchant_id' => $merchant->id,
                        'recharge_type' => 7,
                        'app_class_id' => $merchant->app_class_id,
                        'back_sale_id' => $merchant->admin_ids,
                        'front_sale_id' => $merchant->front_sale_id,
                        'title' => '无效线索审核通过系统补量充值' . $threadInfo->thread_price,
                        'remark' => '无效线索审核通过系统补量充值' . $threadInfo->thread_price,
                        'supplement_nums' => 1,
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
}