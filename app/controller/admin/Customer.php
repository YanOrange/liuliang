<?php

namespace app\controller\admin;

use app\model\admin\login\Log;
use app\service\admin\AuthServiceFacade;
use app\service\admin\UserServiceFacade;
use app\validate\admin\customer\Customer as CustomerValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use laytp\library\Str;
use think\facade\Config;
use think\facade\Db;
use Hedeqiang\TenIM\IM;
use think\facade\Event;
use app\model\admin\Merchant;

/**
 * 后台客服控制器
 */
class Customer extends Backend
{
    protected $model;//当前模型对象

    protected function _initialize()
    {
        $this->model = new \app\model\admin\Customer();
    }

    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);

        $whereCon = [];
        $merchantId = $this->request->param('merchant_id', 0);

        // 干事角色ID 22.09.02 chenlele
        // 非超级管理员
        if ($merchantId) {
            $whereCon[] = ['merchant_id', '=', $merchantId];
        }
        if ($loginId != 1) {

            // 8 - 干事：管理自己负责的客户
            if (in_array(env('ROLE.GANSHI'), $roleIds)) {
                $merchantIds = Merchant::whereFindInSet('admin_ids', $loginId)->column('id');
                $whereCon[] = ['merchant_id', 'in', $merchantIds];
            }

            // 客服主管：看站内数据 @chenlele 0929
            if (in_array(env('ROLE.CUSTOMERLEADER'), $roleIds)) {
                $merchantIds = Merchant::where('is_source', 1)->column('id');
                $whereCon[] = ['merchant_id', 'in', $merchantIds];
            }
        }

        /*  if ($merchantId) {
              $whereCon[] = ['merchant_id', '=', $merchantId];
          }else{
              if ($loginId != 1 && in_array(6,$roleIds)) {
                  $merchantIds = Merchant::whereFindInSet('admin_ids',$loginId)->column('id');
                  $whereCon[] = ['merchant_id', 'in', $merchantIds];
              }
          }*/
        $data = $this->model->withCount(['validThreadNums','thread'])->where($where)->where($whereCon)->with(['merchant' => function ($query) {
            $query->field('id,merchant_name');
        }])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }

        return $this->success('数据获取成功', $data);
    }

    public function customerList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->where($where)->where('status', 1)->with(['merchant' => function ($query) {
            $query->field('id,merchant_name');
        }])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        if (!empty($data)) {
            foreach ($data as &$val) {
                $val['nickname'] = isset($val['merchant']['merchant_name']) ? '[' . $val['merchant']['merchant_name'] . ']' . $val['nickname'] : $val['nickname'];
            }
        }
        return $this->success('数据获取成功', $data);
    }

    //添加
    public function add()
    {
        $post = CommonFun::filterPostData($this->request->post());
        $validate = new CustomerValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            if(in_array($post['merchant_id'],[142,195,229])){
                $post['weight'] = 1;
            }
            if(isset($post['daily_intake_time_period']) && !empty($post['daily_intake_time_period'])){
                $dailyIntakeTimePeriod = explode(' - ',$post['daily_intake_time_period']);
                $dailyIntakeTimePeriodStart = $dailyIntakeTimePeriod[0] ?? '';
                $dailyIntakeTimePeriodEnd = $dailyIntakeTimePeriod[1] ?? '';
                if($dailyIntakeTimePeriodStart && $dailyIntakeTimePeriodEnd){
                    $post['daily_intake_time_period'] = date('H:i',strtotime(date('Y-m-d').' '.$dailyIntakeTimePeriodStart)).'-'.date('H:i',strtotime(date('Y-m-d').' '.$dailyIntakeTimePeriodEnd));
                }
            }
            $post['pwd'] = Str::createPassword($post['pwd']);
            $saveRes = $this->model->save($post);
            if (!$saveRes) throw new \Exception('数据库异常，操作失败');
            Event::trigger('CustomerAdd', [
                'customer' => $this->model->find($this->model->id),
            ]);
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
        if($info['daily_intake_time_period']){
            $info['daily_intake_time_period'] = str_replace("-", " - ", $info['daily_intake_time_period']);
        }
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post = CommonFun::filterPostData($this->request->post());
        $validate = new CustomerValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $post = CommonFun::filterPostData($this->request->post());
            $customer = $this->model->findOrEmpty($post['id']);
            if(in_array($post['merchant_id'],[142,195,229])){
                unset($post['weight']);
            }
            //var_dump($customer->id);die;
            if (!$customer) throw new \Exception('id参数错误');
            if ($post['pwd']) {
                $post['pwd'] = Str::createPassword($post['pwd']);
            } else {
                unset($post['pwd']);
            }
            if(isset($post['daily_intake_time_period']) && !empty($post['daily_intake_time_period'])){
                $dailyIntakeTimePeriod = explode(' - ',$post['daily_intake_time_period']);
                $dailyIntakeTimePeriodStart = $dailyIntakeTimePeriod[0] ?? '';
                $dailyIntakeTimePeriodEnd = $dailyIntakeTimePeriod[1] ?? '';
                if($dailyIntakeTimePeriodStart && $dailyIntakeTimePeriodEnd){
                    $post['daily_intake_time_period'] = date('H:i',strtotime(date('Y-m-d').' '.$dailyIntakeTimePeriodStart)).'-'.date('H:i',strtotime(date('Y-m-d').' '.$dailyIntakeTimePeriodEnd));
                }
            }
            $updateRes = $customer->update($post);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Event::trigger('CustomerEdit', [
                'customer' => $this->model->find($updateRes->id),
            ]);
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
                Event::trigger('CustomerDel', [
                    'customerIds' => $ids,
                ]);
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

    //设置进量状态
    public function setThreadStatus()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['thread_status'] = $fieldVal;
        try {
            if ($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                Event::trigger('CustomerThreadStatus', [
                    'customer' => $this->model->find($id),
                ]);
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

    //回收站
    public function recycle()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $limit = $this->request->param('limit', 10);
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);
        $whereCon = [];
        $merchantId = $this->request->param('merchant_id', 0);

        if ($merchantId) {
            $whereCon[] = ['merchant_id', '=', $merchantId];
        }
        if ($loginId != 1 && in_array(env('ROLE.GANSHI'), $roleIds)) {

            // 8 - 干事：管理自己负责的客户
            $merchantIds = Merchant::whereFindInSet('admin_ids', $loginId)->column('id');
            $whereCon[] = ['merchant_id', 'in', $merchantIds];

        }
        $data = $this->model->onlyTrashed()
            ->order($order)->where($where)->where($whereCon)->with(['merchant' => function ($query) {
                $query->field('id,merchant_name');
            }])->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }
}