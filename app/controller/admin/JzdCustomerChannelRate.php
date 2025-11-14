<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Request;
use app\model\admin\Channel;
use app\model\admin\Customer;
use app\lib\api\douyin\MarketingPushApi;
use app\model\admin\GatherUserInfo;
use app\model\api\UserList;
use app\model\api\Thread;
use app\model\admin\UserProfile;
use app\model\api\v2\GatherUserInfo as GatherUserInfoModel;

/**
 * 后台推荐阅读控制器
 */
class JzdCustomerChannelRate extends Backend
{
    protected $model;//当前模型对象
    protected $noNeedAuth = ['*'];
    protected $noNeedLogin = [];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\JzdCustomerChannelRate();
    }

    //查看
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $datetime = date('Y-m-d');
        $channelNames = $this->model->where('datetime',$datetime)
            ->group('channel')
            ->column('channel');
        $customerIds = $this->model->where('datetime',$datetime)
            ->field('id,customer_id')
            ->group('customer_id')
            ->paginate(10)
            ->toArray();
        $count = $this->model->where('datetime',$datetime)->group('customer_id')->count();
        $customerIds = array_column($customerIds['data'],'customer_id');
        $channelRateData = [];
        foreach($customerIds as $customerId){
            $channelRateData[] = $this->model->where('datetime',$datetime)
                ->whereIn('channel',$channelNames)
                ->where('customer_id',$customerId)
                ->field('id,customer_id,channel,component_rate')
                ->select()
                ->toArray();
        }
        $dataAll = [];
        foreach($channelRateData as $key => $item){
            $customerId = $customerIds[$key];
            $nickname = Customer::where('id',$customerId)->value('nickname');
            $data = array_column($item,'component_rate','channel');
            $data = array_merge(['customer_id' => $customerId,'nickname' => $nickname],$data);
            $dataAll[] = $data;
        }
        return $this->success('数据获取成功', ['data' => $dataAll,'total' => $count]);
    }

    //渠道分期列表
    public function channelList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->with(['admin' => function($query){
            $query->field('id,nickname')->find();
        }])->where($where)->order($order)->group('admin_id');
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->each(function($item){
                $item['channel_num'] = (new \app\model\admin\JzdCustomerChannelRate())->where('admin_id',$item['admin_id'])
                    ->group('channel')
                    ->count();
                return $item;
            })->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //查看详情
    public function info()
    {
        $id   = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
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
        // unset($sheet[0]);
        // foreach($sheet as $item){
        //     $ganther = self::getGatherUserInfo($item[25]);
        //     if($ganther['age'] && $ganther['ageRange']){
        //         $userInfo = UserList::where('phone',$item[1])->find();
        //         $userInfo->age_range_id = $ganther['age'];
        //         $userInfo->save();
        //         $threadInfo = Thread::where('uid',$userInfo->id)->find();
        //         $threadInfo->age = $ganther['ageRange'];
        //         $threadInfo->save();
        //     }
        // }
        // die('sss');
        // foreach($sheet as $key => $item){
        //     if($key > 1){
        //         $data = [
        //             'telphone' => $item[1],
        //             'name' => $item[0],
        //             'weixin' => $item[17],
        //             'province' => explode('-',$item[12])[0],
        //             'city' => explode('-',$item[12])[1],
        //             'channel' => 'jdcz_douyin1',
        //             'create_time' => strtotime($item[10])
        //         ];
        //         $marketingPushApi = new MarketingPushApi();
        //         $marketingPushApi->cluePush($data);
        //     }
        // }
        // //return $this->success('success',[]);
        // dump($data);die;

        //登记人员
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $adminName = $loginUserInfo['username'];

        unset($sheet[0][0]);

        $channelNames = $sheet[0];

        array_shift($sheet);

        $ex = "<br>";
        Db::startTrans();
        $arr = [];
        $msg = '';
        foreach($sheet as $key1 => $item){
            if(empty($item[0])) continue;
            $customerId = Customer::where('merchant_id',177)
                ->where('nickname',$item[0])
                ->value('id');
            if(empty($customerId)){
                $msg .= '第 [ ' . $key1 . ' ]行' . '销售 [ ' . $item[0] . ' ] 不存在' . $ex;
                break;
            }
            foreach($item as $key2 => $v){
                if($key2 == 0) continue;
                if(isset($channelNames[$key2])){
                    if(!empty($item[$key2])){
                        $channelId = Channel::where('channel_name',$channelNames[$key2])->value('id');
                        if(empty($channelId)){
                            $msg .= '第 [ ' . $key2 . ' ] 行' . '渠道 [ ' . $channelNames[$key2] . ' ] 不存在' . $ex;
                            break;
                        }
                        $arr[] = [
                            'customer_id' => $customerId,
                            'channel' => $channelNames[$key2],
                            'component_rate' => substr($v,0,strrpos($v,"%")),
                            'datetime' => date('Y-m-d'),
                            'admin_id' => $loginUserInfo['id'],
                        ];
                    }else{
                        $arr[] = [
                            'customer_id' => $customerId,
                            'channel' => $channelNames[$key2],
                            'component_rate' => 0,
                            'datetime' => date('Y-m-d'),
                            'admin_id' => $loginUserInfo['id'],
                        ];
                    }
                }
            }
        }
        // foreach($channelNames as $key => $channel){
        //     $channelRateTotal = 0;
        //     foreach($arr as $item){
        //         if($item['channel'] == $channel){
        //             $channelRateTotal += $item['component_rate'];
        //         }
        //     }
        //     if($channelRateTotal <= 0 || $channelRateTotal > 100){
        //         $msg .= '第 [ ' . $key . ' ] 行' . '渠道 [ ' . $channel . ' ] 分期比率不等于100' . $ex;
        //         break;
        //     }
        // }
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
    
    public static function getGatherUserInfo($remarkDict = '')
    {
        $age = 2;
        $ageRange1 = '';
        $ageRangeList = GatherUserInfoModel::where('id',1)->find();
        $ageRange = json_decode($ageRangeList['gather_info_json'],true);
        $remarkDict = explode('/',$remarkDict);
        foreach($remarkDict as $item){
            if(strpos($item,'您的年龄') !== false){
                $remark1 = explode('？:',$item);
                $remark2 = $remark1[1] ?? '';
                foreach($ageRange as $item){
                    if($remark2 == $item['name']){
                        $age = $item['id'];
                        $ageRange1 = $item['name'];
                    }
                }
            }
        }
        
        $data['age'] = $age;
        $data['ageRange'] = $ageRange1;
        return $data;
    }

}