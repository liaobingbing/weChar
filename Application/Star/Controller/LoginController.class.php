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
        $recommend_user_id=I("post.recommend_user_id",0);

        if ($code) {
            $arr = array(
                'appid' => C("WECHAT_APPID"),
                'secret' => C("WECHAT_APPSECRET"),
                'js_code' => $code,
                'grant_type' => 'authorization_code'
            );

                $code_session = $this->post_url('https://api.weixin.qq.com/sns/jscode2session', $arr);

                $code_session = json_decode($code_session, true);

                if($code_session['errcode']==40163){
                    $data['code']=400;
                    $data['msg']='code been used';
                    $this->ajaxReturn($data,'JSON');
                }


            if ($code_session['openid'] && $code_session['session_key']) {

                $encryptedData = I("post.encryptedData", "", false);
                $iv = I("post.iv", "", false);
                $session_key = $code_session['session_key'];

                //TODO 验证签名


                vendor("wxaes.WXBizDataCrypt");
                $wxBizDataCrypt = new \WXBizDataCrypt(C("WECHAT_APPID"), $session_key);
                $data_arr = array();
                $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data_arr);
                if ($errCode == 0) {
                    $json_data = json_decode($data_arr, true);

                    $openid = $json_data['openId'];
                    $user = $userdao->findByOpenid($openid);
                    if (!$user) {
                        $user_data = array();
                        $user_data['openid'] = $openid;
                        $user_data['unionid'] = $json_data['unionId'];
                        $user_data['gender'] = $json_data['gender'];
                        $user_data['city'] = $json_data['city'];
                        $user_data['add_time'] = time();
                        $user_data['province'] = $json_data['province'];
                        $user_data['country'] = $json_data['country'];
                        $user_data['avatarUrl'] =  str_replace('/0','/132',$json_data['avatarUrl'] );
                        $user_data['add_time'] =time();
                        $user_data['last_time'] =time();
                        $user_data['login_time'] =time();
                        $user_data['sign'] =1;
                        $user_data['nickname'] = $json_data['nickName'];
                        $uid = $userdao->data($user_data)->add();
                        $user_game['uid']=$uid;
                        $user_game['nickname']=$json_data['nickName'];
                        $user_game['gold_num']=200;
                        $user_game['avatarUrl']=str_replace('/0','/132',$json_data['avatarUrl'] );
                        M('user_game')->add($user_game);
                    }else{
                        if($user['login_time']<strtotime(date("Y-m-d"),time())){
                            M('user_game')->where('uid='.$user['id'])->setField("sign",1);
                            M('user_game')->where('uid='.$user['id'])->setField("share_num",0);
                            M('users')->where('id='.$user['id'])->setField("avatarUrl", str_replace('/0','/132',$json_data['avatarUrl']));
                            M('user_game')->where('uid='.$user['id'])->setField("avatarUrl", str_replace('/0','/132',$json_data['avatarUrl']));
                        }
                        M('users')->where('id='.$user['id'])->setField("last_time",$user['login_time']);
                        M('users')->where('id='.$user['id'])->setField("login_time",time());
                        $uid=$user['id'];
                    }
                    if($recommend_user_id!==0){

                        $has=M('user_friend')->where('uid=%d and recomend_user_id=%d',$user['id'],$recommend_user_id)->find();
                        if(!$has){
                            $recommend_arr['uid']=$user['id'];
                            $recommend_arr['recomend_user_id']=$recommend_user_id;
                            M('user_friend')->data($recommend_arr)->add();
                            $userdao->share_gold($recommend_user_id);
                        }
                        $has2=M('user_friend')->where('uid=%d and recomend_user_id=%d',$recommend_user_id,$user['id'])->find();
                        if(!$has2){
                            $recommend_arr['uid']=$recommend_user_id;
                            $recommend_arr['recomend_user_id']=$user['id'];
                            M('user_friend')->data($recommend_arr)->add();
                        }

                    }

                    $session_k=session_id();
                    session('user_id',$uid);


                    $data['code']=200;
                    $data['msg']='success';

                    $data['recommend_user_id']=$recommend_user_id;
                    $data['data']=array('session_key'=>$session_k);
                    $this->ajaxReturn($data,'JSON');
                } else {
                    $data['code']=400;
                    $data['msg']='登录失败';
                    $this->ajaxReturn($data,'JSON');
                }

            }  else {

                $data['code']=400;
                $data['msg']=$code_session['errcode'];
                $this->ajaxReturn($data,'JSON');

            }
        } else {
            $data['code']=400;
            $data['msg']='参数code为空';
            $this->ajaxReturn($data,'JSON');
        }
    }

//更新世界排行
    public function mx_world_ranking(){
        $key=I('get.key');
        if($key==$this->key){
            $world_arr=array();
            $ranking_arr=M('user_game')->field('uid,avatarUrl,nickname,gold_num,idiom_num')->order('idiom_num desc')->select();
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