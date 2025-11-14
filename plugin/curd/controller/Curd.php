<?php

namespace plugin\curd\controller;

use laytp\controller\Backend;
use plugin\curd\library\Menu;
use plugin\curd\model\curd\Field;
use plugin\curd\model\curd\Table;

class Curd extends Backend
{
    protected $noNeedAuth = ['getFieldList'];
    protected $orderRule = ['show_sort' => 'desc', 'id' => 'asc'];//排序规则

    public function getTableList()
    {
        $tables = Table::field('id,`table` as title')->order('id', 'desc')->select()->toArray();
        return $this->success('数据获取成功', $tables);
    }

    //获取字段列表
    public function getFieldList()
    {
        if ($table = $this->request->param('table')) {
            $where = $this->buildSearchParams();
            $tableId = Table::where('table', '=', $this->request->param('table'))->value('id');
            $where[] = ['table_id', '=', $tableId];
            $order = $this->buildOrder();
            $limit = $this->request->param('limit');
            $data  = Field::where($where)->order($order)->paginate($limit)->toArray();
            return $this->success('数据获取成功', $data);
        } else {
            return $this->success('数据获取失败');
        }
    }

    //生成常规CURD
    public function createNormalCurd()
    {
        $tableId     = $this->request->post('table_id');
        $createTable = $this->request->post('create_table', 0);
        $createMenu  = $this->request->post('create_menu', 0);
        $menuId      = $this->request->post('menu_id', 0);
        $save        = [];

        if ($createMenu) {
            $menu = new Menu();
            $menu->create($tableId, $menuId);
            $save['has_create_menu'] = 1;
        }

        if ($createTable) {
            $save['has_create_table'] = 1;
        }

        $curd        = new \plugin\curd\library\Curd($tableId, $createTable);
        if ($curd->execute()) {
            $save['create_type'] = 1;
            $save['create_addition'] = json_encode([]);
            Table::where('id', '=', $tableId)->save($save);
            return $this->success('生成成功');
        } else {
            return $this->error($curd->getError());
        }
    }

    //生成分类CURD
    public function createCategoryCurd()
    {
        $tableId                 = $this->request->post('table_id');
        $createTable             = $this->request->post('create_table', 0);
        $createMenu              = $this->request->post('create_menu', 0);
        $menuId                  = $this->request->post('menu_id', 0);
        $parentField             = $this->request->post('parent_field', 0);
        $idOrderType             = $this->request->post('id_order_type', 0);
        $orderField              = $this->request->post('order_field', 0);
        $orderType               = $this->request->post('order_type', 0);
        $save['create_addition'] = json_encode([
            'parent_field'  => $parentField,
            'id_order_type' => $idOrderType,
            'order_field'   => $orderField,
            'order_type'    => $orderType,
        ], JSON_UNESCAPED_UNICODE);

        if ($createMenu) {
            $menu = new Menu();
            $menu->create($tableId, $menuId);
            $save['has_create_menu'] = 1;
        }

        if ($createTable) {
            $save['has_create_table'] = 1;
        }

        $curdCategory = new \plugin\curd\library\CurdCategory($tableId, $createTable, $save);
        if ($curdCategory->execute()) {
            $save['create_type'] = 2;
            $save['create_addition'] = json_encode([]);
            Table::where('id', '=', $tableId)->save($save);
            return $this->success('生成成功');
        } else {
            return $this->error($curdCategory->getError());
        }
    }
}
