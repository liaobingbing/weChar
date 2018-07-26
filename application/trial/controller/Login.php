<?php
/**
 * Created by sub.
 * User: wei
 * Date: 2018/7/10
 * Time: 14:10
 */

namespace app\trial\controller;

use app\trial\model\Users;
use common\controller\ApiLogin;

class Login extends ApiLogin
{
	private  $key="kuaiyu666666";

    // 首页内容
    public function xcxIndex()
    {
        $user_id    = intval(input('post.user_id'));
        $userInfo = db('users')->where('id',$user_id)->field('nickname,avatar_url,login_time')->find();
        if(date('Y-m-d',$userInfo['login_time']) == date('Y-m-d')){
            // 当日已签到
            return array('code'=>201,'msg'=>'已签到','data'=>$userInfo);
        }else{
            if(db('users')->where('id',$user_id)->update(array('chance_num'=>3,'login_time'=>time()))){
                return array('code'=>200,'msg'=>'签到成功','data'=>$userInfo);
            }else{
                return array('code'=>400,'msg'=>'签到失败','data'=>$userInfo);
            }
        }
    }

    // 分享领取挑战机会
    public function share_group()
    {
        $user_id=input('post.user_id');
        $session_key=input('post.wx_session_key');
        $encryptedData = input("post.encryptedData");
        $iv = input("post.iv");
        if($encryptedData&&$iv&&$session_key){
            if($session_key){
                vendor("wxaes.wxBizDataCrypt");
                $wxBizDataCrypt = new \WXBizDataCrypt(config("WECHAT_APPID"), $session_key);
                $data_arr = array();
                $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data_arr);
                if($errCode==0){
                    $json_data = json_decode($data_arr, true);
                    // 删除掉所有过期的群分享id
                    $where['user_id'] = $user_id;
                    $where['add_time'] = ['lt',strtotime(date("Y-m-d"),time())];
                    db('share_group')->where($where)->delete();
                    // 是否分享过
                    if(db('share_group')->where(array('user_id'=>$user_id,'group_id'=>$json_data['openGId']))->count()){
                        return array('code'=>400,'msg'=>'已分享');
                    }else{
                        $add_arr = [
                            'user_id'=>$user_id,
                            'group_id'=>$json_data['openGId'],
                            'add_time'=>time()
                        ];
                        db('share_group')->insertGetId($add_arr);
                        db('users')->where('id',$user_id)->setInc('chance_num');
                        return array('code'=>200,'msg'=>'分享成功');
                    }
                }else{
                    return array('code'=>403,'msg'=>'微信session_key过期');
                }
            }else{
                return array('code'=>401,'msg'=>'重新登录');
            }
        }else{
            return array('code'=>400,'msg'=>'参数不全');
        }
        return array('code'=>400,'msg'=>'网络错误，请稍后再试');
    }

    public function login(){
        $userdao=new Users();
        $userInfo=input('post.userInfo');//获取前台传送的用户信息
        $userInfo=str_replace("&quot;","\"",$userInfo);
        $userInfo=json_decode($userInfo,true);
        $openid=input('post.openid');//获取opendId
        if($openid&&$userInfo){
            $user = $userdao->findByOpenid($openid);
            $user_data = [
                'gender'=>$userInfo['gender'],
                'city'=>$userInfo['city'],
                'province'=>$userInfo['province'],
                'country'=>$userInfo['country'],
                'avatar_url'=>str_replace('/0','/132',$userInfo['avatarUrl'] ),
                'nickname'=>$userInfo['nickName'],
                "login_time"=>time()
            ];
            if (!$user) {
                $user_data['openid'] = $openid;
                $uid = db('users')->insertGetId($user_data);
            }else{
                $user_data['add_time'] = time();
                db('users')->where('openid',$openid)->update($user_data);
            }
            // cache('user_id',$uid,3600);
            // cache("openid",$openid);
            return array('code'=>200,'msg'=>'添加成功','data'=>$userInfo);
        }
        else{
            return array("code"=>400,"msg"=>"error","data"=>null);
        }

    }

    public function get_openid()
    {
        $code = input('post.code');
        $login_data = $this->test_weixin($code);
        if (empty($login_data['code'])&&$login_data['openid']) {
            $openid = $login_data['openid'];
            $session_key=$login_data['session_key'];
            $userdao=new Users();
            $user = $userdao->findByOpenid($openid);
            if(empty($user)){
                $data['openid']=$openid;
                $data['login_time'] = time();
                $data['add_time'] = time();
                $uid = db("users")->insertGetId($data);
            }else{
                $uid = $user['id'];
            }
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openid"=>$openid,"wx_session_key"=>$session_key,'user_id'=>$uid));
            return $arr;
        }
        else{
        	return $login_data;
        }
    }

    public function addXcxFormId()
    {
        $form_id = input('form_id');
        $open_id = input('open_id');

        if (empty($form_id) || empty($open_id) || $form_id == 'the formId is a mock one' || $form_id == 'undefined') {
            $arr=resCode(200,"SUCCESS");
            return $arr;
        }
        $arr=['form_id' => $form_id,
            'open_id' => $open_id,
            'add_time' => time()
        ];
        $data=cache("trial_formid");
        if(empty($data)){
            $data[]=$arr;
            cache("trial_formid",$data);
            $arr=resCode(400,"error");
            return $arr;
        }else if(count($data)<1){
            array_push($data,$arr);
            cache("trial_formid",$data);
            $arr=resCode(400,"error");
            return $arr;
        }else{
            Db::name('xcx_formid')->insertAll($data);
            cache("trial_formid",null);
            $arr=resCode(200,"SUCCESS");
            return $arr;
        }
    }

    //从缓存中取
    public function cache_formid()
    {
        $data=cache("trial_formid");
        if(!empty($data)){
            Db::name('xcx_formid')->insertAll($data);
            cache("trial_formid",null);
        }
    }

    //查询缓存的长度
    public function cache_long(){
        $data=cache("trial_formid");
        return count($data);
    }
}