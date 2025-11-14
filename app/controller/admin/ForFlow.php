<?php

namespace app\controller\admin;

use app\validate\admin\for_flow\ForFlow as ForFlowValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Session;
/**
 * 后台投流控制器
 */
class ForFlow extends Backend
{
    protected $model;//当前模型对象
    protected $noNeedAuth = ['getChannelList'];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\ForFlow();
    }
    public function getChannelList()
    {
        $flowId = $this->request->get('id',0);
        if (isset($flowId) && !empty($flowId)) {
            $info = $this->model->findOrEmpty($flowId)->toArray();
            if (isset($info['h5_link_json']) && !empty($info['h5_link_json'])) {
                $h5LinkArr = json_decode($info['h5_link_json'], true);
                $channelList = array_column($h5LinkArr, 'channel');
            }
        }
        return $channelList ?? [];
    }
    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->withCount(['applyNums', 'registerNums'])->where($where)->where('type', 1)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();

            foreach ($data['data'] as $k => &$v) {
                $v['pv_nums_count'] = \app\model\api\h5\FlowPvUv::getH5TotalPv($v['id'], 'page');
                $v['pv_button_count'] = \app\model\api\h5\FlowPvUv::getH5TotalPv($v['id'], 'button');
                $v['uv_nums_count'] = \app\model\api\h5\FlowPvUv::getH5TotalUv($v['id'], 'page');
                $v['uv_button_count'] = \app\model\api\h5\FlowPvUv::getH5TotalUv($v['id'], 'button');
            }
        }
        return $this->success('数据获取成功', $data);
    }

    //添加
    public function add()
    {
        $post     = $this->request->post();
        if($post['page_type'] == 2){
            if(empty($post['content'])){
                return $this->error('文章类型内容不为空');
            }
            $post['content'] = explode('@@@@@@@@',$post['content']);
            $post['content'] = json_encode($post['content'], JSON_UNESCAPED_UNICODE);
        }
        $post['other_info_set_json'] = json_encode($post['other_info_set_json'], JSON_UNESCAPED_UNICODE);
        $post['type'] = 1;
        //判断是否有站内商户
//        $merchant_ids = $post['merchant_ids'];
//        $inSiteMerchantIds = \app\model\admin\Merchant::where('is_source',1)->column('id');
//        //取得交集
//        $intersection = array_intersect(explode(',',$merchant_ids), $inSiteMerchantIds);
//        if(empty($intersection)){
//            throw new \Exception('请选择一个以上的站内商户');
//        }
        $validate = new ForFlowValidate();
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
        //$info['gather_info_set_json'] = json_decode($info['gather_info_set_json'],true);
        $info['other_info_set_json'] = json_decode($info['other_info_set_json'],true);
        if(!empty($info['content']) && $info['page_type'] == 2) {
            $info['content'] = implode('@@@@@@@@', json_decode($info['content'], true));
        }
        $info['h5_link_json'] = json_decode($info['h5_link_json'],true);
        $info['link'] = env('flow.link');
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post     = $this->request->post();
        if($post['page_type'] == 2){
            if(empty($post['content'])){
                return $this->error('文章类型内容不为空');
            }
            $post['content'] = explode('@@@@@@@@',$post['content']);
            $post['content'] = json_encode($post['content'], JSON_UNESCAPED_UNICODE);
        }
        $post['other_info_set_json'] = json_encode($post['other_info_set_json'], JSON_UNESCAPED_UNICODE);
        $validate = new ForFlowValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        //判断是否有站内商户
//        $merchant_ids = $post['merchant_ids'];
//        $inSiteMerchantIds = \app\model\admin\Merchant::where('is_source',1)->column('id');
//        //取得交集
//        $intersection = array_intersect(explode(',',$merchant_ids), $inSiteMerchantIds);
//        if(empty($intersection)){
//            throw new \Exception('请选择一个以上的站内商户');
//        }
        Db::startTrans();
        try {
            $forFlow = $this->model->findOrEmpty($post['id']);
            if (!$forFlow) throw new \Exception('id参数错误');
            $updateRes  = $forFlow->update($post);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }

    function array_unset_tt($arr=[],$key=null){

    }

    //生成链接
    public function editLink()
    {
        $h5LinkUrl = env('flow.link');
        $post  = $this->request->post();
        $data = $post['h5_link_json'];
        $data = arrayDuplicate($data,'channel_id');
        sort($data);
        if(!empty($data)){
            foreach ($data as $key => $val){
                if($val['channel_id'] == 0 || empty($val['channel_id'])){
                    unset($data[$key]);
                }else{
                    if(strpos($val['link'],'&channel=') === false){
                        $data[$key]['link']= $val['link'].'&channel='.$val['channel_id'];
                    }
                }

            }
        }
        /*$channelArr = $post['h5_link_json']['channel'];
        $data = [];
        for($i = 0; $i < count($channelArr); $i++) {
            if (!empty($channelArr[$i])) {
                $data[] = [
                    'channel' => $channelArr[$i],
                    'link' => $h5LinkUrl . '?flow_id=' . $post['id'] . '&channel=' . $channelArr[$i],
                ];
            }
        }*/
        if (empty($data)) return $this->error('参数错误');
        Db::startTrans();
        try {
            $forFlow = $this->model->findOrEmpty($post['id']);
            if (!$forFlow) throw new \Exception('id参数错误');
            $post['h5_link_json'] = json_encode($data, JSON_UNESCAPED_UNICODE);
            $updateRes  = $forFlow->update($post);
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