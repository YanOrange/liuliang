<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use app\service\ConfServiceFacade;
use laytp\library\UploadDomain;
use plugin\ali_oss\service\Oss;
use plugin\qiniu_kodo\service\Kodo;
use laytp\controller\Backend;
use think\facade\Env;
use think\facade\Filesystem;
use think\File;
use app\service\admin\AuthServiceFacade;
use app\model\admin\Merchant;
use app\lib\api\ocr\Ocr;
class CommonApi extends Backend
{
    use Ocr;

    protected $noNeedAuth = ['*'];
    protected $noNeedLogin = [''];

    //OCR图片识别
    public function ocrImageToText()
    {
        $imageUrl = $this->request->post('imageUrl');
        if (empty($imageUrl)) {
            return $this->error('识别图片为空');
        }
        return $this->success('OCR图片识别',$this->ocrImage($imageUrl));

    }
    //获取客服列表
    public function getCustomerList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $model = new \app\model\admin\Customer();

        // 干事：管理自己负责得商户 2022-09-02
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);

        if ($loginId != 1 && in_array(env('ROLE.GANSHI'), $roleIds)) {
            // 8 - 干事：管理自己负责的客户
            $merchantIds = Merchant::whereFindInSet('admin_ids', $loginId)->column('id');
            $where[] = ['merchant_id', 'in', $merchantIds];
        }

        $data = $model->field('id,nickname,merchant_id')->where($where)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }
    //获取商户列表
    public function getMerchantList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $model = new \app\model\admin\Merchant();

        /*// 干事：管理自己负责得商户 2022-09-02
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);

        if ($loginId != 1) {
            // 8 - 干事：管理自己负责的客户
            if (in_array(env('ROLE.GANSHI'), $roleIds)) {
                $where[] = ['admin_ids', '=', $loginId];
            }

            // 客服主管：看站内数据 @chenlele 0929
            if (in_array(env('ROLE.CUSTOMERLEADER'), $roleIds)) {
                $where[] = ['is_source', '=', 1];
            }
        }*/

        $data = $model->field('id,merchant_name')->where($where)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }
    //获取应用列表
    public function getAppList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $model = new \app\model\admin\App();
        $data = $model->field('id,app_name')->where($where)->with(['class' => function($query) {
            $query->field('id,app_class_name');
        }])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //获取课程列表
    public function getCourseList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $model = new \app\model\admin\Course();
        $data = $model->field('id,title,merchant_id')->where($where)->where('course_type', 0)->with(['merchant' => function($query) {
            $query->field('id,merchant_name');
        }])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        foreach ($data as $item => $val) {
            $data[$item]['course_merchant_name'] = isset($val['merchant']['merchant_name']) ? '(' . $val['merchant']['merchant_name'] . ')' . $val['title'] : $val['title'];
        }
        return $this->success('数据获取成功', $data);
    }


    //获取渠道列表
    public function getChannelList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $model = new \app\model\admin\Channel();
        $data = $model->field('id,channel_name,app_id')->where($where)->with(['app' => function($query) {
            $query->field('id,app_name');
        }])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //获取类目列表
    public function getAppClassList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $model = new \app\model\admin\AppClass();
        $data = $model->field('id,app_class_name')->where($where)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //获取外部线索来源列表
    public function getExternalSourceList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $model = new \app\model\admin\thread\ThreadSource();
        $data = $model->field('id,title')->where($where)->where('status',1)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }


    //获取微信小程序列表
    public function getWxMiniProgramList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $model = new \app\model\admin\WxMiniProgram();
        $data = $model->field('id,wxmini_name')->where($where)->where('wxmini_status',1)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }


    //获取部门列表
    public function getDepartmentList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $model = new \app\model\api\Department();
        $data = $model->field('id,department_name')->where($where)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //获取新媒体平台列表
    public function getNewMediaPlatformList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $model = new \app\model\admin\NewMediaPlatform();
        $data = $model->field('id,platform_name')->where($where)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    //获取新媒体平台列表
    public function getNewMediaAccountList()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $model = new \app\model\admin\WxOfficialAccount();
        $data = $model->field('id,platform_id,account_name')->with(['platform'])->where($where)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }
    //获取逾期APP版本类型列表
    public function getSmsConfig()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $model = new \app\model\admin\SubmailSmsConfig();
        $data = $model->field('id,sign_name')->where($where)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }
    //获取逾期APP版本类型列表
    public function getOverdueAppVersionTypeList()
    {
        $data = [
            ['title' => '编程改逾期', 'field' => 'programmeToOverdue'],
            ['title' => '抄立刻搞定逾期', 'field' => 'copyLkgdyq'],
            ['title' => '抄立刻搞定逾期改橙色案例', 'field' => 'copyLkgdyqOrange'],
            ['title' => '橙色服务凹陷导航', 'field' => 'orangeServiceAxdh'],
            ['title' => '橙色服务凹陷导航拷贝-9.14', 'field' => 'orangeServiceAxdhCopy0914'],
            ['title' => '王帅逾期中秋版', 'field' => 'wsOverdueZq'],
            ['title' => '逾期马甲-9.9', 'field' => 'overdueVest99'],
        ];
        return $this->success('数据获取成功', $data);
    }
}
