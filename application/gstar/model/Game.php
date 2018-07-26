<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/12
 * Time: 10:51
 */

namespace app\gstar\model;


use think\Db;
use think\Model;

class Game extends Model
{
    public function get_question($openId,$layer=1,$rank=1)
    {
        if($layer==1){
            cache($openId,null);
        }
        $question=cache($openId);
        if(empty($question)){
            $question=db('answer')->where("rank",$rank)->field("id,rank,answer,img_url")->select();
           //print_r($question);die;
            shuffle($question);
            foreach ($question as $k=>$v){
                $res=Db::name("answer")->field("answer,select1,select2")->where('id',$v['id'])->find();
                $num=$k+1;
                $arr=array($res['answer'],$res['select1'],$res['select2']);
                shuffle($arr);
                $question[$num]=array_merge($v,array('key'=>$arr));
            }
            cache($openId,$question,3600);
        }
       // print_r($question);die;
        return $question[$layer];
    }
}