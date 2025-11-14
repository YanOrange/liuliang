<?php

namespace app\lib\service\caituokun;

use app\lib\api\exception\ExceptionStd;
use app\model\api\caituokun\AssetAccountType;
use app\model\api\caituokun\AssetAnalysis;

class AssetAnalysisService
{

    /**
     * 资产分析列表
     * @param $params
     * @param $user_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function list($params = [], $user_id = 0)
    {
        $assetCateList     = [];
        $params['user_id'] = $user_id;
        $list              = AssetAnalysis::where($params)->select()->toArray();
        if ($list) {
            $assetIds      = array_column($list, 'category');
            $assetCateList = AssetAccountType::where(['status' => 1])
                            ->whereIn('id',$assetIds)
                            ->limit(200)
                            ->select()->toArray();
            foreach ($assetCateList as $key => &$val) {
                if (!isset($val['list'])) $val['list'] = [];
                foreach ($list as $k => $vl) {
                    if ($val['id'] == $vl['category']) {
                        //名称处理
                        $vl['name']    = self::showHandleName($vl, $val);
                        $val['list'][] = $vl;
                    }
                }
            }
        }
        $returnData['list'] = $assetCateList;

        //资产分析统计 正资产
        $assetSum = self::assetAmountSum(['user_id' => $user_id, 'type' => 1]);
        //负债
        $nassetSum = self::assetAmountSum(['user_id' => $user_id, 'type' => 2]);

        $recount = $assetSum - $nassetSum;

        $returnData['asset'] = [
            'count'       => $recount,//资产结果统计
            'asset_count' => $assetSum, //正资产统计
            'debt_count'  => $nassetSum, //负债统计
        ];


        return $returnData;
    }

    //资产分析 统计金额
    public static function assetAmountSum($where = [])
    {
        return AssetAnalysis::where($where)->sum('amount') ?? 0;
    }


    //处理 资产分析 显示名称
    protected static function showHandleName($vl, $val)
    {
        $name_str = '';
        //名称
        if ($vl['name']) {
            $name_str = $vl['name'];
        }
        //卡号
        if ($vl['card_no']) {
            $name_str .= $vl['card_no'];
        }
        //虚拟账户类型
        if ($vl['virtual_account_type']) {
            $virtual_account_type_arr = [0 => '', 1 => '支付宝', 2 => '微信', 3 => '其他'];
            $name_str                 = $virtual_account_type_arr[$vl['virtual_account_type']] ?? '';
        }
        //投资账户类型
        if ($vl['investment_account_type']) {
            $virtual_account_type_arr = [0 => '', 1 => '股票', 2 => '基金', 3 => '其他'];
            $name_str                 = $virtual_account_type_arr[$vl['investment_account_type']] ?? '';
        }
        //如果都没有则使用 类别名称
        if(!$name_str){
            $name_str = $val['name'] ?? '';
        }
        return $name_str;
    }


    // 资产分析 添加
    public static function create($params = [], $users_id = 0)
    {
        if (!$params) {
            new ExceptionStd('参数错误');
        }
        $create_time = date('Y-m-d H:i:s', time());
        foreach ($params as $key => &$val) {
            $val['user_id']    = $users_id;
            $val['created_at'] = $create_time;
            if (empty($val['type'])) {
                new ExceptionStd('参数错误1');
            }
            if (empty($val['category'])) {
                new ExceptionStd('参数错误2');
            }
            if (empty($val['amount'])) {
                new ExceptionStd('参数错误3');
            }
        }

        return AssetAnalysis::insertAll($params);;
    }



    //类别列表
    public static function getCateList()
    {
        $where['status'] = 1;
        return AssetAccountType::where($where)
            ->limit(200)
            ->select();
    }






}