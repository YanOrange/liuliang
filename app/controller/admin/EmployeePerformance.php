<?php

namespace app\controller\admin;

use app\service\admin\UserServiceFacade;
use laytp\controller\Backend;
use think\facade\Db;

/**
 * 员工绩效
 *
 * Class EmployeePerformance
 * @package app\controller\admin
 *
 * @date 2022-11-18
 */
class EmployeePerformance extends Backend
{

    const TableMain = 'employee_performance';           // 主表
    const TableInfo = 'employee_performance_info';      // 详情表
    const TableLog = 'employee_performance_log';        // 日志表

    protected $model;

    public function _initialize()
    {
        $this->model = new \app\model\admin\EmployeePerformance();
    }

    // 查看 ==============================================
    // 查看主表
    public function index()
    {
        $wArr = $this->buildSearchParams();

        $where = [];
        foreach ($wArr as $key => $w) {
            foreach ($w as $val) {
                if ($val == 'employee_id') {
                    $val = 'i.employee_id';
                }
                $where[$key][] = $val;
            }
        }
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $adminId = $loginUserInfo['id'];

        $column = 'i.employee_id';
        if ($loginUserInfo['is_super_manager']) {
            $column = 'i.check_id';
        }

        $defaultWhere = [
            ['i.delete_time', '=', 0],
            ['p.delete_time', '=', 0],
            [$column, '=', $adminId],
        ];
        $dWhere[] = array_merge($where, $defaultWhere);

        $whereOr = [
            ['i.delete_time', '=', 0],
            ['p.delete_time', '=', 0],
            ['i.admin_user_id', '=', $adminId,],
        ];
        $oWhere[] = array_merge($where, $whereOr);

        $limit = (int)$this->request->param('limit');
        $data = Db::name(self::TableInfo)->alias('i')
            ->field('p.id, p.dept_id, p.employee_id, p.start_time, p.end_time, i.id iid, i.performance_id, i.content, i.description, i.money, i.single_money, i.check_id, i.admin_user_id, i.create_time')
            ->where($dWhere)
            ->whereOr($oWhere)
            ->order('id desc')
            ->leftJoin('lt_employee_performance p', 'p.id = i.performance_id');
        $data = $data->paginate($limit)->toArray();

        $deptArr = self::dpt(true);
        $userArr = self::employee();

        foreach ($data['data'] as &$item) {
            $item['dept_id'] = $deptArr[$item['dept_id']];
            $item['employee_id'] = $userArr[$item['employee_id']];
            $item['total'] = Db::name(self::TableLog)->where([['info_id', '=', $item['iid']], ['delete_time', '=', 0]])->sum('single_money');
            $item['count'] = Db::name(self::TableLog)->where([['info_id', '=', $item['iid']], ['delete_time', '=', 0]])->count();
            $item['check_id'] = $userArr[$item['check_id']];
            $item['admin_user_id'] = $userArr[$item['admin_user_id']];
            $item['start_time'] = date('Y/m/d', $item['start_time']) . ' ~ ' . date('Y/m/d', $item['end_time']);
            $item['create_time'] = date('Y/m/d H:i:s', $item['create_time']);
        }

        return $this->success('数据获取成功', $data);

    }

    protected function record($ids)
    {
        $record = Db::name(self::TableInfo)->where([
            ['performance_id', 'in', $ids],
            ['delete_time', '=', 0]
        ])->select()->toArray();

        $arr = [];
        foreach ($record as $item) {
            $arr[$item['performance_id']][] = $item;
        }

        return $arr;
    }

