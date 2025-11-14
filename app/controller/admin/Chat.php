<?php

namespace app\controller\admin;
use think\Db;
use laytp\controller\Backend;
use think\Session;

class Chat extends Backend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function index(){
        //获取用户列表且最新的消息记录
        $user_list = $this->getUserList();
        //获取自己的用户数据
        $info = Db::name('chat_user')->where(['u_id'=> Session::get('uid')])->find();
        $this->assign('info',$info);
        $this->assign('user_list',$user_list);

        return $this->view->fetch();
    }

    /**
     * 登录并且注册的处理程序
     * 主要对账号密码验证
     *      1. 账号存在，判断密码是否正确，
     *      2. 账号不存在，做新加
     */
    public function login(){
        if ($this->request->isPost()) {
            //接收参数
            $params = $this->request->param();
            $data = [];
            $data['username'] = $params['username'];
            $data['password'] = md5($params['password']);
            //查询该账号是否存在
            $info = Db::name('chat_user')->where(['username'=>$data['username']])->find();
            $return_data =[];

            if(!empty($info)){
                //存在，且密码正确就用session存储u_id
                if($info['password'] == $data['password']){
                    $return_data['u_id'] = $info['u_id'];
                    Session::set('uid',$info['u_id']);
                }else{
                    //密码不正确返回错误，并报错
                    $return_data['u_id'] = 0;
                    $return_data['msg'] = '该账号的密码错误';
                }
            }else{
                //不存在代表没有该用户，新增用户
                $data['u_name'] = '小萝卜'.rand(1000,9999);
                $return_data['u_id'] = Db::name('chat_user')->insertGetId($data);
                Session::set('uid',$return_data['u_id']);
            }
            echo json_encode($return_data);die;
        } else{
            return $this->view->fetch();
        }
    }

    /**
     * 修改名称
     */
    public function update(){
        if ($this->request->isPost()) {
            $params = $this->request->param();
            $uid = Session::get('uid');
            Db::name('chat_user')->where(['u_id'=>$uid])->update(['u_name'=>$params['u_name']]);
            echo $uid;die;
        }
    }

    /**
     * 获取用户的列表
     */
    public function getUserList(){
         $u_id = Session('uid');
         //获取除了自己的用户列表
         $u_list = Db::name('chat_user')->alias('u')
             ->field('u.u_id,u.u_name,u.is_online')
             ->where("u.u_id <> $u_id")
             ->select();

         //
         $sql = "SELECT n1.* FROM bl_chat_note AS n1, (SELECT n2.u_id,n2.gu_id,n2.room_id,MAX(n2.add_time) AS add_time FROM bl_chat_note AS n2 WHERE n2.u_id = $u_id OR n2.gu_id = $u_id GROUP BY n2.room_id) as r1 WHERE n1.`room_id`=r1.`room_id` AND n1.add_time = r1.add_time ;";
         $note_list = Db::query($sql);

         foreach($note_list as $k=>$v){
             foreach($u_list as $key=>$val){
                if(($v['u_id'] == $val['u_id'] && $u_id == $v['gu_id']) || ($v['gu_id'] == $val['u_id'] && $u_id == $v['u_id'] )){
                    $u_list[$key]['desc'] = $v['desc'];
                    $u_list[$key]['add_time'] = $v['add_time'];
                }
             }
         }
         return $u_list;
    }



    /**
     * 获取用户聊天信息
     */
    public function get_note_url(){
        if ($this->request->isPost()) {
            $params = $this->request->param();
            $u_id = $params['u_id'];
            $gu_id = $params['gu_id'];

            //查询是否与该用户用专用的room_id，没有则建立
            $info = Db::name('chat_room')->where("(u_id = $u_id and gu_id = $gu_id) or (u_id = $gu_id and gu_id = $u_id)")->find();
            $r_id = $info['r_id'];
            if(empty($info)){
                $r_id = Db::name('chat_room')->getLastInsID(['u_id'=>$u_id,'gu_id'=>$gu_id]);
            }

            $list = Db::name('chat_note')->alias('n')
                ->field('n.*,u.u_name')
//                ->where($where)
                ->where(['n.room_id'=>$r_id])
                ->join('chat_user u','u.u_id = n.u_id')
                ->order('n.add_time desc')
                ->limit(10)
                ->select();
            if(!empty($list)){
                //根据add_time把数据重新排序一下，旧的在前面，新的在下面
                array_multisort(array_column($list, 'add_time'), SORT_ASC, $list);
            }
            $rerun_data = [];
            $rerun_data['status'] = 1;
            $rerun_data['data'] = $list;
            echo json_encode($rerun_data);die;
        }
    }

    public function object_array($array) {
        if(is_object($array)) {
            $array = (array)$array;
        }
        if(is_array($array)) {
            foreach($array as $key=>$value) {
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }
    

}
