<?php

namespace plugin\apidoc\controller;

use laytp\controller\Backend;
use laytp\library\CommonFun;
use laytp\library\UploadDomain;

class Index extends Backend
{
    /**
     * apidoc模型对象
     * @var \plugin\devtool\model\Apidoc
     */
    protected $model;
    public $hasSoftDel = 1;//是否拥有软删除功能
    protected $noNeedLogin = ['getMenu'];

    public function initialize()
    {
        parent::initialize();
        $this->model = new \plugin\apidoc\model\Apidoc();
    }

    //添加
    public function add()
    {
        $post        = CommonFun::filterPostData($this->request->post());
        $post['des'] = UploadDomain::delUploadDomain($post['des']);
        if ($this->model->create($post)) {
            return $this->success('添加成功', $post);
        } else {
            return $this->error('操作失败');
        }
    }

    //编辑
    public function edit()
    {
        $id          = $this->request->param('id');
        $info        = $this->model->find($id);
        $post        = CommonFun::filterPostData($this->request->post());
        $post['des'] = UploadDomain::delUploadDomain($post['des']);
        foreach ($post as $k => $v) {
            $info->$k = $v;
        }
        try {
            $updateRes = $info->save();
            if ($updateRes) {
                return $this->success('编辑成功');
            } else if ($updateRes === 0) {
                return $this->success('未做修改');
            } else if ($updateRes === null) {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }

    //生成常规CURD
    public function create()
    {
        $api = new \plugin\apidoc\library\Apidoc();
        if ($api->execute('api文档')) {
            return $this->success('生成成功');
        } else {
            return $this->error($api->getError());
        }
    }

    // 获取文档菜单接口
    public function getMenu()
    {
        $menuOutputFile  = app()->getRootPath() . 'public' . DS . 'static' . DS . 'admin' . DS . 'data' . DS . 'apidocMenu.json';
        $menuJson = file_get_contents($menuOutputFile);
        $menu = json_decode($menuJson, true);
        return $this->success('生成成功', $menu);
    }
}
