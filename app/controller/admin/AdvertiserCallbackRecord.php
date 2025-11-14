<?php

namespace app\controller\admin;

use app\model\api\TodayReceiveMonitorData;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Session;
/**
 * 后台转化记录控制器
 */
class AdvertiserCallbackRecord extends Backend
{
    protected $model;//当前模型对象
    protected $cvTypeList = ['register' => '注册', 'active' => '激活', 'submit' => '表单提交', 'pay' => '应用付费'];
    protected function _initialize()
    {
        $this->model = new \app\model\admin\AdvertiserCallbackRecord();
    }

    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $create_time = $this->request->param('search_param')['create_time']['value'];
        $tableName = 'advertiser_callback_record';
        if(isset($create_time) && !empty($create_time)){
            list($startTime, $endTime) = explode(' - ',$create_time);
            if(strtotime($startTime) >= strtotime(date('Y-m-01'))){
                $dateYm = date('Ym');
                $tableName = "advertiser_callback_record_{$dateYm}";
            }
            foreach($where as $k=>$v){
                if(isset($v[0]) && $v[0] == 'create_time'){
                    unset($where[$k]);
                }
            }
            $where[] = ['create_time','between',[strtotime($startTime),strtotime($endTime)]];
        }

        $data = Db::name($tableName)->where($where)->order($order);
        Session::set('advertiser_callback_record_con', json_encode($where));
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        if(!empty($data['data'])){
            foreach($data['data'] as &$item){
                if($item['app_bundle_id'] == 'com.yuluojishu.kuaixue' && $item['channel_name'] == 'kuaixue_oppo')
                {
                    $receiveSource = TodayReceiveMonitorData::where('oaid', $item['oaid'])
                        ->where('channel', $item['channel_name'])
                        ->where('app_bundle_id', $item['app_bundle_id'])
                        ->order('id desc')
                        ->value('source');
                    if(isset($receiveSource) && !empty($receiveSource)){
                        if($receiveSource == 1){
                            $item['source'] = 3;
                        }
                        if($receiveSource == 2){
                            $item['source'] = 4;
                        }
                    }else{
                        $item['source'] = 3;
                    }
                }
                $item['create_time'] = date('Y-m-d H:i:s',$item['create_time']);
            }
            
        }
        return $this->success('数据获取成功', $data);
    }

    //线索导出
    public function exportAdvertiserCallbackRecord()
    {
        set_time_limit(0);
        //设置程序运行内存
        ini_set('memory_limit', '50M');
        $fileName = date("Y-m-d H:i:s") . '-线索数据';
        header('Content-Encoding: UTF-8');
        header("Content-type:application/vnd.ms-excel;charset=UTF-8");
        header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');
        //打开php标准输出流
        $fp = fopen('php://output', 'a');
        //添加BOM头，以UTF8编码导出CSV文件，如果文件头未添加BOM头，打开会出现乱码。
        fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));
        $startTime = 0;
        $endTime = 0;
        $tableName = 'advertiser_callback_record';
        $where = json_decode(Session::get('advertiser_callback_record_con'), true);
        foreach($where as $item){
            if(isset($item[0]) && $item[0] == 'create_time'){
                list($startTime, $endTime) = explode(',',$item[2]);
                if(strtotime($startTime) >= strtotime(date('Y-m-01'))){
                    $dateYm = date('Ym');
                    $tableName = "advertiser_callback_record_{$dateYm}";
                }
            }
        }
        $data = Db::name($tableName)->where($where)->order('id desc')->select();
        fputcsv($fp, ['商店', '渠道','包名', '用户行为', 'oaid', '行为上传时间']);
        foreach ($data as $val) {
            $item = [
                $val['channel'],
                $val['channel_name'],
                $val['app_bundle_id'],
                $this->cvTypeList[$val['cvType']],
                $val['oaid'],
                date("Y-m-d H:i:s", $val['create_time']),
            ];
            fputcsv($fp, $item);
        }
        ob_flush();
        flush();
    }

}