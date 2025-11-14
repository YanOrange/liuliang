<?php

namespace app\lib\service\caituokun;

use app\model\api\caituokun\CheckInRecord;

class CheckInRecordService
{

    //签到打卡详情
    public static function detail($where,$order='id')
    {
        $result =  CheckInRecord::where($where)->order($order,'desc')->find();

        return $result ?: [];
    }

    //签到
    public static function appSign($user_id)
    {
        //获取当前用户当天签到数据
        $where = ['user_id' => $user_id];
        $dtime = time();//当前年月日
        $dtimeDay = date('Ymd');//当前年月日
        $qtimeDay = date('Ymd',strtotime("- 1 day")); //当前日期前一天
        $where['date'] = $dtimeDay;
        $info = self::detail($where);
        if(!$info) {

            $insert = [
                'date' => $dtimeDay,
                'user_id' => $user_id,
                'sgin_time' => $dtime,
                'num' => 1
            ];

            $yesterdayWhere['user_id'] = $user_id;
            $yesterdayWhere['date'] = $qtimeDay;
            $yesterdayInfo = self::detail($yesterdayWhere);
            if($yesterdayInfo) {
                $insert['num'] = $yesterdayInfo['num'] + 1;
            }
            CheckInRecord::insert($insert);
        }

        return true;
    }
}