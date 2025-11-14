<?php

namespace app\controller\admin;

use app\lib\api\city\IpCity;
use app\lib\api\exception\Exception;
use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use app\validate\admin\wechatuserthread\WechatUserThread as WechatUserThreadValidate;
use app\model\admin\AccompanyingHospital;
use app\model\admin\AccompanyingSku;
use app\model\admin\AccompanyingUserInfo;
use app\model\admin\AccompanyingPlatformAccount;

/**
 * 后台应用分类控制器
 */
class AccompanyingWechatUserTwo extends Backend
{
    protected $model;//当前模型对象

    protected $noNeedAuth = ['platformAccountList'];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\AccompanyingPletterUser();
    }

    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->with(['account'])->where($where)->where('type',1)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //平台账号列表
    public function platformAccountList()
    {
        $platformId = $this->request->post('platform_id');
        $data = AccompanyingPlatformAccount::where('platform_id',$platformId)->field('id,account_name as name')->order('id asc');
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 100);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //添加
    public function add()
    {
        $post     = CommonFun::filterPostData($this->request->post());
//        $validate = new WechatUserThreadValidate();
//        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $post['active_passive'] = isset($post['active_passive']) == 1 ? '主动' : '被动';
            $saveRes = $this->model->save($post);
            if (!$saveRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('数据库异常，操作失败');
        }
    }

    //编辑
    public function edit()
    {
        $post     = CommonFun::filterPostData($this->request->post());
//        $validate = new AccompanyingDailyNumValidate();
//        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            if(empty($post['platform_account'])){
                unset($post['platform_account']);
            }
            $appClass = $this->model->findOrEmpty($post['id']);
            if (!$appClass) throw new \Exception('id参数错误');
            $post['active_passive'] = isset($post['active_passive']) == 1 ? '主动' : '被动';
            $updateRes  = $appClass->update($post);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }

    //查看详情
    public function info()
    {
        $id   = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        return $this->success('获取成功', $info);
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

    //数据导出
    public function exportAccompanying()
    {
        set_time_limit(0);
        //设置程序运行内存
        ini_set('memory_limit', '512M');
        $fileName = date("Y-m-d H:i:s") . '-陪诊数据';
        header('Content-Encoding: UTF-8');
        header("Content-type:application/vnd.ms-excel;charset=UTF-8");
        header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');
        //打开php标准输出流
        $fp = fopen('php://output', 'a');
        //添加BOM头，以UTF8编码导出CSV文件，如果文件头未添加BOM头，打开会出现乱码。
        fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $titleArr = [
            'ID', '平台','姓名', '年龄', '手机号',
            '职业', 'sku', '成交金额','陪诊师','是否复购',
            '病种', '城市','市区', '医院','就诊时间',
            '沟通特点', '特殊需求', '加微类型', '创建时间',
        ];

        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->with(['hospital'])->where($where)->order($order)->select();
        fputcsv($fp, $titleArr);

        foreach ($data as $val) {
            $platform = '';
            if ($val->platform_id == 1) $platform = '抖音';
            if ($val->platform_id == 2) $platform = '小红书';
            $repurchase = '';
            if ($val->is_repurchase == 0) $repurchase = '否';
            if ($val->is_repurchase == 1) $repurchase = '是';
            $microType = '';
            if ($val->micro_type == 1) $microType = '陪诊加微';
            if ($val->micro_type == 2) $microType = '学习加微';

            $item = [
                $val->id,
                $platform,
                $val->nickname,
                $val->age,
                $val->phone,
                $val->career,
                $val->hospital_sku ?? '',
                $val->transaction_amount,
                $val->accompanying_physician,
                $repurchase,
                $val->disease_type,
                $val->city,
                $val->area,
                $val->hospital_name ?? '',
                $val->visit_time,
                $val->communicate_feature,
                $val->special_needs,
                $microType,
                $val->create_time,
            ];

            fputcsv($fp, $item);
        }
        ob_flush();
        flush();
    }

}