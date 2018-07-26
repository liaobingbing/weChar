<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/19
 * Time: 9:17
 */

namespace app\findword\controller;

use app\findword\model\ShareGroup;
use app\findword\model\UserGame;
use app\findword\model\Users;
use think\Controller;
use think\Cache;
use think\Db;
class Api extends Controller
{
    /* // 初始化
     public function _initialize()
     {
         $this->check_sign();    // 验证用户签到状态
     }*/


    // 总挑战次数
    public function challenge_num(){
        $count_challenge=cache("findword_challenge");
        if(!$count_challenge){
            $count_challenge=Db::name("user_game")->sum("challenge_num");
            cache("findword_challenge",$count_challenge+5000);
        }else{
            Cache::inc("findword_challenge");
        }
        $result=resCode(200,"ok",array('count_challenge' => $count_challenge));
        return $result;
    }

    // 验证挑战次数接口
    public function check_chance_num(){
        $openid=input("openid");
        $userdao=new Users();
        $user = $userdao->findByOpenid($openid);
        $UserGame = new UserGame();

        $result = array('code'=>400,'msg'=>'无挑战次数');
        $re = $UserGame->check_chance_num($user['id']);

        if($re){
            $result = array('code'=>200,'msg'=>'有挑战次数');
        }

        return $result;
    }


    //开始 挑战
    public function begin_challenge(){
        $openid=input("openid");
        $userdao=new Users();
        $user = $userdao->findByOpenid($openid);
        $user_id = $user['id'];
        $UserGame = new UserGame();
        $user_game = $UserGame->find_by_user_id($user_id);
        if($user_game) {
            if($user_game['chance_num'] <= 0){
                $arr=array('code' => 400, 'msg' => '挑战次数为空');
                return $arr;
            }
            $UserGame->where(array('uid' => $user_id))->setDec('chance_num');
            $UserGame->where(array('uid' => $user_id))->setInc('challenge_num');
            $result['code'] =   200;
            $result['msg']  =   '开始挑战成功';
            $result['data'] = null;

        }else{
            $result['code'] =400;
            $result['msg']  = '用户不存在';
            $result['data'] = null;
        }
        return $result;

    }

    // 验证用户状态
    public function check_status(){
        $openid=input("openid");
        $userdao=new Users();
        $user = $userdao->findByOpenid($openid);
        $user_id = $user['id'];
        $User = new Users();
        $user = $User->find_by_user_id($user_id,'id,status');

        if($user['status'] != 1){
            //session('user_status',1);
            $result['code'] = 403;
            $result['msg']  = '该用户已禁用';
            return $result;
        }
    }

    // 验证签到状态
    public function check_sign(){
        $openid=input("openid");
        $userdao=new Users();
        $user = $userdao->findByOpenid($openid);
        $user_id = $user['id'];
        $key = 'find_word_sign_status_'.$openid;
        $sign = cache($key);

        if( !$sign ){
            $UserGame = new UserGame();
            $UserGame->do_sign($user_id);

            $today_0 = strtotime(date('Y-m-d',time()));
            $expire = $today_0 + 24*60*60 - time();

            cache($key,1,$expire);
        }
    }

    // 群分享操作
    public function share_group(){
        $openid=input("openid");
        $userdao=new Users();
        $user = $userdao->findByOpenid($openid);
        $user_id = $user['id'];
        $session_key=input('post.session_key');

        $encryptedData = input("post.encryptedData");
        $iv = input("post.iv");
        $share_type=input('post.share_type',1);  // 分享类型

        $result = array( 'code' => 400 , 'msg' => '分享失败');

        if($encryptedData && $iv){
            vendor("wxaes.wxBizDataCrypt");
            $wxBizDataCrypt = new \WXBizDataCrypt(config("WECHAT_APPID"), $session_key);
            $data_arr = array();
            $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data_arr);
            // 对微信数据解密
            // $ApiLogin = new ApiLoginController();
            //$data = $ApiLogin->wx_biz_data_crypt($encryptedData,$iv,$session_key);

            if($errCode == 0){
                // 验证今天是否已分享
                $ShareGroup = new ShareGroup();
                $data = json_decode($data_arr,true);
                $re = $ShareGroup->check_share_group($user_id,$data['openGId']);

                if($re){
                    if($share_type == 1){
                        $UserGame = model('UserGame');
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

        return $result;
    }

    // 毅力榜
    public function challenge_top(){
        $UserGame = new UserGame();
        $rankings = $UserGame->get_rankings('challenge_num',8);
        $result = array('code'=>400, 'msg'=>'获取失败');

        if($rankings){
            $result['code'] = 200;
            $result['msg']  = '获取成功';
            $result['data'] = array('ranking_list' => $rankings);
        }

        return $result;
    }

    // 荣耀榜
    public function get_prize_top(){
        $UserGame = new UserGame();
        $rankings = $UserGame->get_rankings('get_prize',8);
        $result = array('code'=>400, 'msg'=>'获取失败');

        if($rankings){
            $result['code'] = 200;
            $result['msg']  = '获取成功';
            $result['data'] = array('ranking_list' => $rankings);
        }

        return $result;
    }

    // 奖品列表
    public function prize_list(){
        $page = input('page',1);
        $len  = input('len',10);

        $prize_list = db('prize')->page($page,$len)->select();
        $result = array('code'=>200,'msg'=>'获取成功');
        $result['data'] = $prize_list;

        return $result;
    }

    // 将用户禁用
    public function disable(){
        $user_id = session('user_id');
        $result = array('code'=>400, 'msg'=> '禁用失败');
        $re = db('users')->where("id={$user_id}")->setField('status',0);

        if($re){
            //session('user_status',1);
            $result['code'] = 403;
            $result['msg']  = '已禁用';
            $result['data'] = array('user_id'=>$user_id);
        }

        return $result;
    }

    // 获取用户信息
    public function get_user_info(){
        $openid=input("openid");
        $userdao=new Users();
        $user = $userdao->findByOpenid($openid);
        $user_id = $user['id'];
        $UserGame = new UserGame();

        $result = array('code'=>400, 'msg'=> '获取失败');

        $user = $UserGame->find_by_user_id($user_id,'challenge_num,chance_num,get_prize,uid,nickname,avatar_url');

        if($user) {
            $result['code'] = 200;
            $result['msg'] = '获取成功';
            $result['data'] = $user;
        }

        return $result;
    }

    //获取用户ID
    public function get_user_id(){
        $openid=input("openid");
        $userdao=new Users();
        $user = $userdao->findByOpenid($openid);
        $user_id = $user['id'];
        if($user_id){
            $data['code']='200';
            $data['msg']='成功';
            $data['user_id']=$user_id;
        }else{
            $data['code']=401;
        }
        return $data;
    }

}