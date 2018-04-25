<?php
namespace Film\Controller;

use Common\Controller\ApiLoginController;
use Film\Model\UsersModel;


class LoginController extends ApiLoginController {

    private $key='kuaiyu666666';


    //小程序登录
    public function login(){
        $userdao=new UsersModel();
        $code=I('post.code');
        $encryptedData=I('post.encryptedData');
        $iv=I('post.iv');
        $login_data=$this->get_weixin($code,$encryptedData,$iv);
        if($login_data['code']!=400){
            $openid = $login_data['openId'];
            $user = $userdao->findByOpenid($openid);
            if (!$user) {
                $user_data['openid'] = $openid;
                $user_data['unionid'] = $login_data['unionId'];
                $user_data['gender'] = $login_data['gender'];
                $user_data['city'] = $login_data['city'];
                $user_data['province'] = $login_data['province'];
                $user_data['country'] = $login_data['country'];
                $user_data['avatar_url'] =  str_replace('/0','/132',$login_data['avatarUrl'] );
                $user_data['nickname'] = $login_data['nickName'];
                $user_data['add_time'] = time();
                $user_data['last_time'] = time();
                $user_data['login_time'] = time();
                $uid = M('users')->data($user_data)->add();
                $user_game['uid']=$uid;
                $user_game['nickname']=$login_data['nickName'];
                $user_game['avatar_url']=str_replace('/0','/132',$login_data['avatarUrl'] );
                M('user_game')->add($user_game);
            }else{
                if($user['status']==0) {
                    $data['code']=403;//已经被拉黑
                    $data['msg']='已经被拉黑';
                    $this->ajaxReturn($data,'JSON');
                }
                if($user['login_time']<strtotime(date("Y-m-d"),time())){
                    M('user_game')->where('uid='.$user['id'])->setField("share_num",0);
                    M('user_game')->where('uid='.$user['id'])->setField("sign",1);
                    M('users')->where('id='.$user['id'])->setField("avatar_url", str_replace('/0','/132',$login_data['avatarUrl']));
                    M('user_game')->where('uid='.$user['id'])->setField("avatar_url", str_replace('/0','/132',$login_data['avatarUrl']));

                }
                M('users')->where('id='.$user['id'])->setField("last_time",$user['login_time']);
                M('users')->where('id='.$user['id'])->setField("login_time",time());
                $uid=$user['id'];
            }
            $session_k=session_id();
            session('user_id',$uid,3600);
            session("openid",$openid);
            $data['code']=200;
            $data['msg']='success';
            $data['data']=array('session_key'=>$session_k);
            $this->ajaxReturn($data,'JSON');

        }
        else{
            $this->ajaxReturn($login_data);
        }

    }

//更新世界排行
    public function gm_world_ranking(){
        $key=I('get.key');
        if($key==$this->key){
            $world_arr=array();
            $ranking_arr=M('user_game')->field('uid,avatarUrl,nickname,gold_num,success_num')->order('success_num desc')->select();
            foreach($ranking_arr as $k=>$v){
                $ranking=$k+1;
                $world_arr[$ranking]=$v;
                $world_arr[$ranking]['ranking']=$ranking;
            }
            S('gm_world_ranking',$world_arr);
            $s=date("Y-m-d H:i:s",time());
            file_put_contents('gm_cache_time.txt',$s);
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

}