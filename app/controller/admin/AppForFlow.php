<?php

namespace app\controller\admin;

use app\validate\admin\app_for_flow\AppForFlow as AppForFlowValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Session;
/**
 * 后台app信息流控制器
 */
class AppForFlow extends Backend
{
    protected $model;//当前模型对象

    protected function _initialize()
    {
        $this->model = new \app\model\admin\ForFlow();
    }
    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->where($where)->where('type', 2)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }
    public function dataAnalysisList()
    {
        $limit = $this->request->param('limit', 10);
        $order = $this->buildOrder();
        $data = $this->buildSearch()->with(['user','merchant','app'])->order($order)->paginate($limit)->toArray();
        return $this->success('数据获取成功', $data);
    }
    public function buildSearch()
    {
        $filter = $this->request->param('search_param') ?? [];
        $flowId = $this->request->get('id', 0);
        $threadModel = new \app\model\admin\Thread();
        extract($filter);
        $name = $threadModel->getName();
        $tableName = env('database.prefix') . $name;
        if (isset($flowId) && !empty($flowId)) {
            $threadModel = $threadModel->where('flow_id', '=', $flowId);
        }
        if (isset($merchant_id) && !empty($merchant_id)) {
            $threadModel = $threadModel->where('merchant_id', '=', $merchant_id);
        }
        if (isset($is_discern_qrcode) && is_numeric($is_discern_qrcode)) {
            $threadModel = $threadModel->where('is_discern_qrcode', '=', $is_discern_qrcode);
        }
        if (isset($app_id) && !empty($app_id)) {
            $threadModel = $threadModel->where('app_id', '=', $app_id);
        }
        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $threadModel = $threadModel->where('create_time', 'between', strtotime($startTime). ','. strtotime($endTime));
        }

        if ((isset($age_range_id) && is_numeric($age_range_id))
            || (isset($identity_id) && is_numeric($identity_id))
            || (isset($is_has_computer_id) && is_numeric($is_has_computer_id))
            || (isset($education_id) && is_numeric($education_id))
            || (isset($nickname) && !empty($nickname))
            || (isset($phone) && !empty($phone))
            || (isset($education_id) && is_numeric($education_id))
            || (isset($study_goal_id) && is_numeric($study_goal_id))) {
                    $threadModel = $threadModel->whereExists(function ($query) use ($tableName, $filter) {
                        $userListTableName = (new \app\model\admin\UserList())->getName();
                        $query = $query->table(env('database.prefix') .$userListTableName)->where(env('database.prefix') . $userListTableName . '.id=' .   $tableName . '.uid');
                        extract($filter);
                        if (isset($age_range_id) && is_numeric($age_range_id)) {
                            $query = $query->where('age_range_id', '=', $age_range_id);
                        }
                        if (isset($identity_id) && is_numeric($identity_id)) {
                            $query = $query->where('identity_id', '=', $identity_id);
                        }
                        if (isset($education_id) && is_numeric($education_id)) {
                            $query = $query->where('education_id', '=', $education_id);
                        }
                        if (isset($study_goal_id) && is_numeric($study_goal_id)) {
                            $query = $query->where('study_goal_id', '=', $study_goal_id);
                        }
                        if (isset($is_has_computer_id) && is_numeric($is_has_computer_id)) {
                            $query = $query->where('is_has_computer_id', '=', $is_has_computer_id);
                        }
                        if (isset($nickname) && !empty($nickname)) {
                            $query = $query->where('nickname', '=', $nickname);
                        }
                        if (isset($phone) && !empty($phone)) {
                            $query = $query->where('phone', '=', $phone);
                        }
                        return $query;
                    });

        }
        return $threadModel;
    }
    //添加
    public function add()
    {
        $post     = $this->request->post();

        $officialArr = $post['header_official_json']['content'];
        $durationArr = $post['header_official_json']['times'];
        $sortArr = $post['header_official_json']['sort'];
        $headerOfficialArr = [];
        for ($i = 0; $i < count($officialArr); $i++) {
            if (!empty($officialArr[$i])) {
                $headerOfficialArr[] = [
                    'content' => $officialArr[$i],
                    'times' => $durationArr[$i] > 0 ? (int)$durationArr[$i] : 0,
                    'sort' => $sortArr[$i] > 0 ? (int)$sortArr[$i] : 0,
                ];
            }
        }
        $post['header_official_json'] = json_encode($headerOfficialArr, JSON_UNESCAPED_UNICODE );
        $post['gather_info_set_json'] = json_encode($post['gather_info_set_json'], JSON_UNESCAPED_UNICODE );
        $post['type'] = 2;
        $validate = new AppForFlowValidate();
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
        $info['header_official_json'] = !empty($info['header_official_json']) && $info['header_official_json'] != '[]' ? json_decode($info['header_official_json'],true) : null;
        $info['gather_info_set_json'] = json_decode($info['gather_info_set_json'],true);
        $customProblemArr  = json_decode($info['custom_problem_json'],true);
        $customProblemList = [];
        if (!empty($customProblemArr)) {
            foreach ($customProblemArr as  $val) {
                $val['type'] = (int)$val['type'];
                $customProblemList[] = $val;
            }
        }
        $info['custom_problem_json'] = $customProblemList;
        return $this->success('获取成功', $info);
    }
    public function editCustomProblem()
    {
        $post     = $this->request->post();
        $customProblemArr = $post['custom_problem_json'];
        //var_dump($post['custom_problem_json']);die;
        $customProblemList = [];
        if (!empty($customProblemArr)) {
            $count  = count($customProblemArr);
            foreach ($customProblemArr as &$val) {
                $val['sort'] = (int)$val['sort'];
                $val['type'] = (int)$val['type'];
                if ($val['type'] == 1) {
                    $val['value'] = [];
                }
                if ($count > 1) {
                    if (empty($val['content'])) {
                        return $this->error('选项问题内容必填');
                    }
                    if ($val['type'] == 2) {
                        foreach ($val['name'] as $itme) {
                            if (empty($itme['text'])) {
                                return $this->error('问题选项容必填');
                            }
                        }
                    }
                    $customProblemList[] = $val;
                } else {
                    if (!empty($val['content'])) {
                        if ($val['type'] == 2) {
                            foreach ($val['name'] as $itme) {
                                if (empty($itme['text'])) {
                                    return $this->error('问题选项容必填');
                                }
                            }
                        }
                        $customProblemList[] = $val;
                    } else {
                        $customProblemList = [];
                    }
                }

            }
        }
        $post['custom_problem_json'] = json_encode($customProblemList, JSON_UNESCAPED_UNICODE);
        Db::startTrans();
        try {
            $appForFlow = $this->model->findOrEmpty($post['id']);
            if (!$appForFlow) throw new \Exception('id参数错误');
            $updateRes  = $appForFlow->update($post);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            //echo $e->getMessage();die;
            Db::rollback();
            return $this->exceptionError($e);
        }
    }
    //编辑
    public function edit()
    {
        $post     = $this->request->post();
        $officialArr = $post['header_official_json']['content'];
        $durationArr = $post['header_official_json']['times'];
        $sortArr = $post['header_official_json']['sort'];
        $headerOfficialArr = [];
        for ($i = 0; $i < count($officialArr); $i++) {
            if (!empty($officialArr[$i])) {
                $headerOfficialArr[] = [
                    'content' => $officialArr[$i],
                    'times' => $durationArr[$i] > 0 ? (int)$durationArr[$i] : 1,
                    'sort' => $sortArr[$i] > 0 ? (int)$sortArr[$i] : "0",
                ];
            }
        }
        $post['header_official_json'] = json_encode($headerOfficialArr, JSON_UNESCAPED_UNICODE );
        $post['gather_info_set_json'] = json_encode($post['gather_info_set_json'], JSON_UNESCAPED_UNICODE);
        $validate = new AppForFlowValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $appForFlow = $this->model->findOrEmpty($post['id']);
            if (!$appForFlow) throw new \Exception('id参数错误');
            $updateRes  = $appForFlow->update($post);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }
    //设置投流状态
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
}