<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use app\validate\admin\biboost\BiBoost as BiBoosts;

/**
 * BI商户补量记录登记
 *
 * Class BiCost
 * @package app\controller\admin
 * @date 2022-11-04
 */
class BiBoost extends Backend
{
    protected $model;

    public function _initialize()
    {
        $this->model = new \app\model\admin\BiBoost();
    }

    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $data = $this->model->where($where)->order('threadtime desc');

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
    public function add()
    {
        $postOld = CommonFun::filterPostData($this->request->post());
        if (!$postOld['threadtime']) {
            return $this->error('请选择线索日期');
        }
        $timer = strtotime($postOld['threadtime']);
        if ($timer > strtotime(date('Y-m-d'))) {
            return $this->error('日期不能大于今天');
        }

        $post = [];
        foreach ($postOld as $key => $val) {
            $post[$key] = trim($val);
        }
        $post['threadtime'] = $timer;

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post['admin_user_id'] = $loginUserInfo['id'];
        $post['admin_user_username'] = $loginUserInfo['username'];
        $post['threaddate'] = $postOld['threadtime'];

        $salesArr = [];
        $sales = self::sales(true);
        foreach ($sales as $s) {
            $salesArr[$s['id']] = $s['nickname'];
        }
        $post['pre_sales_name'] = isset($salesArr[$post['pre_sales_id']]) ? $salesArr[$post['pre_sales_id']] : '';
        $post['after_sales_name'] = isset($salesArr[$post['after_sales_id']]) ? $salesArr[$post['after_sales_id']] : '';

        $merchant = Db::name('merchant')
            ->field('id, merchant_name')
            ->where('delete_time', 0)
            ->where('id', $post['merchant_id'])
            ->find();
        if (!$merchant) {
            return $this->error('请选择商户');
        }
        $post['merchant_name'] = $merchant['merchant_name'];
        $validate = new BiBoosts();
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
        $id = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        if (!$info) {
            return $this->error('数据未找到');
        }
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $postOld = CommonFun::filterPostData($this->request->post());
        if (!$postOld['threadtime']) {
            return $this->error('请选择投放日期');
        }
        $timer = strtotime($postOld['threadtime']);
        if ($timer > strtotime(date('Y-m-d'))) {
            return $this->error('日期不能大于今天');
        }

        $post = [];
        foreach ($postOld as $key => $val) {
            $post[$key] = trim($val);
        }
        $post['threadtime'] = $timer;

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post['admin_modify_uid'] = $loginUserInfo['id'];
        $post['admin_modify_username'] = $loginUserInfo['username'];
        $post['threaddate'] = $postOld['threadtime'];

        $salesArr = [];
        $sales = self::sales(true);
        foreach ($sales as $s) {
            $salesArr[$s['id']] = $s['nickname'];
        }
        $post['pre_sales_name'] = isset($salesArr[$post['pre_sales_id']]) ? $salesArr[$post['pre_sales_id']] : '';
        $post['after_sales_name'] = isset($salesArr[$post['after_sales_id']]) ? $salesArr[$post['after_sales_id']] : '';
        $merchant = Db::name('merchant')
            ->field('id, merchant_name')
            ->where('delete_time', 0)
            ->where('id', $post['merchant_id'])
            ->find();
        if (!$merchant) {
            return $this->error('请选择商户');
        }
        $post['merchant_name'] = $merchant['merchant_name'];

        $validate = new BiBoosts();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $info = $this->model->findOrEmpty($post['id']);
            if (!$info) throw new \Exception('id参数错误');

            $updateRes = $info->update($post);
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


    // 用于筛选销售
    public function sales($isArr = false)
    {
        $data = Db::name('admin_user')
            ->field('id, nickname')
            ->where('delete_time', 0)
            ->where('is_super_manager', 2)
            ->select()->toArray();

        return ($isArr) ? $data : $this->success('获取成功', $data);
    }

    // 前端销售用于搜索
    public function presale()
    {
        $data = Db::name('bi_merchant_boost')
            ->distinct('pre_sales_name')
            ->field('pre_sales_id, pre_sales_name')
            ->where('delete_time', 0)
            ->select()->toArray();

        return $this->success('获取成功', $data);
    }

    // 后端销售用于搜索
    public function aftersale()
    {
        $data = Db::name('bi_merchant_boost')
            ->distinct('after_sales_name')
            ->field('after_sales_id, after_sales_name')
            ->where('delete_time', 0)
            ->select()->toArray();

        return $this->success('获取成功', $data);
    }

    // 商户用于搜索
    public function mer()
    {
        $data = Db::name('bi_merchant_boost')
            ->distinct('merchant_name')
            ->field('merchant_id, merchant_name')
            ->where('delete_time', 0)
            ->select()->toArray();

        return $this->success('获取成功', $data);
    }

    // 商户
    public function merchant()
    {
        $data = Db::name('merchant')
            ->field('id, merchant_name')
            ->where('delete_time', 0)
            ->select()->toArray();

        return $this->success('获取成功', $data);
    }
}