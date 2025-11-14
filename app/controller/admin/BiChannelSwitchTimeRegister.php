<?php

namespace app\controller\admin;

use app\service\admin\AuthServiceFacade;
use app\service\admin\UserServiceFacade;
use app\validate\admin\bi_channel_switch_time_register\BiChannelSwitchTimeRegister as BiChannelSwitchTimeRegisterValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use app\model\admin\Channel;
use app\model\admin\AppClass;
use app\model\admin\User;
use app\model\admin\role\User as RoleUser;

/**
 * 后台应用分类控制器
 */
class BiChannelSwitchTimeRegister extends Backend
{
    protected $model;//当前模型对象
    protected $noNeedAuth = ['channelList','searchChannelList','searchClassList','getClassName','adminUserList'];
    protected function _initialize()
    {
        $this->model = new \app\model\admin\BiChannelSwitchTimeRegister();
    }
    //查看
    public function index()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->with(['app','appClass','addAdmin','upAdmin'])->where($where)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        $data['hasAllow'] = AuthServiceFacade::hasAuth($loginId, 'admin.bi_channel_switch_time_register/upload');
        return $this->success('数据获取成功', $data);
    }

    //渠道列表
    public function channelList()
    {
        $store = $this->request->param('store','');
        $where = [];
        if($store){
            $where[] = ['store','=',$store];
        }
        $data = Channel::where($where)->field('id,channel_name');
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 100);
            $data = $data->paginate($limit)->toArray();
        }

        array_multisort(array_column($data['data'], 'channel_name'), SORT_ASC, SORT_STRING, $data['data']);
        return $this->success('数据获取成功', $data['data']);
    }

    //搜索渠道列表
    public function searchChannelList()
    {
        $data = $this->model->field('channel_id,channel_name')->group('channel_id')->select()->toArray();
//        $allData = $this->request->param('all_data');
//        if ($allData) {
//            $data = $data->select();
//        } else {
//            $limit = $this->request->param('limit', 10);
//            $data = $data->paginate($limit)->toArray();
//        }
        foreach($data as &$item){
            $item['id'] = $item['channel_id'];
        }
        return $this->success('数据获取成功', $data);
    }

    //搜索类目列表
    public function searchClassList()
    {
        $data = AppClass::field('id,app_class_name')->select()->toArray();
        return $this->success('数据获取成功', $data);
    }

    public function getClassName()
    {
        $channelId = $this->request->param('channel_id');
        $channelInfo = Channel::with(['app' => function($query){
            $query->field('id');
            $query->with(['class'=> function($query1){
                $query1->field('id,app_class_name');
            }]);
        }])->where('id',$channelId)->find();
        return $this->success('获取数据成功',$channelInfo['app']['class']);
    }

    public function adminUserList()
    {
        $adminIds = RoleUser::where('admin_role_id',5)->column('admin_user_id');
        $data = User::whereIn('id',$adminIds)->field('id,nickname')->select()->toArray();
        return $this->success('数据获取成功', $data);
    }

    //添加
    public function add()
    {
        $post = CommonFun::filterPostData($this->request->post());
        $validate = new BiChannelSwitchTimeRegisterValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        $timer = strtotime($post['release_day']);
        if ($timer > strtotime(date('Y-m-d 23:59:59'))) {
            return $this->error('开户时间不能大于今天');
        }
        if (strtotime($post['unix_close_time']) > strtotime(date('Y-m-d 23:59:59'))) {
            return $this->error('关户时间不能大于今天');
        }
        if (strtotime($post['unix_close_time']) <= $timer) {
            return $this->error('关户时间不能小于于开户时间');
        }

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post['add_admin_id'] = $loginUserInfo['id'];
        $post['unix_release_time'] = strtotime($post['release_day']);
        $post['unix_close_time'] = strtotime($post['unix_close_time']);
        $post['unix_close_time1'] = $post['unix_close_time'] + 15 * 60;

        $classify = Channel::with(['app' => function($query){
            $query->field('id');
            $query->with(['class'=> function($query1){
                $query1->field('id,app_class_name');
            }]);
        }])->where('id',$post['channel_id'])->find();
        if (!$classify) {
            return $this->error('请检查渠道信息');
        }
        $post['app_class_id'] = $classify['app']['class']['id'];
        $post['channel_name'] = $classify['channel_name'];
        $post['channel_id'] = $classify['id'];
        $post['app_id'] = $classify['app']['id'];

        $channelRes = $this->model->where('release_day',date('Y-m-d',strtotime($post['release_day'])))
            ->where('channel_id',$post['channel_id'])->find();
        if(!empty($channelRes)){
            return $this->error('请勿重复添加数据');
        }

        Db::startTrans();
        try {
            $saveRes = $this->model->save($post);
            if (!$saveRes) throw new \Exception('数据库异常，操作失败1');
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
        $id = $this->request->param('id');
        $info = $this->model->with(['app','appClass'])->findOrEmpty($id)->toArray();
        if (!$info) {
            return $this->error('数据未找到');
        }
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post = CommonFun::filterPostData($this->request->post());
        $validate = new BiChannelSwitchTimeRegisterValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        $timer = strtotime($post['release_day']);
        if ($timer > strtotime(date('Y-m-d 23:59:59'))) {
            return $this->error('开户时间不能大于今天');
        }
        if (strtotime($post['unix_close_time']) > strtotime(date('Y-m-d 23:59:59'))) {
            return $this->error('关户时间不能大于今天');
        }
        if (strtotime($post['unix_close_time']) <= $timer) {
            return $this->error('关户时间不能小于开户时间');
        }

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post['up_admin_id'] = $loginUserInfo['id'];
        $post['unix_release_time'] = strtotime($post['release_day']);
        $post['unix_close_time'] = strtotime($post['unix_close_time']);
        $post['unix_close_time1'] = $post['unix_close_time'] + 15 * 60;

        $classify = Channel::with(['app' => function($query){
            $query->field('id');
            $query->with(['class'=> function($query1){
                $query1->field('id,app_class_name');
            }]);
        }])->where('id',$post['channel_id'])->find();
        if (!$classify) {
            return $this->error('请检查渠道信息');
        }
        $post['app_class_id'] = $classify['app']['class']['id'];
        $post['channel_name'] = $classify['channel_name'];
        $post['channel_id'] = $classify['id'];
        $post['app_id'] = $classify['app']['id'];

        $channelRes = $this->model->where('release_day',date('Y-m-d',strtotime($post['release_day'])))
            ->where('channel_id',$post['channel_id'])
            ->where('id','<>',$post['id'])
            ->find();
        if(!empty($channelRes)){
            return $this->error('请勿重复添加数据');
        }

        Db::startTrans();
        try {
            $appClass = $this->model->findOrEmpty($post['id']);
            if (!$appClass) throw new \Exception('id参数错误');
            $updateRes  = $appClass->update($post);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
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
            if ($this->model->where('id','in',$ids)->delete()) {
                return $this->success('数据删除成功');
            } else {
                return $this->error('数据删除失败');
            }
        }catch (\Exception $e){
            return $this->exceptionError($e);
        }
    }

    // 导入数据
    public function upload()
    {
        $file = $this->request->file('file');
        $ext = $file->getOriginalExtension();

        if (!in_array($ext, ['xlsx', 'xls'])) {
            return $this->error('请上传xls或者xlsx格式');
        }

        $savename = \think\facade\Filesystem::disk('public')->putFile('bichannelswitch', $file);
        $path = public_path() . 'static/storage/' . $savename;
        $mode = ($ext == 'xlsx') ? 'Excel2007' : 'Excel5';

        $reader = \PHPExcel_IOFactory::createReader($mode);

        $excel = $reader->load($path, $encode = 'utf-8');
        $sheet = $excel->getSheet(0)->toArray();
        if (!isset($sheet[0])) {
            return $this->error('请检查文件的数据是否为空');
        }

//        $column = $sheet[0];
//        if (count($column) != 3) {
//            return $this->error('请检查表头是否与数据字段一致');
//        }

        // 类目
        $class = Db::name('app_class')->field('id, app_class_name')->select()->toArray();
        $clsArr = [];
        foreach ($class as $c) {
            $clsArr[$c['app_class_name']] = $c['id'];
        }

        // 渠道
        $channel = Db::name('channel')->field('c.id, c.app_id, c.channel_name, a.app_name')
            ->alias('c')
            ->leftJoin('app a', 'a.id = c.app_id')
            ->select()->toArray();

        $appArr = [];
        $chanArr = [];
        foreach ($channel as $c) {
            $chanArr[$c['channel_name']] = $c['id'];
            $appArr[$c['id']] = ['id' => $c['app_id'], 'name' => $c['app_name']];
        }

        // 登记人员
        $users = Db::name('admin_user')->field('id, nickname')->select()->toArray();
        $userArr = [];
        foreach ($users as $u) {
            $userArr[$u['nickname']] = $u['id'];
        }

        $arr = [];
        $msg = '';
        $store = ['oppo','vivo','xiaomi','huawei'];
        $timer = strtotime(date('Y-m-d 23:59:59'), time());

        $loginUserInfo = UserServiceFacade::getUserInfo();

        $ex = "<br>";
        array_shift($sheet);
        Db::startTrans();
        foreach ($sheet as $k => $v) {
            $k = $k+2;
            $v[0] = trim($v[0]);
            $cid = isset($chanArr[$v[0]]) ? $chanArr[$v[0]] : 0;
            if (!$cid) {
                $msg .= '第 [ ' . $k . ' ]行' . '渠道 [ ' . $v[0] . ' ] 未匹配到对应的渠道' . $ex;
                break;
            }

            if (!$v[1]) {
                $msg .= '第 [ ' . $k . ' ]行' . '开户日期 [ ' . $v[1] . ' ] 为空' . $ex;
                break;
            }
            $releaseDay = strtotime($v[1]);

            if (!$releaseDay) {
                $msg .= '第 [ ' . $k . ' ]行' . '开户日期 [ ' . $v[1] . ' ] 日期不正确' . $ex;
                break;
            }

//            if ($releaseDay > $timer) {
//                $msg .= '第 [ ' . $k . ' ]行' . '开户日期 [ ' . $v[1] . ' ] 日期不能大于今天' . $ex;
//                break;
//            }

            if (!$v[2]) {
                $msg .= '第 [ ' . $k . ' ]行' . '关户日期 [ ' . $v[2] . ' ] 为空' . $ex;
                break;
            }
            $unixCloseTime = strtotime($v[2]);

            if (!$unixCloseTime) {
                $msg .= '第 [ ' . $k . ' ]行' . '关户日期 [ ' . $v[2] . ' ] 日期不正确' . $ex;
                break;
            }

//            if ($unixCloseTime > $timer) {
//                $msg .= '第 [ ' . $k . ' ]行' . '关户日期 [ ' . $v[2] . ' ] 日期不能大于今天' . $ex;
//                break;
//            }

            if ($unixCloseTime <= $releaseDay) {
                $msg .= '第 [ ' . $k . ' ]行' . '关户日期 [ ' . $v[2] . ' ] 日期不能小于开户日期' . $ex;
                break;
            }

            $channelInfo = Channel::with(['app' => function($query){
                $query->field('id');
                $query->with(['class'=> function($query1){
                    $query1->field('id,app_class_name');
                }]);
            }])->where('channel_name',$v[0])->find();

            $aid = isset($channelInfo['app']['class']['id']) ? $channelInfo['app']['class']['id'] : 0;
            if (!$aid) {
                $msg .= '第 [ ' . $k . ' ]行' . '类目 [ ' . $v[0] . ' ] 未匹配到对应的类目' . $ex;
                break;
            }

            $channelRes = $this->model->where('release_day',date('Y-m-d',$releaseDay))
                ->where('channel_id',$cid)
                ->find();
            if ($channelRes) {
                $msg .= '第 [ ' . $k . ' ]行' . '该渠道 [ ' . $v[0] . ' ] 当日已有开户记录' . $ex;
                break;
            }
            $app = $appArr[$cid];

            $arr[] = [
                'store' => $channelInfo['store'],
                'channel_id' => $cid,
                'app_id' => (isset($app['id']) && $app['id']) ? $app['id'] : 0,
                'app_class_id' => $aid,
                'channel_name' => $v[0],
                'release_day' => date('Y-m-d',strtotime($v[1])),
                'unix_release_time' => strtotime($v[1]),
                'unix_close_time' => strtotime($v[2]),
                'unix_close_time1' => strtotime($v[2]) + 15 * 60,
                'add_admin_id' => $loginUserInfo['id'],
                'create_time' => time(),
            ];
        }

        if (!$arr || !empty($msg)) {
            Db::rollBack();
            return $this->error($msg);
        }
        $message = strlen($msg) ? $msg : '已导入';

        $bool = $this->model->saveAll($arr);
        if($bool){
            Db::commit();
            return $this->success($message);
        }else{
            Db::rollBack();
            return $this->error();
        }
    }
}