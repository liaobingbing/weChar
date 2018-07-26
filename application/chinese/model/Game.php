<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/12
 * Time: 10:51
 */

namespace app\chinese\model;


use think\Db;
use think\Model;

class Game extends Model
{
    public function get_question($openId,$layer="")
    {
            $question=cache("chinese_question");
            if(empty($question)) {
                $question=db('questions')->field("option_1,option_2")->select();
                foreach ($question as $k=>$v){
                    $num=$k+1;
                    $question[$num]=$v;
                }
                array_shift($question);
                cache("chinese_question",$question);
            }
            shuffle($question);
            return $question[$layer-1];
    }

    public function get_answer($v,$layer)
    {
        $layer=$layer%12;
        if($layer==1){
            $len=4;
            $s=1;
        }else if($layer==2) {
            $len=4;
            $s=1;
        } else if($layer==3) {
            $len=9;
            $s= 0.64;
        } else if($layer==4){
            $len=9;
            $s= 0.64;
        }else if($layer==5){
            $len=16;
            $s= 0.46;
        } else if($layer==6){
            $len=16;
            $s= 0.46;
        }else if($layer==7){
            $len=25;
            $s= 0.36;
        }else if($layer==8){
            $len=25;
            $s= 0.36;
        }else if($layer==9){
            $len=36;
            $s= 0.28;
        }else if($layer==10){
            $len=36;
            $s= 0.28;
        }else{
            $len=49;
            $s= 0.25;
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