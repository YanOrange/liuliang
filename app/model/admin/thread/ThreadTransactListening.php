<?php
/**
 * 线索试听课程关联表模型
 */

namespace app\model\admin\thread;

use app\lib\api\exception\Exception;
use app\service\admin\UserServiceFacade;
use laytp\BaseModel;
use think\facade\Db;
use think\model\concern\SoftDelete;

class ThreadTransactListening extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'thread_transact_listening';

    protected $hidden = [
        'merchant',
        'customer',
    ];


    public static function getStatusText($status)
    {
        $statusArr = [
            0 => '已取消',
            1 => '待开始',
            2 => '已签到'
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
    public static function getThreadTransactListening($params = [])
    {
        extract($params);
        $where = [
            ['thread_id', '=', $id],
            ['is_external_thread', '=',0],
        ];
        $courseData = self::where($where)
            ->with(['listeningCourse', 'merchant', 'customer'])
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
                if (!empty($item['listeningCourse'])) {
                    $item['listeningCourse']['start_time_text'] = date('Y-m-d', $item['listeningCourse']['start_time']);
                    $item['listeningCourse']['end_time_text'] = date('Y-m-d', $item['listeningCourse']['end_time']);
                }
                return $item;
            })
            ->toArray();
        return $courseData;
    }


    public function listeningCourse1()
    {
        return $this->belongsTo('app\model\admin\ListeningCourse', 'listening_id', 'id')
            ->removeOption('soft_delete');
    }

    public function listeningCourse()
    {
        return $this->belongsTo('app\model\admin\ListeningCourse', 'listening_id', 'id')
            ->bind(['title'])
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
        return $this->belongsTo('app\model\admin\Thread', 'thread_id', 'id')->removeOption('soft_delete');
    }


    public function threadCopy()
    {
        return $this->belongsTo('app\model\admin\ThreadCopy', 'thread_id', 'id')->removeOption('soft_delete');
    }
}
