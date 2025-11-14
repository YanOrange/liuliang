<?php
/**
 * 线索来源表模型
 */

namespace app\model\admin\thread;

use app\lib\api\exception\Exception;
use app\service\admin\UserServiceFacade;
use laytp\BaseModel;
use think\facade\Db;
use think\model\concern\SoftDelete;

class ThreadSource extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'thread_source';

    protected static function getStatusText($status)
    {
        $statusArr = [
            0 => '禁用',
            1 => '启用',
        ];
        return $statusArr[$status];
    }

    /**
     * 列表
     * @param array $params
     * @return array
     * @throws \think\db\exception\DbException
     */
    public static function getThreadSourceList($params = [])
    {
        extract($params);
        $merchant = UserServiceFacade::getUserInfo();
        $merchantId = $merchant['id'];
        $where[] = ['merchant_id', '=', $merchantId];
        if (!empty($title)) {
            $where[] = ['title', '=', $title ];
        }
        $sourceList = self::field('id,title,status,create_time,update_time')
            ->where($where)
            ->order('id desc')
            ->paginate($pagesize)
            ->toArray();
        $data = isset($aplyPayfeeListList['data']) && !empty($aplyPayfeeListList['data']) ? $aplyPayfeeListList['data'] : [];
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                $data[$key]['status_text'] = self::getStatusText($val['status']);
            }
        }
        $aplyPayfeeListList['data'] = $data;
        return $aplyPayfeeListList;
    }



}
