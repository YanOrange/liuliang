<?php
/**
 * 线索用户状态关联表模型
 */

namespace app\model\admin\thread;

use app\lib\api\exception\Exception;
use app\model\admin\Thread;
use app\service\admin\UserServiceFacade;
use laytp\BaseModel;
use think\facade\Db;
use think\model\concern\SoftDelete;

class ThreadUserStatus extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'thread_user_status';

    /**
     * 获取用户状态列表
     * @param array $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getThreadUserStatus($params = [])
    {
        extract($params);
        if(isset($source_id) && $source_id == 0){
            $thread = Thread::field('id,merchant_id')->with(['merchant'=>function($query){
                $query->field('id,app_class_id');
            }])->where('id',$id)->find();
        }else{
            $thread = ThreadExternal::field('id,merchant_id')->with(['merchant'=>function($query){
                $query->field('id,app_class_id');
            }])->where('id',$id)->find();
        }
        if(empty($thread) || empty($thread['merchant'])){
            return [];
        }
        $app_class_id = $thread['merchant']['app_class_id'];
        $where = [
            ['merchant_id', '=', $thread['merchant_id']],
            ['app_class_id' ,'=', $app_class_id],
            ['status', '=', 1],
            ['level', '=', 1],
        ];
        $whereOr[] = [
            ['merchant_id', '=', 0],
            ['is_sys', '=', 1],
            ['app_class_id' ,'=', $app_class_id],
            ['pid', '=', 0],
        ];
        $actionData = ThreadFollowAction::field('id,title')->where($where)->whereOr($whereOr)->order('sort desc')->select()->toArray();
        if (!empty($actionData)) {
            foreach ($actionData as $key => $val) {
                $status_id = 0;
                $select_id = 0;
                $select_title = '-';
                $child = ThreadFollowAction::field('id,title')->where('pid', $val['id'])->select()->toArray();
                if (!empty($child)) {
                    foreach ($child as $ckey => $cval) {
                        $is_select = 0;
                        $hasAction = self::field('id')
                            ->where('thread_id', $id)
                            ->where('is_external_thread', 0)
                            ->where('current_action_id', $cval['id'])
                            ->find();
                        if (!empty($hasAction)) {
                            $is_select = 1;
                            $select_id = $cval['id'];
                            $select_title = $cval['title'];
                            $status_id = $hasAction['id'];
                        }
                        $child[$ckey]['is_select'] = $is_select;
                        $child[$ckey]['status_id'] = $status_id;
                    }
                }
                $actionData[$key]['select_id'] = $select_id;
                $actionData[$key]['select_title'] = $select_title;
                $actionData[$key]['status_id'] = $status_id;
                $actionData[$key]['children'] = $child;
            }
        }
        return $actionData;
    }


    public static function getThreadUserStatusText($userStatus = [])
    {
        $interest = ' - ';
        $toclass = ' - ';
        $deposit = ' - ';
        $success = ' - ';
        $answerPhone = ' - ';
        $reply = ' - ';
        $del = ' - ';
        $addWechat = ' - ';
        if(!empty($userStatus)){
            foreach ($userStatus as &$usval){
                $select_text = $usval['followAction']['title'];
                switch ($usval['parentFollowAction']['title']) {
                    case "客户兴趣":
                        $interest = $select_text;
                        break;
                    case "是否到课":
                        $toclass = $select_text;
                        break;
                    case "是否定金":
                        $deposit = $select_text;
                        break;
                    case "是否成交":
                        $success = $select_text;
                        break;
                    case "是否接听电话":
                        $answerPhone = $select_text;
                        break;
                    case "是否回复":
                        $reply = $select_text;
                        break;
                    case "是否删除":
                        $del = $select_text;
                        break;
                    case "是否加V":
                        $addWechat = $select_text;
                        break;
                }
            }
        }
         return [
            'interest'=>$interest,
            'toclass'=>$toclass,
            'deposit'=>$deposit,
            'success'=>$success,
            'answer_phone'=>$answerPhone,
            'reply'=>$reply,
            'del'=>$del,
            'add_wechat'=>$addWechat,
        ];
    }

    public function followAction()
    {
        return $this->belongsTo('app\model\admin\thread\ThreadFollowAction', 'current_action_id', 'id')->removeOption('soft_delete');
    }

    public function parentFollowAction()
    {
        return $this->belongsTo('app\model\admin\thread\ThreadFollowAction', 'current_parent_action_id', 'id')->removeOption('soft_delete');
    }
}
