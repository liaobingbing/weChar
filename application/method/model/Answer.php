<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/4/14
 * Time: 10:45
 */

namespace app\method\model;


use think\Model;

class Answer extends Model
{

    public function get_question($layer,$openId=null){
        if($layer==1){
            cache($openId,null);
        }
        $question_arr=cache($openId);
        if(!$question_arr){
            $question_arr1=db('answer')->where('id<=72')->select();
            shuffle($question_arr1);
            $question_arr1=array_slice($question_arr1,0,20);
            $question_arr2=db('answer')->where('id>72')->select();
            shuffle($question_arr2);
            $question_arr2=array_slice($question_arr2,0,10);
            $question_arr3=array_merge($question_arr1,$question_arr2);
            foreach($question_arr3 as $k=>$v){
                $num=$k+1;
                $question_arr[$num]=$v;
            }
            cache($openId,$question_arr,3600);
        }
        return $question_arr[$layer];
    }

}