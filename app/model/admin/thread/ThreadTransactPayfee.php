<?php
/**
 * 线索报名缴费关联表模型
 */

namespace app\model\admin\thread;

use app\lib\api\exception\Exception;
use app\service\admin\UserServiceFacade;
use laytp\BaseModel;
use think\facade\Db;
use think\model\concern\SoftDelete;

class ThreadTransactPayfee extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'thread_transact_payfee';

    protected $hidden = [
        'apply_payfee',
        'merchant',
        'customer',
    ];

    protected static function getTypeText($type)
    {
        $typeArr = [
            1 => '全款',
            2 => '定金',
            3 => '尾款'
        ];
        return $typeArr[$type];
    }

    protected static function getStatusText($status)
    {
        $statusArr = [
            0 => '已取消',
            1 => '已付款',
        ];
        return $statusArr[$status];
    }


    /**
     * 获取用户状态列表
     * @param array $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getThreadTransactPayfeeList($params = [])
    {
        extract($params);
        $merchant = UserServiceFacade::getUserInfo();
        $where = [
            ['thread_id', '=', $id],
            ['is_external_thread', '=', 0],
        ];
        $courseData = self::where($where)
            ->with(['applyPayfee', 'merchant', 'customer'])
            ->order('create_time desc')
            ->paginate($pagesize)
            ->each(function ($item, $key) {
                $adminUserName = "系统管理员";
                if ($item['admin_type'] == 2) {
                    if(!empty($item['customer'])){
                        $adminUserName = $item['customer']['nickname'];
                    }else{
                        $adminUserName = '-';
                    }
                }
                $item['admin_username'] = $adminUserName;
                $item['status_text'] = self::getStatusText($item['status']);
                return $item;
            })
            ->toArray();
        return $courseData;
    }

    public function applyPayfee()
    {
        return $this->belongsTo('app\model\admin\ApplyPayfee', 'payfee_id', 'id')
            ->bind(['title', 'price'])
            ->removeOption('soft_delete');
    }

    public function merchant()
    {
        return $this->belongsTo('app\model\admin\Merchant', 'admin_id', 'id')
            ->bind(['merchant_name'])
            ->removeOption('soft_delete');
    }

    public function customer()
    {
        return $this->belongsTo('app\model\admin\Customer', 'admin_id', 'id')
            ->bind(['nickname'])
            ->removeOption('soft_delete');
    }

    public function thread()
    {
        return $this->belongsTo('app\model\admin\Thread', 'merchant_id', 'id')->removeOption('soft_delete');
    }

    public function threadCopy()
    {
        return $this->belongsTo('app\model\admin\ThreadCopy', 'merchant_id', 'id')->removeOption('soft_delete');
    }

    public function payfee()
    {
        return $this->belongsTo('app\model\admin\ApplyPayfee', 'payfee_id', 'id')->removeOption('soft_delete');
    }

}
