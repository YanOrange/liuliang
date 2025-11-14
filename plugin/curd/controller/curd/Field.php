<?php

namespace plugin\curd\controller\curd;

use laytp\controller\Backend;

class Field extends Backend
{
    protected $model;//当前模型对象
    protected $noNeedAuth = [];
    protected $orderRule = ['show_sort'=>'desc','id'=>'asc'];

    protected function _initialize()
    {
        $this->model = new \plugin\curd\model\curd\Field();
    }

    //添加
    public function add()
    {
        $post = $this->request->post();
        if ($post['relation']['table']) {
            if (!$post['relation']['type']) {
                return $this->error('关联方式不能为空');
            }
            if (!$post['relation']['field']) {
                return $this->error('关联字段不能为空');
            }
            if (!$post['relation']['show_field']) {
                return $this->error('关联显示字段不能为空');
            }
            if (!$post['relation']['fun_name']) {
                return $this->error('关联函数名不能为空');
            }
        }

        if (array_key_exists('addition', $post)) {
            if ($post['form_type'] === 'checkbox') {
                $default = [];
                foreach ($post['addition']['value'] as $k => $v) {
                    if (isset($post['addition']['default'][$k])) {
                        $default[$k] = $post['addition']['value'][$k];
                    }
                }
                sort($default);
                $post['addition']['default'] = $default;
            } else if (in_array($post['form_type'], ['radio', 'select'])) {
                $default = 0;
                foreach ($post['addition']['value'] as $k => $v) {
                    if (isset($post['addition']['default'][$k])) {
                        $default = $post['addition']['value'][$k];
                    }
                }
                $post['addition']['default'] = $default;
            } else if ($post['form_type'] === 'upload') {
                $post['addition']['width']  = intval($post['addition']['width']);
                $post['addition']['height'] = intval($post['addition']['height']);
            } else if ($post['form_type'] === 'xm_select' && $post['addition']['data_from_type'] === 'data') {
                $default = [];
                foreach ($post['addition']['value'] as $k => $v) {
                    if (isset($post['addition']['default'][$k])) {
                        $default[] = $post['addition']['value'][$k];
                    }
                }
                $post['addition']['default'] = $default;
            }
            $post['addition'] = json_encode($post['addition'], JSON_UNESCAPED_UNICODE);
        } else {
            $post['addition'] = '';
        }

        $post['relation'] = json_encode($post['relation'], JSON_UNESCAPED_UNICODE);

        if ($this->model->create($post)) {
            return $this->success('添加成功', $post);
        } else {
            return $this->error('操作失败');
        }
    }

    //编辑
    public function edit()
    {
        $id   = $this->request->param('id');
        $info = $this->model->find($id);
        $post = $this->request->post();
        if ($post['relation']['table']) {
            if (!$post['relation']['type']) {
                return $this->error('关联方式不能为空');
            }
            if (!$post['relation']['field']) {
                return $this->error('关联字段不能为空');
            }
            if (!$post['relation']['show_field']) {
                return $this->error('关联显示字段不能为空');
            }
            if (!$post['relation']['fun_name']) {
                return $this->error('关联函数名不能为空');
            }
        }
        if (array_key_exists('addition', $post)) {
            if ($post['form_type'] === 'checkbox') {
                $default = [];
                foreach ($post['addition']['value'] as $k => $v) {
                    if (isset($post['addition']['default'][$k])) {
                        $default[$k] = $post['addition']['value'][$k];
                    }
                }
                sort($default);
                $post['addition']['default'] = $default;
            } else if (in_array($post['form_type'], ['radio', 'select'])) {
                $default = 0;
                foreach ($post['addition']['value'] as $k => $v) {
                    if (isset($post['addition']['default'][$k])) {
                        $default = $post['addition']['value'][$k];
                    }
                }
                $post['addition']['default'] = $default;
            } else if ($post['form_type'] === 'upload') {
                $post['addition']['width']  = intval($post['addition']['width']);
                $post['addition']['height'] = intval($post['addition']['height']);
            } else if ($post['form_type'] === 'xm_select' && $post['addition']['data_from_type'] === 'data') {
                $default = [];
                foreach ($post['addition']['value'] as $k => $v) {
                    if (isset($post['addition']['default'][$k])) {
                        $default[] = $post['addition']['value'][$k];
                    }
                }
                $post['addition']['default'] = $default;
            }
            $post['addition'] = json_encode($post['addition'], JSON_UNESCAPED_UNICODE);
        } else {
            $post['addition'] = '';
        }
        $post['relation'] = json_encode($post['relation'], JSON_UNESCAPED_UNICODE);
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

    //设置单行输入框（可编辑）
    public function setShowSort()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['show_sort'] = $fieldVal;
        try {
            if($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }
            if ($updateRes) {
                return $this->success('操作成功');
            } else if ($updateRes === 0) {
                return $this->success('未作修改');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('数据库异常，操作失败');
        }
    }
}