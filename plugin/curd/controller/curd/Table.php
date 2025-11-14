<?php

namespace plugin\curd\controller\curd;

use laytp\controller\Backend;

class Table extends Backend
{
    protected $model;//当前模型对象
    protected $noNeedAuth = [];

    protected function _initialize()
    {
        $this->model = new \plugin\curd\model\curd\Table();
    }
}