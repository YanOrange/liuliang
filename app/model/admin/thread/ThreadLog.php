<?php
/**
 * 线索操作日志表模型
 */

namespace app\model\admin\thread;

use app\lib\api\exception\Exception;
use app\model\admin\Thread;
use app\service\admin\UserServiceFacade;
use laytp\BaseModel;
use think\facade\Db;
use think\model\concern\SoftDelete;

class ThreadLog extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'thread_log';

    protected static function getOperationTypeText($type)
    {
        $operationTypeArr = [
            1 => '跟进记录',
            2 => '用户状态',
            3 => '用户标签',
            4 => '转让记录',
            5 => '放弃记录',
            6 => '修改来源信息',
            7 => '分配记录',
            8 => '删除记录',
            9 => '基本信息',
        ];
        return $operationTypeArr[$type];
    }

    protected static function getOperationRemarkTitleText($type)
    {
        $operationRemarkTitleArr = [
            1 => '跟进信息',
            2 => '用户状态',
            3 => '用户标签',
            4 => '记录理由',
            5 => '放弃理由',
            6 => '修改原因',
            7 => '分配理由',
            8 => '删除理由',
            9 => '基本信息',
        ];
        return $operationRemarkTitleArr[$type];
    }

    /**
     * 列表(根据线索ID获取)
     * @param array $params
     * @return array
     * @throws \think\db\exception\DbException
     */
    public static function getThreadLogList($params = [])
    {
        extract($params);
        $thread = Thread::field('id,merchant_id')->where('id',$id)->find();
        if(empty($thread)){
            return [];
        }
        $merchantId = $thread['merchant_id'];
        $where[] = [
            ['merchant_id', '=', $merchantId],
            ['thread_id', '=', $id],
        ];
        if (!empty($operation_type)) {
            $where[] = ['operation_type', '=', $operation_type];
        }
        $threadLogList = self::where($where)
            ->with(['log_files'=>function($query){
                $query->field('log_id,path');
                $query->where('delete_time',0);
            }])
            ->order('create_time desc')
            ->paginate($pagesize)
            ->each(function ($item, $key) {
                $adminUserName = "系统管理员";
                if ($item['admin_type'] == 2) {
                    $adminUserInfo = Customer::field('nickname')->where('id', $item['admin_id'])->find();
                    if (!empty($adminUserInfo)) {
                        $adminUserName = $adminUserInfo['nickname'];
                    }
                }
                $item['operation_content'] = json_decode($item['operation_content'], true);
                $item['admin_username'] = $adminUserName;
                $item['operation_type_text'] = self::getOperationTypeText($item['operation_type']);
                $item['operation_remark_text'] = self::getOperationRemarkTitleText($item['operation_type']);
                return $item;
            })
            ->toArray();
        return $threadLogList;
    }


    /**
     * 添加跟进信息
     * @param array $params
     */
    public static function addThreadLog($params = [])
    {
        Db::startTrans();
        try {
            $merchant = UserServiceFacade::getUserInfo();
            $merchantId = $merchant['id'];
            extract($params);
            $threadModel = $source_id ? (new ThreadExternal()) : (new Thread());
            $thread = $threadModel->field('merchant_id')->where('id', $thread_id)->find();
            if (empty($thread)) new Exception('线索信息不存在');
            $params['admin_id'] = $merchantId;
            $params['merchant_id'] = $merchantId;
            $params['operation_content'] = json_encode([$params['operation_content']]);
            $params['operation_type'] = 1;
            $params['is_external_thread'] = ($source_id>0)?1:0;
            $createRes = self::create($params);
            if ($createRes === false) new Exception('数据库异常，操作失败');
            if (!empty($files_list)) {
                $logId = self::getLastInsID();
                $filesData = [];
                foreach ($files_list as $val) {
                    $filesData[] = [
                        'log_id' => $logId,
                        'path' => $val,
                    ];
                }
                if (!empty($filesData)) {
                    $fileSaveRes = (new ThreadLogFiles())->saveAll($filesData);
                }
            }
            //更新线索最后沟通时间
            Thread::updateThreadLastCommunicationTime($thread);
            Db::commit();
            return;
        } catch (Exception $e) {
            Db::rollback();
            new Exception('数据库异常，操作失败');
        }
    }

    /**
     * 编辑线索日志
     * @param array $params
     */
    public static function editThreadLog($params = [])
    {
        extract($params);
        Db::startTrans();
        try {
            $threadLog = self::where('id', $id)->where('operation_type', 1)->find();
            if (empty($threadLog)) new Exception('日志信息不存在');
            $threadLog->operation_content = json_encode([$operation_content]);
            $saveRes = $threadLog->save();
            if ($saveRes === false) new Exception('数据库异常，操作失败');
            //删除原有的文件信息
            $fileList = ThreadLogFiles::where('log_id', $id)->column('id');
            if (!empty($fileList)) {
                ThreadLogFiles::destroy($fileList);
            }
            if (!empty($files_list)) {
                $filesData = [];
                foreach ($files_list as $val) {
                    $filesData[] = [
                        'log_id' => $id,
                        'path' => $val,
                    ];
                }
                if (!empty($filesData)) {
                    (new ThreadLogFiles())->saveAll($filesData);
                }
            }
            Db::commit();
            return;
        } catch (Exception $e) {
            Db::rollback();
            new Exception('数据库异常，操作失败');
        }
    }

    /**
     * 保存线索日志(内部调用)
     * @param array $params
     */
    public static function saveThreadLog($params = [])
    {
        Db::startTrans();
        try {
            $params['ip'] = request()->ip();
            $createRes = self::create($params);
            if ($createRes === false) new Exception('数据库异常，操作失败');
            Db::commit();
            return;
        } catch (Exception $e) {
            Db::rollback();
            new Exception('数据库异常，操作失败');
        }
    }

    public function logFiles()
    {
        return $this->hasMany('app\model\admin\thread\ThreadLogFiles', 'log_id', 'id')->removeOption('soft_delete');
    }

    public function thread()
    {
        return $this->belongsTo('app\model\admin\thread\Thread', 'thread_id', 'id')->removeOption('soft_delete');
    }


    public function threadCopy()
    {
        return $this->belongsTo('app\model\admin\thread\ThreadExternal', 'thread_id', 'id')->removeOption('soft_delete');
    }

}
