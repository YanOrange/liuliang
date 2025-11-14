<?php
return[[
    'name'=>'生成CURD',
    'href'=>'/admin/plugin/curd/index.html',
    'is_menu'=>1,
    'icon'=>'fa fa-code',
    'children'=>[
        ['name'=>'获取数据表列表', 'rule'=>'/plugin/curd/curd/getTableList', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'添加表', 'rule'=>'/plugin/curd/curd.table/add', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'获取表详情', 'rule'=>'/plugin/curd/curd.table/info', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'编辑表', 'rule'=>'/plugin/curd/curd.table/edit', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'添加字段', 'rule'=>'/plugin/curd/curd/index', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'删除字段', 'rule'=>'/plugin/curd/curd.field/del', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'获取字段列表', 'rule'=>'/plugin/curd/curd/getFieldList', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'生成常规CURD', 'rule'=>'/plugin/curd/curd/createNormalCurd', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'生成无限级分类CURD', 'rule'=>'/plugin/curd/curd/createCategoryCurd', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'设置排序', 'rule'=>'/plugin/curd/curd.field/setShowSort', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
    ]
]];