    // 主表详情
    public function info()
    {
        $id = (int)$this->request->param('id');
        $info = Db::name(self::TableMain)->where('id', $id)->where('delete_time', 0)->find();
        if (!$info) {
            return $this->error('数据未找到');
        }

        $userArr = self::employee();
        $info['nickname'] = $userArr[$info['employee_id']];
        $info['start_time'] = date('Y-m-d', $info['start_time']);
        $info['end_time'] = date('Y-m-d', $info['end_time']);

        $userIds = Db::name('admin_role_user')->where('admin_user_id', '<>', $info['employee_id'])->where('admin_role_id', $info['dept_id'])->column('admin_user_id');
        $info['user'] = Db::name('admin_user')
            ->field('id, nickname')
            ->where([
                ['id', 'in', $userIds],
                ['delete_time', '=', 0],
                ['status', '=', 1],
                ['is_super_manager', '=', 2],
            ])->select()->toArray();

        $info['record'] = Db::name(self::TableInfo)->where([
            ['performance_id', '=', $id],
            ['delete_time', '=', 0]
        ])->select()->toArray();

        return $this->success('获取成功', $info);
    }

    // 查看 log 列表
    public function logs()
    {
        $limit = (int)$this->request->param('limit');
        $infoId = (int)$this->request->param('info_id');
        $data = Db::name(self::TableLog)
            ->field('id, happen_time, reason, single_money, admin_user_id, create_time')
            ->where('info_id', $infoId)
            ->where('delete_time', 0)
            ->order('id desc');

        $data = $data->paginate($limit)->toArray();
        $userArr = self::users(true, self::TableLog, 'admin_user_id');
        foreach ($data['data'] as &$item) {
            $item['happen_time'] = date('Y/m/d', $item['happen_time']);
            $item['create_time'] = date('Y/m/d H:i:s', $item['create_time']);
            $item['admin_user_id'] = $userArr[$item['admin_user_id']];
        }

        return $this->success('获取成功', $data);
    }

    // 添加log
    public function raise()
    {
        $infoId = (int)$this->request->param('info_id');
        $reason = trim($this->request->param('reason'));
        $money = (double)$this->request->param('single_money');
        $happen = trim($this->request->param('happen_time'));

        if (!$reason || mb_strlen($reason) > 1000) {
            return $this->error('扣款理由长度范围在0 - 1000');
        }

        if ($money <= 0 || $money > 99999999) {
            return $this->error('请检查扣款金额');
        }

        if (!$happen) {
            return $this->error('请填写事发日期');
        }
        $happen = strtotime($happen);
        if ($happen > time()) {
            return $this->error('请检查事发日期');
        }

        $hasInfo = Db::name(self::TableInfo)->where('id', $infoId)->find();
        if (!$hasInfo) return $this->error('未找到要记录的绩效');

        $total = Db::name(self::TableLog)->where('delete_time', 0)->where('info_id', $infoId)->sum('single_money');

        $main = Db::name(self::TableMain)->where('id', $hasInfo['performance_id'])->find();
        if ($happen < $main['start_time'] || $happen > $main['end_time']) return $this->error('事发时间超出考核时间范围');
        // if ($money > $hasInfo['single_money']) return $this->error('扣款金额超过了单次扣款金额');
        if ($hasInfo['money'] > 0 && ($total + $money) > $hasInfo['money']) return $this->error('扣款金额已超过封顶金额');

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $adminId = $loginUserInfo['id'];
        $arr = [
            'info_id' => $hasInfo['id'],
            'performance_id' => $hasInfo['performance_id'],
            'employee_id' => $hasInfo['employee_id'],
            'check_id' => $hasInfo['check_id'],
            'reason' => $reason,
            'single_money' => $money,
            'happen_time' => $happen,
            'create_time' => time(),
            'admin_user_id' => $adminId,
        ];
        $bool = Db::name(self::TableLog)->insert($arr);

        if (!$bool) $this->error('创建失败');

        return $this->success('创建成功');
    }

