<?php

namespace app\controller\admin;

use app\validate\admin\channel\Channel as ChannelValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use app\model\admin\UserList;
use app\model\admin\GatherUserInfo;
use think\facade\Request;
use app\model\admin\App;
use app\model\admin\PromotionMethod;
use app\model\admin\PromotionPlatform;
use app\model\admin\WxOfficialAccount;
use app\model\admin\ChannelDeliveryMode;
use app\model\admin\ChannelConfig;
use app\model\admin\ChannelStatus;

/**
 * 后台渠道控制器
 */
class Channel extends Backend
{
    protected $model;//当前模型对象

    protected $noNeedAuth = ['getChannelListBySource','getTree','getPromotionMethod','getPromotionPlatform','getwxOfficialAccount', 'getChannelConfig'];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\Channel();
    }

    public function getChannelListBySource()
    {
        $source = $this->request->param('source', 2);
        $data = $this->model->field('id,channel_name,app_id')->where('source', $source)->order('id desc')->select();
        return $this->success('数据获取成功', $data);
    }

    public function getPromotionMethod()
    {
        $data = PromotionMethod::field('id,name')->select();
        return $this->success('数据获取成功', $data);
    }

    public function getPromotionPlatform()
    {
        $data = PromotionPlatform::field('id,name')->select();
        return $this->success('数据获取成功', $data);
    }

    public function getwxOfficialAccount()
    {
        $data = WxOfficialAccount::field('id,account_name')->select();
        return $this->success('数据获取成功', $data);
    }

    //查看
    public function index()
    {
        $order = $this->buildOrder();
        $data = $this->buildSearch()->with(['app' => function($query){
            $query->field('id,app_name');
        },'method','platform'])->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        return $this->success('数据获取成功', $data);
    }

    // 渠道增加 应用分类 搜索 @chenlele - 0928 start
    private function buildSearch($isDelete = false, $whereCon = [])
    {
        $app_class_id = $this->request->param('app_class_id');
        $app_id = $this->request->param('app_id');
        $filter = $this->request->param('search_param');
        $filter = !empty($filter) ?  $filter : [];

        $whereCon = !empty($whereCon) ? $whereCon : [];
        extract($filter);
        extract($whereCon);
        if ($isDelete) {
            $model = $this->model->onlyTrashed();
        } else {
            $model = $this->model;
        }
        $name = $this->model->getName();
        $tableName = env('database.prefix') . $name;

        if (isset($app_class_id) && !empty($app_class_id)) {
            $model = $model->withjoin(['app'],'inner');
            $model = $model->where('app.app_class_id', '=', $app_class_id);
        }
        if (isset($app_id) && is_numeric($app_id)) {
            $model = $model->where($tableName.'.app_id', '=', $app_id);
        }
        if (isset($channel_name) && !empty($channel_name)) {
            $model = $model->where($tableName.'.channel_name', 'like', '%' . $channel_name . '%');
        }
        if (isset($is_login_show) && is_numeric($is_login_show)) {
            $model = $model->where($tableName.'.is_login_show', $is_login_show);
        }
        if (isset($is_wx_auth) && is_numeric($is_wx_auth)) {
            $model = $model->where($tableName.'.is_wx_auth', '=', $is_wx_auth);
        }
        if (isset($is_landing_page) && is_numeric($is_landing_page)) {
            $model = $model->where($tableName.'.is_landing_page', '=', $is_landing_page);
        }
        if (isset($is_article_show) && is_numeric($is_article_show)) {
            $model = $model->where($tableName.'.is_article_show', '=', $is_article_show);
        }
        if (isset($platform_id) && is_numeric($platform_id)) {
            $model = $model->where($tableName.'.platform_id', '=', $platform_id);
        }
        if (isset($create_time) && !empty($create_time)) {
            list($startTime, $endTime) = explode(' - ', $create_time);
            $model = $model->where(env('database.prefix').$this->model->getName().'.create_time', 'between', strtotime($startTime). ','. strtotime($endTime));
        }
        return $model;
    }

    public function getTree()
    {
        $channelId = $this->request->param('channel_id');
        $sourceData = GatherUserInfo::order('id asc')->select()->toArray();
        $sourceGatherData = [];
        $channelInfo = $this->model->where('id', $channelId)->find();
        $gatherUserInfoIds = !empty($channelInfo['gather_user_info_ids']) ? json_decode($channelInfo['gather_user_info_ids'], true) : [];
        $gatherParentIds = array_column($gatherUserInfoIds, 'pid');
        foreach ($sourceData as $item) {
            $gatherUserInfo = json_decode($item['gather_info_json'], true);
            $selectPid = false;
            //if(in_array($item['id'],$gatherParentIds)) $selectPid = true;
            $parentData = [
                'id' => $item['id'] . '#',
                'name' => $item['title'],
                'title' => $item['title'],
                'checked' => $selectPid,
                'pid' => 0,
                'children' => []
            ];
            if (!empty($gatherUserInfo)) {
                $gatherChildrens = $this->inArrayKey($gatherUserInfoIds, $item['id'], 'pid');
                $gatherChildrenIds = isset($gatherChildrens[0]['cid']) ? explode(',', $gatherChildrens[0]['cid']) : [];
                foreach ($gatherUserInfo as $v) {
                    $selectCid = false;
                    if (in_array($v['id'], $gatherChildrenIds)) $selectCid = true;
                    $parentData['children'][] =
                        [
                            'id' => '#' . $item['id'] . '_' . $v['id'],
                            'name' => $v['name'],
                            'title' => $v['name'],
                            'checked' => $selectCid,
                            'pid' => $item['id']
                        ];
                }
            }
            $sourceGatherData[] = $parentData;
        }
        return $this->success('获取成功', $sourceGatherData);
    }

    public function inArrayKey($array, $inarray, $field)
    {
        if (!is_array($inarray)) {
            $inarray = explode(',', $inarray);
        }
        $arr = [];
        foreach ($array as $key => $value) {
            if (in_array($value[$field], $inarray)) {
                $arr[] = $value;
            }
        }
        return $arr;
    }

    //添加
    public function add()
    {
        $allowField = ['source','channel_name','channel_app_name','app_id','source_id','user_material_btn_desc','wx_btn_desc','is_login_show',
            'is_landing_page','is_wx_auth','is_under_eighteen_apply','is_article_show','capital_page_position','retention_page_desc','cost_price',
            'gather_user_info_ids','startup_page_image','admin_user_id','is_vest_bag','app_version','is_show_nickname',
            'show_course_title','show_article_title','is_jump_miniprogram','free_landing_page_affirm','pay_landing_page_affirm',
            'course_free_landing_page_affirm','course_pay_landing_page_affirm', 'mc_h5_content','jump_wechat_version','wxmini_path_ids',
            'app_login_btn_desc','app_login_btn_color', 'auth_wx_version','mc_h5_url', 'front_page_id','is_jump_online_service',
            'is_cultivate_menu_show','promotion_id','platform_id','is_part_job_menu_show', 'wx_official_id','part_version','delivery_mode_id','overdue_app_version_type','is_show_personal_statement'
        ];
        $post = CommonFun::filterPostData(Request::only($allowField));
        $gather_ids = $this->request->post('gather_ids');
        $retention_page_desc = $this->request->post('retention_page_desc');
        $app_home_desc = $this->request->post('app_home_desc');
        $overdue_version_home_desc = $this->request->post('overdue_version_home_desc');
        $overdue_version_home_image1 = $this->request->post('overdue_version_home_image1');
        $overdue_version_home_image2 = $this->request->post('overdue_version_home_image2');
        $post['retention_page_desc'] = json_encode($retention_page_desc, JSON_UNESCAPED_UNICODE);
        $post['app_home_desc'] = json_encode($app_home_desc, JSON_UNESCAPED_UNICODE);
        $post['overdue_version_home_desc'] = json_encode($overdue_version_home_desc, JSON_UNESCAPED_UNICODE);
        $post['overdue_version_home_image'] = json_encode([$overdue_version_home_image1, $overdue_version_home_image2], JSON_UNESCAPED_UNICODE);
        $validate = new ChannelValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        $gatherParentIds = [];
        $gatherAllIds = [];
        if(!empty($gather_ids)) {
            $gatherChildrenIds = explode(',', $gather_ids);
            foreach ($gatherChildrenIds as $item) {
                $gatherParentStr1 = substr($item, 0, strrpos($item, "_"));
                $gatherParentIds[] = substr($gatherParentStr1, strripos($gatherParentStr1, "#") + 1);
            }
            $gatherParentIds = array_unique($gatherParentIds);
            $gatherParentIds = array_values($gatherParentIds);
            foreach ($gatherParentIds as $pid) {
                $gatherBelongId = [];
                $gatherBelongStr = '';
                foreach ($gatherChildrenIds as $cid) {
                    if (strpos($cid, '#' . $pid . '_') !== false) {
                        $cid1 = substr($cid, strripos($cid, "_") + 1);
                        $gatherBelongId[] = $cid1;
                        $gatherBelongStr = implode(',', $gatherBelongId);
                    }
                }
                $gatherAllIds[] = ['pid' => $pid, 'cid' => $gatherBelongStr];
            }
        }
        $post['gather_user_info_ids'] = json_encode($gatherAllIds);
//        if($post['source'] == 1) {
//            if(strpos($post['channel_name'],'oppp')) $post['store'] = 'oppo';
//            if(strpos($post['channel_name'],'vivo')) $post['store'] = 'vivo';
//            if(strpos($post['channel_name'],'huawei')) $post['store'] = 'huawei';
//            if(strpos($post['channel_name'],'xiaomi')) $post['store'] = 'xiaomi';
//        }
        $post['store'] = PromotionPlatform::where('id',$post['platform_id'])->value('english_name');
        //if($post['source'] == 2) $post['store'] = substr($post['channel_name'],0,strrpos($post['channel_name'],"_"));
        Db::startTrans();
        try {
            $saveRes = $this->model->save($post);
            ChannelDeliveryMode::createData($this->model->id, isset($post['delivery_mode_id']) ? $post['delivery_mode_id'] : 0);
            ChannelStatus::create(['channel_id' => $this->model->id]);
            if (!$saveRes) throw new \Exception('数据库异常，操作失败');
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
        $id = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        $appClassId = App::where('id', $info['app_id'])->value('app_class_id');
        $gatherUserInfoIds = !empty($info['gather_user_info_ids']) ? json_decode($info['gather_user_info_ids'], true) : [];
        $gatherIds = [];
        foreach ($gatherUserInfoIds as $item) {
            $gatherChildrenIds = explode(',', $item['cid']);
            foreach ($gatherChildrenIds as $cid) {
                $gatherIds[] = '#' . $item['pid'] . '_' . $cid;
            }
        }
        $info['gather_ids'] = implode(',', $gatherIds);
        $info['retention_page_desc'] = json_decode($info['retention_page_desc'], true);
        $info['app_home_desc'] = json_decode($info['app_home_desc'], true);
        $info['overdue_version_home_desc'] = json_decode($info['overdue_version_home_desc'], true);
        $info['overdue_version_home_image'] = json_decode($info['overdue_version_home_image'], true);
        if (!isset($info['retention_page_desc'][0])) {
            $info['retention_page_desc'][0] = '花七秒钟让我们了解你';
        }
        if (!isset($info['retention_page_desc'][1])) {
            $info['retention_page_desc'][1] = '以便帮你推荐合适的教学老师';
        }
        if ($appClassId == 9) {
            if (!isset($info['app_home_desc'][0])) {
                $info['app_home_desc'][0] = '十年专业处理债务逾期问题';
            }
            if (!isset($info['app_home_desc'][1])) {
                $info['app_home_desc'][1] = '业处理债务逾期 不做放贷请知悉';
            }
        } else {
            if (!isset($info['app_home_desc'][0])) {
                $info['app_home_desc'][0] = '边学习边赚钱';
            }
            if (!isset($info['app_home_desc'][1])) {
                $info['app_home_desc'][1] = '推荐兼职 高薪副业 注册领取新人大礼包';
            }
        }
        if (!isset($info['retention_page_desc'][0])) {
            $info['retention_page_desc'][0] = '花七秒钟让我们了解你';
        }
        if (!isset($info['retention_page_desc'][1])) {
            $info['retention_page_desc'][1] = '以便帮你推荐合适的教学老师';
        }
        if (!isset($info['overdue_version_home_desc'][0])) $info['overdue_version_home_desc'][0] = '';
        if (!isset($info['overdue_version_home_desc'][1])) $info['overdue_version_home_desc'][1] = '';
        if (!isset($info['overdue_version_home_image'][0])) $info['overdue_version_home_image'][0] = '';
        if (!isset($info['overdue_version_home_image'][1])) $info['overdue_version_home_image'][1] = '';


        // @date 22.10.18 微信路径选中
        $pathIds = [];
        if (isset($info['wxmini_path_ids']) && $info['wxmini_path_ids']) {
            $pathIds = explode(',', $info['wxmini_path_ids']);
        }

        $info['wxpathIds'] = $pathIds;
        $info['wxpath'] = WxMiniProgram::_getPath($pathIds);

        $pagePathId = $info['front_page_id'] ? [$info['front_page_id']] : [];
        $info['pagePathId'] = $pagePathId;
        $info['frontPagePath'] = FalseFrontPage::_getPage([$info['front_page_id']]);

        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $allowField = ['id','source','channel_name','channel_app_name','app_id','source_id','user_material_btn_desc','wx_btn_desc','is_login_show',
            'is_landing_page','is_wx_auth','is_under_eighteen_apply','capital_page_position','is_article_show','retention_page_desc','cost_price',
            'gather_user_info_ids','startup_page_image','admin_user_id','is_vest_bag','app_version','is_show_nickname',
            'show_course_title','show_article_title','is_jump_miniprogram','free_landing_page_affirm','pay_landing_page_affirm',
            'course_free_landing_page_affirm','course_pay_landing_page_affirm', 'mc_h5_content','jump_wechat_version','wxmini_path_ids',
            'app_login_btn_desc','app_login_btn_color','auth_wx_version','mc_h5_url', 'front_page_id','is_jump_online_service',
            'is_cultivate_menu_show','promotion_id','platform_id','is_part_job_menu_show', 'wx_official_id','part_version','delivery_mode_id','overdue_app_version_type','is_show_personal_statement'
        ];
        $post = CommonFun::filterPostData(Request::only($allowField));
        $gather_ids = $this->request->post('gather_ids');
        $retention_page_desc = $this->request->post('retention_page_desc');
        $app_home_desc = $this->request->post('app_home_desc');
        $overdue_version_home_desc = $this->request->post('overdue_version_home_desc');
        $overdue_version_home_image1 = $this->request->post('overdue_version_home_image1');
        $overdue_version_home_image2 = $this->request->post('overdue_version_home_image2');
        $post['retention_page_desc'] = json_encode($retention_page_desc, JSON_UNESCAPED_UNICODE);
        $post['app_home_desc'] = json_encode($app_home_desc, JSON_UNESCAPED_UNICODE);
        $post['overdue_version_home_desc'] = json_encode($overdue_version_home_desc, JSON_UNESCAPED_UNICODE);
        $post['overdue_version_home_image'] = json_encode([$overdue_version_home_image1, $overdue_version_home_image2], JSON_UNESCAPED_UNICODE);
        $validate = new ChannelValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        $gatherParentIds = [];
        $gatherAllIds = [];
        if(!empty($gather_ids)) {
            $gatherChildrenIds = array_filter(explode(',', $gather_ids));
            foreach ($gatherChildrenIds as $item) {
                $gatherParentStr1 = substr($item, 0, strrpos($item, "_"));
                $gatherParentIds[] = substr($gatherParentStr1, strripos($gatherParentStr1, "#") + 1);
            }
            $gatherParentIds = array_unique($gatherParentIds);
            $gatherParentIds = array_values($gatherParentIds);
            foreach ($gatherParentIds as $pid) {
                $gatherBelongId = [];
                $gatherBelongStr = '';
                foreach ($gatherChildrenIds as $cid) {
                    if (strpos($cid, '#' . $pid . '_') !== false) {
                        $cid1 = substr($cid, strripos($cid, "_") + 1);
                        $gatherBelongId[] = $cid1;
                        $gatherBelongStr = implode(',', $gatherBelongId);
                    }
                }
                $gatherAllIds[] = ['pid' => $pid, 'cid' => $gatherBelongStr];
            }
        }
        $post['gather_user_info_ids'] = json_encode($gatherAllIds);

//        if($post['source'] == 1){
//            if(strpos($post['channel_name'],'oppp')) $post['store'] = 'oppo';
//            if(strpos($post['channel_name'],'vivo')) $post['store'] = 'vivo';
//            if(strpos($post['channel_name'],'huawei')) $post['store'] = 'huawei';
//            if(strpos($post['channel_name'],'xiaomi')) $post['store'] = 'xiaomi';
//        }
        $post['store'] = PromotionPlatform::where('id',$post['platform_id'])->value('english_name');
        //if($post['source'] == 2) $post['store'] = substr($post['channel_name'],0,strrpos($post['channel_name'],"_"));
        Db::startTrans();
        try {
            $channel = $this->model->findOrEmpty($post['id']);
            if (!$channel) throw new \Exception('id参数错误');
            $updateRes = $channel->update($post);
            ChannelDeliveryMode::createData($post['id'], isset($post['delivery_mode_id']) ? $post['delivery_mode_id'] : 0);
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
        try {
            if ($this->model->destroy($ids)) {
                return $this->success('数据删除成功');
            } else {
                return $this->error('数据删除失败');
            }
        } catch (\Exception $e) {
            return $this->exceptionError($e);
        }
    }
    
    //设置多次报名
    public function setIsMoreApply()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_more_apply'] = $fieldVal;
        try {
            if ($isRecycle) {
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
    
    //设置是否显示个人信息声明
    public function setIsShowPersonalStatement()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_show_personal_statement'] = $fieldVal;
        try {
            if ($isRecycle) {
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
    
    //设置报名是否跳落地页
    public function setIsLdy()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_ldy'] = $fieldVal;
        try {
            if ($isRecycle) {
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
    
    
    //设置app启动登录状态
    public function setIsLoginShow()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_login_show'] = $fieldVal;
        try {
            if ($isRecycle) {
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

    //设置落地页状态
    public function setIsLandingPage()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_landing_page'] = $fieldVal;
        try {
            if ($isRecycle) {
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

    //设置微信授权状态
    public function setIsWxAuth()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_wx_auth'] = $fieldVal;
        try {
            if ($isRecycle) {
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

    //设置文章状态按钮
    public function setIsArticleShow()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_article_show'] = $fieldVal;
        try {
            if ($isRecycle) {
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

    //设置是否开启个别商户加速进量
    public function setIsSpeedFeed()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_speed_feed'] = $fieldVal;
        try {
            if ($isRecycle) {
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

    //设置获客链接
    public function setIsCustomerLink()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_customer_link'] = $fieldVal;
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
    //设置悬浮按钮
    public function setIsSuspensionBtn()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_suspension_btn'] = $fieldVal;
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
    
    //设置是否开启个别商户加速进量
    public function setIsSlow()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_slow'] = $fieldVal;
        try {
            if ($isRecycle) {
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
    
    //设置是否交付
    public function setIsDelivery()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_delivery'] = $fieldVal;
        $update['delivery_time'] = time();
        $model = new ChannelStatus();
        try {
            if ($isRecycle) {
                $updateRes = $model->onlyTrashed()->where('channel_id', '=', $id)->update($update);
            } else {
                $updateRes = $model->where('channel_id', '=', $id)->update($update);
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
    
   //设置是否上架
    public function setIsPutaway()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_putaway'] = $fieldVal;
        $update['putaway_time'] = time();
        $model = new ChannelStatus();
        try {
            if ($isRecycle) {
                $updateRes = $model->onlyTrashed()->where('channel_id', '=', $id)->update($update);
            } else {
                $updateRes = $model->where('channel_id', '=', $id)->update($update);
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
    //设置是否开户
    public function setIsOpen()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_open'] = $fieldVal;
        $update['open_time'] = time();
        $model = new ChannelStatus();
        try {
            if ($isRecycle) {
                $updateRes = $model->onlyTrashed()->where('channel_id', '=', $id)->update($update);
            } else {
                $updateRes = $model->where('channel_id', '=', $id)->update($update);
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
    //设置是否投放
    public function setIsPut()
    {
        $id = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['is_put'] = $fieldVal;
        $update['put_time'] = time();
        $model = new ChannelStatus();
        try {
            if ($isRecycle) {
                $updateRes = $model->onlyTrashed()->where('channel_id', '=', $id)->update($update);
            } else {
                $updateRes = $model->where('channel_id', '=', $id)->update($update);
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
    
    public function setChannelAppName()
    {
        $id = $this->request->post('id');
        $channelAppName = trim($this->request->post('channel_app_name'));
        if(!$channelAppName){
            return $this->error('渠道应用名称不能为空');
        }
        $update['channel_app_name'] = $channelAppName;
        try {
            $updateRes = $this->model->where('id', '=', $id)->update($update);
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
    public function setNewMediaAutoMsgChannelName()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['new_media_auto_msg_channel_name'] = $fieldVal;
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
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $limit = $this->request->param('limit', 10);
        $data = $this->model->onlyTrashed()
            ->with(['app' => function ($query) {
                $query->field('id,app_name');
            }])
            ->order($order)->where($where)->paginate($limit)->toArray();
        return $this->success('回收站数据获取成功', $data);
    }
    public function getChannelConfig()
    {
        $channelId = $this->request->param('channel_id');
        $model = new ChannelConfig();
        $defaultinfo = [
            'channel_id' => $channelId,
            'sms_id' => 0,
            'chat_avatar' => '',
            'chat_nickname' => '',
            'chat_autoreply_message' => '',
            'chat_backgroud_color' => '',
            'chat_page' => '',
            'chat_robot_avatar' => '',
            'chat_artificial_avatar' => '',
        ];
        $info = $model->where('channel_id', $channelId)->findOrEmpty()->toArray();
        return $this->success('获取成功', !empty($info) ? $info : $defaultinfo);
    }
    public function setChannelConfig()
    {
        $post = $this->request->post('');
       // var_dump($post);die;
        $channelConfigInfo = ChannelConfig::where('channel_id', $post['channel_id'])->find();
        $ret = !empty($channelConfigInfo) ? $channelConfigInfo->save($post) : ChannelConfig::create($post);
        return $ret !== false ? $this->success('操作成功') : $this->error('数据库异常，操作失败');
    }
}