<?php

namespace app\controller\admin;

use app\validate\admin\distribution\DistributionCommissionRule as DistributionCommissionRuleValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use laytp\library\Str;
use think\facade\Db;

/**
 * 后台应用控制器
 */
class DistributionCommissionRule extends Backend
{
    protected $model;//当前模型对象

    protected $noNeedAuth = ['*'];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\DistributionCommissionRule();
    }
    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->where($where)->with(['channel'])->order($order);
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
        //$post     = CommonFun::filterPostData($this->request->post());
        $post['settlement_period'] = $this->request->post('settlement_period');
        $post['commission_rate'] = $this->request->post('commission_rate');
        $commissionSharingRules = $this->request->post('commission_sharing_rules');
        $commissionSharingRulesAll = [];
        foreach($commissionSharingRules['amount_min'] as $key => $item){
            $commissionSharingRulesAll[] = [
                'amount_min' => $item,
                'amount_max' => $commissionSharingRules['amount_max'][$key],
                'remind' => $commissionSharingRules['remind'][$key]
            ];
        }
        $post['commission_sharing_rules'] = json_encode($commissionSharingRulesAll);
        $validate = new DistributionCommissionRuleValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $saveRes = $this->model->save($post);
            if (!$saveRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
        }
    }

    //查看详情
    public function info()
    {
        //$id   = $this->request->param('id',1);
        $id = 1;
        $info = $this->model->findOrEmpty($id)->toArray();
        if(!empty($info)){
            $info['commission_sharing_rules'] = json_decode($info['commission_sharing_rules'],true);
            $info['rule_num'] = count($info['commission_sharing_rules']);
        }else{
            $info['settlement_period'] = '';
            $info['commission_rate'] = '';
            $info['commission_sharing_rules'] = '';
            $info['rule_num'] = 0;
        }
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $id = $this->request->post('id');
        if(!empty($id) && $id !== 'undefined'){
            $post['id'] = $id;
        }
        $post['settlement_period'] = $this->request->post('settlement_period');
        $post['commission_rate'] = $this->request->post('commission_rate');
        $commissionSharingRules = $this->request->post('commission_sharing_rules');
        $commissionSharingRulesAll = [];
        foreach($commissionSharingRules['amount_min'] as $key => $item){
            $commissionSharingRulesAll[] = [
                'amount_min' => $item,
                'amount_max' => $commissionSharingRules['amount_max'][$key],
                'remind' => $commissionSharingRules['remind'][$key]
            ];
        }
        $post['commission_sharing_rules'] = json_encode($commissionSharingRulesAll);
        $validate = new DistributionCommissionRuleValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $commissionRules = $this->model->findOrEmpty($id);
            if (!empty($commissionRules)){
                if($post['settlement_period'] != $commissionRules['settlement_period']){
                    $post['settlement_period_two'] = $post['settlement_period'];
                    $post['period_weight'] = 1;
                }
                if($post['commission_rate'] != $commissionRules['commission_rate']){
                    $post['commission_rate_two'] = $post['commission_rate'];
                    $post['rate_weight'] = 1;
                }
            }
            unset($post['settlement_period']);
            unset($post['commission_rate']);
            $updateRes  = $this->model->update($post);
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
}