<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/15
 * Time: 14:12
 */

namespace app\age\controller;


use app\age\model\Game;
use common\controller\ApiBase;
use think\Controller;

class Api extends Controller
{
    //获取题目
    public function get_question()
    {
        $layer=input('post.layer',1);
        $openId=input('post.openId',1);
        if($layer<=10){
            $answerdao=new Game();
            $question=$answerdao->get_question($layer,$openId);
            if($question){
                $arr=resCode(200,"获取成功",array("layer"=>(int)$layer,"nex_layer"=>$layer+1,"question"=>$question));
            }else{
                $arr=resCode(400,"题库出错");
            }

        }else{
            $arr=resCode(400,"没有此等级");
        }
        return $arr;
    }
    //获取opend_id
    public function get_openid()
    {
        $code = input('post.code');
        $login_data = $this->test_weixin($code);
        if ($login_data['code'] != 400) {
            $openid = $login_data['openid'];
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid));
            return $arr;
        }
        else{
            return $login_data;

        }
    }
    //分享群
    public function share_group(){
        $data=array("code"=>200,"msg"=>"success","data"=>null);
        return $data;
    }

    public function test_result()
    {
        $layer=input('post.layer');
        $number=rand(1,11);
        $sql=db("result")->where("layer",$layer)->fetchSql(true)->value('title,result'.$number);
        $result=db()->query($sql);
        $arr=resCode(200,"查询成功",$result);
        return $arr;
    }

   

}