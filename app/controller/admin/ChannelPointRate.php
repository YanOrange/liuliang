<?php

namespace app\controller\admin;

use app\model\admin\Channel;
use app\model\admin\Customer;
use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use laytp\library\Tree;
use think\facade\Db;
use think\facade\Request;
use app\validate\admin\channel\ChannelPointRate as ChannelPointRateValidate;

/**
 * 地区管理
 */
class ChannelPointRate extends Backend
{

    protected $model;
    protected $noNeedAuth = ['adminNameList'];

    public function _initialize()
    {
        $this->model = new \app\model\admin\ChannelPointRate();
    }

    //查看
    public function index()
    {
        //$where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->buildSearch()->with(['channel' => function($query){
            $query->with(['channelPromotion', 'channelPlatform', 'channelDeliveryMode']);
        },'class' => function($query){
            $query->field('id,app_class_name');
        },'admin' => function($query){
            $query->field('id,nickname');
        },])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)
                ->each(function($item){
                    $item['actual_consumption'] = round($item['cost'] / (1+$item['point_rate']), 2);
                    $item['point_rate'] = ($item['point_rate'] * 100) .'%';
                    return $item;
                })
                ->toArray();
        }

        $delivery = \app\model\admin\DeliveryMode::select()->toArray();
        foreach ($data['data'] as $key => $item) {
            $result = '';
            $data['data'][$key]['channel']['channelDeliveryMode'] = substr((new Tree())->getParents($item['channel']['channelDeliveryMode']['id'], $delivery, $result), 0, -1);
        }

        return $this->success('数据获取成功', $data);
    }

    // 构建查询条件
    public function buildSearch()
    {
        $filter = $this->request->param('search_param');
        $filter = !empty($filter) ? $filter : [];
        extract($filter);

        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;
        $threadModel = $this->model;

        if (isset($app_class_id) && !empty($app_class_id)) {
            $threadModel = $threadModel->where($tableName . '.app_class_id', $app_class_id);
        }
        if (isset($channel_id) && !empty($channel_id)) {
            $threadModel = $threadModel->where($tableName . '.channel_id', '=', $channel_id);
        }
        if (isset($admin_id) && !empty($admin_id)) {
            $threadModel = $threadModel->where($tableName . '.admin_id', '=', $admin_id);
        }

        if (isset($point_date) && !empty($point_date)) {
            list($startTime, $endTime) = explode(' - ', $point_date);
            $threadModel = $threadModel->where($tableName . '.point_date', 'between', $startTime . ',' . $endTime);
        }

        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $threadModel = $threadModel->where($tableName . '.create_time', 'between', strtotime($startTime) . ',' . strtotime($endTime));
        }

        return $threadModel;
    }

    public function adminNameList()
    {
        $adminIds = $this->model->group('admin_id')->column('admin_id');
        $data = (new \app\model\admin\User())->whereIn('id',$adminIds)->field('id,nickname')->paginate(100)->toArray();
        return $this->success('数据获取成功', $data['data']);
    }

    //添加
    public function add()
    {
        return [];
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $post = CommonFun::filterPostData($this->request->post());

        $validate = new ChannelPointRateValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $dataAll = [];
            $channelName = Channel::where('id',$post['channel_id'])->value('channel_name');
            $channelInfo = (new \app\model\api\Channel())->getChannelAppClass($channelName);
            $channelPointRate = $this->model->where('channel_id',$channelInfo['channel_id'])
                ->where('point_date',$post['point_date'])
                ->find();
            if(!empty($channelPointRate)){
                return $this->error('渠道日期已存在');
            }
            $dataAll[] = [
                'channel_id' => $post['channel_id'],
                'app_class_id' => $channelInfo['app_class_id'],
                'point_rate' => $post['point_rate'],
                'point_date' => $post['point_date'],
                'admin_id' => $loginId
            ];
            $saveRes = $this->model->saveAll($dataAll);
            if (!$saveRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            dump($e->getMessage());die;
            return $this->error('数据库异常，操作失败');
        }
    }

    //查看详情
    public function info()
    {
        $id   = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post = CommonFun::filterPostData($this->request->post());

        $validate = new ChannelPointRateValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $channelPoint = $this->model->findOrEmpty($post['id']);
            if (!$channelPoint) throw new \Exception('id参数错误');
            $channelName = Channel::where('id',$post['channel_id'])->value('channel_name');
            $channelInfo = (new \app\model\api\Channel())->getChannelAppClass($channelName);
            $channelPointRate = $this->model->where('channel_id',$post['channel_id'])
                ->where('point_date',$post['point_date'])
                ->where('id','<>',$post['id'])
                ->find();
            if(!empty($channelPointRate)){
                return $this->error('渠道日期已存在');
            }
            $post['app_class_id'] = $channelInfo['app_class_id'];
            $updateRes  = $channelPoint->update($post);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }

    //编辑
    public function editAll()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $post = CommonFun::filterPostData($this->request->post());
        $validate = new ChannelPointRateValidate();
        if (!$validate->scene('editAll')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $dataAll = [];
            $channelPointRateIds = explode(',',$post['ids']);
            if(isset($post['channel_id'])) {
                $channelName = Channel::where('id', $post['channel_id'])->value('channel_name');
                $channelInfo = (new \app\model\api\Channel())->getChannelAppClass($channelName);
                // $channelPointRate = $this->model->where('channel_id', $post['channel_id'])
                //     ->where('point_date', $post['point_date'])
                //     ->where('id', '<>', $post['id'])
                //     ->find();
                // if (!empty($channelPointRate)) {
                //     return $this->error('渠道日期已存在');
                // }
                foreach($channelPointRateIds as $id){
                    $dataAll[] = [
                        'id' => $id,
                        'channel_id' => $post['channel_id'],
                        'app_class_id' => $channelInfo['app_class_id'],
                        'admin_id' => $loginId
                    ];
                }
            }
            if(isset($post['point_rate'])){
                foreach($channelPointRateIds as $id){
                    $dataAll[] = [
                        'id' => $id,
                        'point_rate' => $post['point_rate'],
                        'admin_id' => $loginId
                    ];
                }
            }
            $updateRes  = $this->model->saveAll($dataAll);
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

        $reader = \PHPExcel_IOFactory::createReader($mode);

        $excel = $reader->load($path, $encode = 'utf-8');
        $sheet = $excel->getSheet(0)->toArray();

        if (!isset($sheet[0])) {
            return $this->error('请检查文件的数据是否为空');
        }

        // 登记人员
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];

        array_shift($sheet);
        foreach($sheet as $key => $item){
            if($item[0] == null){
                unset($sheet[$key]);
            }
        }

        $ex = "<br>";
        Db::startTrans();
        $arr = [];
        $msg = '';
        foreach($sheet as $key => $item){
            if(!$item[0]){
                $msg .= '第 [ ' . $key . ' ]行' . '渠道 [ ' . $item[0] . ' ] 为空' . $ex;
                break;
            }
            if(!$item[1]){
                $msg .= '第 [ ' . $key . ' ]行' . '返点 [ ' . $item[1] . ' ] 为空' . $ex;
                break;
            }
            if($item[1] < 0 || $item[1] > 100){
                $msg .= '第 [ ' . $key . ' ]行' . '返点 [ ' . $item[1] . ' ] 数据不正确' . $ex;
                break;
            }
            $channelInfo = (new \app\model\api\Channel())->getChannelAppClass($item[0]);
            if(empty($channelInfo)){
                $msg .= '第 [ ' . $key . ' ]行' . '渠道 [ ' . $item[0] . ' ] 不存在' . $ex;
                break;
            }
            $channelPointRate = $this->model->where('channel_id',$channelInfo['channel_id'])
                ->where('point_date',$item[3])
                ->find();
            if(!empty($channelPointRate)){
                $msg .= '第 [ ' . $key . ' ]行' . '渠道 [ ' . $item[0] . ' ] 与日期 ['.$item[3].'] 已存在' . $ex;
                break;
            }
            $arr[] = [
                'channel_id' => $channelInfo['channel_id'],
                'app_class_id' => $channelInfo['app_class_id'],
                'point_rate' => round(substr($item[1],0,strrpos($item[1],"%")) / 100,2),
                'point_date' => $item[3],
                'cost' => $item[2],
                'admin_id' => $loginId
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

    //type 1 = minute;  2 = hour;  3 = day;
    public function averageTime($startTime,$endTime,$type = 3)
    {
        $startTime = strtotime($startTime);
        $endTime = strtotime($endTime);
        $formatStyle = [
            1 => 'Y-m-d H:i:s',
            2 => 'Y-m-d H:i',
            3 => 'Y-m-d',
        ];

        $format = $formatStyle[$type];

        if ($type == 1) {
            //minute
            $seconds = 60;
        } elseif ($type == 2) {
            //hour
            $seconds = 3600;
        } else {
            //day
            $seconds = 86400;
        }
        $result = [];

        $for    = intval(($endTime - $startTime) / $seconds);
        for($i = 0; $i <= $for; $i++){
            $result[] = date($format,$startTime + $i * $seconds);
        }

        return $result;
    }
}
