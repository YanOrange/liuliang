<?php
/**
 * 报名表模型
 */

namespace app\model\api\learn;

use laytp\BaseModel;
use think\model\concern\SoftDelete;
use app\lib\api\exception\Exception;
use app\model\api\Channel;
use app\model\admin\LearnBanner;

class Article extends BaseModel
{
    use SoftDelete;
    //模型名
    protected $name = 'article';

    public static function getArticleList($params)
    {
        extract($params);
        $channelInfo = Channel::getChannelAppClass($channel);
        $where = [];
        if(isset($class_id) && !empty($class_id)){
            $where[] = ['class_id','=',$class_id];
        }
        $data['articleImage'] = LearnBanner::where('status',1)
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            ->where('banner_type',2)->value('image');
        $data['articleClass'] = [
            ['id' => 0,'name' => '推荐'],
            ['id' => 1,'name' => '学习咨询']
        ];
        if ($channelInfo['app_class_id'] == 9) {
            $data['articleTop'] = self::whereIn('id', [101,102,103,104,105,106])
            //->where('article_type',4)
            ->field('id,title,image,content,article_video_url,virtual_read_nums,create_time')
            ->order('id desc')
            ->limit(6)
            ->select()
            ->toArray();
        } else {
            $data['articleTop'] = self::whereFindInSet('channel_ids',$channelInfo['channel_id'])
            //->where('article_type',4)
            ->where('status',1)
            ->field('id,title,image,content,article_video_url,virtual_read_nums,create_time')
            ->order('id desc')
            ->limit(6)
            ->select()
            ->toArray();
        }
        
        if(!empty($data['articleTop'])){
            foreach($data['articleTop'] as &$item){
                $item['username'] = self::userNameAvatar()['name'][rand(0,6)];
                $item['avatar'] = self::userNameAvatar()['avatar'][rand(0,6)];
            }
        }
        $data['articleList'] = self::where($where)
            ->whereFindInSet('channel_ids',$channelInfo['channel_id'])
            //->where('article_type',4)
            ->where('status',1)
            ->field('id,title,image,content,article_video_url,virtual_read_nums,create_time')
            //->order('id desc')
            ->paginate(10)
            ->toArray();
        if(!empty($data['articleList']['data'])){
            foreach($data['articleList']['data'] as &$item1){
                $item1['username'] = self::userNameAvatar()['name'][rand(0,6)];
                $item1['avatar'] = self::userNameAvatar()['avatar'][rand(0,6)];
            }
        }
        return $data;
    }

    public static function userNameAvatar()
    {
        $data['name'] = ['李园梦','纪志颖','杨俊才','张雅兰','田浩宇','杨王丽','曹金萍'];
        $data['avatar'] = [
            'http://cdnwm.yuluojishu.com/uploads/20220511/bd3c087e2fd965cd3eb51b1e9b2f96c4.jpg',
            'http://cdnwm.yuluojishu.com/uploads/20220930/7d81f70dce124891f1ae3bef5105b09c.jpg',
            'http://cdnwm.yuluojishu.com/uploads/20221117/8af697ffb87bac275a58de6893affb48.jpg',
            'http://cdnwm.yuluojishu.com/uploads/20220511/6f4c9121daf28a0f3dcd69e1407fb833.jpg',
            'http://cdnwm.yuluojishu.com/uploads/20220511/dcb0767cee09fb99f627637a61390274.jpg',
            'http://cdnwm.yuluojishu.com/uploads/20220511/a045bbe9988ee76d2f5ebbbe12a54929.png',
            'http://cdnwm.yuluojishu.com/uploads/20220511/d34dae02a95e5876be6b96588ac7db3b.jpg',
        ];
        return $data;
    }

}