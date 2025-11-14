<?php

namespace app\controller\admin;

use laytp\controller\Backend;
use laytp\library\CommonFun;

/**
 * lt_admin
 */
class Admin extends Backend
{

    /**
     * admin模型对象
     * @var \app\model\Admin
     */
    protected $model;
	protected $hasSoftDel=0;//是否拥有软删除功能

    protected $noNeedLogin = []; // 无需登录即可请求的方法
    protected $noNeedAuth = ['index', 'info']; // 无需鉴权即可请求的方法

    public function _initialize()
    {
        $this->model = new \app\model\Admin();
    }

    
    //查看和搜索列表
    public function index(){
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $allData = $this->request->param('all_data');
        $data = $this->model->where($where)->order($order);
        if($allData){
            $data = $data->select()->toArray();
        }else{
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    

    
    //查看详情
    public function info()
    {
        $id   = $this->request->param('id');
        $info = $this->model->find($id);
        return $this->success('获取成功', $info);
    }

    

    

    
}
