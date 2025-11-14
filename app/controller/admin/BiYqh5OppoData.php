<?php

namespace app\controller\admin;

use app\validate\admin\app_class\AppClass as AppClassValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use app\model\api\Thread;
use think\facade\Db;

/**
 * 后台应用分类控制器
 */
class BiYqh5OppoData extends Backend
{
    protected $model;//当前模型对象
    protected $province = [
        'province' => ['黑龙江','吉林','辽宁','河北','山西','青海','山东','河南','江苏','安徽',
        '浙江', '福建','江西','湖南','湖北','广东','台湾','海南','甘肃','陕西','四川','贵州','云南'],
        'city' => ['北京','上海','天津','重庆'],
        'autonomous' => ['内蒙古自治区','新疆维吾尔自治区','广西壮族自治区','宁夏回族自治区','西藏自治区'],
        'special' => ['香港特别行政区','澳门特别行政区']
    ];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\BiYqh5OppoData();
    }
    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->where($where)->order('submit_time desc');
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
        $validate = new AppClassValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
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
        //$validate = new AppClassValidate();
        //if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
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

        $arr = [];
        $msg = '';

        $ex = "<br>";
        array_shift($sheet);
        Db::startTrans();
        foreach ($sheet as $k => $v) {
            $k = $k+2;
            if (!isset($v[0]) || empty($v[0])) {
                $msg .= '第 [ ' . $k . ' ]行' . '姓名为空' . $ex;
                break;
            }
            if (!isset($v[9]) || empty($v[9])) {
                $msg .= '第 [ ' . $k . ' ]行' . '线索ID为空' . $ex;
                break;
            }
            if (!isset($v[10]) || empty($v[10])) {
                $msg .= '第 [ ' . $k . ' ]行' . '省份为空' . $ex;
                break;
            }

            $biYqh5OppoData = $this->model->where('thread_id',$v[9])->count();
            if($biYqh5OppoData > 0){
                $this->model->where('thread_id',$v[9])->update(['delete_time' => time()]);
            }

            $province = $v[10];
            if(in_array($v[10],$this->province['province'])) $province = $v[10].'省';
            if(in_array($v[10],$this->province['city'])) $province = $v[10].'市';
            if($v[10] == '内蒙古') $province = '内蒙古自治区';
            if($v[10] == '新疆') $province = '新疆维吾尔自治区';
            if($v[10] == '广西') $province = '广西壮族自治区';
            if($v[10] == '宁夏') $province = '宁夏回族自治区';
            if($v[10] == '西藏') $province = '西藏自治区';
            if(in_array($v[10],$this->province['special'])) $province = $v[10].'特别行政区';
            $createTime = strtotime($v[6]) - 2;
            $insideThreadId = Thread::where('province',$v[10] ?? '')
                ->where('create_time',$createTime)
                ->value('id');
            $arr[] = [
                'nick_name' => $v[0] ?? '',
                'phone' => $v[1] ?? '',
                'advertiser_id' => $v[2] ?? 0,
                'land_page_name' => $v[3] ?? '',
                'land_page_id' => $v[4] ?? 0,
                'form_name' => $v[5] ?? '',
                'submit_time' => $v[6] ?? 0,
                'submit_time_id' => date('YmdHi',strtotime($v[6])) ?? 0,
                'connect_status' => $v[7] ?? '',
                'follow_status' => $v[8] ?? '',
                'thread_id' => $v[9] ?? '',
                'province' => $province,
                'city' => $v[11] ?? '',
                'lahei_status' => $v[12] ?? '',
                'inside_thread_id' => $insideThreadId ?? 0
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