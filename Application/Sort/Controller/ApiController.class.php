<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/4/14
 * Time: 14:12
 */

namespace Sort\Controller;


use Common\Controller\ApiBaseController;

class ApiController extends ApiBaseController
{


    //娃娃奖品图片列表
    public function prize_list()
    {
        $prize_info=M('prize')->select();
         $arr=array('code'=>200,'msg'=>'sucess','data'=>$prize_info);
        $this->ajaxReturn($arr);
    }
    //截屏监听事件，把用户置为无效
    public function user_status()
    {
        $uid=I('post.user_id');
        M('user_game')->where('uid='.$uid)->setField("status",0);
        session(null);
        $arr=array('code'=>403,'msg'=>'已经被拉黑','data'=>"");
        $this->ajaxReturn($arr);
    }

    //开始挑战
    public function begin_challenge(){
        $user_id=session('user_id');

        $user_game=M('user_game')->where("uid=%d",$user_id)->find();
        if($user_game){
            if($user_game['chance_num']>0){
                $user_game['chance_num']-=1;
                $user_game['challenge_num']+=1;
                $info=M('user_game')->save($user_game);
                if($info){
                    $data['code']=200;
                    $data['msg']='开始成功';
                }else{
                    $data['code']=400;
                    $data['msg']='开始失败';
                }
            }else{
                $data['code']=400;
                $data['msg']='开始失败';
            }
        }else{
            $data['code']=401;
        }

        $this->ajaxReturn($data,'JSON');
    }


    /*//获取题目
    public function get_question(){
        $user_id=session('user_id');

        $user_game=M('user_game')->where("uid=%d",$user_id)->find();

        if($user_game){

            $layer=I('post.layer',1);
            if($layer<=5){
                $arr_num=($layer+2)*($layer+2);
                for($i=1;$i<=$arr_num;$i++){
                    $arr['num']=$i;
                    $arr['status']=false;
                    $question[]=$arr;
                }
                shuffle($question);
                $next_layer=$layer+1;
                $data['code']=200;
                $data['msg']='获取成功';
                $data['data']['question']=$question;
                $data['data']['layer']=$layer;
                $data['data']['next_layer']=$next_layer;
            }else{
                $data['code']=400;
                $data['msg']='没有此等级';
            }

        }else{
            $data['code']=401;
        }


        $this->ajaxReturn($data,'JSON');
    }*/


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


    //分享群
    public function share_group(){
        $user_id=session('user_id');

        $encryptedData = I("post.encryptedData");
        $iv = I("post.iv");
        $share_type=I('post.share_type',1);
        if($encryptedData&&$iv){
            $session_key=session('wx_session_key');
            if($session_key){
                $user_game=M('user_game')->where("uid=%d",$user_id)->find();
                if($user_game){
                    vendor("wxaes.WXBizDataCrypt");
                    $wxBizDataCrypt = new \WXBizDataCrypt(C("WECHAT_APPID"), $session_key);
                    $data_arr = array();
                    $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data_arr);
                    if($errCode==0){
                        $json_data = json_decode($data_arr, true);
                        $has=M('share_group')->where('uid='.$user_id.' and open_gid like "'.$json_data['openGId'].'"')->find();
                        if($has){
                            if($has['share_time']<strtotime(date("Y-m-d"),time())){
                                if($share_type==1){
                                    $user_game['chance_num']+=1;
                                    M('user_game')->save($user_game);
                                }
                                $has['share_time']=time();
                                M('share_group')->save($has);
                                $data['code']=200;
                                $data['msg']='分享成功';
                            }else{
                                $data['code']=400;
                                $data['msg']='该群已分享过';
                            }
                        }else{
                            if($share_type==1){
                                $user_game['chance_num']+=1;
                                M('user_game')->save($user_game);
                            }
                            $group['uid']=$user_id;
                            $group['open_gid']=$json_data['openGId'];
                            $group['share_time']=time();
                            M('share_group')->add($group);
                            $data['code']=200;
                            $data['msg']='分享成功';
                        }
                    }else{
                        $data['code']=402;
                        $data['msg']='session_key过期，需重新登录获取';
                    }
                }else{
                    $data['code']=401;
                    $data['msg']='用户不存在';
                }

            }else{
                $data['code']=401;
                $data['msg']='session_key过期，需重新登录获取';
            }
        }else{
            $data['code']=400;
            $data['msg']='参数不全';
        }

        $this->ajaxReturn($data,'JSON');
    }

    //我的奖品
    public function my_prize(){
        $user_id=session('user_id');
        if($user_id){
            $user_game=M('user_game')->where("uid=%d",$user_id)->find();
            if($user_game){
                $data['code']=200;
                $data['msg']='获取成功';
                $data['data']['avatar_url']=$user_game['avatar_url'];
                $data['data']['nickname']=$user_game['nickname'];
                $data['data']['get_number']=$user_game['get_number'];
                $data['data']['chance_num']=$user_game['chance_num'];
                $data['data']['challenge_num']=$user_game['challenge_num'];
            }else{
                $data['code']=401;
            }
        }else{
            $data['code']=401;
        }
        $this->ajaxReturn($data,'JSON');
    }

}