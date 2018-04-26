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
        // 类型
        $type = M('AppListType')->select();
        $this->assign('type',$type);
        // 列表
        $list = M('AppList')->order('sort')->select();
        $this->assign('list',$list);
        $count = M('AppList')->count();
        $this->assign('count',$count);

        $this->display();
    }

    // 小程序添加页面
    public function app_add()
    {
        // 类型
        $type = M('AppListType')->select();
        $this->assign('type',$type);
        $this->display();
    }

    // 小程序更新页面
    public function app_edit()
    {
        $id = I('id');

        $app = M('AppList')->find($id);
        $this->assign('app',$app);
        // 类型
        $type = M('AppListType')->select();
        $this->assign('type',$type);
        $this->display();
    }

    // 小程序添加操作
    public function do_add()
    {
        $request = I('');
        $logo = $_FILES['logo'];
        $logo =  $this->qiniu_upload($logo,'App/Logo/');

        $result = array('code' => 400, 'msg' => '添加失败');

        if($logo){
            $request['logo'] = $logo;
            $request['add_time'] = time();

            // 验证规则
            $rules = array(
                array('appid','','APPID已经存在！',0,'unique',1), // 在新增的时候验证 appid 字段是否唯一
            );
            $AppList = M('AppList');
            // 自动验证
            if($AppList->validate($rules)->create($request)){
                if( $AppList->add()){
                    $result = array('code'=>200,'msg'=>'添加成功');
                }
            }else{
                $result = array('code'=>400,'msg'=>$AppList->getError());
            }

        }else{
            $result = array('code' => 400, 'msg' => '图片上传失败!');
        }

        $this->ajaxReturn($result);
    }

    // 小程序更新操作
    public function do_edit()
    {
        $result = array('code' => 400, 'msg' => '修改失败');

        if($_FILES['logo']){
            $logo =  $this->qiniu_upload($_FILES['logo'],'App/Logo/');
            if($logo) $request['logo']  =   $logo;
        }

        $request['id']    =   I('post.id');
        $request['name']    =   I('post.name');
        $request['desc']    =   I('post.desc');
        $request['sort']    =   I('post.sort');
        $request['type']    =   I('post.type');
        $request['status']  =   I('post.status');

        if(M('AppList')->save($request)){
            $result = array('code' => 200, 'msg' => '修改成功!');
        }

        $this->ajaxReturn($result);
    }

    // 小程序状态变更
    public function do_status()
    {
        $id   = I('id');
        $result = array('code' => 400, 'msg' => '变更失败');
        $app = M('AppList')->find($id);

        if($app['status'] == 0){
            $app['status'] = 1;
        }else{
            $app['status'] = 0;
        }

        if(M('AppList')->save($app)){
            $result = array('code' => 200, 'msg' => '变更成功');
        }

        $this->ajaxReturn($result);
    }

    // 小程序删除操作
    public function app_del()
    {
        $result = array( 'code'=>400 ,'msg' => '删除失败');
        $id = I('id');

        if (M('AppList')->delete($id)){
            $result = array( 'code'=>200 ,'msg' => '删除成功!');
        }

        $this->ajaxReturn($result);
    }

    // 小程序Banner列表
    public function app_banner_list()
    {
        // 类型
        $type = M('AppList')->select();
        $this->assign('appList',$type);
        // 列表
        $list = M('AppListBanner')->order('sort')->select();
        $this->assign('list',$list);
        $count = M('AppListBanner')->count();
        $this->assign('count',$count);

        $this->display();
    }

    // 小程序Banner添加页面
    public function app_banner_add()
    {
        // 小程序列表
        $list = M('AppList')->select();
        $this->assign('list',$list);
        $this->display();
    }

    // 小程序Banner更新页面
    public function app_banner_edit()
    {
        $id = I('id');

        $banner = M('AppListBanner')->find($id);
        $app = M('AppList')->where(array('id'=>$banner['app_id']))->field('id,name')->find();
        $this->assign('banner',$banner);
        $this->assign('app',$app);

        $this->display();
    }

    // 小程序Banner添加操作
    public function do_banner_add()
    {
        $request = I('');
        $banner = $_FILES['banner'];
        $banner =  $this->qiniu_upload($banner,'App/Banner/');

        $result = array('code' => 400, 'msg' => '添加失败');

        if($banner){
            $data['banner'] = $banner;

            $app = M('AppList')->find($request['app_id']);

            if( $app ){
                $data['app_id']  =    $app['id'];
                $data['sort']    =    $request['sort'];
                $data['status']  =    $request['status'];
                $data['add_time']=   time();

                // 验证规则
                $rules = array(
                    array('app_id','','该小程序已经存在Banner图！',0,'unique',1), // 在新增的时候验证 appid 字段是否唯一
                );
                $AppListBanner = M('AppListBanner');
                // 自动验证
                if($AppListBanner->validate($rules)->create($data)){
                    if( $AppListBanner->add()){
                        $result = array('code'=>200,'msg'=>'添加成功');
                    }
                }else{
                    $result = array('code'=>400,'msg'=>$AppListBanner->getError());
                }
            }else{
                $result = array('code'=>400,'msg' => '请先添加小程序!');
            }

        }else{
            $result = array('code' => 400, 'msg' => '图片上传失败!');
        }

        $this->ajaxReturn($result);
    }

    // 小程序Banner更新操作
    public function do_banner_edit()
    {
        $result = array('code' => 400, 'msg' => '修改失败');
        $request = I('');

        if($_FILES['banner']){
            $banner =  $this->qiniu_upload($_FILES['banner'],'App/Logo/');
            if($banner) $data['banner']  =  $banner;
        }

        $app = M('AppListBanner')->find($request['id']);

        if( $app ){
            $data['id']  =    $app['id'];
            $data['sort']    =    $request['sort'];
            $data['status']  =    $request['status'];

            if(M('AppListBanner')->save($data)){
                $result = array('code' => 200, 'msg' => '修改成功!');
            }

        }else{
            $result = array('code'=>400,'msg' => '请先添加小程序!');
        }



        $this->ajaxReturn($result);
    }

    // 小程序Banner状态变更
    public function do_banner_status()
    {
        $id   = I('id');
        $result = array('code' => 400, 'msg' => '变更失败');
        $app = M('AppListBanner')->find($id);

        if($app['status'] == 0){
            $app['status'] = 1;
        }else{
            $app['status'] = 0;
        }

        if(M('AppListBanner')->save($app)){
            $result = array('code' => 200, 'msg' => '变更成功');
        }

        $this->ajaxReturn($result);
    }

    // 小程序Banner删除操作
    public function app_banner_del()
    {
        $result = array( 'code'=>400 ,'msg' => '删除失败');
        $id = I('id');

        if (M('AppListBanner')->delete($id)){
            $result = array( 'code'=>200 ,'msg' => '删除成功!');
        }

        $this->ajaxReturn($result);
    }

}