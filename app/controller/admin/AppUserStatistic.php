<?php

namespace app\controller\admin;

use laytp\controller\Backend;
use app\model\admin\UserList;
use app\model\admin\AppUserStartRecord;

class AppUserStatistic extends Backend
{
    protected $model;

    public function _initialize()
    {
        $this->model = new \app\model\admin\App();
    }

    //查看和搜索列表
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $allData = $this->request->param('all_data');
        $data = $this->model->where($where)->order('id asc');
        if($allData){
            $data = $data->select()->toArray();
        }else{
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        if(!empty($data['data'])){
            foreach($data['data'] as &$val){
                $val['app_register_num'] = UserList::where('app_id',$val['id'])->count();
                $val['app_yesterday_register_num'] = UserList::where('app_id',$val['id'])->whereDay('create_time', 'yesterday')->count();
                $val['app_yesterday_active_num'] = AppUserStartRecord::where('app_id',$val['id'])->whereDay('create_time', 'yesterday')->group('uid')->count();
                $appYesterdayStartNum = AppUserStartRecord::where('app_id',$val['id'])->whereDay('create_time', 'yesterday')->count();
                $val['app_yesterday_percapita_start_num'] = $val['app_yesterday_active_num'] > 0 && $appYesterdayStartNum > 0 ? round($appYesterdayStartNum/$val['app_yesterday_active_num'],0) : 0;
                $appYesterdayUseTimes = AppUserStartRecord::where('app_id',$val['id'])->whereDay('create_time', 'yesterday')->sum('use_time');
                $val['app_yesterday_percapita_use_time'] = $val['app_yesterday_active_num'] > 0 && $appYesterdayUseTimes > 0 ? round($appYesterdayUseTimes/$val['app_yesterday_active_num'],0) : 0;
            }
        }
        return $this->success('数据获取成功', $data);
    }
}