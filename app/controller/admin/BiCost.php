<?php

namespace app\controller\admin;

use app\service\admin\AuthServiceFacade;
use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use app\validate\admin\bicost\BiCost as BiCosts;

/**
 * 小米华为消耗
 *
 * Class BiCost
 * @package app\controller\admin
 * @date 2022-11-01
 */
class BiCost extends Backend
{
    protected $model;

    public function _initialize()
    {
        $this->model = new \app\model\admin\BiCost();
    }

    //查看
    public function index()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $where = $this->buildSearchParams();
        $data = $this->model->where($where)->order('plantime desc');

        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        $data['hasAllow'] = AuthServiceFacade::hasAuth($loginId, 'admin.bi_cost/upload');
        return $this->success('数据获取成功', $data);
    }

    //添加
    public function add()
    {
        $postOld = CommonFun::filterPostData($this->request->post());
        if (!$postOld['plantime']) {
            return $this->error('请选择投放日期');
        }
        $timer = strtotime($postOld['plantime']);
        if ($timer > strtotime(date('Y-m-d'))) {
            return $this->error('日期不能大于今天');
        }

        $post = [];
        foreach ($postOld as $key => $val) {
            $post[$key] = trim($val);
        }
        $post['plantime'] = $timer;

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post['admin_user_id'] = $loginUserInfo['id'];
        $post['admin_user_username'] = $loginUserInfo['username'];
        $post['plandate'] = $postOld['plantime'];
        $post['plantime_channel_id'] = $postOld['plantime'] . '_' . $post['channel_id'];

        $classify = Db::name('channel')
            ->alias('c')
            ->field('c.id, c.channel_name, c.app_id, a.app_class_id, a.app_name, s.app_class_name')
            ->leftJoin('app a', 'a.id = c.app_id')
            ->leftJoin('app_class s', 's.id = a.app_class_id')
            ->where('c.id', $post['channel_id'])
            ->find();
        if (!$classify) {
            return $this->error('请检查渠道信息');
        }
        $post['app_class_id'] = $classify['app_class_id'];
        $post['app_class_name'] = $classify['app_class_name'];
        $post['channel_name'] = $classify['channel_name'];
        $post['channel_id'] = $classify['id'];
        $post['app_id'] = $classify['app_id'];
        $post['app_name'] = $classify['app_name'];

        $validate = new BiCosts();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());

        $today = Db::name('bi_xiaomihuawei_cost')
            ->where('channel_id', $post['channel_id'])
            ->where('store', $post['store'])
            ->where('delete_time', 0)
            ->where('plantime', $timer)
            ->count();

        if ($today) return $this->error('该渠道当日已填写请前往修改');

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
        $id = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        if (!$info) {
            return $this->error('数据未找到');
        }

        $channel = Db::name('channel')
            ->field('id, channel_name')
            ->where([
                ['delete_time', '=', 0],
                ['store', 'in', $info['store']],
            ])->select()->toArray();
        $info['channel'] = $channel;
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $postOld = CommonFun::filterPostData($this->request->post());
        if (!$postOld['plantime']) {
            return $this->error('请选择投放日期');
        }
        $timer = strtotime($postOld['plantime']);
        if ($timer > strtotime(date('Y-m-d'))) {
            return $this->error('日期不能大于今天');
        }

        $post = [];
        foreach ($postOld as $key => $val) {
            $post[$key] = trim($val);
        }
        $post['plantime'] = $timer;

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $post['admin_modify_uid'] = $loginUserInfo['id'];
        $post['admin_modify_username'] = $loginUserInfo['username'];
        $post['plandate'] = $postOld['plantime'];
        $post['plantime_channel_id'] = $postOld['plantime'] . '_' . $post['channel_id'];

        $classify = Db::name('channel')
            ->alias('c')
            ->field('c.id, c.channel_name, c.app_id, a.app_name, a.app_class_id, s.app_class_name')
            ->leftJoin('app a', 'a.id = c.app_id')
            ->leftJoin('app_class s', 's.id = a.app_class_id')
            ->where('c.id', $post['channel_id'])
            ->find();
        if (!$classify) {
            return $this->error('请检查渠道信息');
        }
        $post['app_class_id'] = $classify['app_class_id'];
        $post['app_class_name'] = $classify['app_class_name'];
        $post['channel_name'] = $classify['channel_name'];
        $post['channel_id'] = $classify['id'];
        $post['app_id'] = $classify['app_id'];
        $post['app_name'] = $classify['app_name'];

        $today = Db::name('bi_xiaomihuawei_cost')
            ->where('channel_id', $post['channel_id'])
            ->where('store', $post['store'])
            ->where('delete_time', 0)
            ->where('plantime', $timer)
            ->find();
        if ($today['id'] && $today['id'] != $post['id']) return $this->error('该渠道今日已填写请前往修改');

        $validate = new BiCosts();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $info = $this->model->findOrEmpty($post['id']);
            if (!$info) throw new \Exception('id参数错误');

            $updateRes = $info->update($post);
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
        try {
            if ($this->model->destroy($ids)) {
                return $this->success('数据删除成功');
            } else {
                return $this->error('数据删除失败');
            }
        } catch (\Exception $e) {
            return $this->exceptionError($e);
        }
    }

    // 渠道
    public function channel()
    {
        $store = $this->request->param('store');
        $store = ($store) ? [trim($store)] : [];

        $channel = Db::name('channel')
            ->field('id, channel_name')
            ->where([
                ['delete_time', '=', 0],
                ['store', 'in', $store],
            ])->select()->toArray();

        return $this->success('获取成功', $channel);
    }

    // 获取类目
    public function classify()
    {
        $id = $this->request->param('id');
        if (!$id) {
            return $this->error('请选择渠道');
        }

        $classify = Db::name('channel')
            ->alias('c')
            ->field('c.id, c.channel_name, c.app_id, a.app_class_id, s.app_class_name')
            ->leftJoin('app a', 'a.id = c.app_id')
            ->leftJoin('app_class s', 's.id = a.app_class_id')
            ->where('c.id', $id)
            ->find();
        if (!$classify) {
            return $this->error('未找到');
        }

        return $this->success('获取成功', $classify);
    }

    // 用于搜索渠道
    public function chan()
    {
        $channel = Db::name('bi_xiaomihuawei_cost')
            ->distinct('channel_name')
            ->field('channel_id, channel_name')
            ->where('delete_time', 0)
            ->select()->toArray();

        return $this->success('获取成功', $channel);
    }

    // 用于搜索类目
    public function classes()
    {
        $classify = Db::name('bi_xiaomihuawei_cost')
            ->distinct('app_class_name')
            ->field('app_class_id, app_class_name')
            ->where('delete_time', 0)
            ->select()->toArray();

        return $this->success('获取成功', $classify);
    }

    // 导入数据
    public function upload()
    {
        $file = $this->request->file('file');
        $ext = $file->getOriginalExtension();

        if (!in_array($ext, ['xlsx', 'xls'])) {
            return $this->error('请上传xls或者xlsx格式');
        }

        $savename = \think\facade\Filesystem::disk('public')->putFile('bicost', $file);
        $path = public_path() . 'static/storage/' . $savename;
        $mode = ($ext == 'xlsx') ? 'Excel2007' : 'Excel5';

        // $path = public_path() . 'static/storage/bicost/20221102/f06861c0744a4cd0393a880b423d024a.xlsx';
        // $mode = 'Excel2007';
        $reader = \PHPExcel_IOFactory::createReader($mode);

        $excel = $reader->load($path, $encode = 'utf-8');
        $sheet = $excel->getSheet(0)->toArray();

        if (!isset($sheet[0])) {
            return $this->error('请检查文件的数据是否为空');
        }

        $column = $sheet[0];
        if (count($column) != 5) {
            return $this->error('请检查表头是否与数据字段一致');
        }

        // 类目
        $class = Db::name('app_class')->field('id, app_class_name')->select()->toArray();
        $clsArr = [];
        foreach ($class as $c) {
            $clsArr[$c['app_class_name']] = $c['id'];
        }

        // 渠道
        $channel = Db::name('channel')->field('c.id, c.app_id, c.channel_name, a.app_name,a.app_class_id,b.app_class_name')
            ->alias('c')
            ->leftJoin('app a', 'a.id = c.app_id')
            ->leftJoin('app_class b', 'b.id = a.app_class_id')
            ->select()->toArray();

        $appArr = [];
        $chanArr = [];
        foreach ($channel as $c) {
            $chanArr[$c['channel_name']] = $c['id'];
            $appArr[$c['id']] = ['id' => $c['app_id'], 'name' => $c['app_name'],'app_class_id' => $c['app_class_id'],'app_class_name' => $c['app_class_name']];
        }

        // 登记人员
        $users = Db::name('admin_user')->field('id, nickname')->select()->toArray();
        $userArr = [];
        foreach ($users as $u) {
            $userArr[$u['nickname']] = $u['id'];
        }

        $arr = [];
        $msg = '';
        $store = ['xiaomi', 'huawei','meizu','lenovo'];
        $timer = strtotime(date('Y-m-d 23:59:59'), time());

        $loginUserInfo = UserServiceFacade::getUserInfo();

        $ex = "<br>";
        array_shift($sheet);
        Db::startTrans();
        foreach ($sheet as $k => $v) {
            $v[0] = trim($v[0]);
            if (!$v[0]) {
                $msg .= '第 [ ' . $k . ' ]行' . '投放日期 [ ' . $v[0] . ' ] 为空' . $ex;
                break;
            }
            $plantime = strtotime($v[0]);

            // if ($plantime > $timer) {
            //     $msg .= '第 [ ' . $k . ' ]行' . '投放日期 [ ' . $v[0] . ' ] 日期不能大于今天' . $ex;
            //     break;
            // }

            $cid = isset($chanArr[$v[1]]) ? $chanArr[$v[1]] : 0;
            if (!$cid) {
                $msg .= '第 [ ' . $k . ' ]行' . '渠道 [ ' . $v[1] . ' ] 未匹配到对应的渠道' . $ex;
                break;
            }

            $cost = (double)$v[2];
            if ($cost > 99999) {
                $msg .= '第 [ ' . $k . ' ]行' . '消耗 [ ' . $v[2] . ' ] 值异常' . $ex;
                break;
            }

            $expose = (int)$v[3];
            if ($expose > 99999999) {
                $msg .= '第 [ ' . $k . ' ]行' . '曝光量 [ ' . $v[3] . ' ] 值异常' . $ex;
                break;
            }

            $download = (int)$v[4];
            if ($download > 99999999) {
                $msg .= '第 [ ' . $k . ' ]行' . '下载量 [ ' . $v[4] . ' ] 值异常' . $ex;
                break;
            }

            $today = Db::name('bi_xiaomihuawei_cost')
                ->where('channel_id', $cid)
                ->where('delete_time', 0)
                ->where('plantime', $plantime)
                ->count();
            if ($today) {
                $msg .= '第 [ ' . $k . ' ]行' . '该渠道 [ ' . $v[1] . ' ] 当日已有投放记录' . $ex;
                break;
            }
            $app = $appArr[$cid];

            $store = '';
            if(strpos($v[1],'xiaomi') || $v[1] == 'xiaomi') $store = 'xiaomi';
            if(strpos($v[1],'huawei') || $v[1] == 'huawei') $store = 'huawei';
            if(strpos($v[1],'meizu') || $v[1] == 'meizu') $store = 'meizu';
            if(strpos($v[1],'lenovo') || $v[1] == 'lenovo') $store = 'lenovo';
            $arr[] = [
                'plandate' => $v[0],
                'plantime' => $plantime,
                'store' => $store,
                'app_id' => (isset($app['id']) && $app['id']) ? $app['id'] : 0,
                'app_name' => (isset($app['name']) && $app['name']) ? $app['name'] : '',
                'app_class_id' => (isset($app['app_class_id']) && $app['app_class_id']) ? $app['app_class_id'] : 0,
                'app_class_name' => (isset($app['app_class_name']) && $app['app_class_name']) ? $app['app_class_name'] : 0,
                'channel_id' => $cid,
                'channel_name' => $v[1],
                'plantime_channel_id' => $v[0] . '_' . $cid,
                'cost' => $cost,
                'expose' => $expose,
                'download' => $download,
                'admin_user_id' => $loginUserInfo['id'],
                'admin_user_username' => $loginUserInfo['username'],
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
            return $this->error($message);
        }
    }
}