    // 编辑
    public function edit()
    {
        $request = $this->request->param();
        $id = (int)$request['id'];
        $main = Db::name(self::TableMain)->where([
            ['id', '=', $id],
            ['delete_time', '=', 0]
        ])->find();

        if (!$main) return $this->error('未找到要编辑得信息');
        $record = Db::name(self::TableInfo)->where('performance_id', $main['id'])->column('id');

        $deptId = (int)$request['dept_id'];
        $employeeId = (int)$request['employee_id'];
        if (!$deptId || !$employeeId) return $this->error('请选择部门和被考核人');

        $startTime = trim($request['start_time']);
        $endTime = trim($request['end_time']);
        if (!$startTime || !$endTime) return $this->error('请选择考核时间');

        $startTime = strtotime($startTime);
        $endTime = strtotime($endTime);
        if ($endTime <= $startTime) return $this->error('请检查考核时间');
        $hasRecord = Db::name(self::TableMain)
            ->where([
                ['employee_id', '=', $employeeId],
                ['dept_id', '=', $deptId],
                ['start_time', 'between', [$startTime, $endTime]],
                ['delete_time', '=', 0],
            ])->find();
        if ($hasRecord && $hasRecord['id'] != $id) return $this->error('此考核人在该时间段已有绩效，请前往修改');

        Db::name(self::TableMain)->update([
            'id' => $id,
            'dept_id' => $deptId,
            'employee_id' => $employeeId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'update_time' => time(),
        ]);

        $delIds = ltrim($request['delid'], ',');
        if ($delIds) {
            $idArr = explode(',', $delIds);
            foreach ($idArr as $id) {
                Db::name(self::TableInfo)->update([
                    'id' => $id,
                    'delete_time' => time(),
                ]);
            }
        }

        // 修改
        $msg = '';
        $flag = 0;
        foreach ($record as $key) {
            if (isset($request[$key . '_info_id'])) {

                if (!$request[$key . '_content'] || mb_strlen($request[$key . '_content']) > 1000) {
                    $msg = '[ 第 ' . $key . ' 项 ] 考核内容长度范围在0 - 1000<br>';
                    return $this->error($msg);
                }

                if (!$request[$key . '_description'] || mb_strlen($request[$key . '_description']) > 1000) {
                    $msg = '[ 第 ' . $key . ' 项 ] 考核长度范围在0 - 1000<br>';
                    return $this->error($msg);
                }

                if (!$request[$key . '_check_id']) {
                    $msg = '[ 第 ' . $key . ' 项 ] 选择考核人<br>';
                    return $this->error($msg);
                }

                $money = (double)$request[$key . '_money'];
                if ($money < 0 || $money > 99999999) {
                    $msg = '[ 第 ' . $key . ' 项 ] 封顶金额0 - 99999999<br>';
                    return $this->error($msg);
                }
                $sMoney = (double)$request[$key . '_single_money'];
                if ($sMoney < 0 || $sMoney > 99999999) {
                    $msg = '[ 第 ' . $key . ' 项 ] 单次扣款金额 0 - 99999999<br>';
                    return $this->error($msg);
                }

                if ($sMoney > $money) {
                    $msg = '[ 第 ' . $key . ' 项 ] 单次扣款金额 大于 封顶金额<br>';
                    return $this->error($msg);
                }

                $flag += Db::name(self::TableInfo)->update([
                    'id' => $request[$key . '_info_id'],
                    'content' => $request[$key . '_content'],
                    'description' => $request[$key . '_description'],
                    'check_id' => $request[$key . '_check_id'],
                    'money' => $money,
                    'single_money' => $sMoney,
                    'update_time' => time()
                ]);
            }
        }

        // 新增
        $infoArr = [];
        $content = isset($request['content']) ? $request['content'] : [];
        $description = isset($request['description']) ? $request['description'] : [];
        $checkIds = isset($request['check_id']) ? $request['check_id'] : [];
        $money = isset($request['money']) ? $request['money'] : [];
        $singleMoney = isset($request['single_money']) ? $request['single_money'] : [];

        if ($content) {
            foreach ($content as $k => $v) {
                $infoArr[$k]['content'] = $v;
            }
            foreach ($description as $k => $v) {
                $infoArr[$k]['description'] = $v;
            }
            foreach ($checkIds as $k => $v) {
                $infoArr[$k]['check_id'] = $v;
            }
            foreach ($money as $k => $v) {
                $infoArr[$k]['money'] = $v;
            }
            foreach ($singleMoney as $k => $v) {
                $infoArr[$k]['single_money'] = $v;
            }
        }

        $lastId = $main['id'];
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $adminId = $loginUserInfo['id'];

        $arr = [];
        foreach ($infoArr as $k => $info) {
            $content = trim($info['content']);
            $ind = $k + 1;
            if (!$content || mb_strlen($content) > 1000) {
                $msg = '[ 第 ' . $ind . ' 项 ] 考核内容长度范围在0 - 1000<br>';
                return $this->error($msg);
            }

            $description = trim($info['description']);
            if (!$description || mb_strlen($description) > 1000) {
                $msg = '[ 第 ' . $ind . ' 项 ] 考核长度范围在0 - 1000<br>';
                return $this->error($msg);
            }

            $checkId = (int)$info['check_id'];
            if (!$checkId) {
                $msg = '[ 第 ' . $ind . ' 项 ] 选择考核人<br>';
                return $this->error($msg);
            }

            $money = (double)$info['money'];
            if ($money < 0 || $money > 99999999) {
                $msg = '[ 第 ' . $ind . ' 项 ] 封顶金额0 - 99999999<br>';
                return $this->error($msg);
            }
            $sMoney = (double)$info['single_money'];
            if ($sMoney < 0 || $sMoney > 99999999) {
                $msg = '[ 第 ' . $ind . ' 项 ] 单次扣款金额 0 - 99999999<br>';
                return $this->error($msg);
            }

            if ($sMoney > $money) {
                $msg = '[ 第 ' . $ind . ' 项 ] 单次扣款金额 大于 封顶金额<br>';
                return $this->error($msg);
            }

            $arr[] = [
                'employee_id' => $employeeId,
                'performance_id' => $lastId,
                'content' => $content,
                'description' => $description,
                'money' => $money,
                'single_money' => $sMoney,
                'check_id' => $checkId,
                'create_time' => time(),
                'admin_user_id' => $adminId,
            ];
        }

        if ($arr && Db::name(self::TableInfo)->insertAll($arr)) {
            return $this->success('创建成功<br>' . $msg);
        }

        if ($flag) {
            return $this->success('更新成功<br>' . $msg);
        }
        return $this->error('更新失败<br>' . $msg);
    }

