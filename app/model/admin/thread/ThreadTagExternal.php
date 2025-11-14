<?php
/**
 * 线索标签模型
 */

namespace app\model\admin\thread;

use app\lib\api\exception\Exception;
use app\model\admin\Thread;
use app\service\admin\UserServiceFacade;
use laytp\BaseModel;
use think\facade\Db;
use think\model\concern\SoftDelete;

class ThreadTagExternal extends BaseModel {
    use SoftDelete;

    //模型名
    protected $name = 'thread_tag_external';

    /**
     * 根据线索ID获取标签列表
     * @param array $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getThreadTagList($params = []) {
        extract($params);
        if (isset($source_id) && $source_id == 0) {
            $thread = Thread::field('id,merchant_id')->with(['merchant' => function ($query) {
                $query->field('id,app_class_id');
            }])->where('id', $id)->find();
        } else {
            $thread = ThreadExternal::field('id,merchant_id')->with(['merchant' => function ($query) {
                $query->field('id,app_class_id');
            }])->where('id', $id)->find();
        }
        if (empty($thread) ) {
            return [];
        }
        $merchantId = $thread['merchant_id'];
        $where = [
            ['merchant_id', '=', $merchantId],
        ];
        $whereOr[] = [
            ['merchant_id', '=', 0],
            ['is_sys', '=', 1],
        ];
        $data = ThreadTagCategoryExternal::field('id,title')
            ->where($where)
            ->whereOr($whereOr)
            ->select()->toArray();
        if(!empty($data)){
            foreach ($data as $key => $item){
                $tags = self::field('id,title')
                    ->where('cate_id', $item['id'])
                    ->select()
                    ->each(function ($citem, $ckey) use ($id, $source_id, $merchantId) {
                        if (!empty($citem)) {
                            $is_selected = 0;
                            $myTag = ThreadTagAssociation::field('id')->where('thread_id', $id)
                                ->where('merchant_id', $merchantId)
                                ->where('tag_id', $citem['id'])
                                ->where('is_external_thread', ($source_id > 0) ? 1 : 0)
                                ->find();
                            if (!empty($myTag)) {
                                $is_selected = 1;
                            }
                            $citem['is_selected'] = $is_selected;
                            return $citem;
                        }
                    })
                    ->toArray();
                $data[$key]['children'] = $tags;
            }
        }
        return $data;
    }



}
