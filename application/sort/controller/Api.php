<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/26
 * Time: 14:10
 */

namespace app\sort\controller;


use think\Controller;

class Api extends Controller
{
	//娃娃奖品图片列表
    public function prize_list()
    {
        $prize_info=db('prize')->select();
        $arr=array('code'=>200,'msg'=>'sucess','data'=>$prize_info);
        return $arr;
        // $this->ajaxReturn($arr);
    }
    //截屏监听事件，把用户置为无效
    public function user_status()
    {
        $uid=input('post.user_id');
        db('user_game')->where('uid',$uid)->update(array('status'=>0));
        $arr=array('code'=>403,'msg'=>'已经被拉黑','data'=>"");
        return $arr;
        // $this->ajaxReturn($arr);
    }

    //开始挑战
    public function begin_challenge(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');
        $user_game=db('user_game')->where("uid",$user_id)->find();
        if($user_game){
            if($user_game['chance_num']>0){
                $user_game['chance_num']-=1;
                $user_game['challenge_num']+=1;
                $info=db('user_game')->where("uid",$user_id)->update($user_game);
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
        return $data;
        // $this->ajaxReturn($data,'JSON');
    }

    //获取用户ID
    public function get_user_id(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');
        if($user_id){
            $data['code']='200';
            $data['msg']='成功';
            $data['user_id']=$user_id;
        }else{
            $data['code']=401;
        }
        return $data;
        // $this->ajaxReturn($data,'JSON');
    }


    //分享群
    public function share_group(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');
        $encryptedData = input("post.encryptedData");
        $iv = input("post.iv");
        $share_type=input('post.share_type',1);
        if($encryptedData&&$iv){
            $session_key=input('post.session_key');
            if(empty($session_key)) {
                $session_key = session('wx_session_key');
            }
            if($session_key){
                $user_game=db('user_game')->where("uid",$user_id)->find();
                if($user_game){
                    vendor("wxaes.WXBizDataCrypt");
                    $wxBizDataCrypt = new \WXBizDataCrypt(config("WECHAT_APPID"), $session_key);
                    $data_arr = array();
                    $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data_arr);
                    if($errCode==0){
                        $json_data = json_decode($data_arr, true);
                        // $has=db('share_group')->where('uid='.$user_id.' and open_gid like "'.$json_data['openGId'].'"')->find();
                        $has=db('share_group')->where(array('uid'=>$user_id,'open_gid'=>array('LIKE',$json_data['openGId'])))->find();
                        if($has){
                            if($has['share_time']<strtotime(date("Y-m-d"),time())){
                                if($share_type==1){
                                    $user_game['chance_num']+=1;
                                    db('user_game')->where("uid",$user_id)->update($user_game);
                                }
                                $has['share_time']=time();
                                db('share_group')->here("uid",$user_id)->update($has);
                                $data['code']=200;
                                $data['msg']='分享成功';
                            }else{
                                $data['code']=400;
                                $data['msg']='该群已分享过';
                            }
                        }else{
                            if($share_type==1){
                                $user_game['chance_num']+=1;
                                // M('user_game')->save($user_game);
                                db('user_game')->where("uid",$user_id)->update($user_game);
                            }
                            $group['uid']=$user_id;
                            $group['open_gid']=$json_data['openGId'];
                            $group['share_time']=time();
                            db('share_group')->insertGetId($group);
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
        return $data;
        // $this->ajaxReturn($data,'JSON');
    }

    //我的奖品
    public function my_prize(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');
        if($user_id){
            $user_game=db('user_game')->where("uid",$user_id)->find();
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
        return $data;
        // $this->ajaxReturn($data,'JSON');
    }

}