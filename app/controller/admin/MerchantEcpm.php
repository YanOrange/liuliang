<?php

namespace app\controller\admin;

use laytp\controller\Backend;
use app\model\admin\Merchant;
use app\model\admin\Thread;
use app\model\api\LandingPageBrowsingHistory;
class MerchantEcpm extends Backend
{
    //查看
    public function index()
    {
        $where = ' 1=1';
        $create_time = $this->request->post('create_time','');
        if(!empty($create_time)){
            $create_time = explode(' - ',$create_time);
            $where .= " and create_time >=".strtotime($create_time[0]);
            $where .= " and create_time <=".strtotime($create_time[1]);
        }
        $merchantList = Merchant::field('id,merchant_name,leisure_price')->select()->toArray();
        $merchantNameList = array_column($merchantList, 'merchant_name');
        $landingPageBrowsingNums = [];
        $landingPageThreadNums = [];
        $merchantEcpm = [];
        foreach ($merchantList as $v) {
            $landingPageBrowsingNum = LandingPageBrowsingHistory::where('merchant_id', $v['id'])->where($where)->count();
            $landingPageBrowsingNums[] = $landingPageBrowsingNum;
            $landingPageThreadNum = Thread::where('merchant_id', $v['id'])->where($where)->where('landing_page_id','>',0)->count();
            $landingPageThreadNums[] = $landingPageThreadNum;
            $merchantEcpm[] = $landingPageBrowsingNum ? sprintf("%.2f",(($landingPageThreadNum * $v['leisure_price']) / $landingPageBrowsingNum) * 1000) : 0;
        }
        $data['merchantNameList'] = $merchantNameList;
        $data['landingPageBrowsingNums'] = $landingPageBrowsingNums;
        $data['landingPageThreadNums'] = $landingPageThreadNums;
        $data['merchantEcpm'] = $merchantEcpm;
        return $this->success('数据获取成功', $data);
    }
}