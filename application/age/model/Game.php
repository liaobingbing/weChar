<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/15
 * Time: 14:15
 */

namespace app\age\model;


use think\Model;

class Game extends Model
{
    public function get_question($layer,$openid)
    {
        if($layer==1){
            cache($openid,null);//缓存
        }
        $question =cache($openid);
        if (empty($question))
        {
            $question1 = db("answer")->where("layer", 1)->select();
            $question2 = db("answer")->where("layer", 2)->select();
            $question3 = db("answer")->where("layer", 3)->select();
            $question4 = db("answer")->where("layer", 4)->select();
            $question5 = db("answer")->where("layer", 5)->select();
            shuffle($question1);
            $question1 = array_slice($question1, 0, 2);
            shuffle($question2);
            $question2 = array_slice($question2, 0, 2);
            shuffle($question3);
            $question3 = array_slice($question3, 0, 2);
            shuffle($question4);
            $question4 = array_slice($question4, 0, 2);
            shuffle($question5);
            $question5 = array_slice($question5, 0, 2);
            $question = array_merge($question1, $question2, $question3, $question4, $question5);
            shuffle($question);
            foreach ($question as $k => $v) {
                $num = $k + 1;
                $question[$num] = $v;
            }
            //print_r($question);die;
            cache($openid, $question, 7200);
        }
        //print_r($question) ;die;
        return $question[$layer];
    }
}