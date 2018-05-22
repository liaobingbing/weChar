<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/21
 * Time: 17:59
 */

namespace Film\Controller;


use Film\Model\AnswerModel;
use Film\Model\UsersModel;
use Think\Controller;

class AuthController extends Controller
{
    //获取好友排行榜
    public function get_friend_ranking(){
        $this->check_openid();
        $user_id=session('user_id');

        $userdao=new UsersModel();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            $answer=new AnswerModel();
            $friend_detail=$answer->get_one_friend($user_game['uid']);
            if($friend_detail){
                $page=$this->check_int(I('post.page',1));
                $page_size=10;
                $count=count($friend_detail['data']);
                $start=($page-1)*$page_size;
                $total = ceil($count/$page_size);
                $data['code']=200;
                $data['msg']='成功';
                $data['data']['my_ranking']=$friend_detail['my_ranking'];
                $data['data']['my_success']=$user_game['success_num'];
                $data['data']['nickname']=$user_game['nickname'];
                $data['data']['page']=$page;
                $data['data']['page_size']=$page_size;
                $data['data']['count']=$count;
                $data['data']['total']=$total;
                $data['data']['friend']=array_slice($friend_detail['data'],$start,$page_size);
            }else{
                $data['code']=400;
                $data['msg']='出错了，请联系管理员';
            }
        }else{
            $data['code']=401;
            $data['msg']='重新登录';
        }

        $this->ajaxReturn($data,'JSON');
    }

    //获取世界排行榜
    public function get_world_ranking(){
        $this->check_openid();
        $user_id=session('user_id');

        $userdao=new UsersModel();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            $page=$this->check_int(I('post.page',1));
            $answer=new AnswerModel();
            $all_ranking=$answer->get_world_ranking($user_game['uid']);
            if($all_ranking){
                $page_size=10;
                $count=count($all_ranking['data']);
                $start=($page-1)*$page_size;
                $total = ceil($count/$page_size);
                $data['code']=200;
                $data['msg']='成功';
                $data['data']['my_ranking']=$all_ranking['my_ranking'];
                $data['data']['my_success']=$user_game['success_num'];
                $data['data']['nickname']=$user_game['nickname'];
                $data['data']['page']=$page;
                $data['data']['page_size']=$page_size;
                $data['data']['count']=$count;
                $data['data']['total']=$total;
                $data['data']['world']=array_slice($all_ranking['data'],$start,$page_size);
            }else{
                $data['code']=400;
                $data['msg']='出错了，请联系管理员';
            }
        }else{
            $data['code']=401;
            $data['msg']='重新登录';
        }

        $this->ajaxReturn($data,'JSON');
    }
    public function check_openid()
    {
        $openid = session('openid');
        if( !$openid ){
            $result['code'] = 401;
            $result['msg']  = '未登录缺少OpenId';
            $this->ajaxReturn($result);
        }
    }
    //验证整数
    public function check_int($num){
        if(floor($num)==$num){
            return $num;
        }else{
            return '';
        }
    }
}