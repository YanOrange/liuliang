<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use app\validate\admin\biboostphone\BiBoostPhone as BiBoostsphone;

/**
 * BI商户补量手机号记录登记
 *
 * Class BiBoostPhone
 * @package app\controller\admin
 * @date 2022-11-09
 */
class BiBoostPhone extends Backend
{
    protected $model;

    public function _initialize()
    {
        $this->model = new \app\model\admin\BiBoostPhone();
    }

    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $data = $this->model->where($where)->order('boosttime desc');

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

        if (!$postOld['boosttime']) {
            return $this->error('请选择补量日期');
        }

        $timer = strtotime($postOld['boosttime']);
        if ($timer > strtotime(date('Y-m-d'))) {
            return $this->error('日期不能大于今天');
        }

        $post = [];
        foreach ($postOld as $key => $val) {
            $post[$key] = trim($val);
        }

        $phoneArr = explode("\n", $post['phone']);
        if (!$phoneArr) {
            return $this->error('请填写手机号');
        }

        $users = Db::name('user_list')->field('phone')->where('phone', 'in', $phoneArr)->select()->toArray();
        $userArr = array_column($users, 'phone');

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $merchant = Db::name('merchant')
            ->field('id, merchant_name')
            ->where('delete_time', 0)
            ->where('id', $post['merchant_id'])
            ->find();
        if (!$merchant) {
            return $this->error('请选择商户');
        }

        $validate = new BiBoostsphone();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());

        $msg = '';
        $arr = [];
        foreach ($phoneArr as $val) {
            if (!preg_match("/^1[3456789]{1}\d{9}$/", $val)) {
                $msg .= $val .',';
                continue;
            }

            $isExist = in_array($val, $userArr) ? 1 : 2;
            $arr[] = [
                'merchant_id' => $post['merchant_id'],
                'merchant_name' => $merchant['merchant_name'],
                'phone' => $val,
                'admin_user_id' => $loginUserInfo['id'],
                'admin_user_username' => $loginUserInfo['username'],

                'status' => $isExist,
                'type' => $post['type'],
                'boostdate' => $postOld['boosttime'],
                'boosttime' => $timer,
                'create_time' => time()
            ];
        }

        if (!$arr) {
            return $this->error('无可用数据');
        }

        $bool = Db::name('bi_merchant_boost_phone')->insertAll($arr);
        if ($bool) {
            $msg = ($msg) ? '未能导入的电话号码' . $msg : '导入成功';
            return $this->success($msg);
        }

        return $this->error('导入失败');
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
        if (!$postOld['boosttime']) {
            return $this->error('请选择补量日期');
        }
        $timer = strtotime($postOld['boosttime']);
        if ($timer > strtotime(date('Y-m-d'))) {
            return $this->error('日期不能大于今天');
        }

        $post = [];
        foreach ($postOld as $key => $val) {
            $post[$key] = trim($val);
        }
        $post['boosttime'] = $timer;

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post['admin_modify_uid'] = $loginUserInfo['id'];
        $post['admin_modify_username'] = $loginUserInfo['username'];
        $post['boostdate'] = $postOld['boosttime'];

        $merchant = Db::name('merchant')
            ->field('id, merchant_name')
            ->where('delete_time', 0)
            ->where('id', $post['merchant_id'])
            ->find();
        if (!$merchant) {
            return $this->error('请选择商户');
        }
        $post['merchant_name'] = $merchant['merchant_name'];

        $validate = new BiBoostsphone();
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

    // 商户用于搜索
    public function mer()
    {
        $data = Db::name('bi_merchant_boost_phone')
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