    // 新增主表
    public function add()
    {
        $request = $this->request->param();

        $deptId = (int)$request['dept_id'];
        $employeeId = (int)$request['employee_id'];
        if (!$deptId || !$employeeId) return $this->error('请选择部门和被考核人');

        $startTime = trim($request['start_time']);
        $endTime = trim($request['end_time']);
        if (!$startTime || !$endTime) return $this->error('请选择考核时间');

        $startTime = strtotime($startTime);
        $endTime = strtotime($endTime);
        if ($endTime <= $startTime) return $this->error('请检查考核时间');

        $hasRecord = Db::name(self::TableMain)
            ->where([
                ['employee_id', '=', $employeeId],
                ['dept_id', '=', $deptId],
                ['start_time', 'between', [$startTime, $endTime]],
                ['delete_time', '=', 0],
            ])->count();

        if ($hasRecord) return $this->error('此考核人在该时间段已有绩效，请前往修改');

        $infoArr = [];
        $content = isset($request['content']) ? $request['content'] : [];
        $description = isset($request['description']) ? $request['description'] : [];
        $checkIds = isset($request['check_id']) ? $request['check_id'] : [];
        $money = isset($request['money']) ? $request['money'] : [];
        $singleMoney = isset($request['single_money']) ? $request['single_money'] : [];

        foreach ($content as $k => $v) {
            $infoArr[$k]['content'] = $v;
        }
        foreach ($description as $k => $v) {
            $infoArr[$k]['description'] = $v;
        }
        foreach ($checkIds as $k => $v) {
            $infoArr[$k]['check_id'] = $v;
        }
        foreach ($money as $k => $v) {
            $infoArr[$k]['money'] = $v;
        }
        foreach ($singleMoney as $k => $v) {
            $infoArr[$k]['single_money'] = $v;
        }

        foreach ($infoArr as $k => $info) {
            $content = trim($info['content']);
            $ind = $k + 1;
            if (!$content || mb_strlen($content) > 1000) {
                $msg = '[ 第 ' . $ind . ' 项 ] 考核内容长度范围在0 - 1000<br>';
                return $this->error($msg);
            }

            $description = trim($info['description']);
            if (!$description || mb_strlen($description) > 1000) {
                $msg = '[ 第 ' . $ind . ' 项 ] 考核长度范围在0 - 1000<br>';
                return $this->error($msg);
            }

            $checkId = (int)$info['check_id'];
            if (!$checkId) {
                $msg = '[ 第 ' . $ind . ' 项 ] 选择考核人<br>';
                return $this->error($msg);
            }

            $money = (double)$info['money'];
            if ($money < 0 || $money > 99999999) {
                $msg = '[ 第 ' . $ind . ' 项 ] 封顶金额0 - 99999999<br>';
                return $this->error($msg);
            }

            $sMoney = (double)$info['single_money'];
            if ($sMoney < 0 || $sMoney > 99999999) {
                $msg = '[ 第 ' . $ind . ' 项 ] 单次扣款金额 0 - 99999999<br>';
                return $this->error($msg);
            }

            if ($sMoney > $money) {
                $msg = '[ 第 ' . $ind . ' 项 ] 单次扣款金额 大于 封顶金额<br>';
                return $this->error($msg);
            }
        }

        $loginUserInfo = UserServiceFacade::getUserInfo();
        $adminId = $loginUserInfo['id'];
        $main = [
            'dept_id' => $deptId,
            'employee_id' => $employeeId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'create_time' => time(),
            'admin_user_id' => $adminId,
        ];
        $lastId = Db::name(self::TableMain)->insert($main, true);

        $arr = [];
        foreach ($infoArr as $info) {
            $arr[] = [
                'employee_id' => $employeeId,
                'performance_id' => $lastId,
                'content' => trim($info['content']),
                'description' => trim($info['description']),
                'money' => (double)$info['money'],
                'single_money' => (double)$info['single_money'],
                'check_id' => (int)$info['check_id'],
                'create_time' => time(),
                'admin_user_id' => $adminId,
            ];
        }

        if (!$arr) return $this->error('没有有效考核项');

        if (Db::name(self::TableInfo)->insertAll($arr)) {
            return $this->success('创建成功');
        }

        return $this->error('创建失败');
    }

