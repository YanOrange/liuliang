<?php

namespace app\lib\service\caituokun;

use app\lib\api\exception\ExceptionStd;
use app\model\api\caituokun\Cashbook;
use app\model\api\caituokun\CashbookSignIn;
use think\facade\Db;

class CashbookService
{


    //记账本 列表
    public static function list($params, $user_id = 0)
    {
        $where['user_id'] = $user_id;
        if(!empty($params['date'])){
            $dateTime = strtotime($params['date']);
            $where['year'] = date('Y',$dateTime);
            $where['month'] = date('m',$dateTime);
        }else{
            $dateTime = time();
            $where['year'] = date('Y',$dateTime);
            $where['month'] = date('m',$dateTime);
        }

        $list = Cashbook::field("*")->where($where)->order('date','desc')->select()->toArray();
        $dateList = [];
        if($list){
            $dateList = array_column($list,null,'date');
            foreach ($list as $k => $vl){
                $dateList[$vl['date']]['list'][] = $vl;
            }
            $dateList = array_values($dateList);
        }
        $page = request()->param('page', 1);
        $limit = request()->param('offset', 20);
        $offsetPage = ($page - 1) * $limit;
        $count = count($dateList);
        $dateList = array_slice($dateList,$offsetPage,$limit);
        $returnData['list'] = $dateList;
        //支出
        $returnData['expenses'] = Cashbook::where($where)->where('type',1)->sum('amount');
        //收入
        $returnData['income'] = Cashbook::where($where)->where('type',2)->sum('amount');

        $returnData['paginator'] = [
            "total" => $count,
            "per_page" => (int)  $limit,
            "current_page" => (int)  $page,
            "last_page" =>  $count ? ceil($count/$limit) : 0
        ];

        return $returnData;
    }

    //账本 修改
    public static function create($params,$user_id)
    {
        if(empty($params['amount'])){
            new ExceptionStd('金额不能小于0');
        }

        $params['user_id'] = $user_id;
        if(!empty($params['date'])){
            $dateTime = strtotime($params['date']);
        }else{
            $dateTime = time();
            $params['date'] =  date('Y-m-d',$dateTime);
        }

        $params['year'] = date('Y',$dateTime);
        $params['month'] = date('m',$dateTime);
        $params['day'] = date('d',$dateTime);
        $params['week'] = date('N', $dateTime);
        $params['note_time'] = $dateTime;

        $result =  Cashbook::create($params);;
        if($result){
            //记录连续记账
            self::signCreate($user_id);
        }

        return $result;
    }


    // 签到操作
    public static function signCreate($user_id)
    {
        //获取当前用户当天签到数据
        $where = ['user_id' => $user_id];
        $dtime = time();//当前年月日
        $dtimeDay = date('Ymd');//当前年月日
        $qtimeDay = date('Ymd',strtotime("- 1 day")); //当前日期前一天
        $where['date'] = $dtimeDay;
        //$info = CashbookService::getSignDetail($where);
        $info = CashbookSignIn::where($where)->order('id','desc')->find();
        if(!$info) {
            $insert = [
                'date' => $dtimeDay,
                'user_id' => $user_id,
                'sgin_time' => $dtime,
                'num' => 1
            ];

            $yesterdayWhere['user_id'] = $user_id;
            $yesterdayWhere['date'] = $qtimeDay;
            //$yesterdayInfo = CashbookService::getSignDetail($yesterdayWhere);
            $yesterdayInfo = CashbookSignIn::where($yesterdayWhere)->order('id','desc')->find();;
            if($yesterdayInfo) {
                $insert['num'] = $yesterdayInfo['num'] + 1;
            }

            //CashbookService::signCreate($insert);
            CashbookSignIn::create($insert);
        }

        return true;
    }

    /**
     * 记账本 - 修改
     * @param $params
     * @param $user_id
     * @return mixed
     */
    public static function edit($params,$user_id)
    {
        if(empty($params['id'])){
            new ExceptionStd('参数不能为空');
        }
        $params['user_id'] = $user_id;
        $where = ['id' =>$params['id'], 'user_id' => $user_id];

        $info = Cashbook::where($where)->find();
        $resReturn = false;
        if($info){
            $resReturn =  $info->update($params);
        }

        return $resReturn ? 1 : 0;
    }

    /**
     * 记账本 - 删除
     * @param $id
     * @param $user_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function del($id,$user_id)
    {

        $where = ['id' => $id, 'user_id' => $user_id];
        $info = Cashbook::where($where)->find();
        $res = false;
        if($info){
            $res = $info->delete();
        }

        return $res;
    }


    //账本所有收支统计
    public static function recount($user_id = 0)
    {
        $where = ['user_id' => $user_id];
        //支出
        $returnData['expenses'] =  Cashbook::where($where)->where('type',1)->sum('amount');
        //收入
        $returnData['income'] = Cashbook::where($where)->where('type',2)->sum('amount');

        return $returnData;
    }

    //账本连续记账详情
    public static function getSignDetail($where, $order='id')
    {
        $result = CashbookSignIn::where($where)->order($order,"desc")->find();

        return $result ? $result->toArray() : [];
    }

    public static function count($where)
    {
        $count = Cashbook::where($where)->count();

        return $count;
    }

}