<?php

namespace app\lib\api\service;

class AdvertisementAccountConfigService {

    /**
     * 获取列表
     */
    public function getList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->where($where)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    /**
     * 新建
     */
    public function add()
    {

    }

    /**
     * 编辑
     */
    public function update()
    {

    }
}