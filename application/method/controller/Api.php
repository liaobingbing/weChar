<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/4/14
 * Time: 14:12
 */

namespace app\method\controller;



use app\method\model\Answer;
use app\method\model\Users;
use think\Db;
use think\Controller;

class Api extends Controller
{
    //娃娃奖品图片列表
    public function prize_list()
    {
        $prize_info=Db::name('prize')->select();
         $arr=array('code'=>200,'msg'=>'sucess','data'=>$prize_info);
         return $arr;
    }
    //截屏监听事件，把用户置为无效
    public function user_status()
    {
        $status['status']=0;
        $uid=input('post.user_id');
        $info=Db::name('users')->where('id',$uid)->update(["status"=>$status]);
        if($info) {
            $arr=array('code'=>403,'msg'=>'已经被拉黑','data'=>"");
            session(null);

        }else{
            $arr=array('code'=>400,'msg'=>'拉黑失败','data'=>"");
        }
          return $arr;
    }

    //检查机会次数
    public function check_chance_num(){
        $openid=input("openid");
        $userdao=new Users();
        $user = $userdao->findByOpenid($openid);
        $user_id = $user['id'];
        if($user_id){
            $user_game=$userdao->findGame($user_id);
            if($user_game){
                if($user_game['chance_num']>0){
                    $data['code']=200;
                    $data['msg']='有挑战次数';
                }else{
                    $data['code']=400;
                    $data['msg']='无挑战次数';
                }
            }else{
                $data['code']=401;
            }
        }else{
            $data['code']=401;
        }
       return $data;
    }

    //开始挑战
    public function begin_challenge(){
        $openid=input("openid");
        $userdao=new Users();
        $user = $userdao->findByOpenid($openid);
        $user_id = $user['id'];
        if($user_id){
            $user_game=$userdao->findGame($user_id);
            if($user_game){
                if($user_game['chance_num']>0){
                    $user_game['chance_num']-=1;
                    $user_game['challenge_num']+=1;
                    $info=Db::name('user_game')->update($user_game);
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
        }else{
            $data['code']=401;
        }
       return $data;
    }


    //获取题目
    public function get_question(){
        $openid=input("openid");
        $userdao=new Users();
        $user = $userdao->findByOpenid($openid);
        $user_id = $user['id'];
        if($user_id){
            $user_game=$userdao->findGame($user_id);
            if($user_game){
                $layer=I('post.layer',1);

                if($layer<=30){
                    $answerdao=new Answer();
                    $question=$answerdao->get_question($layer);
                    if($question){
                        $data['code']=200;
                        $data['msg']='获取成功';
                        $data['data']['layer']=$layer;
                        $data['data']['nex_layer']=$layer+1;
                        $data['data']['subject1']=$question['subject1'];
                        $data['data']['subject2']=$question['subject2'];
                        if($layer>23){
                            $odds=($layer-23)*100;
                        }else{
                            $odds=0;
                        }
                        $rand=rand(0,500);
                        if($rand>$odds){
                            $data['data']['answer']=$question['answer'];
                        }else{
                            $data['data']['answer']=2;
                        }
                    }else{
                        $data['code']=400;
                        $data['msg']='题库出错';
                    }

                }else{
                    $data['code']=400;
                    $data['msg']='没有此等级';
                }

            }else{
                $data['code']=401;
            }
        }else{
            $data['code']=401;
        }


       return $data;
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


    //分享群
    public function share_group(){
        $openid=input("openid");
        $userdao=new Users();
        $user = $userdao->findByOpenid($openid);
        $user_id = $user['id'];
        if($user_id){
            $encryptedData = input("post.encryptedData");
            $iv = input("post.iv");
            $share_type=input('post.share_type',1);
            if($encryptedData&&$iv){
                $session_key=input('post.session_key');
                if($session_key){
                    $userdao=new Users();
                    $user_game=$userdao->findGame($user_id);
                    if($user_game){
                        vendor("wxaes.WXBizDataCrypt");
                        $wxBizDataCrypt = new \WXBizDataCrypt(config("WECHAT_APPID"), $session_key);
                        $data_arr = array();
                        $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data_arr);
                        if($errCode==0){
                            $json_data = json_decode($data_arr, true);
                            $has=Db::name('share_group')->where('uid='.$user_id.' and open_gid like "'.$json_data['openGId'].'"')->find();
                            if($has){
                                if($has['share_time']<strtotime(date("Y-m-d"),time())){
                                    if($share_type==1){
                                        $user_game['chance_num']+=1;
                                        Db::name('user_game')->update($user_game);
                                    }
                                    $has['share_time']=time();
                                    Db::name('share_group')->update($has);
                                    $data['code']=200;
                                    $data['msg']='分享成功';
                                }else{
                                    $data['code']=400;
                                    $data['msg']='该群已分享过';
                                }
                            }else{
                                if($share_type==1){
                                    $user_game['chance_num']+=1;
                                    Db::name('user_game')->update($user_game);
                                }
                                $group['uid']=$user_id;
                                $group['open_gid']=$json_data['openGId'];
                                $group['share_time']=time();
                                Db::name('share_group')->insert($group);
                                $data['code']=200;
                                $data['msg']='分享成功';
                            }
                        }else{
                            $data['code']=402;
                            $data['msg']='session_key过期，需重新登录获取';
                        }
                    }else{
                        $data['code']=401;
                    }

                }else{
                    $data['code']=401;
                }
            }else{
                $data['code']=400;
                $data['msg']='参数不全';
            }
        }else{
            $data['code']=401;
        }
        return $data;
    }

    //我的奖品
    public function my_prize(){
        $openid=input("openid");
        $userdao=new Users();
        $user = $userdao->findByOpenid($openid);
        $user_id = $user['id'];
        if($user_id){
            $user_game=Db::name('user_game')->where('uid',$user_id)->find();
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
    }

}