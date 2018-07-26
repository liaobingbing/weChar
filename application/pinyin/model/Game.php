<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/12
 * Time: 10:51
 */

namespace app\pinyin\model;


use think\Db;
use think\Model;

class Game extends Model
{
    public function get_question($openId,$layer="")
    {
        if($layer==1){
            cache($openId,null);
        }
        $question=cache("pinyin");
        if(empty($question)){
            $question=db('answer')->where("rank",1)->field("id,word,answer,select1,layer")->select();
            //shuffle($question);
            //$question=array_slice($question,0,5);
            foreach ($question as $k=>$v){
                $num=$k+1;
                $question[$num]=$v;
                $question[$num]['answer']=$this->answer($v['answer']);
                $question[$num]['select1']=$this->get_answer(str_shuffle($v['select1']));
            }
            array_shift($question);
            cache("pinyin",$question,86400);
        }
        return $question[$layer-1];
    }

    public function get_answer($str)
    {
        $num=strlen($str);
        $arr=[];
        for($i=0;$i<$num;$i++){
           $arr[$i]['text']=$str[$i];
           $arr[$i]['status']=false;
        }
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