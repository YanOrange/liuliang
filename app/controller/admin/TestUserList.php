<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use app\validate\admin\channel\Channel as ChannelValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Config;
use app\model\admin\Thread;
/**
 * 后台用户控制器
 */
class TestUserList extends Backend
{
    protected $noNeedLogin = ['*'];
    protected $noNeedCheckSign = ['*'];

    /**
     * @var \app\model\admin\UserList
     */
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\UserList();
    }
    //查看
    public function index()
    {
        $params = $this->request->get();

        $testUserPhone = Config::load("extra/test/userphone", "extra") ?? [];

        $dbData = $this->model->field(['id', 'phone', 'channel', 'login_time', 'age_range_id', 'identity_id', 'education_id'])
            ->whereIn('phone', $testUserPhone)
            ->when(isset($params['phone']) && $params['phone'], function ($query) use ($params) {
                return $query->where('phone', 'like', "%{$params['phone']}%");
            });

        $allData =$params['all_data'] ?? 0;
        if ($allData) {
            $data = $dbData->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $dbData->paginate($limit)->toArray();
        }

        return $this->success('数据获取成功', $data);
    }

    //删除
    public function del()
    {
        $ids = array_filter($this->request->param('ids'));
        if (!$ids) {
            return $this->error('参数ids不能为空');
        }

        $testUserPhone  = Config::load("extra/test/userphone", "extra") ?? [];

        $userListIds    = $this->model->whereIn('id', $ids)->whereIn('phone', $testUserPhone)->column('id');
        $threadIds      = Thread::whereIn('uid', $userListIds)->column('id');

        try{
            if ($this->model->destroy($userListIds)) {
                Thread::destroy($threadIds);
                return $this->success('数据删除成功');
            } else {
                return $this->error('数据删除失败');
            }
        }catch (\Exception $e){
            return $this->exceptionError($e);
        }
    }
}