<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/19
 * Time: 9:17
 */

namespace FindWord\Controller;


use Common\Controller\ApiBaseController;
use Common\Controller\ApiLoginController;
use FindWord\Model\QuestionsModel;
use FindWord\Model\ShareGroupModel;
use FindWord\Model\UserGameModel;
use FindWord\Model\UsersModel;

class ApiController extends ApiBaseController
{
    // 首页
    public function index(){
        $Questions = new QuestionsModel();
        $Users = new UsersModel();
        $UserGame = new UserGameModel();
        $ShareGroup = new ShareGroupModel();

        $Login =new ApiLoginController();
        $result = $Login->wx_biz_data_crypt(1,2,3,'123');



        $this->ajaxReturn($result);
    }

    // 验证挑战次数接口
    public function check_chance_num(){
        $user_id = session('user_id');
        $UserGame = new UserGameModel();

        $re = $UserGame->check_chance_num($user_id);

        $result = array('code'=>400,'msg'=>'无挑战次数');

        if($re){
            $result = array('code'=>200,'msg'=>'有挑战次数');
        }

        $this->ajaxReturn($result);
    }

    // 获取题目
    public function get_question(){
        $Questions = new QuestionsModel();

        $questions = $Questions->get_rand_questions();
        $result = array('code'=>400,'msg'=>'获取失败');

        if($questions){
            $result = array('code'=>200,'msg'=>'获取成功');
            $result['data'] = $questions;
        }

        $this->ajaxReturn($result);
    }


}