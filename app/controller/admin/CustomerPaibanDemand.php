<?php

namespace app\controller\admin;

use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use app\validate\admin\customerpaiban\CustomerPaiban as CustomerPaibanValidate;
use app\model\api\PlanDetailData;
use app\model\api\VivoPlanDetailData;
use app\model\admin\Channel;
use app\model\admin\Customer;

/**
 * 后台应用分类控制器
 */
class CustomerPaibanDemand extends Backend
{
    protected $noNeedLogin = ['*']; // 无需登录即可请求的方法
    protected $noNeedAuth = ['customerList'];

    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\BiCustomerPaiban();
    }
    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->with(['customer' => function($query){
            $query->field('id,nickname');
        }])->where($where)->where('merchant_id',142)->where('data_type',2)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //山之名客服列表
    public function customerList()
    {
        $data = Customer::where('merchant_id',142)->order('id asc');
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //添加
    public function add()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new CustomerPaibanValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        $post['data_type'] = 2;
        $post['merchant_id'] = 142;
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
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new CustomerPaibanValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
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

        $savename = \think\facade\Filesystem::disk('public')->putFile('bichannelswitch', $file);
        $path = public_path() . 'static/storage/' . $savename;
        $mode = ($ext == 'xlsx') ? 'Excel2007' : 'Excel5';

        $reader = \PHPExcel_IOFactory::createReader($mode);

        $excel = $reader->load($path, $encode = 'utf-8');
        $sheet = $excel->getSheet(0)->toArray();
        if (!isset($sheet[0])) {
            return $this->error('请检查文件的数据是否为空');
        }

        // 渠道
        $channelArr = Channel::column('channel_name');

        $arr = [];
        $msg = '';
        $ex = "<br>";
        array_shift($sheet);
        Db::startTrans();
        foreach ($sheet as $k => $v) {
            $k = $k+2;
            $v[0] = trim($v[0]);
            if (!in_array($v[0],$channelArr)) {
                $msg .= '第 [ ' . $k . ' ]行' . '渠道 [ ' . $v[0] . ' ] 未匹配到对应的渠道' . $ex;
                break;
            }

            if (!$v[1]) {
                $msg .= '第 [ ' . $k . ' ]行' . '总消耗 [ ' . $v[1] . ' ] 为空' . $ex;
                break;
            }

            if (!$v[2]) {
                $msg .= '第 [ ' . $k . ' ]行' . '计划日期 [ ' . $v[2] . ' ] 为空' . $ex;
                break;
            }

            $startTime = date('Y-m-d',strtotime($v[2]));
            $channelRes = $this->model->where('start_time',$startTime)
                ->where('channel',$v[0])
                ->find();
            if ($channelRes) {
                $msg .= '第 [ ' . $k . ' ]行' . '该渠道 [ ' . $v[0] . ' ] 当日已导入消耗数据' . $ex;
                break;
            }
            $totalConsume = 0;
            if(strstr($v[0], 'oppo') !== false){
                $totalConsume = PlanDetailData::where('channel',$v[0])
                    ->whereBetween('plantime',[strtotime($v[2]),strtotime($v[2].' 23:59:59')])
                    ->sum('consume_amount');
            }

            if(strstr($v[0], 'vivo') !== false){
                $totalConsume = PlanDetailData::where('channel',$v[0])
                    ->whereBetween('report_time',[strtotime($startTime),strtotime($startTime.' 23:59:59')])
                    ->sum('spent');
            }
            $post['total_consume'] = $totalConsume;
            $arr[] = [
                'channel' => $v[0],
                'flow_consume' => $v[1],
                'total_consume' => $totalConsume,
                'plantime' => $startTime,
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