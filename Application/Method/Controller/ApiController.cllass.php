<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/4/14
 * Time: 14:12
 */

namespace Method\Controller;


use Common\Controller\ApiBaseController;

class ApiController extends ApiBaseController
{
    //统计挑战的次数
    public function count_challenge()
    {
        $count_challenge=M('user_game')->count('challenge_num');
        if($count_challenge<=5000){
            $count_challenge=rand(5000,10000);
        }
        $data['code']=200;
        $data['msg']='success';
        $data['data']=$count_challenge;
       $this->ajaxReturn($data);
    }
    //智力榜
    public function intelligence_top()
    {
        $user_info=M('user_game')->field('get_number,avatar_url,nickname')->order('get_number desc')->limit(5)->select();
        $arr=array('code'=>200,'msg'=>'sucess','data'=>$user_info);
        $this->ajaxReturn($arr);
    }
    //毅力榜
    public function num_top()
    {
        $user_info=M('user_game')->field('challenge_num,avatar_url,nickname')->order('challenge_num desc')->select();
        $page=I('post.page');
        $page_size=10;
        //$count=count($user_info);
        $start=($page-1)*$page_size;
       // $total = ceil($count/$page_size);
        $data=array_slice($user_info,$start,$page_size);
        $arr=array('code'=>200,'msg'=>'sucess','data'=>$data);
        $this->ajaxReturn($arr);
    }
    //娃娃奖品列表
    public function prize_list()
    {
        $prize_info=M('prize')->select();
         $arr=array('code'=>200,'msg'=>'sucess','data'=>$prize_info);
        $this->ajaxReturn($arr);
    }
}