<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/12
 * Time: 18:05
 */

namespace Admin\Model;

class AdminModel extends BaseModel
{


    // 登录验证
    public function do_login($username,$password){
        $admin = M('Admin')->where(array('user_name'=>$username))->find();

        $result = '';

        if($admin){
            if( $admin['status'] == 1 ){
                if( $admin['user_pwd'] == md5(md5($password))){
                    $admin['last_login_time'] = $admin['login_time'];
                    $admin['last_login_ip'] = $admin['login_ip'];
                    $admin['login_time'] = time();
                    $admin['login']++;

                    $re = M('Admin')->save($admin);
                    if($re){
                        session('expire',3600);
                        session('admin_id',$admin['id']);
                        session('admin_username',$admin['user_name']);
                        session('admin_lv',$admin['lv']);
                        $this->write_log('登录');
                        $result = 'login_success';
                    }else{
                        $result = 'login_fail';
                    }
                }else{
                    $result = 'pwd_error';
                }
            }else{
                $result = 'admin_status';
            }
        }else{
            $result = 'no_admin';

        }

        return $result;
    }

    //查找管理员
    public function find_admin($admin_id=null){
        if($admin_id==null) {
            $admin_id = session("admin_id");
        }
        $admin = D('Admin')->where("id=".$admin_id)->find();
        return $admin;
    }

}