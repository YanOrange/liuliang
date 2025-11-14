<?php

namespace app\controller\admin;

use app\validate\admin\legalimagelink\LegalImageLink as LegalImageLinkValidate;
use app\lib\api\baidu\ShortUrlApi;
use laytp\controller\Backend;
use laytp\library\CommonFun;
use think\facade\Db;

/**
 * 后台应用分类控制器
 */
class LegalImageLink extends Backend
{
    protected $model;//当前模型对象

    protected $noNeedLogin = ['getLegalImage'];

    protected $noNeedAuth = ['getLegalImage'];

    protected function _initialize()
    {
        $this->model = new \app\model\admin\LegalImageLink();
    }
    //查看
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
        return $this->success('数据获取成功', $data);
    }

    //添加
    public function add()
    {
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new LegalImageLinkValidate();
        if (!$validate->scene('add')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $post['legal_images'] = str_replace(',',', ',$post['legal_images']);
            $saveRes = $this->model->save($post);
            if (!$saveRes) throw new \Exception('数据库异常，操作失败');
            $ShortUrlApi = new ShortUrlApi();
            $legalLink = '';
            $legalShortLink = json_decode($ShortUrlApi->getLegalShortUrl($this->model->id),true);
            $legalImageLink = $this->model->find($this->model->id);
            $legalImageLink->legal_short_link = $legalShortLink['ShortUrls'][0]['ShortUrl'] ?? '';
            $legalImageLink->legal_long_link = $legalShortLink['ShortUrls'][0]['LongUrl'] ?? '';
            $legalImageLink->save();
            Db::commit();
            return $this->success('操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error($e->getMessage());
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
        $post     = CommonFun::filterPostData($this->request->post());
        $validate = new LegalImageLinkValidate();
        if (!$validate->scene('edit')->check($post)) return $this->error($validate->getError());
        Db::startTrans();
        try {
            $appClass = $this->model->findOrEmpty($post['id']);
            if (!$appClass) throw new \Exception('id参数错误');
            $updateRes  = $appClass->update($post);
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

    //编辑
    public function getLegalImage()
    {
        $legalId = $this->request->post('legal_id');
        $info = $this->model->findOrEmpty($legalId)->toArray();
        if(!empty($info)){
            unset($info['legal_short_link']);
            unset($info['legal_long_link']);
            $info['legal_images'] = explode(',',$info['legal_images']);
            return $this->success('获取成功',$info);
        }
        return $this->error('暂无数据');
    }
}