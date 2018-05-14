<?php
namespace Star\Controller;

use Common\Controller\ApiLoginController;
use Star\Model\UsersModel;

class LoginController extends ApiLoginController {


    private $key='kuaiyu666666';

    //小程序登录
    public function login()
    {

        $userdao = new UsersModel();
        $code = I("post.code");
        $userInfo=I('post.userInfo');//获取前台传送的用户信息
        $userInfo=str_replace("&quot;","\"",$userInfo);
        $userInfo=json_decode($userInfo,true);
        $login_data=$this->test_weixin($code);
        if ($login_data['code']!=400&&$userInfo) {
            $session_key = $login_data['session_key'];
            session('wx_session_key',$session_key);
            $openid = $login_data['openid'];
            $user = $userdao->findByOpenid($openid);
            if (!$user) {
                $user_data = array();
                $user_data['openid'] = $openid;
                $user_data['unionid'] = $userInfo['unionId'];
                $user_data['gender'] = $userInfo['gender'];
                $user_data['city'] = $userInfo['city'];
                $user_data['province'] = $userInfo['province'];
                $user_data['country'] = $userInfo['country'];
                $user_data['avatarUrl'] =  str_replace('/0','/132',$userInfo['avatarUrl'] );
                $user_data['add_time'] =time();
                $user_data['last_time'] =time();
                $user_data['login_time'] =time();
                $user_data['sign'] =1;
                $user_data['nickname'] = $userInfo['nickName'];
                $uid = $userdao->data($user_data)->add();
                $user_game['uid']=$uid;
                $user_game['nickname']=$userInfo['nickName'];
                $user_game['gold_num']=200;
                $user_game['avatarUrl']=str_replace('/0','/132',$userInfo['avatarUrl'] );
                 M('user_game')->add($user_game);
                }else{
                    if($user['status']==0) {
                        $data['code']=403;//已经被拉黑
                        $data['msg']='已经被拉黑';
                         $data['data']['user_id']=$user['id'];
                        $this->ajaxReturn($data,'JSON');
                     }
                        $uid=$user['id'];
                }
                    $session_k=session_id();
                    session('user_id',$uid);
                    $data['code']=200;
                    $data['msg']=$userInfo;
                    $data['data']=array('session_key'=>$session_k);
                    $this->ajaxReturn($data,'JSON');
                }else {
                    $this->ajaxReturn($login_data);
                }

    }

//更新世界排行
    public function mx_world_ranking(){
        $key=I('get.key');
        if($key==$this->key){
            $world_arr=array();
            $ranking_arr=M('user_game')->field('uid,avatarUrl,nickname,gold_num,idiom_num')->order('idiom_num desc')->limit(100)->select();
            foreach($ranking_arr as $k=>$v){
                $ranking=$k+1;
                $world_arr[$ranking]=$v;
                $world_arr[$ranking]['ranking']=$ranking;
            }
            S('mx_world_ranking',$world_arr);
            $s=date("Y-m-d H:i:s",time());
            file_put_contents('mx_cache_time.txt',$s);
        }
    }


    public function post_url($url,$data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT,15);   //只需要设置一个秒的数量就可以
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        //打印获得的数据
        //print_r($output);
        return $output;
    }

    public function test(){
        session('user_id',2);
    }

}