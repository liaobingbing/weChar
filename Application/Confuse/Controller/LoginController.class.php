<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/4/14
 * Time: 10:47
 */

namespace Confuse\Controller;


use Common\Controller\ApiLoginController;
use Confuse\Model\UsersModel;

class LoginController extends  ApiLoginController
{
    private  $key="kuaiyu666666";

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
                $user_data['login_time'] = time();
                $user_data['province'] = $login_data['province'];
                $user_data['country'] = $login_data['country'];
                $user_data['avatar_url'] =  str_replace('/0','/132',$login_data['avatarUrl'] );
                $user_data['name'] = $login_data['nickName'];
                $uid = M('users')->data($user_data)->add();
                $user_game['uid']=$uid;
                $user_game['nickname']=$login_data['nickName'];
                $user_game['login_time'] = time();
                $user_game['avatar_url']=str_replace('/0','/132',$login_data['avatarUrl'] );
                M('user_game')->add($user_game);
            }else{
                if($user['status']==0) {
                    $data['code']=403;//已经被拉黑
                    $data['msg']='已经被拉黑';
                    $data['data']['user_id']=$user['id'];
                    $this->ajaxReturn($data,'JSON');
                }
                if($user['login_time']<strtotime(date("Y-m-d"),time())){
                    M('user_game')->where('uid='.$user['id'])->setField("chance_num",1);
                    M('users')->where('id='.$user['id'])->setField("avatar_url", str_replace('/0','/132',$login_data['avatarUrl']));
                    M('user_game')->where('uid='.$user['id'])->setField("avatar_url", str_replace('/0','/132',$login_data['avatarUrl']));

                }
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

    //缓存挑战次数
    public function cache_num()
    {
        $key=I('get.key');
        if($key==$this->key){
            $user_info=M('user_game')->field('challenge_num,avatar_url,nickname')->order('challenge_num desc')->limit(8)->select();
            foreach($user_info as $k=>$v){
                $user_info[$k]['ranking']=$k+1;
            }
            $sql1="SELECT avatarUrl,gt_number,nickname FROM confuse_test_game order by gt_number desc limit 3";
            $data1=M()->query($sql1);
            $sql2="SELECT avatarUrl,gt_number,nickname FROM confuse_test_game WHERE id >= ((SELECT MAX(id) FROM confuse_test_game)-(SELECT MIN(id) FROM confuse_test_game)) * RAND() + (SELECT MIN(id) FROM confuse_test_game)  order by  gt_number desc LIMIT 8";
            $data2=M()->query($sql2);
            $user_info2=$data1+$data2;
            foreach($user_info2 as $k=>$v){
                $user_info2[$k]['ranking']=$k+1;
            }
            S("c_num_top",$user_info);
            S("c_intelligence_top",$user_info2);
        }
    }

    //设置session
    public function set_session(){
        session('user_id',1);
    }
}