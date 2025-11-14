<?php

namespace app\controller\admin\thread;

use app\model\admin\api\ThreadTransactPayfee as ThreadTransactPayfeeModel;
use app\service\admin\UserServiceFacade;
use app\validate\admin\api\ThreadTransactPayfeeValidate;
use laytp\controller\Backend;
use think\facade\Config;

/**
 * 报名缴费关联控制器
 */
class ThreadTransactPayfee extends Backend
{
    protected $noNeedAuth = ['*'];
    protected $noNeedLogin = [''];

    protected $model;//当前模型对象

    protected function _initialize() {
        $this->model = new \app\model\admin\thread\ThreadTransactPayfee();
        $this->orderModel = new \app\model\admin\order\CollectionOrder();
    }


    /**
     * 我的报名缴费列表
     * @return false|string|\think\response\Json
     */
    public function getThreadTransactPayfeeList()
    {
        $params = $this->request->param();
        $list = $this->HandlePayFeeList($params);
        //dd($list);

        //$threadTransactPayfeeList = $this->model->getThreadTransactPayfeeList($params);
        return $this->success('数据获取成功', $list);

    }


    protected function HandlePayFeeList($params)
    {
        extract($params);

        $info = $this->model->with('user')->where('id',$id)->find();
        $params['merchant_id'] = $info['merchant_id'] ?? 260;
        $debtinfo = Config::load("extra/company" , "extra");
        $debtinfo = $debtinfo[$params['merchant_id']] ?? $debtinfo[260];

        $merchant = UserServiceFacade::getUserInfo();
        $where = [
            ['thread_id', '=', $id],
            ['is_external_thread', '=', 0],
        ];
        $courseData = $this->orderModel->where($where)
            ->with(['user', 'merchant', 'customer'])
            ->order('create_time desc')
            ->paginate($pagesize)
            ->each(function ($item, $key) use ($debtinfo)  {
                $adminUserName = "系统管理员";
                if ($item['admin_type'] == 2) {
                    if(!empty($item['customer'])){
                        $adminUserName = $item['customer']['nickname'];
                    }else{
                        $adminUserName = '-';
                    }
                }
                $item['admin_username'] = $adminUserName;
                $item['status_text'] = $this->getStatusText($item['status']);
                $item['order_status_text'] = $this->getOrderStatusText($item['order_status']);
                $item['pay_bg_img_url'] = $debtinfo['third_pay_qrcode_bg'];
                $item['pay_img_url'] = $debtinfo['h5_url'] . $item['qr_code'];
                $item['pay_time'] = $item['pay_time'] ?  date('Y-m-d H:i:s',$item['pay_time']) : 0;
                //$item['create_time'] = $item['create_time'] ?  date('Y-m-d H:i:s',$item['create_time']) : 0;

                return $item;
            })
            ->toArray();
        return $courseData;
    }

    protected function getStatusText($status)
    {
        $statusArr = [
            0 => '已取消',
            1 => '已付款',
        ];
        return $statusArr[$status];
    }

    protected function getOrderStatusText($orderStatus)
    {

    $statusArr = [
        0 => '',
        1 => '待付款',
        2 => '已付款',
        3 => '已关闭',
        4 => '已失效',
    ];
        return $statusArr[$orderStatus];
    }

}