<?php

namespace app\controller\admin;

use app\service\admin\AuthServiceFacade;
use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use laytp\library\Str;
use think\facade\Config;
use think\facade\Db;
use app\validate\admin\merchant\MerchantConfigure as MerchantConfigureValidate;

/**
 * 商户规则
 */
class MerchantConfigure extends Backend
{
    protected $model;//当前模型对象
    protected $userCategory;

    protected function _initialize()
    {
        $this->model = new \app\model\admin\MerchantConfigure();
        $this->userCategory = Config::load("extra/user","extra");
    }

    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->where($where)->with(['merchant'])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    public function ageRange()
    {
        return $this->success('数据获取成功', $this->userCategory['age_range_list']);
    }

    public function identify()
    {
        return $this->success('数据获取成功', $this->userCategory['identity_list']);
    }

    public function education()
    {
        return $this->success('数据获取成功', $this->userCategory['education_list']);
    }

    //添加
    public function add()
    {
        $post = $this->request->post();
        $validate = new MerchantConfigureValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $saveRes = $this->model->save($post);
            if (!$saveRes) throw new \Exception('数据库异常，操作失败');
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
        $id   = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post = $this->request->post();
        $validate = new MerchantConfigureValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $merchant = $this->model->findOrEmpty($post['id']);
            if (!$merchant) throw new \Exception('id参数错误');
            $updateRes  = $merchant->update($post);
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

    //设置账号状态
    public function setIsHasComputer()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_has_computer'] = $fieldVal;
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