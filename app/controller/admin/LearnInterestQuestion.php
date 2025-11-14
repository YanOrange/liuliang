<?php

namespace app\controller\admin;

use app\validate\admin\learninterestquestion\LearnInterestQuestion as LearnInterestQuestionValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;

/**
 * 后台应用控制器
 */
class LearnInterestQuestion extends Backend
{
    protected $model;//当前模型对象

    protected $chrNum = [
        1 => 'A',2 => 'B',3 => 'C',4 => 'D',5 => 'E',6 => 'F',7 => 'G',8 => 'H',9 => 'I',
        10 => 'J', 11 => 'K',12 => 'L',13 => 'M',14 => 'N',15 => 'O',
    ];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\LearnInterestQuestion();
    }

    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->where($where)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        foreach($data['data'] as &$item){
            $item['question_answer'] = '';
            $questionAnswer = json_decode($item['question_answer_json'],true);
            foreach($questionAnswer as $item1) {
                foreach ($item1['question_answer_options'] as $item2) {
                    $item['question_answer'] .= $item2['num'] . '.' . $item2['question_answer'].',';
                }
            }
        }

        return $this->success('数据获取成功', $data);
    }

    public function add()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $questionTitle = $this->request->post('question_title');
        $questionAnswer = $this->request->post('question_answer');
        $questionAnswerIds = $this->request->post('question_answer_id');
        $resultAnswer = $this->request->post('result_answer');
        $resultTitle = $this->request->post('result_title');
        $resultContent = $this->request->post('result_content');
        $validate = new LearnInterestQuestionValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        $post['question_answer_json'] = [];
        $post['result_answer_json'] = [];
        $num = count($questionTitle);
        $num_key = count($questionAnswer)/$num;
        foreach($questionTitle as $key => $item){
            $data = [];
            $questionAnswerOrg = $questionAnswer;
            $questionAnswerIdsOrg = $questionAnswerIds;
                if($key == 0){
                    $questionAnswer1 = array_splice($questionAnswerOrg,0,$num_key);
                    $questionAnswerId1 = array_splice($questionAnswerIdsOrg,0,$num_key);
                }else{
                    $questionAnswer1 = array_splice($questionAnswerOrg,$num_key*$key ,$num_key);
                    $questionAnswerId1 = array_splice($questionAnswerIdsOrg,$num_key*$key ,$num_key);
                }
            foreach($questionAnswer1 as $key1 => $item1){
                $type = 2;
                if(is_numeric($questionAnswerId1[$key1])) $type = 1;
                $data[] = ['id' => $key1 + 1,'num' => $this->chrNum[$key1+1],'question_answer' => $item1,'question_answer_id' => $questionAnswerId1[$key1],'type' => $type];
            }
            $post['question_answer_json'][] = ['id' => $key + 1,'question_title' => $item,'question_answer_options' => $data];
        }
        foreach($resultAnswer as $key => $item){
            $result_title_content = [];
            $result_title_arr = $this->request->post('result_title_'.$this->chrNum[$key+1]);
            $result_content_arr = $this->request->post('result_content_'.$this->chrNum[$key+1]);
            if(!empty($result_title_arr)){
                foreach($result_title_arr as $key1 => $item1){
                    $result_title_content[] = ['result_title' => $item1,'result_content' => $result_content_arr[$key1]];
                }
            }
            array_unshift($result_title_content, ['result_title' => $resultTitle[$key],'result_content' => $resultContent[$key]]);
            //$post['result_answer_json'][] = ['id' => $key + 1,'num' => $this->chrNum[$key+1], 'result_answer' => $item,'result_title' => $resultTitle[$key],'result_content' => $resultContent[$key]];
            $post['result_answer_json'][] = ['id' => $key + 1,'num' => $this->chrNum[$key+1], 'result_answer' => $item,'result_answer_options' => $result_title_content];
        }
        $post['question_answer_json'] = json_encode($post['question_answer_json'],JSON_UNESCAPED_UNICODE);
        $post['result_answer_json'] = json_encode($post['result_answer_json'],JSON_UNESCAPED_UNICODE);
        unset($post['question_num']);
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
        $info['question_answer_json'] = json_decode($info['question_answer_json'],true);
        $info['result_answer_json'] = json_decode($info['result_answer_json'],true);
        foreach($info['result_answer_json'] as $key => $item){
            $info['result_answer_json'][$key]['result_title'] = $info['result_answer_json'][$key]['result_answer_options'][0]['result_title'] ?? '';
            $info['result_answer_json'][$key]['result_content'] = $info['result_answer_json'][$key]['result_answer_options'][0]['result_content'] ?? '';
            unset($info['result_answer_json'][$key]['result_answer_options'][0]);
            $info['result_answer_json'][$key]['result_answer_options'] = array_values($info['result_answer_json'][$key]['result_answer_options']);
        }
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $questionTitle = $this->request->post('question_title');
        $questionAnswer = $this->request->post('question_answer');
        $questionAnswerIds = $this->request->post('question_answer_id');
        $resultAnswer = $this->request->post('result_answer');
        $resultTitle = $this->request->post('result_title');
        $resultContent = $this->request->post('result_content');
        $validate = new LearnInterestQuestionValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        $post['question_answer_json'] = [];
        $post['result_answer_json'] = [];
        $num = count($questionTitle);
        $num_key = count($questionAnswer)/$num;
        foreach($questionTitle as $key => $item){
            $data = [];
            $questionAnswerOrg = $questionAnswer;
            $questionAnswerIdsOrg = $questionAnswerIds;
            if($key == 0){
                $questionAnswer1 = array_splice($questionAnswerOrg,0,$num_key);
                $questionAnswerId1 = array_splice($questionAnswerIdsOrg,0,$num_key);
            }else{
                $questionAnswer1 = array_splice($questionAnswerOrg,$num_key*$key ,$num_key);
                $questionAnswerId1 = array_splice($questionAnswerIdsOrg,$num_key*$key ,$num_key);
            }
            foreach($questionAnswer1 as $key1 => $item1){
                $type = 2;
                if(is_numeric($questionAnswerId1[$key1])) $type = 1;
                $data[] = ['id' => $key1 + 1,'num' => $this->chrNum[$key1+1],'question_answer' => $item1,'question_answer_id' => $questionAnswerId1[$key1],'type' => $type];
            }
            $post['question_answer_json'][] = ['id' => $key + 1,'question_title' => $item,'question_answer_options' => $data];
        }
        foreach($resultAnswer as $key => $item){
            $result_title_content = [];
            $result_title_arr = $this->request->post('result_title_'.$this->chrNum[$key+1]);
            $result_content_arr = $this->request->post('result_content_'.$this->chrNum[$key+1]);
            if(!empty($result_title_arr)){
                foreach($result_title_arr as $key1 => $item1){
                    $result_title_content[] = ['result_title' => $item1,'result_content' => $result_content_arr[$key1]];
                }
            }
            array_unshift($result_title_content, ['result_title' => $resultTitle[$key],'result_content' => $resultContent[$key]]);
            //$post['result_answer_json'][] = ['id' => $key + 1,'num' => $this->chrNum[$key+1], 'result_answer' => $item,'result_title' => $resultTitle[$key],'result_content' => $resultContent[$key]];
            $post['result_answer_json'][] = ['id' => $key + 1,'num' => $this->chrNum[$key+1], 'result_answer' => $item,'result_answer_options' => $result_title_content];
        }
        $post['question_answer_json'] = json_encode($post['question_answer_json'],JSON_UNESCAPED_UNICODE);
        $post['result_answer_json'] = json_encode($post['result_answer_json'],JSON_UNESCAPED_UNICODE);
        Db::startTrans();
        try {
            $app = $this->model->findOrEmpty($post['id']);
            if (!$app) throw new \Exception('id参数错误');
            $updateRes  = $app->update($post);
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

    //设置微信授权状态
    public function setIsStatus()
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

    //回收站
    public function recycle()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $whereCon = " 1 = 1";
        $isManyOrganization = $this->request->param('is_many_organization', 1);
        if ($isManyOrganization) {
            $whereCon.= " AND is_many_organization = {$isManyOrganization}";
        }
        $limit = $this->request->param('limit', 10);
        $data  = $this->model->onlyTrashed()
            ->with(['class'])
            ->order($order)->where($where)->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }
}