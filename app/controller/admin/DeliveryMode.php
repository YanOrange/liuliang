<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use laytp\library\Tree;
use app\model\admin\DeliveryMode as DeliveryModeModel;
/**
 * 投放控制器
 */
class DeliveryMode extends Backend
{
    public $noNeedAuth = ['getMenuTree','getTree'];

    public $menuList;
    public $model;
    public $orderRule = [ 'id' => 'asc'];

    public function _initialize()
    {
        $this->model = new \app\model\admin\DeliveryMode();
    }
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $sourceData = $this->model->where($where)->order($order)->select()->toArray();
        $isTree = $this->request->param('is_tree');
        if($isTree){
            $menuTreeObj = Tree::instance();
            $menuTreeObj->init($sourceData);
            $data = $menuTreeObj->getRootTrees();
        }else{
            $data = $sourceData;
        }
        return $this->success('数据获取成功', $data);
    }

  /*  //获取当前登录者的权限列表，返回树形数据，角色管理赋予权限时用到
    public function getTree(){
        $user = UserServiceFacade::getUser();
        if($user->is_super_manager === 1){
            $sourceData  = $this->model->order($this->orderRule)->select()->toArray();
        }else{
            $roleIds = \app\model\admin\role\User::where('admin_user_id','=', $user->id)
                ->column('admin_role_id');
            $menuIds = \app\model\admin\menu\Role::where('admin_role_id','in',$roleIds)
                ->column('admin_menu_id');
            $where[] = ['id', 'in', $menuIds];
            $sourceData  = $this->model->order($this->orderRule)->where($where)->select()->toArray();
        }
        $menuTreeObj = Tree::instance();
        $menuTreeObj->init($sourceData);
        //由列表数据转化成树形结构数据
        $data = $menuTreeObj->getRootTrees();
        return $this->success('获取成功', $data);
    }

    //获取当前登录者的菜单列表，返回树形数据，仅返回is_menu=1的列表，后台菜单列表展示使用
    public function getMenuTree(){
        $user = UserServiceFacade::getUser();
        $where[] = ['is_show', '=', 1];
        $where[] = ['is_menu', '=', 1];
        if($user->is_super_manager === 1){
            $sourceData  = $this->model->order($this->orderRule)->where($where)->select()->toArray();
        }else{
            $roleIds = \app\model\admin\role\User::where('admin_user_id','=', $user->id)
                ->column('admin_role_id');
            $menuIds = \app\model\admin\menu\Role::where('admin_role_id','in',$roleIds)
                ->column('admin_menu_id');
            $where[] = ['id','in',$menuIds];
            $sourceData  = $this->model->order($this->orderRule)->where($where)->select()->toArray();
        }
        $menuTreeObj = Tree::instance();
        $menuTreeObj->init($sourceData);
        //由列表数据转化成树形结构数据
        $data = $menuTreeObj->getRootTrees();
        return $this->success('获取成功', $data);
    }*/

    //添加
    public function add()
    {
        $post = CommonFun::filterPostData($this->request->post());
        if ($this->model->create($post)) {
            return $this->success('添加成功', $post);
        } else {
            return $this->error('操作失败');
        }
    }

    //编辑
    public function edit()
    {
        $id   = $this->request->param('id');
        $info = $this->model->find($id);
        $post = CommonFun::filterPostData($this->request->post());

        if ($id == $post['pid']) {
            return $this->error('不能将上级改成自己');
        }
        foreach ($post as $k => $v) {
            $info->$k = $v;
        }
        $update_res = $info->save();
        if ($update_res) {
            return $this->success('操作成功');
        } else if ($update_res === 0) {
            return $this->success('未做修改');
        } else {
            return $this->error('操作失败');
        }
    }

    //删除
    public function del()
    {
        $ids = $this->request->post('ids');
        if (!$ids) {
            return $this->error('参数ids不能为空');
        }

        $sourceData = $this->model->select()->toArray();
        $treeLib = Tree::instance();
        $treeLib->init($sourceData);
        $childIds = $treeLib->getChildIds($ids);

        if ($this->model->destroy($childIds)) {
            return $this->success('数据删除成功', $childIds);
        } else {
            return $this->error('数据删除失败');
        }
    }
}
