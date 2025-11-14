<?php

namespace app\controller\admin;

use laytp\controller\Backend;
use think\facade\Config;

class PointData extends Backend
{
    protected $model;

    public function _initialize()
    {
        $this->model = new \app\model\admin\PointData();
        $this->userAgreementModel = new \app\model\admin\EUserAgreement();
        $this->loginPageModel = new \app\model\admin\ELoginPage();
        $this->homePageModel = new \app\model\admin\EHomePage();
        $this->homePageCardModel = new \app\model\admin\EHomePageCard();
        $this->homePageRotationChartModel = new \app\model\admin\EHomePageRotationChart();
        $this->homePageArticleClickModel = new \app\model\admin\EHomePageArticleClick();
        $this->homePageMainButtonClickModel = new \app\model\admin\EHomePageMainButtonClick();
        $this->landPagemodel = new \app\model\admin\ELandPage();
        $this->pageGatherInfoAfterModel = new \app\model\admin\EPageGatherInfoAfter();
        $this->pageGatherInfoAfterSelectModel = new \app\model\admin\EPageGatherInfoAfterSelect();
        $this->comonModel = new \app\model\admin\ECommonSelect();
    }

    //查看和搜索列表
    public function index()
    {
        //$where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $allData = $this->request->param('all_data');
        $pageId = $this->request->param('page_id', 0);
        $eventId = $this->request->param('event_id', 0);
        $whereCon = [];
        if($eventId == 1) $this->model = $this->userAgreementModel;
        if($eventId == 2) $this->model = $this->loginPageModel;
        if($eventId == 3) $this->model = $this->homePageModel;
        if($eventId == 4) $this->model = $this->homePageCardModel;
        if($eventId == 5) $this->model = $this->homePageRotationChartModel;
        if($eventId == 7) $this->model = $this->homePageArticleClickModel;
        if($eventId == 8) $this->model = $this->homePageMainButtonClickModel;
        if($eventId == 9) $this->model = $this->landPagemodel;
        if($eventId == 10) $this->model = $this->pageGatherInfoAfterModel;
        if($eventId == 11) $this->model = $this->pageGatherInfoAfterSelectModel;
        if(in_array($eventId,[14,15,17])){
            $this->model = $this->comonModel->where('event_id',$eventId);
        }
        if(in_array($eventId,[18,19])){
            $this->model = $this->landPagemodel->where('event_id',$eventId);
        }
        if($eventId == 7){
            $data = $this->model->with(['app','channel','user','lastPage','article'])->where($whereCon)->order($order);
        }elseif($eventId == 9){
            $data = $this->model->with(['app','channel','user','lastPage','event','merchant'])->where($whereCon)->order($order);
        }else{
            $data = $this->model->with(['app','channel','user'])->where($whereCon)->order($order);
        }
        if($allData){
            $data = $data->select()->toArray();
        }else{
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
            if(!empty($data['data'])){
                foreach ($data['data'] as $ke=>&$v){
                    if(!empty($v['click_phone_textbox_time'])){
                        $data['data'][$ke]['click_phone_textbox_time'] = date('Y-m-d H:i:s',$data['data'][$ke]['click_phone_textbox_time']);
                    }
                    if(!empty($v['input_phone_code_time'])){
                        $data['data'][$ke]['input_phone_code_time'] = date('Y-m-d H:i:s',$data['data'][$ke]['input_phone_code_time']);
                    }
                    if(!empty($v['click_login_button_time'])){
                        $data['data'][$ke]['click_login_button_time'] = date('Y-m-d H:i:s',$data['data'][$ke]['click_login_button_time']);
                    }
                    if(!empty($v['close_time'])){
                        $data['data'][$ke]['close_time'] = date('Y-m-d H:i:s',$data['data'][$ke]['close_time']);
                    }
                    if(!empty($v['sigin_success_time'])){
                        $data['data'][$ke]['sigin_success_time'] = date('Y-m-d H:i:s',$data['data'][$ke]['sigin_success_time']);
                    }
                }
            }
        }
        return $this->success('数据获取成功', $data);
    }

