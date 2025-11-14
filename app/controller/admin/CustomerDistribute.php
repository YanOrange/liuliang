<?php

namespace app\controller\admin;

use laytp\controller\Backend;
use app\service\admin\UserServiceFacade;
use think\facade\Db;
use app\service\admin\AuthServiceFacade;

/**
 * 客服分配方案
 *
 * Class CustomerDistribute
 * @package app\controller\admin
 * @author chenlele
 * @date 2022-09-20
 */
class CustomerDistribute extends Backend
{
    // ========================== 1.1 客服分配质监 start ==========================

    // 类目 - 客服待分配量 - total 数量：新增 | 修改 - 计算
    public function setSetting()
    {
        $clsAppId = (int)$this->request->param('app_class_id');
        $total = $this->request->param('total');
        if (!is_numeric($total) || $total > 500 || $total < 0) {
            return $this->error('非法操作');
        }

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $loginname = $loginUserInfo['username'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);

        $info = Db::name('customer_distribute_setting')
            ->field('id,total')
            ->whereDay('create_time', 'today')
            ->where('app_class_id', $clsAppId)
            ->where('type', 1)
            ->find();
        if (!$info) return $this->error('操作失败');
        if ($info['total'] > 0 && $roleIds == 13) {
            return $this->error('禁止二次修改');
        }
        Db::startTrans();
        try {
            $bool = Db::name('customer_distribute_setting')->update([
                'id' => $info['id'],
                'total' => $total,
                'update_time' => time(),
                'admin_user_id' => $loginId,
                'admin_user_username' => $loginname,
            ]);

            if (!$bool) throw new \Exception('数据库异常，操作失败');

            self::_daysCompute($clsAppId);
            Db::commit();

            return $this->success('操作成功');

        } catch (\Exception $e) {

            Db::rollback();
            return $this->error('数据库异常，操作失败');
        }
    }

    // 类目：展示
    public function classify()
    {
        $lists = Db::name('customer_distribute_setting')
            ->field('id, app_class_id, app_class_name, total, kfp_nums,bhf_nums,invalid_nums,del_nums,nonage_nums,admin_user_username, practical_total,create_time, update_time')
            ->whereDay('create_time', 'today')
            ->where('type', 1)
            ->select()->toArray();

        if (!$lists) {                      // 当天无数据
            $arr = self::_classify();       // 默认配置的类目
            if (!Db::name('customer_distribute_setting')->insertAll($arr)) return $this->error('类目初始化失败');

            $lists = Db::name('customer_distribute_setting')
                ->field('id, app_class_id, app_class_name, total, kfp_nums,bhf_nums,invalid_nums,del_nums,nonage_nums,kfp_nums,admin_user_username, create_time, update_time')
                ->whereDay('create_time', 'today')
                ->where('type', 1)
                ->select()->toArray();
        }

        foreach ($lists as &$item) {
            $timer = ($item['update_time']) ? $item['update_time'] : $item['create_time'];
            $item['update_time'] = date('Y-m-d H:i:s', $timer);
        }

        return $this->success('数据获取成功', $lists);
    }

    // 默认配置的类目
    protected static function _classify()
    {
        $class = env('CUSDIS.app_class_id');
        $clsArr = explode(',', $class);

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $loginname = $loginUserInfo['username'];

        $arr = [];
        foreach ($clsArr as $cls) {
            $str = explode(':', $cls);
            if (!isset($str[0])) continue;

            $arr[] = [
                'app_class_id' => $str[0],
                'app_class_name' => $str[1],
                'total' => 0,
                'type' => 1,
                'admin_user_id' => $loginId,
                'admin_user_username' => $loginname,
                'create_time' => time(),
                'update_time' => 0,
            ];
        }

        return $arr;
    }
    // ========================== 1.1 客服分配质监 end ==========================

    // ========================== 1.2 预设分配比例 start ==========================

    // 预设分配比例 - level 等级：初始化 | 展示
    public function scale()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $loginname = $loginUserInfo['username'];

        $hasScale = Db::name('customer_distribute_setting')->field('id, remark')->where('type', 0)->count();
        if (!$hasScale) {
            $arr = [];
            for ($i = 1; $i < 8; $i++) {
                $arr[] = [
                    'level' => $i, 'val' => 0,
                    'create_time' => time(),
                    'update_time' => 0,
                    'admin_user_id' => $loginId,
                    'admin_user_username' => $loginname
                ];
            }

            $bool = Db::name('customer_distribute_setting')->insert([
                'remark' => json_encode($arr, true),
                'create_time' => time(),
                'admin_user_id' => $loginId,
                'admin_user_username' => $loginname,
            ]);

            if (!$bool) return $this->error('操作失败');
        }

