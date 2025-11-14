<?php

namespace app\controller\admin;

use app\validate\admin\channelhomeconfig\ChannelHomeConfig as ChannelHomeConfigValidate;
use app\validate\admin\onlineservicewechat\OnlineServiceWechat as OnlineServiceWechatValidate;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;
use think\facade\Request;

class ChannelHomeConfig extends Backend
{
    protected $model;//当前模型对象
    protected function _initialize()
    {
        $this->model = new \app\model\admin\ChannelHomeConfig();
    }

    //列表
    public function index()
    {
        $where = $this->buildSearchParams();
        $order = $this->buildOrder();
        $data = $this->model->where($where)->order($order);
        $allData = $this->request->param('all_data');
        if ($allData) {
            $data = $data->select();
        } else {
            $limit = $this->request->param('limit', 10);
            $data = $data->paginate($limit)->toArray();
        }
        if (!empty($data['data'])){
            foreach ($data['data'] as $k=>&$v){
                $channel_ids_arr = explode(',',$v['channel_ids']);
                $channel_list = \app\model\admin\Channel::where('id','in',$channel_ids_arr)->field('id,channel_name')->select()->toArray();
                $channel_name_arr = array_column($channel_list,'channel_name');
                $v['channel_name'] = implode(',',$channel_name_arr);
            }
        }
        return $this->success('数据获取成功', $data);
    }

    //添加
    public function add()
    {
        $allowField = ['channel_ids','banner', 'top_image', 'navigation', 'navigation_desc','sub_image'];
        $post = CommonFun::filterPostData(Request::only($allowField));

        $cIds = [];
        $channelIds = $this->request->post('channel_ids');
        $channelIds = array_filter(explode(',', $channelIds));
        foreach ($channelIds as $ids) {
            $idsArr = explode('_', $ids);
            $cIds[] = (isset($idsArr[1])) ? $idsArr[1] : '';
        }
        $post['channel_ids'] = implode(',', $cIds);
        if (!$post['channel_ids']) {
            return $this->error('请选择关联渠道');
        }
        $validate = new ChannelHomeConfigValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        $navigation_arr = explode(',',$post['navigation']);
        $navigation_desc_arr = explode(',',$post['navigation_desc']);
        if (count($navigation_arr) != count($navigation_desc_arr)){
            return $this->error('导航图片和导航描述数量不一致');
        }
        //将$navigation_arr和$navigation_desc_arr合并成新数组
        foreach ($navigation_arr as $k=>&$v){
            $v = [
                'navigation' => $v,
                'navigation_desc' => $navigation_desc_arr[$k]
            ];
        }
        $post['navigation'] = json_encode($navigation_arr,JSON_UNESCAPED_UNICODE);
        Db::startTrans();
        try {
            $saveRes = $this->model->save($post);
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
        $id   = $this->request->param('id');
        $info = $this->model->findOrEmpty($id)->toArray();
        $navigation_arr = json_decode($info['navigation'],true);
        $info['navigation'] = implode(', ',array_column($navigation_arr,'navigation'));
        $info['navigation_desc'] = implode(',',array_column($navigation_arr,'navigation_desc'));
        $info['top_image'] = implode(', ',explode(',',$info['top_image']));
        return $this->success('获取成功', $info);
    }

    //编辑
    public function edit()
    {
        $allowField = ['id','channel_ids','banner', 'top_image', 'navigation', 'navigation_desc','sub_image'];
        $post = CommonFun::filterPostData(Request::only($allowField));

        $cIds = [];
        $channelIds = $this->request->post('channel_ids');
        $channelIds = array_filter(explode(',', $channelIds));
        //去掉top_imge的空格
        $post['top_image'] = str_replace(' ', '', $post['top_image']);
        foreach ($channelIds as $ids) {
            $idsArr = explode('_', $ids);
            $cIds[] = (isset($idsArr[1])) ? $idsArr[1] : '';
        }
        $post['channel_ids'] = implode(',', $cIds);
        if (!$post['channel_ids']) {
            return $this->error('请选择关联渠道');
        }
        $validate = new ChannelHomeConfigValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        $navigation_arr = explode(',',$post['navigation']);
        $navigation_desc_arr = explode(',',$post['navigation_desc']);
        if (count($navigation_arr) != count($navigation_desc_arr)){
            return $this->error('导航图片和导航描述数量不一致');
        }
        //将$navigation_arr和$navigation_desc_arr合并成新数组
        foreach ($navigation_arr as $k=>&$v){
            $v = [
                'navigation' => trim($v),
                'navigation_desc' => $navigation_desc_arr[$k]
            ];
        }
        $post['navigation'] = json_encode($navigation_arr,JSON_UNESCAPED_UNICODE);
        Db::startTrans();
        try {
            $ChannelHome = $this->model->findOrEmpty($post['id']);
            if (!$ChannelHome) throw new \Exception('id参数错误');
            $updateRes  = $this->model->update($post);
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
                return $this->success('数据删除成功');
            } else {
                return $this->error('数据删除失败');
            }
        }catch (\Exception $e){
            return $this->exceptionError($e);
        }
    }
}