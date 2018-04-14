<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/4/14
 * Time: 10:47
 */

namespace Method\Controller;


use Common\Controller\ApiLoginController;
use Method\Model\UsersModel;

class LoginController extends  ApiLoginController
{
    public function login(){
        $userdao=new UsersModel();
        $code=I('post.code');
        $encryptedData=I('post.encryptedData');
        $iv=I('post.iv');
        $login_data=$this->login($code,$encryptedData,$iv);
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
                $user_data['avatarUrl'] =  str_replace('/0','/132',$login_data['avatarUrl'] );
                $user_data['name'] = $login_data['nickName'];
                $uid = M('method_users')->data($user_data)->add();
                $user_game['uid']=$uid;
                $user_game['nickname']=$login_data['nickName'];
                $user_game['login_time'] = time();
                $user_game['avatarUrl']=str_replace('/0','/132',$login_data['avatarUrl'] );
                M('method_user_game')->add($user_game);
            }else{
                if($user['status']==0) {
                    $data['code']=403;//已经被拉黑
                    $data['msg']='已经被拉黑';
                    $this->ajaxReturn($data,'JSON');
                }
                if($user['last_time']<strtotime(date("Y-m-d"),time())){
                    M('method_user_game')->where('uid='.$user['id'])->setField("chance_num",1);
                    M('method_users')->where('id='.$user['id'])->setField("avatarUrl", str_replace('/0','/132',$login_data['avatarUrl']));
                    M('method_user_game')->where('uid='.$user['id'])->setField("avatarUrl", str_replace('/0','/132',$login_data['avatarUrl']));
                    $session_k=session_id();
                    session('user_id',$user['id'],3600);
                    session("openid",$openid);
                    $data['code']=200;
                    $data['msg']='success';
                    $data['data']=array('session_key'=>$session_k);
                    $this->ajaxReturn($data,'JSON');
                }
            }

        }
        else{
            $this->ajaxReturn($login_data);
        }

    }


    //设置session
    public function set_session(){
        session('user_id',1);
    }
}