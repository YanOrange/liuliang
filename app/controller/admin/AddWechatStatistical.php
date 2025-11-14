<?php

namespace app\controller\admin;

use app\service\ConfServiceFacade;
use laytp\controller\Backend;
use think\facade\Db;

/**
 * 加V数据统计
 */
class AddWechatStatistical extends Backend
{

    /**
     * admin模型对象
     * @var \app\model\Admin
     */
    protected $model;

    public function _initialize()
    {
        $this->model = new \app\model\admin\AddWechatStatistical();
    }


    /**
     * 查看列表
     * @return false|mixed|string|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $allData = $this->request->param('all_data');
        $data = $this->model->where($where)->order('time desc');
        if ($allData) {
            $data = $data->select()->toArray();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        if (!empty($data['data'])) {
            $ratioInfo = ConfServiceFacade::groupGet('add_wechat_ratio', true);
            $ratio = $ratioInfo['ratio'];
            $minNumber = $ratioInfo['minNumber'];
            foreach ($data['data'] as $key => $val) {
                if ($val['time'] == date('Ymd')) {
                    //当天的 根据配置计算
                    $data['data'][$key]['add_number'] = $this->getCurrentNumber($val['add_number'], $ratio, $minNumber);
                } else {
                    //不是当天的
                    $data['data'][$key]['add_number'] = $this->getCurrentNumber($val['add_number'], $val['ratio'], $val['min_number']);
                }
            }
        }
        return $this->success('数据获取成功', $data);
    }

    /**
     * 根据系数计算新的加V数量
     * @param $addNumber
     * @param $ratio
     * @param $minNumber
     * @return float
     */
    public function getCurrentNumber($addNumber, $ratio, $minNumber)
    {

        $currentNumber = $addNumber;
        //当前数量>保底数量 根据系数计算
        if ($addNumber > $minNumber) {
            //大于保底数量 得到*系数后的数量（四舍五入）
            $ratioNum = round($addNumber * $ratio / 100);
            if ($ratioNum > $minNumber) {
                //系数数量>保底数量 返回系数数量
                $currentNumber = $ratioNum;
            } else {
                //系数数量<保底数量 返回保底数量
                $currentNumber = $minNumber;
            }
        }
        return $currentNumber;
    }

    /**
     * 获取系数配置
     * @return false|string|\think\response\Json
     */
    public function getRatioSetting()
    {
        $group = $this->request->param('group');
        $return = ConfServiceFacade::groupGet($group, false);
        return $this->success('获取成功', $return);
    }

    /**
     * 设置系数
     * @return false|string|\think\response\Json
     */
    public function saveRatioSetting()
    {
        $post = $this->request->post();
        $group = $post['group'];
        unset($post['group']);
        foreach ($post as $key => $value) {
            if ($key === 'ratio') {
                $value = sprintf("%01.2f", $value);
            }
            $conf['group'] = $group;
            $conf['key'] = $key;
            $conf['value'] = $value;
            $conf['form_type'] = 'input';
            $allConf[] = $conf;
        }
        Db::startTrans();
        try {
            $res = ConfServiceFacade::groupSet($allConf);
            if ($res === false) {
                return $this->error('设置失败');
            }
            //查询当天的统计记录
            $currentDayRecord = $this->model->where('time', date('Ymd'))->find();
            if (!empty($currentDayRecord)) {
                $currentDayRecord->ratio = $allConf[0]['value'];
                $currentDayRecord->min_number = $allConf[1]['value'];
                $saveRes = $currentDayRecord->save();
                if ($saveRes === false) {
                    return $this->error('设置失败');
                }
            }
            Db::commit();
            return $this->success('保存成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('数据库异常，操作失败');
        }
    }


}
