<?php

namespace app\controller\api\touliu;
use app\model\api\touliu\Show as ShowModel;
use app\controller\api\BaseApi;
use app\model\admin\Article;

/**
 * 首页
 */
class Show extends BaseApi
{
    public $noNeedLogin = ['homePage', 'getCustomerCasesList'];
    public $noNeedCheckSign = ['homePage'];

    /**
     * 首页
     *
     * @return void
     */
    public function homePage()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\show\Show', 'homePage');
        return $this->success('首页', ShowModel::homePage($params));
    }

    /**
     * 预期留资页
     *
     * @return void
     */
    public function getGatherInfoData()
    {
        $token = $this->request->header('token');
        return $this->success('获取成功', ShowModel::getGatherInfoData($token,0));
    }

    /**
     * 问问律师留资页
     *
     * @return void
     */
    public function getGatherInfoDataLawyer()
    {
        $token = $this->request->header('token');
        return $this->success('获取成功', ShowModel::getGatherInfoData($token,1));
    }

    /**
     * 资讯列表
     *
     * @return void
     */
    public function getInformationList()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\show\Show', 'getInformationList');
        return $this->success('获取成功', ShowModel::getInformationList($params));
    }

    /**
     * 资讯详情
     *
     * @return void
     */
    public function getInformationInfo()
    {
        $params = $this->request->post();
        $this->commonApiValidate($params, 'app\validate\api\show\Show', 'getInformationInfo');
        return $this->success('获取成功', Article::where('id', $params['id'])->find());
    }

    public function getCustomerCasesList()
    {
        return $this->success('获取成功', ShowModel::getCustomerCasesList());
    }

    /**
     * 计算器
     */
    public function loanCalculator()
    {
        $params = $this->request->post();

        $principal        = $params['principal'] ?? 0;
        $duration         = $params['duration'] ?? 0;
        $interestRate     = intval($params['interest_rate'] ?? 0);
        $repaymentMethod  = $params['repayment_method'] ?? 1;     # 还款方式：1一次性归本付息  2分期还款

        $interestRate = $interestRate > 0 ? $interestRate / 100 : 0;

        $result = $principal * (1 + $interestRate * $duration);

        return $this->success('获取成功', ['result' => $result]);
    }
}