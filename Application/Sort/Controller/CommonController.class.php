<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/8
 * Time: 17:07
 */

namespace Sort\Controller;


use Think\Controller;

class CommonController extends  Controller
{
    //获取题目
    public function get_question(){
            $layer=I('post.layer',1);
            if($layer<=5){
                $arr_num=($layer+2)*($layer+2);
                for($i=1;$i<=$arr_num;$i++){
                    $arr['num']=$i;
                    $arr['status']=false;
                    $question[]=$arr;
                }
                shuffle($question);
                $next_layer=$layer+1;
                $data['code']=200;
                $data['msg']='获取成功';
                $data['data']['question']=$question;
                $data['data']['layer']=$layer;
                $data['data']['next_layer']=$next_layer;
            }else{
                $data['code']=400;
                $data['msg']='没有此等级';
            }

        $this->ajaxReturn($data,'JSON');
    }
}