<?php

namespace app\controller\api;
use app\model\api\Course as CourseModel;
use app\model\api\Show as ShowModel;
use app\model\api\AppClass as AppClassModel;
use app\model\api\Merchant;
use app\model\api\Channel;
use app\lib\api\service\AbLandingPageService;
/**
 * 首页
 */
class Show extends BaseApi
{
    public $noNeedLogin = ['getChannelMch5Content'];
    public $noNeedCheckSign = ['getCourseInfo','getChannelMch5Content'];

    public function getChannelMch5Content()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\show\Show', 'getAppClassList');
        $mcH5Content = Channel::where('channel_name', $params['channel'])->value('mc_h5_content');
        return $this->success('获取莓茶h5内容', ['mc_h5_content' => !empty($mcH5Content) ? richText($mcH5Content) : '']);

    }
    //获取ab方案落地页
    public function getAbLandingPageInfo()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\show\Show', 'getAbLandingPageInfo');
        return $this->success('获取ab方案落地页', AbLandingPageService::getAbLandingList($params['landing_page_id'], isset($params['is_ab']) ? $params['is_ab'] : 0));
    }
    public function getCourseInfo()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\show\Show', 'getCourseInfo');
        return $this->success('首页', ShowModel::getCourseInfo($params));
    }
    //首页
    public function homePage()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\show\Show', 'homePage');
        return $this->success('首页', ShowModel::homePage($params));
    }
    //类目列表
    public function getAppClassList()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\show\Show', 'getAppClassList');
        return $this->success('类目列表', AppClassModel::getAppClassList($params));
    }

    //机构列表
    public function getMerchantList()
    {
        $params = $this->request->post();
        return $this->success('机构列表', Merchant::getMerchantList($params));
    }

    /**
     * 逾期列表v1
     * @return
     * @date 2022-09-19
     */
    public function getOverdue()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\show\Show', 'homePage');
        return $this->success('逾期列表', CourseModel::getOverdue($params));
    }

    /**
     * 逾期列表v2
     * @return
     * @date 2022-09-19
     */
    public function getOverdueV2()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\show\Show', 'homePage');
        return $this->success('逾期列表', CourseModel::getOverdueV2($params));
    }

}