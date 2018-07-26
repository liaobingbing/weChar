<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/15
 * Time: 14:12
 */

namespace app\armyhand\controller;


use app\armyhand\model\Game;
use think\Controller;

class Api extends Controller
{
    //获取题目
    public function get_question()
    {

         $answerdao=new Game();
         $question=$answerdao->get_question();
        $arr=resCode(200,"获取成功",$question);
        return $arr;
    }
    //分享群
    public function share_group(){
        $data=array("code"=>200,"msg"=>"success","data"=>null);
        return $data;
    }
}