<?php
/**
 * 咨询
 */

namespace app\model\api\overdue;

use app\lib\api\other\CommonCourseV2;
use laytp\BaseModel;
use app\model\api\single\SingleCourse;
use app\model\api\v2\UserList;
use app\model\api\ArticleNews;
use app\model\api\Channel;
use app\model\api\Article;

class Other extends BaseModel
{
    public static function firstPage($params = [])
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $lampImgs = ['http://cdnwm.yuluojishu.com/uploads/20230707/60d3c712dc65a27b4734abddd38298e6.png', 'http://cdnwm.yuluojishu.com/uploads/20230707/9958ce0ee98c4f26dd71d4cf0a2bcd87.png'];
        $user = UserList::getUserInfo();
        $singleCourseList = SingleCourse::field('id,video_cover_image,video_url,title,virtual_apply_nums')->whereIn('id', [10045,10044,10043,10042,10041,10040])->select();
        $consultList = ArticleNews::getArticleNewsListV4($params);
        $enrollData = CommonCourseV2::getCommonCourseToCourseId($channel);
        //   $articleList =
        return [
            'lamp_imgs' => $lampImgs,
            'gather_info_list' => $user['gather_info_list'] ?? [],
            'video_data_list' => $singleCourseList,
            'consult_data_list' => $consultList,
            'case_data_list' => ArticleNews::getArticleNewsListV2($params),
            'article_data_list' => Article::getMerchantArticleList($enrollData['merchant_id']),
            'enroll_data' => $enrollData,
        ];
    }
    //获取咨询
    public static function getConsultData($params = [])
    {
        extract($params);
        $consultData = [
            ['back_img' => 'http://cdnwm.yuluojishu.com/uploads/20230420/d5afa68e783b5a4002ecf012e7915f61.png', 'title' => '预约咨询','sub_title' => '专业逾期咨询顾问/带你摆脱债务困境', 'labelData' => ['停止催收', '合法合规'],  'btn_desc' => '点我咨询'],
            ['back_img' => 'http://cdnwm.yuluojishu.com/uploads/20230420/745a86115ef2af5402bff6d2f4e2a720.png', 'title' => '专业解决方案','sub_title' => '专业逾期咨询顾问/帮你回归正常生活', 'labelData' => ['合法合规', '避免起诉'],  'btn_desc' => '点我解决'],
            ['back_img' => 'http://cdnwm.yuluojishu.com/uploads/20230420/cb562636ecad0396b85028bb71cabe15.png', 'title' => '法务授信', 'sub_title' => '专业逾期咨询顾问/合理合规停止催收', 'labelData' => ['减免罚息', '1v1定制'],  'btn_desc' => '点我授信'],
            ['back_img' => 'http://cdnwm.yuluojishu.com/uploads/20230420/e3b51d002a62a1dc3652468a81b89b82.png', 'title' => '预约咨询','sub_title' => '专业逾期咨询顾问/摆脱催收利滚利', 'labelData' => ['避免起诉', '合法合规', '减免罚息违约金'],  'btn_desc' => '点我咨询'],
        ];
        $enrollData =  CommonCourseV2::getCommonCourseToCourseId($channel);
        return compact('consultData', 'enrollData');
    }
}
