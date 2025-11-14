<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use app\validate\admin\merchantrechargedetail\MerchantRechargeDetail as MerchantRechargeDetailValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use app\model\admin\Merchant;
use app\service\admin\AuthServiceFacade;
/**
 * 后台商户充值明细控制器
 */
class MerchantRechargeDetail extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\MerchantRechargeDetail();
    }
    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);

        // 客户权限 chenlele 2022-09-03
        if ($loginId != 1 && in_array(env('ROLE.GANSHI'), $roleIds)) {

            // 8 - 干事：管理自己负责的客户
            $merchantIds = Merchant::whereFindInSet('admin_ids', $loginId)->column('id');
            $where[] = ['merchant_id', 'in', $merchantIds];
        }

        $data = $this->model->where($where)->with(['merchant','operator'])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }
    //添加
    public function recharge()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new MerchantRechargeDetailValidate();
        if (!$validate->scene('recharge')->check($post)) return $this->error($validate->getError());
        try {
            extract($post);
            if (!preg_match('/^([-+])?\d+(\.[0-9]{1,2})?$/', $recharge_amount)){
                return $this->error('金额错误');
            }
            $merchant = Merchant::find($merchant_id);
            if (!$merchant) {
                throw new \Exception('该商户不存在');
            }
            if ($recharge_amount < 0) {
                $rechargeAmount = str_replace('-', '', $recharge_amount);
                if ($rechargeAmount > $merchant->residue_amount) {
                    return $this->error('商户扣除余额不足');
                }
            }
            if (!$recharge_type) {
                return $this->error('请选择充值类型');
            }
            $redis = get_redis();
            $redisKey = env('redis.merchant_amount_redis_v2_key') . $merchant_id;
            if (!$redis->exists($redisKey)) {
                $redis->set($redisKey, floatToInt($merchant->residue_amount));
            }
            $redis->watch($redisKey);
            $redis->multi();
            if ($recharge_amount < 0) {
                $redis->decrBy($redisKey, floatToInt(str_replace('-', '', $recharge_amount)));
            } else {
                $redis->incrBy($redisKey, floatToInt($recharge_amount));
            }
            $result = $redis->exec();
            if ($result) {
                $merchant->total_amount += $recharge_amount;
                $merchant->residue_amount += $recharge_amount;
                $ret = $merchant->save();
                if (!$ret) {
                    if ($recharge_amount < 0) {
                        $redis->incrBy($redisKey, floatToInt(intval($recharge_amount)));
                    } else {
                        $redis->decrBy($redisKey, floatToInt($recharge_amount));
                    }
                    return $this->error('数据库异常，操作失败');
                }
                $post['app_class_id'] = $merchant->app_class_id;
                $post['operator_id'] = UserServiceFacade::getUser()->id;
                $post['back_sale_id'] = $merchant->admin_ids;
                $post['front_sale_id'] = $merchant->front_sale_id;
                $post['title'] = '后台手动充值';
                $this->model->save($post);
                return $this->success('操作成功');
            }
            return $this->error('数据库异常，操作失败');

        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }
    //删除
    public function del()
    {
        $ids = array_filter($this->request->param('ids'));
        if (!$ids) {
            return $this->error('参数ids不能为空');
        }
        try{
            if ($this->model->destroy($ids)) {
                return $this->success('数据删除成功');
            } else {
                return $this->error('数据删除失败');
            }
        }catch (\Exception $e){
            return $this->exceptionError($e);
        }
    }

    //回收站
    public function recycle()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $limit = $this->request->param('limit', 10);
        $data  = $this->model->onlyTrashed()
            ->with(['merchant','operator'])
            ->order($order)->where($where)->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }


    //更改备注
    public function setRemark()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['remark'] = $fieldVal;
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


    //更改补量条数
    public function setSupplementNums()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['supplement_nums'] = $fieldVal;
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