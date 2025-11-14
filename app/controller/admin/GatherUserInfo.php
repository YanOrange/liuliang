<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use app\validate\admin\gatheruserinfo\GatherUserInfo as GatherUserInfoValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use laytp\library\Tree;
use think\facade\Db;

class GatherUserInfo extends Backend
{
    protected $model;//当前模型对象

    protected function _initialize()
    {
        $this->model = new \app\model\admin\GatherUserInfo();
    }

    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->where($where)->order('id asc');
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //获取当前登录者的权限列表，返回树形数据，角色管理赋予权限时用到
    public function getTree(){
        $sourceData  = $this->model->order('id asc')->select()->toArray();
        $sourceGatherData = [];
        foreach($sourceData as $item){
            $gatherUserInfo = json_decode($item['gather_info_json'],true);
            $parentData = [
                'id' => $item['id'],
                'name' => $item['title'],
                'pid' => 0
            ];
            if(!empty($gatherUserInfo)) {
                foreach ($gatherUserInfo as &$v) {
                    $v['id'] = $item['id'].$v['id'].'0';
                    $v['pid'] = $item['id'];
                    unset($v['sort']);
                }
                array_unshift($gatherUserInfo,$parentData);
            }else{
                $gatherUserInfo = $parentData;
            }
            $sourceGatherData[] = $gatherUserInfo;
        }
        $gatherUserInfoData = [];
        foreach($sourceGatherData as $item){
            foreach($item as $val){
                $gatherUserInfoData[] = $val;
            }
        }
        $menuTreeObj = Tree::instance();
        $menuTreeObj->init($gatherUserInfoData);
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
    }

    //添加
    public function add()
    {
        $post     = $this->request->post();
        $validate = new GatherUserInfoValidate();
        $gatherInfoJson = $post['gather_info_json'];
        $gatherInfoSort = $post['sort'];
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $gatherInfoData = [];
            $i = 0;
            foreach($gatherInfoJson as $key => $val)
            {
                if(empty($val)){
                    unset($gatherInfoJson[$key]);
                }else{
                    $gatherInfoData[] = [
                        'id' => $i+1,
                        'name' => $val,
                        'sort' => isset($gatherInfoSort[$key]) ? $gatherInfoSort[$key] : 1
                    ];
                    $i++;
                }
            }
            $post['gather_info_json'] = !empty($gatherInfoData) ? json_encode($gatherInfoData,JSON_UNESCAPED_UNICODE) : '';
            $saveRes = $this->model->save($post);
            $sql = 'select count(*) from information_schema.columns where table_name = '."'user_profile' ". 'and column_name ='."'{$post['field']}'";
            $res = Db::query($sql);
            if($res[0]['count(*)'] == 0){
                $addFieldSql = "ALTER TABLE user_profile ADD COLUMN {$post['field']} int(1) NOT NULL DEFAULT 0 COMMENT '{$post['title']}'";
                Db::execute($addFieldSql);
            }
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
        $id   = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        $info['gather_info'] = !empty($info['gather_info_json']) ? json_decode($info['gather_info_json'],true) : [];
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post     = $this->request->post();
        $gatherInfoJson = $post['gather_info_json'];
        $gatherInfoSort = $post['sort'];
        $validate = new GatherUserInfoValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $gatherInfoData = [];
            $i = 0;
            foreach($gatherInfoJson as $key => $val)
            {
                if(empty($val)){
                    unset($gatherInfoJson[$key]);
                }else{
                    $gatherInfoData[] = [
                        'id' => $i+1,
                        'name' => $val,
                        'sort' => isset($gatherInfoSort[$key]) ? $gatherInfoSort[$key] : 1
                    ];
                    $i++;
                }
            }
            $post['gather_info_json'] = !empty($gatherInfoData) ? json_encode($gatherInfoData,JSON_UNESCAPED_UNICODE) : '';
            $gatherInfo = $this->model->findOrEmpty($post['id']);
            if (!$gatherInfo) throw new \Exception('id参数错误');
            $updateRes  = $gatherInfo->update($post);
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

    public function setChannel()
    {
        $channelInfo = \app\model\admin\Channel::select()->toArray();
        foreach($channelInfo as $item){
            $data = [];
            if(!empty($item['gather_info_ids'])){
                $gatherUserInfo = \app\model\admin\GatherUserInfo::whereIn('id',$item['gather_info_ids'])->select()->toArray();
                foreach($gatherUserInfo as $val){
                    $gatherInfoJson = json_decode($val['gather_info_json'],true);
                    $gatherCids = array_column($gatherInfoJson,'id');
                    $data[] = [
                        'pid' => $val['id'],
                        'cid' => implode(',',$gatherCids)
                    ];
                }
                $dataJson = json_encode($data);
                \app\model\admin\Channel::where('id',$item['id'])->save(['gather_user_info_ids' => $dataJson]);
            }
        }
        return $this->success('OK',[]);
    }

    public function setUserList()
    {
        $userListModel = new \app\model\api\UserList();
        $userList = $userListModel->whereOr('custom_fields','=','')->limit(1000)->select()->toArray();
        foreach($userList as $item){
            $data = [];
            if($item['study_goal_id'] > 0){
                $data[] = '4='.$item['study_goal_id'];
            }
            if($item['is_has_computer_id'] > 0){
                $data[] = '6='.$item['is_has_computer_id'];
            }
            if($item['is_like_games'] > 0){
                $data[] = '7='.$item['is_like_games'];
            }
            if($item['is_has_shop_id'] > 0){
                $data[] = '8='.$item['is_has_shop_id'];
            }
            if($item['is_has_dspdh_id'] > 0){
                $data[] = '9='.$item['is_has_dspdh_id'];
            }
            if($item['zw_mold'] > 0){
                $data[] = '12='.$item['zw_mold'];
            }
            if($item['zw_money'] > 0){
                $data[] = '13='.$item['zw_money'];
            }
            if($item['biancheng_id'] > 0){
                $data[] = '14='.$item['biancheng_id'];
            }
            if($item['need_id'] > 0){
                $data[] = '15='.$item['need_id'];
            }
            if($item['cuishou_id'] > 0){
                $data[] = '17='.$item['cuishou_id'];
            }
            if($item['zhaiwu_leixing'] > 0){
                $data[] = '18='.$item['zhaiwu_leixing'];
            }
            if($item['zhaiwu_monney'] > 0){
                $data[] = '19='.$item['zhaiwu_monney'];
            }
            if($item['yuqi_pingtaiid'] > 0){
                $data[] = '20='.$item['yuqi_pingtaiid'];
            }
            if($item['cuishou_zhuangtai'] > 0){
                $data[] = '21='.$item['cuishou_zhuangtai'];
            }
            $dataJson = implode(',',$data);
            $data1[] = [
                'id' => $item['id'],
                'custom_fields' => $dataJson
            ];
        }
        $userListModel->saveAll($data1);
        return $this->success('OK',[]);
    }

}