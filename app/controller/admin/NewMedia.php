<?php

namespace app\controller\admin;

use laytp\controller\Backend;
use app\logic\ImportFileLogic;
use app\model\admin\DouyinDayModel;
use app\model\admin\DouyinWorksModel;
use app\model\admin\KuaishouDayModel;
use app\model\admin\KuaishouWorksModel;
use app\model\admin\ShipinhaoWorksModel;
/**
 * 新媒体数据导入控制器
 */

class NewMedia extends Backend
{
    protected $noNeedLogin = ['index'];//无需登录，也无需鉴权的方法名列表，支持*通配符定义所有方法
    protected $noNeedAuth = ['index'];//需要登录，但是无需鉴权的方法名列表，支持*通配符定义所有方法
    public function index()
    {
        $file      = $this->request->file('file');
        $logic     = new ImportFileLogic();
        $sheet_arr = [
            ['title' => '快手日数据', 'model' => new KuaishouDayModel()],
            ['title' => '快手作品数据', 'model' => new KuaishouWorksModel()],
            ['title' => '抖音日数据', 'model' => new DouyinDayModel()],
            ['title' => '抖音作品数据', 'model' => new DouyinWorksModel()],
            ['title' => '视频号作品数据', 'model' => new ShipinhaoWorksModel()],
        ];
        $result    = $logic->get_excel_info($file->getPathname(), $sheet_arr);
        if ($result) {
            return $this->success('导入成功');
        } else {
            return $this->error($logic->getError()?:'导入失败');
        }
    }
}

