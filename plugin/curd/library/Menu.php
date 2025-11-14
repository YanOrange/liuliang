<?php

namespace plugin\curd\library;

use laytp\library\Str;
use laytp\traits\Error;
use plugin\curd\model\curd\Field;
use plugin\curd\model\curd\Table;
use think\facade\Env;

class Menu
{
    use Error;
    protected
        $tableId,//表ID
        $tableName,//表名
        $parentMenuId//上级菜单ID
    ;

    public function compatibleHtmlPath($path)
    {
        if(Env::get("domain.admin")){
            if(substr($path, 0,7) === '/admin/'){
                $path = '/' . substr($path, 7);
            }
        }
        return $path;
    }

    public function compatibleApiRoute($route)
    {
        if(Env::get("domain.adminapi")){
            if(substr($route, 0,7) === '/admin.'){
                $route = '/' . substr($route, 7);
            }
        }
        return $route;
    }

    /**
     * 创建菜单
     * @param $tableId integer 要生成菜单的lt_plugin_devtool_curd_table表ID
     * @param $parentMenuId integer 所属菜单ID
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function create($tableId, $parentMenuId)
    {
        $table = Table::find($tableId);

        $menu    = new \app\model\admin\Menu();
        $midName = strtolower($this->getMidName($table->table));
        $hrefMidName = str_replace('.', '/', $midName);
        $menuId  = $menu->insertGetId([
            'name'    => $table->comment,
            'href'    => $this->compatibleHtmlPath('/admin/' . $hrefMidName . '/index.html'),
            'rule'    => $this->compatibleApiRoute('/admin.' . $midName . '/index'),
            'is_menu' => 1,
            'pid'     => $parentMenuId,
            'is_show' => 1,
            'icon'    => 'layui-icon layui-icon-share',
            'des'     => '',
        ]);

        $arrMenu = [
            [
                'name'    => '查看和搜索列表',
                'rule'    => $this->compatibleApiRoute('/admin.' . $midName . '/index'),
                'is_menu' => 2,
                'pid'     => $menuId,
                'is_show' => 1,
                'icon'    => 'layui-icon layui-icon-share',
                'des'     => '',
            ],
            [
                'name'    => '查看单条数据详情',
                'rule'    => $this->compatibleApiRoute('/admin.' . $midName . '/info'),
                'is_menu' => 2,
                'pid'     => $menuId,
                'is_show' => 1,
                'icon'    => 'layui-icon layui-icon-share',
                'des'     => '',
            ],
            [
                'name'    => '添加',
                'rule'    => $this->compatibleApiRoute('/admin.' . $midName . '/add'),
                'is_menu' => 2,
                'pid'     => $menuId,
                'is_show' => 1,
                'icon'    => 'layui-icon layui-icon-share',
                'des'     => '',
            ],
            [
                'name'    => '编辑',
                'rule'    => $this->compatibleApiRoute('/admin.' . $midName . '/edit'),
                'is_menu' => 2,
                'pid'     => $menuId,
                'is_show' => 1,
                'icon'    => 'layui-icon layui-icon-share',
                'des'     => '',
            ],
            [
                'name'    => '删除',
                'rule'    => $this->compatibleApiRoute('/admin.' . $midName . '/del'),
                'is_menu' => 2,
                'pid'     => $menuId,
                'is_show' => 1,
                'icon'    => 'layui-icon layui-icon-share',
                'des'     => '',
            ],
        ];

        $fields = Field::where('table_id', '=', $tableId)->order(['show_sort' => 'desc', 'id' => 'asc'])->select()->toArray();
        foreach($fields as $k=>$v){
            if (($v['form_type'] == 'input' && isset($v['addition']['open_table_edit']) && $v['addition']['open_table_edit'] == 1) || $v['form_type'] == 'switch') {
                $tempMenu = [
                    'name'    => '设置' . $v['comment'],
                    'rule'    => $this->compatibleApiRoute('/admin.' . $midName . '/set' . ucfirst(Str::underlineToCamel($v['field']))),
                    'is_menu' => 2,
                    'pid'     => $menuId,
                    'is_show' => 1,
                    'icon'    => 'layui-icon layui-icon-share',
                    'des'     => '',
                ];
                $arrMenu[] = $tempMenu;
            }
        }

        $hasSoftDelete = Field::where('table_id', '=', $tableId)->where('field', '=', 'delete_time')->find();
        if ($hasSoftDelete) {
            $arrMenu[] =
                [
                    'name'    => '回收站',
                    'rule'    => $this->compatibleApiRoute('/admin.' . $midName . '/recycle'),
                    'is_menu' => 2,
                    'pid'     => $menuId,
                    'is_show' => 1,
                    'icon'    => 'layui-icon layui-icon-share',
                    'des'     => '',
                ];
            $arrMenu[] =
                [
                    'name'    => '还原',
                    'rule'    => $this->compatibleApiRoute('/admin.' . $midName . '/restore'),
                    'is_menu' => 2,
                    'pid'     => $menuId,
                    'is_show' => 1,
                    'icon'    => 'layui-icon layui-icon-share',
                    'des'     => '',
                ];
            $arrMenu[] =
                [
                    'name'    => '真实删除',
                    'rule'    => $this->compatibleApiRoute('/admin.' . $midName . '/trueDel'),
                    'is_menu' => 2,
                    'pid'     => $menuId,
                    'is_show' => 1,
                    'icon'    => 'layui-icon layui-icon-share',
                    'des'     => '',
                ];
        }
        $menu->saveAll($arrMenu);

        return true;
    }

    /**
     * 根据表名获取中间名
     * @param $tableName
     * @return string
     */
    protected function getMidName($tableName)
    {
        $arrTable = explode('_', $tableName);
        $basename = ucfirst($arrTable[count($arrTable) - 1]);
        array_shift($arrTable);
        array_pop($arrTable);
        if (count($arrTable)) {
            $strTable = implode('.', $arrTable) . '.' . $basename;
        } else {
            $strTable = $basename;
        }
        return (substr($strTable, 0, 6) == 'admin.') ? substr($strTable, 6) : $strTable;
    }
}