        $scaleArr = [];
        $scale = Db::name('customer_distribute_setting')->field('id, remark')->where('type', 0)->find();
        if ($scale && $scale['remark']) {
            $scaleArr = json_decode($scale['remark'], true);

            foreach ($scaleArr as &$item) {
                $timer = ($item['update_time']) ? $item['update_time'] : $item['create_time'];
                $item['update_time'] = date('Y-m-d H:i:s', $timer);
            }
        }

        return $this->success('数据获取成功', $scaleArr);
    }

    // 预设分配比例 - level 等级：修改
    public function setScale()
    {
        $level = (int)$this->request->param('level');
        $val = $this->request->param('val');
        if (!is_numeric($val) || $val > 100 || $val < 0) {
            return $this->error('非法操作');
        }

        $info = Db::name('customer_distribute_setting')->field('id, remark')->where('type', 0)->find();
        if (!$info) return $this->error('预设分配比例未初始化');

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $remark = json_decode($info['remark'], true);
        foreach ($remark as &$item) {
            if ($item['level'] == $level) {
                $item['val'] = $val;
                $item['update_time'] = time();
                $item['admin_user_id'] = $loginUserInfo['id'];
                $item['admin_user_username'] = $loginUserInfo['username'];
            }
        }

        Db::startTrans();
        try {
            $bool = Db::name('customer_distribute_setting')->update([
                'id' => $info['id'],
                'remark' => json_encode($remark, true),
                'update_time' => time(),
            ]);

            if (!$bool) throw new \Exception('数据库异常，操作失败');

            self::_scaleCompute($level, $val);
            Db::commit();
            return $this->success('操作成功');

        } catch (\Exception $e) {

            Db::rollback();
            return $this->error('数据库异常，操作失败');
        }
    }

    // 预设分配比例 计算 $level => 等级，$value => 预设分配比例
    protected function _scaleCompute($level, $preScale)
    {
        $lists = Db::name('customer_distribute_lists')
            ->field('id, app_class_id, merchant_id, reg_days, class_type, feedback_days, level, needs, pre_scale, compute_needs, compute_scale, distribute_count')
            ->where('level', $level)
            ->whereDay('create_time', 'today')->select()->toArray();

        // 类目的需求量计算值
        $clsAppTotalArr = Db::name('customer_distribute_lists')
            ->field(['app_class_id', 'sum(compute_needs) as total'])
            ->whereDay('create_time', 'today')
            ->where('level', '=', $level)
            ->group('app_class_id')
            ->select()->toArray();

        $clsAppTotal = [];
        foreach ($clsAppTotalArr as $cls) {
            $clsAppTotal[$cls['app_class_id']] = $cls['total'];
        }

        // 类目的客服待分配量
        $customerTotalArr = Db::name('customer_distribute_setting')
            ->field('app_class_id, total')
            ->whereDay('create_time', 'today')
            ->where('type', '=', 1)
            ->select()->toArray();

        $customerTotal = [];
        foreach ($customerTotalArr as $cls) {
            $customerTotal[$cls['app_class_id']] = $cls['total'];
        }

        // 需求量总计大于设置的值，重新计算 (needs *  pre_scale) > z = 另外计算
        $preScale = $preScale / 100;
        foreach ($lists as $item) {
            $clsId = $item['app_class_id'];

            if ($clsAppTotal[$clsId] > $customerTotal[$clsId]) {
                $upComputeScale = (($item['compute_needs'] * 100) / $clsAppTotal[$clsId]); // 计算分配比例

                $upComputeScale = $upComputeScale / 100;
                $upDistributeCount = $upComputeScale * $customerTotal[$clsId];

                Db::name('customer_distribute_lists')->update([
                    'id' => $item['id'],
                    'pre_scale' => $preScale,
                    'compute_scale' => $upComputeScale,
                    'distribute_count' => $upDistributeCount,
                ]);

                Db::name('merchant')->update([
                    'id' => $item['merchant_id'],
                    'assign_thread_limit_nums' => $upDistributeCount
                ]);

            } else {

                $computeNeeds = $item['needs'] * $preScale;            // 每天线索数量限制 - totay_thread_limit_nums * 预设分配比例（%）

                Db::name('customer_distribute_lists')->update([
                    'id' => $item['id'],
                    'pre_scale' => $preScale,                           // 预设分配比例       _scale
                    'compute_needs' => $computeNeeds,                  // 需求量计算值       needs *  pre_scale
                    'compute_scale' => $preScale,                       // 计算分配比例       (needs *  pre_scale) > z = 另外计算
                    'distribute_count' => $computeNeeds,                // 可分配条数
                ]);
                Db::name('merchant')->update([
                    'id' => $item['merchant_id'],
                    'assign_thread_limit_nums' => $computeNeeds
                ]);
            }
        }
    }

    // ========================== 1.2 预设分配比例 end ==========================

    // ========================== 2. 商户质量配置 start ==========================

    // 商户质量配置：展示
    public function quality()
    {
        $lists = Db::name('customer_distribute_quality')
            ->field('id, merchant_id, merchant_name, days, admin_user_username, create_time, update_time')
            ->select()->toArray();

        foreach ($lists as &$item) {
            $timer = ($item['update_time']) ? $item['update_time'] : $item['create_time'];
            $item['update_time'] = date('Y-m-d H:i:s', $timer);
        }

        return $this->success('数据获取成功', $lists);
    }

    // 删除 分配质量差连续反馈天数
    public function del()
    {
        $ids = array_filter($this->request->param('ids'));
        if (!$ids) return $this->error('参数ids不能为空');

        $id = $ids[0];
        $info = Db::name('customer_distribute_quality')->field('id, merchant_id')->where('id', $id)->find();
        if (!$info) return $this->error('未找到要删除的数据');

        Db::startTrans();
        try {
            $app = Db::name('customer_distribute_lists')
                ->field('id, app_class_id')
                ->where('merchant_id', $info['merchant_id'])
                ->whereDay('create_time', 'today')
                ->find();

            $bool = Db::name('customer_distribute_quality')->where('id', $id)->delete();
            Db::name('customer_distribute_lists')->where('id', $app['id'])->delete();
            if (!$bool) throw new \Exception('数据库异常，操作失败');

            self::_daysCompute($app['app_class_id']);
            Db::commit();

            return $this->success('操作成功');

        } catch (\Exception $e) {
            Db::rollback();
            return $this->exceptionError($e);
        }
    }

    // 商户质量：新增 | 修改
    public function setQuality()
    {
        $merchantId = (int)$this->request->param('merchant_id');
        $days = (int)$this->request->param('days');
        if (!is_numeric($days) || $days > 100 || $days < 0) {
            return $this->error('非法操作');
        }

        $merchant = Db::name('merchant')->field('id, app_class_id, merchant_name, totay_thread_limit_nums, create_time')->where('id', $merchantId)->find();
        if (!$merchant) return $this->success('商户不存在');

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $loginname = $loginUserInfo['username'];

        $quality = Db::name('customer_distribute_quality')->where('merchant_id', $merchantId)->field('id')->find();
        if (!$quality) {
            Db::startTrans();
            try {
                $bool = Db::name('customer_distribute_quality')->insert([
                    'merchant_id' => $merchantId,
                    'merchant_name' => $merchant['merchant_name'],
                    'days' => $days,
                    'admin_user_id' => $loginId,
                    'admin_user_username' => $loginname,
                    'create_time' => time(),
                ]);
                if (!$bool) throw new \Exception('数据库异常，操作失败');

                self::setDaysCompute($merchant, $days);
                Db::commit();

                return $this->success('操作成功');

            } catch (\Exception $e) {
                Db::rollback();
                return $this->error('数据库异常，操作失败');
            }
        }

        Db::startTrans();
        try {
            $bool = Db::name('customer_distribute_quality')->update([
                'id' => $quality['id'],
                'days' => $days,
                'update_time' => time(),
                'admin_user_id' => $loginId,
                'admin_user_username' => $loginname,
            ]);
            if (!$bool) return $this->error('数据库异常，操作失败');

            self::setDaysCompute($merchant, $days);
            Db::commit();

            return $this->success('操作成功');

        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('数据库异常，操作失败');
        }
    }



    //设置客服实际可分配量
    public function setPracticalTotal()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['practical_total'] = $fieldVal;
        try {
            /*if($isRecycle) {
                $updateRes = $this->model->onlyTrashed()->where('id', '=', $id)->update($update);
            } else {
                $updateRes = $this->model->where('id', '=', $id)->update($update);
            }*/

            $updateRes = Db::name('customer_distribute_setting')->update([
                'id' => $id,
                'practical_total' => $fieldVal,
            ]);

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


    //设置可分配数量
    public function setKfpNums()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['kfp_nums'] = $fieldVal;
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);
        $info = Db::name('customer_distribute_setting')->find($id);
        if (!$info) return $this->error('记录不存在');
        if ($info['kfp_nums'] > 0 && $roleIds == 13) {
            return $this->error('禁止二次修改');
        }
        try {

            $updateRes = Db::name('customer_distribute_setting')->update([
                'id' => $id,
                'kfp_nums' => $fieldVal,
            ]);

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

    //设置不回复数量
    public function setBhfNums()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['bhf_nums'] = $fieldVal;
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);
        $info = Db::name('customer_distribute_setting')->find($id);
        if (!$info) return $this->error('记录不存在');
        if ($info['bhf_nums'] > 0 && $roleIds == 13) {
            return $this->error('禁止二次修改');
        }
        if (!$info) return $this->error('操作失败');

        try {

            $updateRes = Db::name('customer_distribute_setting')->update([
                'id' => $id,
                'bhf_nums' => $fieldVal,
            ]);

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


    //设置无效数量
    public function setInvalidNums()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['invalid_nums'] = $fieldVal;

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);
        $info = Db::name('customer_distribute_setting')->find($id);
        if (!$info) return $this->error('记录不存在');
        if ($info['invalid_nums'] > 0 && $roleIds == 13) {
            return $this->error('禁止二次修改');
        }

        try {

            $updateRes = Db::name('customer_distribute_setting')->update([
                'id' => $id,
                'invalid_nums' => $fieldVal,
            ]);

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



    //设置删除数量
    public function setDelNums()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['del_nums'] = $fieldVal;
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);
        $info = Db::name('customer_distribute_setting')->find($id);
        if (!$info) return $this->error('记录不存在');
        if ($info['del_nums'] > 0 && $roleIds == 13) {
            return $this->error('禁止二次修改');
        }
        try {

            $updateRes = Db::name('customer_distribute_setting')->update([
                'id' => $id,
                'del_nums' => $fieldVal,
            ]);

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


    //设置未成年数量
    public function setNonageNums()
    {
        $id       = $this->request->post('id');
        $fieldVal = $this->request->post('field_val');
        $isRecycle = $this->request->post('is_recycle');
        $update['nonage_nums'] = $fieldVal;
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $roleIds = AuthServiceFacade::getAuthUserRole($loginId);
        $info = Db::name('customer_distribute_setting')->find($id);
        if (!$info) return $this->error('记录不存在');
        if ($info['nonage_nums'] > 0 && $roleIds == 13) {
            return $this->error('禁止二次修改');
        }
        try {

            $updateRes = Db::name('customer_distribute_setting')->update([
                'id' => $id,
                'nonage_nums' => $fieldVal,
            ]);

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



    // 设置分配质量差连续反馈天数
    public function setDays()
    {
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $loginId = $loginUserInfo['id'];
        $loginname = $loginUserInfo['username'];

        $id = (int)$this->request->param('id');
        $days = $this->request->param('days');
        if (!is_numeric($days) || $days >= 200 || $days < 0) {
            return $this->error('非法操作');
        }
        $quality = Db::name('customer_distribute_quality')->where('id', $id)->field('id, merchant_id')->find();
        if (!$quality) return $this->error('数据库异常，操作失败');

        Db::startTrans();
        try {
            $bool = Db::name('customer_distribute_quality')->update([
                'id' => $quality['id'],
                'days' => $days,
                'update_time' => time(),
                'admin_user_id' => $loginId,
                'admin_user_username' => $loginname,
            ]);
            if (!$bool) throw new \Exception('数据库异常，操作失败');

            $merchant = Db::name('merchant')
                ->field('id, app_class_id, merchant_name, totay_thread_limit_nums, create_time')
                ->where('id', $quality['merchant_id'])->find();
            self::setDaysCompute($merchant, $days);
            Db::commit();

            return $this->success('操作成功');

        } catch (\Exception $e) {

            Db::rollback();
            return $this->error('数据库异常，操作失败');
        }
    }

    // 新增 | 更新商户质量 重新计算：具体商户 单条
    public static function setDaysCompute($merchant, $feedbackDays)
    {
        $arr = self::_classify();
        $clsAppId = $merchant['app_class_id'];                              // 是否已配置的类目

        $clsAppName = '';                                                   // 获取类目名称
        foreach ($arr as $item) {
            if ($item['app_class_id'] == $clsAppId) $clsAppName = $item['app_class_name'];
        }
        if (!$clsAppName) return false;

        $needs = $merchant['totay_thread_limit_nums'];                      // 需求量 : 每天线索数量限制 - totay_thread_limit_nums
        $regDays = ceil(time() - $merchant['create_time']) / 86400;    // 注册天数
        $level = self::_level($regDays, $feedbackDays);                     // 质量控制等级

        // 预设分配比例
        $scaleArr = [];
        $scale = Db::name('customer_distribute_setting')->field('id, remark')->where('type', 0)->find();
        if ($scale && $scale['remark']) {
            $remark = json_decode($scale['remark'], true);

            foreach ($remark as $item) {
                $scaleArr[$item['level']] = $item['val'] / 100;             // 等级1 -> 15%
            }
        }

        $preScale = 0;                                                      // 需求量, 预设分配比例
        if (isset($scaleArr[$level])) {
            $preScale = $scaleArr[$level];
        }

        // 默认需求量计算值
        $computeNeeds = $needs * $preScale;                                 // 每天线索数量限制 - totay_thread_limit_nums * 预设分配比例（%）


        // 入库、修改 ===================================================================
        $info = Db::name('customer_distribute_lists')
            ->field('id')
            ->where('app_class_id', $clsAppId)
            ->where('merchant_id', $merchant['id'])
            ->whereDay('create_time', 'today')
            ->find();

        if (!$info) {
            $arr = [
                'app_class_id' => $clsAppId,
                'app_class_name' => $clsAppName,
                'merchant_id' => $merchant['id'],
                'merchant_name' => $merchant['merchant_name'],
                'reg_days' => $regDays,
                'class_type' => ($regDays < 8) ? 1 : 2,
                'feedback_days' => $feedbackDays,
                'level' => $level,
                'needs' => $needs,                      // 需求量 : 每天线索数量限制 - totay_thread_limit_nums
                'pre_scale' => $preScale,               // 预设分配比例       _scale
                'compute_needs' => $computeNeeds,       // 需求量计算值       needs *  pre_scale
                'compute_scale' => $preScale,           // 计算分配比例       (needs *  pre_scale) > z = 另外计算
                'distribute_count' => $computeNeeds,    // 可分配条数
                'create_time' => time(),
                'status' => 1
            ];

            $bool = Db::name('customer_distribute_lists')->insert($arr);
            if (!$bool) return false;

            self::_daysCompute($clsAppId);
            return true;
        }

        $bool = Db::name('customer_distribute_lists')->update([
            'id' => $info['id'],
            'feedback_days' => $feedbackDays,
            'level' => $level,
            'needs' => $needs,                          // 需求量 : 每天线索数量限制 - totay_thread_limit_nums
            'pre_scale' => $preScale,                   // 预设分配比例       _scale
            'compute_needs' => $computeNeeds,           // 需求量计算值       needs *  pre_scale
            'compute_scale' => $preScale,               // 计算分配比例       (needs *  pre_scale) > z = 另外计算
            'distribute_count' => $computeNeeds,        // 可分配条数
            'update_time' => time(),
        ]);

        if (!$bool) return false;

        self::_daysCompute($clsAppId);
        return true;
    }

    // 分配质量差连续反馈天数 计算 $customerTotal 待分配量
    protected static function _daysCompute($clsAppId)
    {
        // 该类目的需求量计算值
        $clsAppTotal = Db::name('customer_distribute_lists')
            ->whereDay('create_time', 'today')
            ->where('app_class_id', '=', $clsAppId)
            ->sum('compute_needs');

        // 该类目的客服待分配量
        $customerTotal = Db::name('customer_distribute_setting')
            ->field('id, total')
            ->where('app_class_id', $clsAppId)
            ->whereDay('create_time', 'today')
            ->value('total');

        $lists = Db::name('customer_distribute_lists')
            ->field('id, merchant_id, reg_days, class_type, feedback_days, level, needs, pre_scale, compute_needs, compute_scale, distribute_count')
            ->where('app_class_id', $clsAppId)
            ->whereDay('create_time', 'today')->select()->toArray();

        // 需求量总计大于设置的值，重新计算 (needs *  pre_scale) > z = 另外计算
        if ($clsAppTotal > $customerTotal) {

            foreach ($lists as $item) {
                $upComputeScale = (($item['compute_needs'] * 100) / $clsAppTotal); // 计算分配比例
                $upComputeScale = $upComputeScale / 100;
                $upDistributeCount = $upComputeScale * $customerTotal;

                Db::name('customer_distribute_lists')->update([
                    'id' => $item['id'],
                    'compute_scale' => $upComputeScale,
                    'distribute_count' => $upDistributeCount,
                ]);

                Db::name('merchant')->update([
                    'id' => $item['merchant_id'],
                    'assign_thread_limit_nums' => $upDistributeCount
                ]);
            }
        } else {

            foreach ($lists as $item) {

                // 默认需求量计算值
                $computeNeeds = $item['needs'] * $item['pre_scale'];                                 // 每天线索数量限制 - totay_thread_limit_nums * 预设分配比例（%）
                Db::name('customer_distribute_lists')->update([
                    'id' => $item['id'],
                    'compute_scale' => $item['pre_scale'],           // 计算分配比例       (needs *  pre_scale) > z = 另外计算
                    'distribute_count' => $computeNeeds,             // 可分配条数
                ]);

                Db::name('merchant')->update([
                    'id' => $item['merchant_id'],
                    'assign_thread_limit_nums' => $computeNeeds
                ]);
            }
        }
    }

    // 指定类目下的所有商户
    public function merchant()
    {
        $arr = self::_classify();
        $appIds = array_column($arr, 'app_class_id');
        $lists = Db::name('merchant')->where([
            ['app_class_id', 'in', $appIds]
        ])->select()->toArray();

        return $this->success('获取成功', $lists);
    }
    // ========================== 2. 商户质量配置 start ==========================


    // ========================== 3. 商户客服分配比例 start ==========================
    public function distribute()
    {
        $lists = Db::name('customer_distribute_lists')
            ->field('id, app_class_name, merchant_name, reg_days, class_type, feedback_days, level, needs, pre_scale, compute_needs, compute_scale, distribute_count')
            ->whereDay('create_time', 'today')
            ->select()->toArray();

        foreach ($lists as &$item) {
            $item['pre_scale'] = round($item['pre_scale'] * 100, 2);
            $item['compute_scale'] = round($item['compute_scale'] * 100, 2);
            $item['class_type'] = self::_types($item['class_type']);
        }

        return $this->success('数据获取成功', $lists);
    }
    // ========================== 3. 商户客服分配比例 end ==========================


    /**
     * 客户分类
     *  1、新甲方： 当前时间减注册时间小于8天
     *  2、老甲方： 当前时间减注册时间大于等于8天
     */
    protected static function _types($key)
    {
        $arr = [
            1 => '新甲方',
            2 => '老甲方'
        ];

        return isset($arr[$key]) ? $arr[$key] : '';
    }

    /**
     *  等级1：新甲方且注册天数小于3天    判断2：（新甲方且注册天数等于3天and小于5天且质量差天数>0则是等级1）
     * 等级2：判断1：新甲方且注册天数等于3天and小于5天且质量差天数=0； 判断2（新甲方&注册天数大于等于5天and小于8天且质量差天数>1则是等级2）
     * 等级3：判断1：新甲方&注册天数等于5天and小于8天且质量差天数<1；
     * 等级4：老甲方质量差天数<1;
     * 等级5：老甲方质量差天数=1；
     * 等级6：老甲方质量差天数=2；
     * 等级7：老甲方质量差天数>2；
     */
    protected static function _level($regDays, $feedbackDays)
    {
        $level = 1;
        if ($regDays < 3 || ($regDays == 3 && $regDays < 5 && $feedbackDays > 0)) {

            $level = 1;

        } else if (($regDays >= 3 && $regDays < 5 && $feedbackDays == 0) || ($regDays >= 5 && $regDays < 8 && $feedbackDays > 1)) {

            $level = 2;

        } else if ($regDays >= 8 && $feedbackDays < 1) {

            $level = 4;

        } else if ($regDays >= 8 && $feedbackDays == 1) {

            $level = 5;

        } else if ($regDays >= 8 && $feedbackDays == 2) {

            $level = 6;

        } else if ($regDays >= 8 && $feedbackDays > 1) {

            $level = 7;
        }

        return $level;
    }
}