    //查看和搜索列表
    public function h5Index()
    {
        //$where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $allData = $this->request->param('all_data');
        $pageId = $this->request->param('page_id', 0);
        $eventId = $this->request->param('event_id', 0);
        $whereCon = [];
        if ($pageId && $eventId) {
            if($eventId == 34){
                $whereCon[] = ['page_id','in',[6,7,8,9,10]];
            }else{
                $whereCon[] = ['page_id', '=', $pageId];
            }
            $whereCon[] = ['event_id', '=', $eventId];
        }
        $data = $this->buildSearchH5(false)->with(['merchant','channel','page','event','forFlow'])->where($whereCon)->order($order);
        if($allData){
            $data = $data->select()->toArray();
        }else{
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    // 构建查询条件
    private function buildSearch($isDelete = false,$whereCon = [])
    {
        $filter = $this->request->param('search_param');
        $filter = !empty($filter) ?  $filter : [];
        extract($filter);
        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;
        if ($isDelete) {
            $pointDataModel = $this->model->onlyTrashed();
        } else {
            $pointDataModel = $this->model;
        }
        if (isset($channel_id) && !empty($channel_id)) {
            $pointDataModel = $pointDataModel->where('channel_id', $channel_id);
        }
        if (isset($app_id) && !empty($app_id)) {
            $pointDataModel = $pointDataModel->where('app_id', $app_id);
        }
        if (isset($page_id) && !empty($page_id)) {
            $pointDataModel = $pointDataModel->where('page_id', $page_id);
        }
        if (isset($agreement_status) && is_numeric($agreement_status)) {
            $pointDataModel = $pointDataModel->where('agreement_status', $agreement_status);
        }
        if (isset($login_status) && is_numeric($login_status)) {
            $pointDataModel = $pointDataModel->where('login_status', '=', $login_status);
        }
        $pointDataModel = $pointDataModel->withJoin(['user'],'inner');
        if (isset($age_range_id) && is_numeric($age_range_id)) {
            $pointDataModel = $pointDataModel->where('user.age_range_id','=',$age_range_id);
        }
        if (isset($identity_id) && is_numeric($identity_id)) {
            $pointDataModel = $pointDataModel->where('user.identity_id','=',$identity_id);
        }
        if (isset($education_id) && is_numeric($education_id)) {
            $pointDataModel = $pointDataModel->where('user.education_id','=',$education_id);
        }
        if (isset($study_goal_id) && is_numeric($study_goal_id)) {
            $pointDataModel = $pointDataModel->where('user.study_goal_id','=',$study_goal_id);
        }
        if (isset($is_jump_wx) && is_numeric($is_jump_wx)) {
            $pointDataModel = $pointDataModel->where('is_jump_wx', $is_jump_wx);
        }

        if (isset($nickname) && !empty($nickname)) {
            $pointDataModel = $pointDataModel->where('user.nickname','=',$nickname);
        }
        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $pointDataModel = $pointDataModel->where('create_time', 'between', strtotime($startTime). ','. strtotime($endTime));
        }

        return $pointDataModel;
    }

    // 构建查询条件
    private function buildSearchH5($isDelete = false,$whereCon = [])
    {
        $filter = $this->request->param('search_param');
        $filter = !empty($filter) ?  $filter : [];
        extract($filter);
        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;
        if ($isDelete) {
            $pointDataModel = $this->model->onlyTrashed();
        } else {
            $pointDataModel = $this->model;
        }
        if (isset($page_id) && !empty($page_id)) {
            $pointDataModel = $pointDataModel->where('page_id', $page_id);
        }
        if (isset($agreement_status) && is_numeric($agreement_status)) {
            $pointDataModel = $pointDataModel->where('agreement_status', $agreement_status);
        }
        if (isset($login_status) && is_numeric($login_status)) {
            $pointDataModel = $pointDataModel->where('login_status', '=', $login_status);
        }
        if (isset($is_jump_wx) && is_numeric($is_jump_wx)) {
            $pointDataModel = $pointDataModel->where('is_jump_wx', $is_jump_wx);
        }
        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $pointDataModel = $pointDataModel->where('create_time', 'between', strtotime($startTime). ','. strtotime($endTime));
        }

        return $pointDataModel;
    }

    //年龄
    public function ageRangeList()
    {
        $userConfig = Config::load('extra/user','extra');
        return $this->success('获取数据成功',$userConfig['age_range_list']);
    }

    //身份
    public function identifyList()
    {
        $userConfig = Config::load('extra/user','extra');
        return $this->success('获取数据成功',$userConfig['identity_list']);
    }

    //学历
    public function educationList()
    {
        $userConfig = Config::load('extra/user','extra');
        return $this->success('获取数据成功',$userConfig['education_list']);
    }
    //学习需求
    public function studyGoalList()
    {
        $userConfig = Config::load('extra/user','extra');
        return $this->success('获取数据成功',$userConfig['study_goal_list']);
    }
}