    // 复制
    public function copy()
    {
        $request = $this->request->param();
        $id = (int)$request['id'];
        $main = Db::name(self::TableMain)->where([
            ['id', '=', $id],
            ['delete_time', '=', 0]
        ])->find();

        if (!$main) return $this->error('未找到要编辑得信息');
        $record = Db::name(self::TableInfo)->where('performance_id', $main['id'])->column('id');

        $deptId = (int)$request['dept_id'];
        $employeeId = (int)$request['employee_id'];
        if (!$deptId || !$employeeId) return $this->error('请选择部门和被考核人');

        $startTime = trim($request['start_time']);
        $endTime = trim($request['end_time']);
        if (!$startTime || !$endTime) return $this->error('请选择考核时间');

        $startTime = strtotime($startTime);
        $endTime = strtotime($endTime);
        if ($endTime <= $startTime) return $this->error('请检查考核时间');

        $hasRecord = Db::name(self::TableMain)
            ->where([
                ['employee_id', '=', $employeeId],
                ['dept_id', '=', $deptId],
                ['start_time', 'between', [$startTime, $endTime]],
                ['delete_time', '=', 0],
            ])->count();
        if ($hasRecord) return $this->error('此考核人在该时间段已有绩效，请前往修改');

        // 修改
        $msg = '';
        $flag = 0;
        foreach ($record as $key) {
            if (isset($request[$key . '_info_id'])) {

                if (!$request[$key . '_content'] || mb_strlen($request[$key . '_content']) > 1000) {
                    $msg = '[ 第 ' . $key . ' 项 ] 考核内容长度范围在0 - 1000<br>';
                    return $this->error($msg);
                }

                if (!$request[$key . '_description'] || mb_strlen($request[$key . '_description']) > 1000) {
                    $msg = '[ 第 ' . $key . ' 项 ] 考核长度范围在0 - 1000<br>';
                    return $this->error($msg);
                }

                if (!$request[$key . '_check_id']) {
                    $msg = '[ 第 ' . $key . ' 项 ] 选择考核人<br>';
                    return $this->error($msg);
                }

                $money = (double)$request[$key . '_money'];
                if ($money < 0 || $money > 99999999) {
                    $msg = '[ 第 ' . $key . ' 项 ] 封顶金额0 - 99999999<br>';
                    return $this->error($msg);
                }
                $sMoney = (double)$request[$key . '_single_money'];
                if ($sMoney < 0 || $sMoney > 99999999) {
                    $msg = '[ 第 ' . $key . ' 项 ] 单次扣款金额 0 - 99999999<br>';
                    return $this->error($msg);
                }

                if ($sMoney > $money) {
                    $msg = '[ 第 ' . $key . ' 项 ] 单次扣款金额 大于 封顶金额<br>';
                    return $this->error($msg);
                }

                $flag += Db::name(self::TableInfo)->update([
                    'id' => $request[$key . '_info_id'],
                    'content' => $request[$key . '_content'],
                    'description' => $request[$key . '_description'],
                    'check_id' => $request[$key . '_check_id'],
                    'money' => $money,
                    'single_money' => $sMoney,
                    'update_time' => time()
                ]);
            }
        }

        // 新增
        $infoArr = [];
        $content = isset($request['content']) ? $request['content'] : [];
        $description = isset($request['description']) ? $request['description'] : [];
        $checkIds = isset($request['check_id']) ? $request['check_id'] : [];
        $money = isset($request['money']) ? $request['money'] : [];
        $singleMoney = isset($request['single_money']) ? $request['single_money'] : [];

        if ($content) {
            foreach ($content as $k => $v) {
                $infoArr[$k]['content'] = $v;
            }
            foreach ($description as $k => $v) {
                $infoArr[$k]['description'] = $v;
            }
            foreach ($checkIds as $k => $v) {
                $infoArr[$k]['check_id'] = $v;
            }
            foreach ($money as $k => $v) {
                $infoArr[$k]['money'] = $v;
            }
            foreach ($singleMoney as $k => $v) {
                $infoArr[$k]['single_money'] = $v;
            }
        }

        $lastId = $main['id'];
        $loginUserInfo = UserServiceFacade::getUserInfo();
        $adminId = $loginUserInfo['id'];

        $arr = [];
        foreach ($infoArr as $k => $info) {
            $content = trim($info['content']);
            $ind = $k + 1;
            if (!$content || mb_strlen($content) > 1000) {
                $msg = '[ 第 ' . $ind . ' 项 ] 考核内容长度范围在0 - 1000<br>';
                return $this->error($msg);
            }

            $description = trim($info['description']);
            if (!$description || mb_strlen($description) > 1000) {
                $msg = '[ 第 ' . $ind . ' 项 ] 考核长度范围在0 - 1000<br>';
                return $this->error($msg);
            }

            $checkId = (int)$info['check_id'];
            if (!$checkId) {
                $msg = '[ 第 ' . $ind . ' 项 ] 选择考核人<br>';
                return $this->error($msg);
            }

            $money = (double)$info['money'];
            if ($money < 0 || $money > 99999999) {
                $msg = '[ 第 ' . $ind . ' 项 ] 封顶金额0 - 99999999<br>';
                return $this->error($msg);
            }
            $sMoney = (double)$info['single_money'];
            if ($sMoney < 0 || $sMoney > 99999999) {
                $msg = '[ 第 ' . $ind . ' 项 ] 单次扣款金额 0 - 99999999<br>';
                return $this->error($msg);
            }

            if ($sMoney > $money) {
                $msg = '[ 第 ' . $ind . ' 项 ] 单次扣款金额 大于 封顶金额<br>';
                return $this->error($msg);
            }

            $arr[] = [
                'employee_id' => $employeeId,
                'performance_id' => $lastId,
                'content' => $content,
                'description' => $description,
                'money' => $money,
                'single_money' => $sMoney,
                'check_id' => $checkId,
                'create_time' => time(),
                'admin_user_id' => $adminId,
            ];
        }

        if ($arr && Db::name(self::TableInfo)->insertAll($arr)) {
            return $this->success('创建成功<br>' . $msg);
        }

        if ($flag) {
            return $this->success('更新成功<br>' . $msg);
        }
        return $this->error('更新失败<br>' . $msg);
    }

