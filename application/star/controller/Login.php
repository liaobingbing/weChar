<?php

namespace app\star\controller;

use app\star\model\Users;
use common\controller\ApiLogin;

class Login extends ApiLogin
{
    private $key = 'kuaiyu666666';

    //小程序登录
    public function login()
    {
        $userdao = new Users();
        $openid = input("post.openId");
        $session_key = input('post.session_key');
        $userInfo = input('post.userInfo');//获取前台传送的用户信息
        $userInfo = str_replace("&quot;", "\"", $userInfo);
        $userInfo = json_decode($userInfo, true);
        if ($openid && $userInfo) {
            session('wx_session_key', $session_key);
            $user = $userdao->findByOpenid($openid);
            if ($user) {
                if ($user['status'] == 0) {
                    $data['code'] = 403;//已经被拉黑
                    $data['msg'] = '已经被拉黑';
                    return $data;
                }
                $user_data = array();
                $user_data['id'] = $user["id"];
                $user_data['openid'] = $openid;
                $user_data['gender'] = $userInfo['gender'];
                $user_data['city'] = $userInfo['city'];
                $user_data['province'] = $userInfo['province'];
                $user_data['country'] = $userInfo['country'];
                $user_data['avatarUrl'] = $userInfo['avatarUrl'];
                $user_data['add_time'] = time();
                $user_data['last_time'] = time();
                $user_data['login_time'] = time();
//                $user_data['sign'] = 1;
                $user_data['nickname'] = $userInfo['nickName'];
                db('users')->update($user_data);
                $user_game['uid'] = $user['id'];
                $user_game['nickname'] = $userInfo['nickName'];
//                $user_game['gold_num'] = 200;
                $user_game['avatarUrl'] = $userInfo['avatarUrl'];
                db('user_game')->where('uid', $user['id'])->update($user_game);
            }

            session('user_id', $user["id"], 3600);
            session("openid", $openid);
            $data['code'] = 200;
            $data['msg'] = 'success';
            //$data['data']=array('session_key'=>$session_k);
            return $data;
        }

    }

//更新世界排行
    public function mx_world_ranking()
    {
        $key = input('get.key');
        if ($key == $this->key) {
            $world_arr = array();
            $ranking_arr = db('user_game')->field('uid,avatarUrl,nickname,gold_num,idiom_num')->where("avatarUrl is not null")->order('idiom_num desc')->limit(100)->select();
            foreach ($ranking_arr as $k => $v) {
                $ranking = $k + 1;
                $world_arr[$ranking] = $v;
                $world_arr[$ranking]['ranking'] = $ranking;
            }
            cache('mx_world_ranking', $world_arr);
        }
    }


    public function post_url($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);   //只需要设置一个秒的数量就可以
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        //打印获得的数据
        //print_r($output);
        return $output;
    }

    public function test()
    {
        session('user_id', 2);
    }

    public function get_openid()
    {
        session("openid", null);
        $userdao = new Users();

        $code = input('post.code');
        $login_data = $this->test_weixin($code);
        if (empty($login_data['code']) && $login_data['openid']) {
            $openid = $login_data['openid'];
            $user = $userdao->findByOpenid($openid);
            $uid = $user['id'];
            if (empty($user)) {
                $data['openid'] = $openid;
                $data['login_time'] = time();
                $user_id = db("users")->insertGetId($data);
                $game["uid"] = $user_id;
                $game['gold_num'] = 200;
                db('user_game')->insert($game);
                $uid = $user_id;
            }
            $session_k = session_id();
            session('user_id', $uid, 3600);
            $session_key = $login_data['session_key'];
            $arr = array("code" => 200, "msg" => "success", "data" => array("openId" => $openid, "wx_session_key" => $session_key, "server_key" => $session_k, "user_id" => $uid,'status'=>1));
            return $arr;
        } else {
            return $login_data;
        }
    }

    /**
     * 小程序formid写入
     * @param form_id 小程序formid
     * @param open_id 微信openid
     */
    public function addXcxFormId()
    {
        $form_id = input('form_id');
        $open_id = input('open_id');

        if (empty($form_id) || empty($open_id) || $form_id == 'the formId is a mock one' || $form_id == 'undefined') {
            $arr = array("code" => 200, "msg" => "SUCCESS");
            return $arr;
        }
        if (db('xcx_formid')->insert(array(
            'form_id' => $form_id,
            'open_id' => $open_id,
            'add_time' => time()
        ))) {
            $arr = array("code" => 200, "msg" => "SUCCESS");
        } else {
            $arr = array("code" => 400, "msg" => "网络错误");
        }
        return $arr;
    }
}
