<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/13
 * Time: 9:09
 */

namespace Admin\Controller;

class IndexController extends AdminController
{
    //后台首页
    public function index(){
        $this->assign('title','后台管理');

        $this->display();
    }

    // 欢迎页面
    public function welcome(){

        $this->display();
    }
}