    // 删除主表记录
    public function del()
    {
        $id = (int)$this->request->param('id');
        $info = Db::name(self::TableMain)->field('id')->where([
            ['id', '=', $id],
            ['delete_time', '=', 0]
        ])->find();

        if (!$info) return $this->error('未找到删除的数据');

        // 删除主表
        $bool = Db::name(self::TableMain)->update([
            'id' => $info['id'],
            'delete_time' => time(),
            'update_time' => time()
        ]);
        if (!$bool) return $this->error('删除失败');

        // 删除info
        $infoIds = Db::name(self::TableInfo)->where('performance_id', $info['id'])->where('delete_time', 0)->column('id');
        foreach ($infoIds as $id) {
            Db::name(self::TableInfo)->update([
                'id' => $id,
                'delete_time' => time(),
                'update_time' => time()
            ]);
        }

        // 删除log
        $logIds = Db::name(self::TableLog)->where('performance_id', $info['id'])->where('delete_time', 0)->column('id');
        foreach ($logIds as $id) {
            Db::name(self::TableLog)->update([
                'id' => $id,
                'delete_time' => time(),
                'update_time' => time()
            ]);
        }

        return $this->success('删除成功');
    }

    // log 详情
    public function log()
    {
        $id = (int)$this->request->param('id');
        $info = Db::name(self::TableLog)->where('id', $id)->where('delete_time', 0)->find();
        if (!$info) {
            return $this->error('数据未找到');
        }
        $info['happen_time'] = date('Y-m-d', $info['happen_time']);

        return $this->success('获取成功', $info);
    }

