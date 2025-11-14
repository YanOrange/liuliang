<?php

namespace app\controller\admin;

use app\validate\admin\app\App as AppValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use app\model\admin\UserList;
use app\model\admin\Thread;

/**
 * 后台控制面板控制器
 */
class Console extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\App();
    }
    //查看
    public function index()
    {
        $totalRegisterUserNums = UserList::count();
        $todayRegisterUserNums = UserList::whereTime('create_time', 'today')->count();
        $totalThreadNums = Thread::count();
        $todayThreadNums = Thread::whereTime('create_time', 'today')->count();
        return $this->success('数据获取成功', [
            'total_register_user_nums' => $totalRegisterUserNums,
            'today_register_user_nums' => $todayRegisterUserNums,
            'total_thread_nums' => $totalThreadNums,
            'today_thread_nums' => $todayThreadNums,
        ]);
    }

}