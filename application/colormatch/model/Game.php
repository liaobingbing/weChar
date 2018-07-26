<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/12
 * Time: 10:51
 */

namespace app\colormatch\model;


use think\Db;
use think\Model;

class Game extends Model
{
    public function get_question($openId,$layer="")
    {
        //$re=db("users")->where("openid",$openId)->value("layer");
        if($layer<=1000){
            $question=cache("color_match_one");
            if(empty($question)) {
                $question=db('questions')->where('id','<',1001)->field("option_1,option_2")->select();
                foreach ($question as $k=>$v){
                    $num=$k+1;
                    $question[$num]=$v;
                }
                cache("color_match_one",$question);
            }
            shuffle($question);
            return $question[$layer-1];
        }else{
            $question=cache("color_match_two");
            if(empty($question)) {
                $question = db('questions')->where('id', '>', 1000)->field("option_1,option_2")->select();
                foreach ($question as $k=>$v){
                    $num=$k+1;
                    $question[$num]=$v;
                }
                cache("color_match_two",$question);
            }
           // print_r($question);die;
            shuffle($question);
            return $question[$layer-1001];
        }
    }

    public function get_answer($v,$layer)
    {
        $layer=$layer%16;
        if($layer==1){
            $len=4;
            $s=1;
        }else if($layer==2) {
            $len=4;
            $s=1;
        } else if($layer==3) {
            $len=9;
            $s= 0.65;
        } else if($layer==4){
            $len=9;
            $s= 0.65;
        }else if($layer==5){
            $len=16;
            $s= 0.47;
        } else if($layer==6){
            $len=16;
            $s= 0.47;
        }else if($layer==7){
            $len=25;
            $s= 0.38;
        }else if($layer==8){
            $len=25;
            $s= 0.38;
        }else if($layer==9){
            $len=36;
            $s= 0.31;
        }else if($layer==10){
            $len=36;
            $s= 0.31;
        }else if($layer==11){
            $len=49;
            $s= 0.27;
        }else if($layer==12){
            $len=49;
            $s= 0.27;
        }else if($layer==13){
            $len=64;
            $s= 0.24;
        }else if($layer==14){
            $len=64;
            $s= 0.24;
        }else{
            $len=81;
            $s= 0.21;
        }
        $arr=[['text'=>$v['option_1'],'percent'=>$s]];
        for($i=0;$i<$len-1;$i++){
            $array=["text"=>$v['option_2'],'percent'=>$s];
            array_push($arr,$array);
        }
        shuffle($arr);
        return $arr;
    }
    public function answer($str)
    {
        $num=strlen($str);
        $arr=[];
        for($i=0;$i<$num;$i++){
            $arr[]=$str[$i];
        }
        return $arr;
    }
}