    // log 编辑
    public function rework()
    {
        $id = (int)$this->request->param('id');
        $reason = trim($this->request->param('reason'));
        $money = (double)$this->request->param('single_money');
        $happen = trim($this->request->param('happen_time'));

        if (!$reason || mb_strlen($reason) > 1000) {
            return $this->error('扣款理由长度范围在0 - 1000');
        }

        if ($money <= 0 || $money > 99999999) {
            return $this->error('请检查扣款金额');
        }

        if (!$happen) {
            return $this->error('请填写事发日期');
        }
        $happen = strtotime($happen);
        if ($happen > time()) {
            return $this->error('请检查事发日期');
        }

        $hasLog = Db::name(self::TableLog)->where('id', $id)->find();
        if (!$hasLog) return $this->error('未找到要编辑的绩效');

        $hasInfo = Db::name(self::TableInfo)->where('id', $hasLog['info_id'])->find();
        $total = Db::name(self::TableLog)->where('delete_time', 0)->where('info_id', $hasLog['info_id'])->sum('single_money');

        $main = Db::name(self::TableMain)->where('id', $hasInfo['performance_id'])->find();
        if ($happen < $main['start_time'] || $happen > $main['end_time']) return $this->error('事发时间超出考核时间范围');

        // if ($money > $hasInfo['single_money']) return $this->error('扣款金额超过了单次扣款金额');
        if ($hasInfo['money'] > 0 && ($total + $money) > $hasInfo['money']) return $this->error('扣款金额已超过封顶金额');

        $arr = [
            'id' => $id,
            'reason' => $reason,
            'single_money' => $money,
            'happen_time' => $happen,
            'update_time' => time(),
        ];
        $bool = Db::name(self::TableLog)->update($arr);

        if (!$bool) $this->error('编辑失败');

        return $this->success('编辑成功');
    }

