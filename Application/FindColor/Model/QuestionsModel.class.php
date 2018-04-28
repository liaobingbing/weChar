<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/19
 * Time: 9:56
 */

namespace FindColor\Model;


use Think\Model;

class QuestionsModel extends Model
{
    /**
     * 获取题库
     * @param int $expire 缓存时间
     * @return mixed
     */
    public function get_questions($expire = 86200){
        $key = 'find_color_questions';
        $question = S($key);

        if( !$question ){
            $question = M('Questions')->field('id,option_1,option_2')->select();
            S($key,$question,$expire);
        }

        return $question;
    }

    /**
     * 随机题库
     * @return array
     */
    public function get_rand_questions(){
        $questions = $this->get_questions();

        $level_1 = array_slice($questions,0,68);
        $level_2 = array_slice($questions,68,-1);
        $level_3 = array_slice($questions,-1,1);

        shuffle($level_1);
        shuffle($level_2);

        $level_1 = array_slice($level_1,0,24);
        $level_2 = array_slice($level_2,0,19);

        foreach($level_1 as $k => $v){
            $rand = rand(1,2);
            $level_1[$k] = $v;
            $key = array_keys($v);
            $level_1[$k]['answer'] = $key[$rand];
        }

        foreach($level_2 as $k => $v){
            $rand = rand(1,2);
            $level_2[$k] = $v;
            $key = array_keys($v);
            $level_2[$k]['answer'] = $key[$rand];
        }

        foreach($level_3 as $k => $v){
            $rand = rand(1,2);
            $level_3[$k] = $v;
            $key = array_keys($v);
            $level_3[$k]['answer'] = $key[$rand];
        }
        $result['level_1'] = $level_1;
        $result['level_2'] = $level_2;
        $result['level_3'] = $level_3;

        $result = array_merge($level_1,$level_2,$level_3);

        return $result;
    }
}