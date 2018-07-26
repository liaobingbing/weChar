<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/12
 * Time: 10:51
 */

namespace app\gmethod\model;


use think\Db;
use think\Model;

class Game extends Model
{
    public function get_question($openId,$layer=1,$type=1)
    {
        if($layer==1){
            cache($openId,null);
        }
        $question=cache($openId);
        if(empty($question)){
            $question=db('answer')->where("type",$type)->field("id,subject,answer,subject2,type")->select();
           //print_r($question);die;
            shuffle($question);
            $question=array_slice($question,0,10);
            foreach ($question as $k=>$v){
                // $res=Db::name("answer")->field("answer,subject,subject2")->where('id',$v['id'])->find();
                // $num=$k+1;
                // $arr=array($res['answer'],$res['subject'],$res['subject2']);
                // shuffle($arr);
                // $question[$num]=array_merge($v,array('key'=>$arr));

                $arr=array($v['answer'],$v['subject'],$v['subject2']);
                shuffle($arr);
                $question[$k+1]=array_merge($v,array('key'=>$arr));
            }
            cache($openId,$question,3600);
        }
       // print_r($question);die;
        return $question[$layer];
    }
}