    // 删除log
    public function discard()
    {
        $id = (int)$this->request->param('id');

        $info = Db::name(self::TableLog)->field('id')->where([
            ['id', '=', $id],
            ['delete_time', '=', 0]
        ])->find();

        if (!$info) return $this->error('未找到删除的数据');

        // 删除info
        $bool = Db::name(self::TableLog)->update([
            'id' => $info['id'],
            'delete_time' => time(),
            'update_time' => time()
        ]);
        if (!$bool) return $this->error('删除失败');

        return $this->success('删除成功');
    }


    // 新增时 - 部门
    public function dept()
    {
        $list = Db::name('department')->field('id, department_name as name')->where('delete_time', 0)->select()->toArray();

        return $this->success('获取成功', $list);
    }

    // 用于搜索，过滤 | 列表展示
    public function dpt($isArr = false)
    {
        /*$deptIds = Db::name(self::TableMain)->distinct('dept_id')->column('dept_id');
        if (!$deptIds) return [];

        $list = Db::name('department')->field('id, department_name as name')->whereIn('id', $deptIds)->where('delete_time', 0)->select()->toArray();*/

        $list = Db::name('department')->field('id, department_name as name')->where('delete_time', 0)->select()->toArray();


        $arr = [];
        if ($isArr) {
            foreach ($list as $item) {
                $arr[$item['id']] = $item['name'];
            }

            return $arr;
        }

        return $this->success('获取成功', $list);
    }

    // 用于搜索，过滤 | 列表展示
    public function users($isArr = false, $tabName = self::TableMain, $uid = 'employee_id')
    {

        $list = Db::name('admin_user')->field('id, nickname')->where('delete_time', 0)->select()->toArray();

        $arr = [];
        if ($isArr) {
            foreach ($list as $item) {
                $arr[$item['id']] = $item['nickname'];
            }

            return $arr;
        }

        return $this->success('获取成功', $list);
    }

    // 用于搜索，过滤 | 列表展示
    public function employee()
    {
        $list = Db::name('admin_user')
            ->field('id, nickname')
            ->select()->toArray();

        $arr = [];
        foreach ($list as $item) {
            $arr[$item['id']] = $item['nickname'];
        }

        return $arr;
    }

    // 新增时 - 被考核人
    public function user()
    {
        $roleId = (int)$this->request->param('role_id');

       /* $userIds = Db::name('admin_role_user')->where('admin_role_id', $roleId)->column('admin_user_id');
        if (!$userIds) return $this->success('该部门暂无成员');*/

        // ->whereFindInSet('merchant_ids', env('EMPLOYEE.merchant_id'))
        $list = Db::name('admin_user')
            ->field('id, nickname')
            ->where([
                ['delete_time', '=', 0],
                ['status', '=', 1],
                ['department_id', '=', $roleId]
            ])->select()->toArray();

        return $this->success('获取成功', $list);
    }

    // 修改时 - 考核人
    public function admin()
    {
        $list = Db::name('admin_user')
            ->field('id, nickname')
            ->where([
                ['delete_time', '=', 0],
                ['status', '=', 1],
            ])->select()->toArray();

        return $this->success('获取成功', $list);
    }

    // 编辑 ==============================================


    // 删除 ==============================================

    // 删除info
    public function drop()
    {
        $id = (int)$this->request->param('id');
        $info = Db::name(self::TableInfo)->field('id')->where([
            ['id', '=', $id],
            ['delete_time', '=', 0]
        ])->find();

        if (!$info) return $this->error('未找到删除的数据');

        // 删除info
        $bool = Db::name(self::TableInfo)->update([
            'id' => $info['id'],
            'delete_time' => time(),
            'update_time' => time()
        ]);
        if (!$bool) return $this->error('删除失败');

        // 删除log
        $logIds = Db::name(self::TableLog)->where('info_id', $info['id'])->where('delete_time', 0)->column('id');
        foreach ($logIds as $id) {
            Db::name(self::TableLog)->update([
                'id' => $id,
                'delete_time' => time(),
                'update_time' => time()
            ]);
        }
        return $this->success('删除成功');
    }
}