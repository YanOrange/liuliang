<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use app\validate\admin\channel\Channel as ChannelValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Config;
use app\model\admin\Thread;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;

/**
 * 后台用户控制器
 */
class UserList extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\UserList();
    }
    //查看
    public function index()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        //$where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->buildSearch()->withCount(['applyNums'])->with(['channelpro','app','class','flow','appUserStart'])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }

        foreach($data['data'] as &$item){
            $item['phone'] = $loginUserInfo['is_show_phone'] !== 1 ? (!empty($item['phone']) ? substr_replace($item['phone'], '****', 3, 4) : '') : $item['phone'];
            
            // 修复：判断字段是否存在，不存在则赋值为空
            $item['fd_money'] = GatherUserInfoModel::getFormatGatherInfo($item['fd_money'] ?? '', 'fd_money')['name'] ?? '';
            $item['fd_overdue'] = GatherUserInfoModel::getFormatGatherInfo($item['fd_overdue'] ?? '', 'fd_overdue')['name'] ?? '';
            $item['fd_amount'] = GatherUserInfoModel::getFormatGatherInfo($item['fd_amount'] ?? '', 'fd_amount')['name'] ?? '';
            $item['jyd_demand'] = GatherUserInfoModel::getFormatGatherInfo($item['jyd_demand'] ?? '', 'jyd_demand')['name'] ?? '';
            $item['jyd_overdue'] = GatherUserInfoModel::getFormatGatherInfo($item['jyd_overdue'] ?? '', 'jyd_overdue')['name'] ?? '';
            $item['jyd_PayAbility'] = GatherUserInfoModel::getFormatGatherInfo($item['jyd_PayAbility'] ?? '', 'jyd_PayAbility')['name'] ?? '';
            $item['jyd_amount'] = GatherUserInfoModel::getFormatGatherInfo($item['jyd_amount'] ?? '', 'jyd_amount')['name'] ?? '';
        }

        return $this->success('数据获取成功', $data);
    }
    public function info()
    {
        echo 1111111;
    }
    // 构建查询条件
    private function buildSearch($whereCon = [])
    {
        $filter = $this->request->param('search_param');
        $filter = !empty($filter) ? $filter : [];
        $whereCon = !empty($whereCon) ? $whereCon : [];
        extract($filter);
        extract($whereCon);
        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;
        $threadModel = $this->model;

        if (isset($id) && is_numeric($id)) {
            $threadModel = $threadModel->where($tableName . '.id', '=', $id);
        }
        if (isset($is_test) && is_numeric($is_test)) {
            $threadModel = $threadModel->where($tableName . '.is_test', '=', $is_test);
        }
        if (isset($channel_id) && is_numeric($channel_id)) {
            $threadModel = $threadModel->where($tableName . '.channel_id', '=', $channel_id);
        }
        if (isset($app_id) && is_numeric($app_id)) {
            $threadModel = $threadModel->where($tableName . '.app_id', '=', $app_id);
        }
        if (isset($app_class_id) && is_numeric($app_class_id)) {
            $threadModel = $threadModel->where($tableName . '.app_class_id', '=', $app_class_id);
        }
        if (isset($nickname) && !empty($nickname)) {
            $threadModel = $threadModel->where($tableName . '.nickname', 'like', $nickname);
        }
        if (isset($phone) && !empty($phone)) {
            $threadModel = $threadModel->where($tableName . '.phone', '=', $phone);
        }
        if (isset($is_apply) && $is_apply != '' && ($is_apply == 0 || $is_apply == 1)) {
            if($is_apply == 1){
                $where = "`thread`.`uid` = `user_list`.`id`";
                $threadModel = $threadModel->withJoin(['thread'], 'inner');
                $threadModel = $threadModel->where($where);
            }
            if($is_apply == 0) {
                $where = "`thread`.`uid` is null";
                $threadModel = $threadModel->withJoin(['thread'], 'left');
                $threadModel = $threadModel->where($where);
            }
        }
        if (isset($source) && !empty($source)) {
            $threadModel = $threadModel->where($tableName . '.source', '=', $source);
        }
        if (isset($status) && $status != '' && ($status == 0 || $status == 1)) {
            $threadModel = $threadModel->where($tableName . '.status', '=', $status);
        }

        if (isset($lately_start_app_time) && !empty($lately_start_app_time)) {
            list($lately_startTime, $lately_endTime) = explode(' - ', $lately_start_app_time);
            $threadModel = $threadModel->where($tableName . '.lately_start_app_time', 'not between', strtotime($lately_startTime) . ',' . strtotime($lately_endTime));
        }
        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $threadModel = $threadModel->where($tableName . '.create_time', 'between', strtotime($startTime) . ',' . strtotime($endTime));
        }

        return $threadModel;
    }

    //查看详情
    public function detail()
    {
        $id   = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        return $this->success('获取成功', $info);
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
    public function setStatus()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['status'] = $fieldVal;
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

    //设置账号状态
    public function setBlock()
    {
        $id = $this->request->post('id');
        $update['status'] = 0;
        try {
            $updateRes = $this->model->where('id', '=', $id)->update($update);
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

    //回收站
    public function recycle()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $limit = $this->request->param('limit', 10);
        $data  = $this->model->onlyTrashed()
            ->order($order)->where($where)->with(['channelpro','app','class','flow'])->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }
}