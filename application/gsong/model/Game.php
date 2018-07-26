<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/12
 * Time: 10:51
 */

namespace app\gsong\model;


use think\Db;
use think\Model;

class Game extends Model
{
    public function get_question($openId,$layer)
    {
        if($layer==1){
            cache($openId,null);
        }
        $question=cache($openId);
        if(empty($question)){
            $question1=db('answer')->where("rank",1)->field("id,listen1,rank,layer,answer,listen2")->select();
            shuffle($question1);
            $question1=array_slice($question1,0,3);
            $question2=db('answer')->where("rank",2)->field("id,listen1,rank,layer,answer,listen2")->select();
            shuffle($question2);
            $question2=array_slice($question2,0,2);
            $question=array_merge($question1,$question2);
            foreach ($question as $k=>$v){
                $res=Db::name("answer")->field("answer,select1,select2,select3")->where('id',$v['id'])->find();
                $num=$k+1;
                $arr=array($res['answer'],$res['select1'],$res['select2'],$res['select3']);
                shuffle($arr);
                $question[$num]=array_merge($v,array('key'=>$arr));
            }
            cache($openId,$question,3600);
        }
        //print_r($question);die;
        return $question[$layer];
    }
}