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
class JzdStagingList extends Backend
{
    protected $model;//当前模型对象

    protected function _initialize()
    {
        $this->model = new \app\model\admin\JzdStagingList();
    }
    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->with(['customer'])->where($where)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
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
        return $this->success('数据获取成功', $data['data']);
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

        $msg = '';
        $ex = "<br>";
        array_shift($sheet);
        Db::startTrans();
        foreach ($sheet as $k => $v) {
            $k = $k+2;
            if(!$v[0]){
                $msg .= '第 [ ' . $k . ' ]行' . '回款时间为空' . $ex;
                break;
            }
            if(!$v[1]){
                $msg .= '第 [ ' . $k . ' ]行' . '用户手机号为空' . $ex;
                break;
            }
            if(!$v[2]){
                $msg .= '第 [ ' . $k . ' ]行' . '用户姓名为空' . $ex;
                break;
            }
            $paybackTime = date('Ymd',strtotime($v['0']));
            $jzdStagingList = $this->model->where('payback_time',$paybackTime)->where('user_mobile',$v[1])->count();
            if($jzdStagingList > 0){
                continue;
            }
            $stagingPlatform = 0;
            if($v[11] == '校企服') $stagingPlatform = 1;
            if($v[11] == '诚学信付') $stagingPlatform = 2;
            if($v[11] == '倍好付') $stagingPlatform = 3;
            if($v[11] == '启辰宝') $stagingPlatform = 4;
            $arr[] = [
                'payback_time' => date('Ymd',strtotime($v['0'])),
                'user_mobile' => $v[1],
                'user_name' => $v[2],
                'should_nums' => $v[3],
                'should_amount' => $v[4],
                'actual_nums' => $v[5],
                'actual_amount' => $v[6],
                'overdue_nums' => $v[7],
                'overdue_amount' => $v[8],
                'overdueyh_nums' => $v[9],
                'overdueyh_amount' => $v[10],
                'staging_platform' => $stagingPlatform,
                'customer_id' => $v[13],
                'refund_nums' => $v[15],
                'refund_amount' => $v[16],
                'refund_course_nums' => $v[17],
                'overdue_seven_day_nums' => $v[18],
                'overdue_seven_day_amount' => $v[19],
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