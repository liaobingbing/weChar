<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/26
 * Time: 9:57
 */

namespace Admin\Controller;


use Think\Controller;

class ApiAppController extends Controller
{
    public function app_list()
    {
        $list = M('AppList')->where(array('status'=>1))->field('id,logo,name,desc,appid,type')->order('sort')->select();

        $result = array('code' => 200, 'msg' => '成功', 'data' => $list);

        $this->ajaxReturn($result);
    }
}