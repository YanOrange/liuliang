<?php

namespace app\controller\admin;

use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;

/**
 * 后台投流数据分析控制器
 */
class FlowPvUv extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\FlowPvUv();
    }
    //查看
    public function index()
    {
        $order = $this->buildOrder();
        $data = $this->buildSearch()->with(['user','thread' => function($query) {
            $query->with('merchant');
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
    //构建查询条件
    private function buildSearch()
    {
        $filter = $this->request->param('search_param') ?? [];
        $flowId = $this->request->get('id', 0);
        extract($filter);
        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;
        $flowPvUvModel = $this->model;
        if (isset($flowId) && !empty($flowId)) {
            $flowPvUvModel = $flowPvUvModel->where('for_flow_id', '=', $flowId);
        }
        if (isset($channel) && !empty($channel)) {
            $flowPvUvModel = $flowPvUvModel->where('channel', 'like', "%{$channel}%");
        }
        if (isset($type) && !empty($type)) {
            $flowPvUvModel = $flowPvUvModel->where('type', '=', $type);
        }
        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $flowPvUvModel = $flowPvUvModel->where('create_time', 'between', strtotime($startTime). ','. strtotime($endTime));
        }
        if (isset($is_apply) && is_numeric($is_apply)) {
            if ($is_apply) {
                $flowPvUvModel = $flowPvUvModel->where('thread_id', '>', 0);
            } else {
                $flowPvUvModel = $flowPvUvModel->where('thread_id', '=', 0);
            }
        }
        if ((isset($is_discern_qrcode) && is_numeric($is_discern_qrcode)) || (isset($merchant_id) && !empty($merchant_id))) {
            if (isset($is_discern_qrcode) && is_numeric($is_discern_qrcode)) {
                if ($is_discern_qrcode == 1) {
                    $flowPvUvModel = $flowPvUvModel->whereExists(function ($query) use ($tableName, $is_discern_qrcode) {
                        $threadTableName = (new \app\model\admin\Thread())->getName();
                        $query = $query->table(env('database.prefix') .$threadTableName)->where(env('database.prefix') . $threadTableName . '.id=' .   $tableName . '.thread_id');
                        $query = $query->where('is_discern_qrcode', '=', 1);
                        return $query;
                    });
                }
                if ($is_discern_qrcode == 0) {
                    $flowPvUvModel = $flowPvUvModel->where('thread_id', '=', 0);
                    $flowPvUvModel = $flowPvUvModel->whereExists(function ($query) use ($tableName, $is_discern_qrcode) {
                        $threadTableName = (new \app\model\admin\Thread())->getName();
                        $query = $query->table(env('database.prefix') .$threadTableName)->where(env('database.prefix') . $threadTableName . '.id=' .   $tableName . '.thread_id');
                        $query = $query->where('is_discern_qrcode', '=', 0);
                        return $query;
                    }, 'or');
                }
            }
            if (isset($merchant_id) && !empty($merchant_id)) {
                $flowPvUvModel = $flowPvUvModel->whereExists(function ($query) use ($tableName, $merchant_id) {
                    $threadTableName = (new \app\model\admin\Thread())->getName();
                    $query = $query->table(env('database.prefix') .$threadTableName)->where(env('database.prefix') . $threadTableName . '.id=' .   $tableName . '.thread_id');
                    $query = $query->where('merchant_id', '=', $merchant_id);
                    return $query;
                });
            }
            return $flowPvUvModel;
        }
        if ((isset($age_range_id) && !empty($age_range_id)) || (isset($identity_id) && !empty($identity_id)) || (isset($education_id) && !empty($education_id))) {
            $flowPvUvModel = $flowPvUvModel->whereExists(function ($query) use ($tableName, $filter) {
                $userListTableName = (new \app\model\admin\UserList())->getName();
                $query = $query->table(env('database.prefix') .$userListTableName)->where(env('database.prefix') . $userListTableName . '.id=' .   $tableName . '.uid');
                extract($filter);
                if (isset($age_range_id) && !empty($age_range_id)) {
                    $query = $query->where('age_range_id', '=', $age_range_id);
                }
                if (isset($identity_id) && !empty($identity_id)) {
                    $query = $query->where('identity_id', '=', $identity_id);
                }
                if (isset($education_id) && !empty($education_id)) {
                    $query = $query->where('education_id', '=', $education_id);
                }
                return $query;
            });
        }
        return $flowPvUvModel;
    }
}