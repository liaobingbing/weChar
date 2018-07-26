<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/12
 * Time: 10:51
 */

namespace app\knowword\model;


use think\Db;
use think\Model;

class Game extends Model
{
    public function get_question($openId)
    {

        $question=cache($openId);
        if(empty($question)){
            $question=db('answer')->select();
            cache($openId,$question,3600);
        }
        shuffle($question);
        $question=array_slice($question,0,5);
        //print_r($question);die;
        return $question;
    }
}