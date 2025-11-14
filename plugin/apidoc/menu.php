<?php
return[[
    'name'=>'生成Api文档',
    'href'=>'/admin/plugin/apidoc/index.html',
    'is_menu'=>1,
    'icon'=>'layui-icon layui-icon-read',
    'children'=>[
        ['name'=>'查看和搜索列表', 'rule'=>'/plugin/apidoc/index/index', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'查看单条数据详情', 'rule'=>'/plugin/apidoc/index/info', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'添加', 'rule'=>'/plugin/apidoc/index/add', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'编辑', 'rule'=>'/plugin/apidoc/index/edit', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'生成文档', 'rule'=>'/plugin/apidoc/index/create', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'删除', 'rule'=>'/plugin/apidoc/index/del', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'回收站', 'rule'=>'/plugin/apidoc/index/recycle', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'还原', 'rule'=>'/plugin/apidoc/index/restore', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
        ['name'=>'真实删除', 'rule'=>'/plugin/apidoc/index/trueDel', 'is_menu'=>2, 'icon'=>'layui-icon layui-icon-fire'],
    ]
]];