<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;

/**
 * 后台渠道控制器
 */
class ChannelLog extends Backend
{
    protected $model;//当前模型对象

    protected $noNeedLogin = ['getType']; // 无需登录即可请求的方法
    protected $noNeedAuth = ['getType'];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\ChannelLog();
    }

    /**
     * @return string[]
     */
    public function getType()
    {
        $type = [
            ['id'=>1,'name'=>'修改APP名称'],
            ['id'=>2,'name'=>'修改分类'],
            ['id'=>3,'name'=>'一句话'],
            ['id'=>4,'name'=>'标签'],
            ['id'=>5,'name'=>'LOGO文案'],
            ['id'=>6,'name'=>'LOGO图'],
            ['id'=>7,'name'=>'截图'],
            ['id'=>8,'name'=>'氛围图文案'],
            ['id'=>9,'name'=>'氛围图'],
            ['id'=>10,'name'=>'备注'],
            ['id'=>11,'name'=>'副标题']
        ];
        return $this->success('更新类型', $type);
    }

    //查看
    public function channelIndex()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = \app\model\admin\Channel::where($where)->where('source',1)->with(['app' => function ($query) {
            $query->field('id,app_name');
            $query->with(['class']);
        }, 'adminUser' => function ($query) {
            $query->field('id,nickname');
        }])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select()->each(function ($item) {
                $count = $this->model->where('channel_id', $item['id'])->count();
                $item['updatrNum'] = $count;
                return $item;
            });
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->each(function ($item) {
                $count = $this->model->where('channel_id', $item['id'])->count();
                $item['updatrNum'] = $count;
                return $item;
            })->toArray();
        }
        return $this->success('数据获取成功', $data);
    }


    //查看
    public function index()
    {
        $where = $this->buildSearch();
        //$order = $this->buildOrder();
        $data = $this->model->where($where)->with(['channel','createUser' => function ($query) {
            $query->field('id,nickname');
        }, 'updateUser' => function ($query) {
            $query->field('id,nickname');
        }, 'deleteUser' => function ($query) {
            $query->field('id,nickname');
        }])->order('last_update_time desc');
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select()->each(function ($item) {
                $count = $this->model->where('channel_id', $item['id'])->count();
                $item['updatrNum'] = $count;
                $content = json_decode($item['content'], true);
                if (!empty($content)) {
                    foreach ($content as &$val) {
                        $val['type_str'] = $this->model->type[$val['type']];
                    }
                }
                $item['content'] = $content;
                $item['last_update_time'] = date('Y-m-d H:i:s', $item['last_update_time']);
                return $item;
            });
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->each(function ($item) {
                $count = $this->model->where('channel_id', $item['id'])->count();
                $item['updatrNum'] = $count;
                $content = json_decode($item['content'], true);
                if (!empty($content)) {
                    foreach ($content as &$val) {
                        $val['type_str'] = $this->model->type[$val['type']];
                    }
                }
                $item['content'] = $content;
                $item['last_update_time'] = date('Y-m-d H:i:s', $item['last_update_time']);
                return $item;
            })->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    public function buildSearch()
    {
        $request = $this->request->request();
        extract($request);
        $where = [];
        if (isset($last_update_time) && !empty($last_update_time)) {
            $lastUpdateTime = explode(' - ', $last_update_time);
            $where[] = ['last_update_time', 'between', strtotime($lastUpdateTime[0]) . ',' . strtotime($lastUpdateTime[1])];
        }
        if (isset($create_time) && !empty($create_time)) {
            $createTime = explode(' - ', $create_time);
            $where[] = ['create_time', 'between', strtotime($createTime[0]) . ',' . strtotime($createTime[1])];
        }
        if (isset($type_id) && !empty($type_id)) {
            $where[] = ['type_ids', 'find in set', $type_id];
        }
        if (isset($channel_id) && !empty($channel_id)) {
            $where[] = ['channel_id', '=', $channel_id];
        }
        return $where;
    }

    //添加
    public function add()
    {
        $post = $this->request->post();
        //数据处理
        if (!isset($post['content']) || (isset($post['content']) && $post['content'] == '')) {
            return $this->error('添加内容不能为空');
        }
        $content = $post['content'];
        if(!isset($post['last_update_time']) || (isset($post['last_update_time']) && $post['last_update_time'] == '')){
            return $this->error('更新时间不能为空');
        }
        $post['last_update_time'] = strtotime($post['last_update_time']);
        $typeIds = [];
        foreach ($content as $key => $val) {
            if($val['type'] == '' || $val['type'] == 0){
                return $this->error('请选择更新类型');
            }
            unset($content[$key]['id']);
            unset($content[$key]['selectList']);
            $typeIds[] = $val['type'];
            $content[$key]['type'] =(int)$val['type'];
        }
        $post['type_ids'] = implode(',', $typeIds);
        $post['content'] = json_encode($content);
        $user = UserServiceFacade::getUserInfo();
        $userId = $user['id'];
        $post['create_user_id'] = $userId;
        Db::startTrans();
        try {
            $saveRes = $this->model->save($post);
            if (!$saveRes) throw new \Exception('数据库异常，操作失败2');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            print_r($e->getMessage());
            Db::rollback();
            return $this->error('数据库异常，操作失败1');
        }
    }


    //查看详情
    public function info()
    {
        $id = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        if (!empty($info)) {
            $content = json_decode($info['content'], true);
            if (!empty($content)) {
                foreach ($content as $key => $val) {
                    $val['type_str'] = $this->model->type[$val['type']];
                    if(!isset($val['current_img'])){
                        $content[$key]['current_img'] = [];
                    }
                    if(!isset($val['last_img'])){
                        $content[$key]['last_img'] = [];
                    }
                }
            }
            $info['content'] = $content;
            $info['last_update_time'] = date('Y-m-d H:i:s', $info['last_update_time']);
        }
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post = $this->request->post();
        $id = $post['id'];
        $info = $this->model->where('id',$id)->find();
        if(empty($info)){
            return $this->error('编辑内容不存在');
        }
        //数据处理
        if (!isset($post['content']) || (isset($post['content']) && $post['content'] == '')) {
            return $this->error('添加内容不能为空');
        }
        $content = $post['content'];
        if(!isset($post['last_update_time']) || (isset($post['last_update_time']) && $post['last_update_time'] == '')){
            return $this->error('更新时间不能为空');
        }
        $post['last_update_time'] = strtotime($post['last_update_time']);
        $typeIds = [];
        foreach ($content as $key => $val) {
            if($val['type'] == '' || $val['type'] == 0){
                return $this->error('请选择更新类型');
            }
            unset($content[$key]['id']);
            unset($content[$key]['selectList']);
            $typeIds[] = $val['type'];
            $content[$key]['type'] =(int)$val['type'];
        }
        $post['type_ids'] = implode(',', $typeIds);
        $post['content'] = json_encode($content);
        $user = UserServiceFacade::getUserInfo();
        $userId = $user['id'];
        $post['update_user_id'] = $userId;
        Db::startTrans();
        try {
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

    //回收站
    public function recycle()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $limit = $this->request->param('limit', 10);
        $data = $this->model->onlyTrashed()
            ->with(['app' => function ($query) {
                $query->field('id,app_name');
            }])
            ->order($order)->where($where)->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }
}