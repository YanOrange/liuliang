<?php

namespace app\controller\admin;

use app\validate\admin\landingpage\LandingPage as LandingPageValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Event;
use think\facade\Request;
/**
 * 后台落地页控制器
 */
class LandingPage extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\LandingPage();
    }
    //查看
    public function index()
    {
        $order = $this->buildOrder();
        $whereCon = [];
        $appClassId = $this->request->param('app_class_id', 0);
        if ($appClassId) {
            $whereCon[] = ['app_class_id', '=', $appClassId];
        }
        $data = $this->buildSearch()->where($whereCon)->with(['app' => function($query){
            $query->field('id,app_name,app_class_id');
            $query->with(['class' => function($query){
                $query->field('id,app_class_name');
            }]);
        },'course' => function($query){
            $query->field('id,title,video_url,merchant_id,app_ids,entry_fee');
            $query->with(['merchant' => function($query){
                $query->field('id,merchant_name,app_class_id');
            }]);
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

    // 构建查询条件
    private function buildSearch($isDelete = false)
    {
        $filter = $this->request->param('search_param') ?? [];
        extract($filter);
        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;
        if ($isDelete) {
            $landingPageModel = $this->model->onlyTrashed();
        } else {
            $landingPageModel = $this->model;
        }
        if (isset($channel_ids) && !empty($channel_ids)) {
            $landingPageModel = $landingPageModel->whereFindInSet('channel_ids', $channel_ids);
        }
        if (isset($channel_ids) && !empty($channel_ids)) {
            $landingPageModel = $landingPageModel->whereFindInSet('channel_ids', $channel_ids);
        }
        if (isset($app_id) && !empty($app_id)) {
            $landingPageModel = $landingPageModel->where('app_id', $app_id);
        }
        if (isset($landing_page_type) && !empty($landing_page_type)) {
            $landingPageModel = $landingPageModel->where('landing_page_type', '=', $landing_page_type);
        }
        if (isset($course_id) && !empty($course_id)) {
            $landingPageModel = $landingPageModel->where('course_id', '=', $course_id);
        }
        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $landingPageModel = $landingPageModel->where('create_time', 'between', strtotime($startTime). ','. strtotime($endTime));
        }
        if (isset($merchant_id) && !empty($merchant_id)) {
            $landingPageModel = $landingPageModel->whereExists(function ($query) use ($tableName, $merchant_id) {
                $courseTableName = (new \app\model\admin\Course())->getName();
                $query = $query->table(env('database.prefix') .$courseTableName)->where(env('database.prefix') . $courseTableName . '.id=' .   $tableName . '.course_id');
                $query->whereExists(function($query) use($courseTableName, $merchant_id){
                    $merchantTableName = (new \app\model\admin\Merchant())->getName();
                    $query = $query->table(env('database.prefix') .$merchantTableName)->where(env('database.prefix') . $merchantTableName . '.id=' .   env('database.prefix') . $courseTableName . '.merchant_id');
                    $query = $query->where('merchant_id', '=', $merchant_id);
                    return $query;
                });
            });
        }
        if (isset($app_class_id) && !empty($app_class_id)) {
            $landingPageModel = $landingPageModel->whereExists(function ($query) use ($tableName, $app_class_id) {
                $appTableName = (new \app\model\admin\App())->getName();
                $query = $query->table(env('database.prefix') .$appTableName)->where(env('database.prefix') . $appTableName. '.id=' .   $tableName . '.app_id');
                $query->whereExists(function($query) use($appTableName, $app_class_id){
                    $appClassTableName = (new \app\model\admin\AppClass())->getName();
                    $query = $query->table(env('database.prefix') .$appClassTableName)->where(env('database.prefix') . $appClassTableName . '.id=' .   env('database.prefix') . $appTableName . '.app_class_id');
                    $query = $query->where('id', '=', $app_class_id);
                    return $query;
                });
            });
        }
        return $landingPageModel;
    }

    //添加
    public function add()
    {
        // 过滤渠道、应用ID，前端#38_82 @chenlele 2926 start
        $allowField = ['id', 'landing_page_type', 'is_pay', 'video_url', 'laytpUploadFile', 'app_id', 'expose_period_num', 'weight',
            'btn_desc', 'channel_ids', 'course_id', 'is_lamp', 'landing_image', 'lamp_back_image', 'end_image','a_expose_num','b_expose_num',
            'lamp_font_color','not_wx_landing_image','not_wx_desc_image', 'desc_image', 'expose_num','is_abscheme','a_is_lamp','a_landing_images',
            'a_video_url','a_lamp_back_image','a_end_image','a_lamp_font_color','a_desc_image','b_is_lamp','b_landing_images','b_video_url',
            'b_lamp_back_image','b_end_image','b_lamp_font_color','b_desc_image','ldy_btn_desc','ldylz_option_btn_desc','ldylz_btn_color',
            'ldylz_option_color','video_cover_image','btn_gif','is_region','external_copy','retention_method'
        ];
        $post = CommonFun::filterPostData(Request::only($allowField));

        // 渠道ID多选
        $cIds = [];
        $channelIds = $this->request->post('channel_ids');
        $channelIds = array_filter(explode(',', $channelIds));
        foreach ($channelIds as $ids) {
            $idsArr = explode('_', $ids);
            $cIds[] = (isset($idsArr[1])) ? $idsArr[1] : '';
        }
        $post['channel_ids'] = implode(',', $cIds);

        // 应用ID单选
        $appIds = $this->request->post('app_id');
        $appid = explode('_', $appIds);
        $post['app_id'] = (isset($appid[1])) ? $appid[1] : 0;
        if ($post['landing_page_type'] == 2 && !$post['app_id']) return $this->error('请选择关联的应用');
        // 过滤渠道、应用ID，前端#38_82 @chenlele 2926 end

        $validate = new LandingPageValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
//        if($post['is_lamp'] != 1){
//            unset($post['lamp_back_image']);
//            unset($post['end_image']);
//            unset($post['lamp_font_color']);
//        }
        if ($post['landing_page_type'] == 2 && $post['is_abscheme'] == 1) {
            if (empty($post['a_video_url'])) {
                return $this->error('请上传a方案视频');
            }
            if (empty($post['b_video_url'])) {
                return $this->error('请上传b方案视频');
            }
        }
        if ($post['a_is_lamp'] == 1 && $post['is_abscheme'] == 1) {
            if (empty($post['a_lamp_back_image'])) {
                return $this->error('请上传a方案跑马灯背景图');
            }
            if (empty($post['a_end_image'])) {
                return $this->error('请上传a方案落地页位图');
            }
        }
        if ($post['b_is_lamp'] == 1 && $post['is_abscheme'] == 1) {
            if (empty($post['b_lamp_back_image'])) {
                return $this->error('请上传b方案跑马灯背景图');
            }
            if (empty($post['b_end_image'])) {
                return $this->error('请上传b方案落地页位图');
            }
        }

        Db::startTrans();
        try {
            $saveRes = $this->model->save($post);
            if (!$saveRes) throw new \Exception('数据库异常，操作失败');
            Event::trigger('landingPageAdd', [
                'landingPage' => $this->model,
            ]);
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('数据库异常，操作失败');
        }
    }

    //查看详情
    public function info()
    {
        $id   = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        // 过滤渠道、应用ID，前端#38_82 @chenlele 2926 start
        $allowField = ['id', 'landing_page_type', 'is_pay', 'video_url', 'laytpUploadFile', 'app_id', 'expose_period_num', 'weight',
            'btn_desc', 'channel_ids', 'course_id', 'is_lamp', 'landing_image', 'lamp_back_image', 'end_image','a_expose_num','b_expose_num',
            'lamp_font_color', 'desc_image', 'not_wx_landing_image','not_wx_desc_image','expose_num','is_abscheme','a_is_lamp','a_landing_images',
            'a_video_url','a_lamp_back_image','a_end_image','a_lamp_font_color','a_desc_image','b_is_lamp','b_landing_images','b_video_url',
            'b_lamp_back_image','b_end_image','b_lamp_font_color','b_desc_image','ldy_btn_desc','ldylz_option_btn_desc','ldylz_btn_color',
            'ldylz_option_color','video_cover_image','btn_gif','is_region','external_copy','retention_method'
        ];
        $post = CommonFun::filterPostData(Request::only($allowField));
        if (substr($post['video_url'], 0, 4) !== 'http') {
            $post['video_url'] = 'http://cdnwm.yuluojishu.com' . $post['video_url'];
        }
        // 渠道ID多选
        $cIds = [];
        $channelIds = $this->request->post('channel_ids');
        $channelIds = array_filter(explode(',', $channelIds));
        foreach ($channelIds as $ids) {
            $idsArr = explode('_', $ids);
            $cIds[] = (isset($idsArr[1])) ? $idsArr[1] : '';
        }
        $post['channel_ids'] = implode(',', $cIds);

        // 应用ID单选
        $appIds = $this->request->post('app_id');
        $appid = explode('_', $appIds);
        $post['app_id'] = (isset($appid[1])) ? $appid[1] : 0;
        if ($post['landing_page_type'] == 2 && !$post['app_id']) return $this->error('请选择关联的应用');
        // 过滤渠道、应用ID，前端#38_82 @chenlele 2926 end

        $validate = new LandingPageValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
//        if($post['is_lamp'] != 1){
//            unset($post['lamp_back_image']);
//            unset($post['end_image']);
//            unset($post['lamp_font_color']);
//        }
        if ($post['landing_page_type'] == 2 && $post['is_abscheme'] == 1) {
            if (empty($post['a_video_url'])) {
                return $this->error('请上传a方案视频');
            }
            if (empty($post['b_video_url'])) {
                return $this->error('请上传b方案视频');
            }
        }
        if ($post['a_is_lamp'] == 1 && $post['is_abscheme'] == 1) {
            if (empty($post['a_lamp_back_image'])) {
                return $this->error('请上传a方案跑马灯背景图');
            }
            if (empty($post['a_end_image'])) {
                return $this->error('请上传a方案落地页尾图');
            }
        }
        if ($post['b_is_lamp'] == 1 && $post['is_abscheme'] == 1) {
            if (empty($post['b_lamp_back_image'])) {
                return $this->error('请上传b方案跑马灯背景图');
            }
            if (empty($post['b_end_image'])) {
                return $this->error('请上传b方案落地页尾图');
            }
        }
        Db::startTrans();
        try {
            $landingPage = $this->model->findOrEmpty($post['id']);
            if (!$landingPage) throw new \Exception('id参数错误');
            $updateRes  = $landingPage->update($post);
            if (!$updateRes) throw new \Exception('数据库异常，操作失败');
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }
    //删除
    public function del()
    {
        $ids = array_filter($this->request->param('ids'));
        if (!$ids) {
            return $this->error('参数ids不能为空');
        }
        try{
            if ($this->model->destroy($ids)) {
                Event::trigger('LandingPageDel', [
                    'landingPageIds' => $ids,
                ]);
                return $this->success('数据删除成功');
            } else {
                return $this->error('数据删除失败');
            }
        }catch (\Exception $e){
            return $this->exceptionError($e);
        }
    }
    //设置课程状态
    public function setStatus()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['status'] = $fieldVal;
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
    //设置排序
    public function setSort()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['sort'] = $fieldVal;
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

    //设置课程状态
    public function setIsLamp()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_lamp'] = $fieldVal;
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

    //回收站
    public function recycle()
    {
        $order = $this->buildOrder();
        $limit = $this->request->param('limit', 10);
        $data  = $this->buildSearch(true)->onlyTrashed()
            ->with(['app' => function($query){
                $query->field('id,app_name,app_class_id');
            },'course' => function($query){
                $query->field('id,title,video_url,merchant_id,app_ids,entry_fee');
                $query->with(['merchant' => function($query){
                    $query->field('id,merchant_name,app_class_id');
                }]);
            }])
            ->order($order)->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }
}