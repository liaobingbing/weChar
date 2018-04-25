<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/25
 * Time: 15:23
 */

namespace Admin\Controller;


use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class AppController extends AdminController
{
    // 小程序列表
    public function app_list()
    {
        $list = array(
            array('id'=>1,'logo'=>'logo','name'=>'名称','desc'=>'描述','appid'=>'appid','sort'=>2,'status'=>1,'type'=>'猜猜类'),
        );

        $this->assign('list',$list);

        $this->display();
    }

    public function app_add()
    {

        $this->display();
    }

    public function do_add()
    {
        $request = I('');

        $logo = $_FILES['logo'];

        $logo =  $this->qiniu_upload($logo,'App/Logo/');

        if($logo){
            $request['logo'] = $logo;
        }



        $this->ajaxReturn($request);
    }

}