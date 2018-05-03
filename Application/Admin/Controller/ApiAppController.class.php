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
    protected function _initialize(){
        $key = I('post.key');
        if( $key != 'kuaiyu123456' ){
            $this->ajaxReturn(array('code' => 400, 'msg' => '获取失败'));
        }
    }

    public function app_list()
    {
        $result = array('code' => 400, 'msg' => '获取失败');

        $list = M('AppList')->where(array('status'=>1))->field('id,logo,name,desc,appid,type')->order('sort')->select();

        $result = array('code' => 200, 'msg' => '成功', 'data' => $list);

        $this->ajaxReturn($result);
    }

}