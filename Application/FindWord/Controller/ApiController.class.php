<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/19
 * Time: 9:17
 */

namespace FindWord\Controller;


use Common\Controller\ApiBaseController;
use Common\Controller\ApiLoginController;
use FindWord\Model\QuestionsModel;
use FindWord\Model\ShareGroupModel;
use FindWord\Model\UserGameModel;
use FindWord\Model\UsersModel;

class ApiController extends ApiBaseController
{
    // 初始化
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        //$this->check_status();  // 验证用户状态 1:正常 0:禁用
        $this->check_sign();    // 验证用户签到状态
    }

    // 总挑战次数
    public function challenge_num(){
        $result = array('code' => 400, 'msg' => '获取失败', 'data' => 0);
        $count_challenge = M('UserGame')->sum('challenge_num');

        if($count_challenge <= 5000){
            $count_challenge += 5000;
        }

        if($count_challenge){
            $result['code'] =   200;
            $result['msg']  =   '获取成功';
            $result['data'] = array('count_challenge' => $count_challenge);
        }

        $this->ajaxReturn($result);
    }

    // 验证挑战次数接口
    public function check_chance_num(){
        $user_id = session('user_id');
        $UserGame = new UserGameModel();

        $result = array('code'=>400,'msg'=>'无挑战次数');
        $re = $UserGame->check_chance_num($user_id);

        if($re){
            $result = array('code'=>200,'msg'=>'有挑战次数');
        }

        $this->ajaxReturn($result);
    }


    //开始 挑战
    public function begin_challenge(){
        $user_id = session('user_id');
        $UserGame = new UserGameModel();
        $user_game = $UserGame->find_by_user_id($user_id);
        if($user_game) {
            if($user_game['chance_num'] <= 0){
                $this->ajaxReturn(array('code' => 400, 'msg' => '挑战次数为空'));
            }
            M('UserGame')->where(array('uid' => $user_id))->setDec('chance_num');
            M('UserGame')->where(array('uid' => $user_id))->setInc('challenge_num');
            $result['code'] =   200;
            $result['msg']  =   '开始挑战成功';
            $result['data'] = null;

        }else{
            $result['code'] =400;
            $result['msg']  = '用户不存在';
            $result['data'] = null;
        }
        $this->ajaxReturn($result);

    }

    // 验证用户状态
    public function check_status(){
        $user_id = session('user_id');

        $User = new UsersModel();
        $user = $User->find_by_user_id($user_id,'id,status');

        if($user['status'] != 1){
            session('user_status',1);
            $result['code'] = 403;
            $result['msg']  = '该用户已禁用';
            $this->ajaxReturn($result);
        }
    }

    // 验证签到状态
    public function check_sign(){
        $user_id = session('user_id');
        $openid = session('openid');
        $key = 'find_word_sign_status_'.$openid;
        $sign = S($key);

        if( !$sign ){
            $UserGame = new UserGameModel();
            $UserGame->do_sign($user_id);

            $today_0 = strtotime(date('Y-m-d',time()));
            $expire = $today_0 + 24*60*60 - time();

            S($key,1,$expire);
        }
    }

    // 群分享操作
    public function share_group(){
        $user_id = session('user_id');
        $session_key=I('post.session_key');
        if(empty($session_key)) {
            $session_key = session('wx_session_key');
        }
        $encryptedData = I("post.encryptedData");
        $iv = I("post.iv");
        $share_type=I('post.share_type',1);  // 分享类型

        $result = array( 'code' => 400 , 'msg' => '分享失败');

        if($encryptedData && $iv){
            // 对微信数据解密
            $ApiLogin = new ApiLoginController();
            $data = $ApiLogin->wx_biz_data_crypt($encryptedData,$iv,$session_key);

            if($data['errCode'] == 0){
                // 验证今天是否已分享
                $ShareGroup = new ShareGroupModel();
                $data = json_decode($data['data'],true);
                $re = $ShareGroup->check_share_group($user_id,$data['openGId']);

                if($re){
                    if($share_type == 1){
                        $UserGame = M('UserGame');
                        if($UserGame->where("uid=$user_id")->setInc('chance_num')){
                            $result['code'] = 200;
                            $result['msg']  = '分享成功';
                        }
                    }else{
                        $result['code'] = 200;
                        $result['msg']  = '分享成功';
                    }
                }else{
                    $result['code'] = 400;
                    $result['msg']  = '该群今天已分享过';
                }

            }else{
                $result['code']=402;
                $result['msg']='session_key过期，需重新登录获取';
            }

        }else{
            $result['code'] = 400;
            $result['msg'] = '参数不全';
        }

        $this->ajaxReturn($result);
    }

    // 毅力榜
    public function challenge_top(){
        $UserGame = new UserGameModel();
        $rankings = $UserGame->get_rankings('challenge_num',8);
        $result = array('code'=>400, 'msg'=>'获取失败');

        if($rankings){
            $result['code'] = 200;
            $result['msg']  = '获取成功';
            $result['data'] = array('ranking_list' => $rankings);
        }

        $this->ajaxReturn($result);
    }

    // 荣耀榜
    public function get_prize_top(){
        $UserGame = new UserGameModel();
        $rankings = $UserGame->get_rankings('get_prize',8);
        $result = array('code'=>400, 'msg'=>'获取失败');

        if($rankings){
            $result['code'] = 200;
            $result['msg']  = '获取成功';
            $result['data'] = array('ranking_list' => $rankings);
        }

        $this->ajaxReturn($result);
    }

    // 奖品列表
    public function prize_list(){
        $page = I('page',1);
        $len  = I('len',10);

        $prize_list = M('Prize')->page($page,$len)->select();
        $result = array('code'=>200,'msg'=>'获取成功');
        $result['data'] = $prize_list;

        $this->ajaxReturn($result);
    }

    // 将用户禁用
    public function disable(){
        $user_id = session('user_id');
        $result = array('code'=>400, 'msg'=> '禁用失败');
        $re = M('Users')->where("id={$user_id}")->setField('status',0);

        if($re){
            session('user_status',1);
            $result['code'] = 403;
            $result['msg']  = '已禁用';
            $result['data'] = array('user_id'=>$user_id);
        }

        $this->ajaxReturn($result);
    }

    // 获取用户信息
    public function get_user_info(){
        $user_id = session('user_id');
        $UserGame = new UserGameModel();

        $result = array('code'=>400, 'msg'=> '获取失败');

        $user = $UserGame->find_by_user_id($user_id,'challenge_num,chance_num,get_prize,uid,nickname,avatar_url');

        if($user) {
            $result['code'] = 200;
            $result['msg'] = '获取成功';
            $result['data'] = $user;
        }

        $this->ajaxReturn($result);
    }

    //获取用户ID
    public function get_user_id(){
        $user_id=session('user_id');
        if($user_id){
            $data['code']='200';
            $data['msg']='成功';
            $data['user_id']=$user_id;
        }else{
            $data['code']=401;
        }
        $this->ajaxReturn($data,'JSON');
    }

}