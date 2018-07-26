<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/12
 * Time: 10:28
 */

namespace app\confuse\controller;

use app\confuse\model\User;
use app\confuse\model\UserGame;
use common\controller\ApiLogin;
use think\Cache;
use think\Db;

class Login extends ApiLogin
{
    private $key = "kuaiyu666666";

    //获取小程序的openid
    public function get_openid()
    {
        $code = input('post.code');
        $login_data = $this->test_weixin($code);
        if (empty($login_data['400']) && $login_data['openid']) {
            $openid = $login_data['openid'];
            $session_key = $login_data['session_key'];
            $userdao = new User();
            $user = $userdao->findByOpenid($openid);
            $user_id = $user['id'];
            if (empty($user)) {
                $data['openid'] = $openid;
                $data['login_time'] = date("Y-m-d H:i:s");
                $user_id = db("users")->insertGetId($data);
                $game['uid'] = $user_id;
                $game['login_time'] = time();
                db("user_game")->insert($game);
            }
            $arr = array("code" => 200, "msg" => "success", "data" => array("openId" => $openid, "wx_session_key" => $session_key, "user_id" => $user_id, "status" => 1));
            return $arr;
        } else {
            return $login_data;
        }
    }

    //授权
    public function login()
    {
        $userInfo = input('post.userInfo');//获取前台传送的用户信息
        $userInfo = str_replace("&quot;", "\"", $userInfo);
        $userInfo = json_decode($userInfo, true);
        $openid = input('post.openId');//获取opendId
        $wx_key = input('post.session_key');
        if ($openid && $userInfo) {
            session('wx_session_key', $wx_key);
            // user_game 表数据处理
            $uid = $this->deal_user_game_data($userInfo, $openid);
            if (is_array($uid)) return $uid;
            $session_k = session_id();
            session('user_id', $uid, 3600);
            session("openid", $openid);
            $data['code'] = 200;
            $data['msg'] = 'success';
            $data['data'] = array('session_key' => $session_k);
            return $data;
        } else {
            $arr = array("code" => 400, "msg" => "参数不全", null);
            return $arr;
        }
    }

    //分享群
    public function share_group()
    {
        $data = array("code" => 200, "msg" => "success", "data" => null);
        return $data;
    }

    //缓存挑战次数
    public function cache_num()
    {
        $key = input('get.key');
        if ($key == $this->key) {
            $user_info = db('user_game')->field('challenge_num,avatar_url,nickname')->where('avatar_url is not null')->order('challenge_num desc')->limit(8)->select();
            foreach ($user_info as $k => $v) {
                $user_info[$k]['ranking'] = $k + 1;
            }
            $sql2 = "SELECT avatar_url as avatarUrl ,get_number as gt_number ,nickname FROM confuse_user_game   where(avatar_url is not null) order by  gt_number desc LIMIT 0,8";
            $user_info2 = Db::query($sql2);
            //$user_info2=array_merge($data1,$data2);
            foreach ($user_info2 as $k => $v) {
                $user_info2[$k]['ranking'] = $k + 1;
            }
            cache("c_num_top", $user_info);
            cache("c_intelligence_top", $user_info2);
        }
    }

    //统计挑战的次数
    public function count_challenge()
    {
        $count_challenge=cache("confuse_challenge");
        if(!$count_challenge){
            $count_challenge=Db::name('user_game')->sum('challenge_num');
            cache("confuse_challenge",$count_challenge+5000,86400);
        }else{
            Cache::inc("confuse_challenge");
        }
        $result=resCode(200,"success",$count_challenge);
        return $result;
    }

    //智力榜
    public function intelligence_top()
    {
        $user_info = cache('c_intelligence_top');
        if (!$user_info) {
            //SELECT avatarUrl,gt_number as number,nickname FROM method_test_game WHERE id >= ((SELECT MAX(id) FROM method_test_game)-(SELECT MIN(id) FROM method_test_game)) * RAND() + (SELECT MIN(id) FROM method_test_game)  order by  number desc LIMIT 5;
            $sql2 = "SELECT avatar_url as avatarUrl ,get_number as gt_number ,nickname FROM confuse_user_game  where(avatar_url is not null)  order by  gt_number desc LIMIT 0,8";
            $user_info = Db::query($sql2);
            foreach ($user_info as $k => $v) {
                $user_info[$k]['ranking'] = $k + 1;
            }
            cache("c_intelligence_top", $user_info);
        }
        // $user_info=M('user_game')->field('get_number,avatar_url,nickname')->order('get_number desc')->limit(5)->select();
        $arr = array('code' => 200, 'msg' => 'success', 'data' => $user_info);
        return $arr;
    }

    //毅力榜
    public function num_top()
    {
        $user_info = cache('c_num_top');
        if (!$user_info) {
            $user_info = db('user_game')->field('challenge_num,avatar_url,nickname')->where('avatar_url is not null')->order('challenge_num desc')->limit(8)->select();
            foreach ($user_info as $k => $v) {
                $user_info[$k]['ranking'] = $k + 1;
            }
            cache("c_num_top", $user_info);
        }
        $arr = array('code' => 200, 'msg' => 'success', 'data' => $user_info);
        return $arr;
    }

    //获取题目
    public function get_question()
    {
        $layer = input('post.layer', 1);
        if ($layer <= 30) {
            $sql = 'SELECT * FROM confuse_answer WHERE status=1 ORDER BY  RAND() LIMIT 1';
            $question = Db::query($sql);
            if ($question) {
                $data['code'] = 200;
                $data['msg'] = '获取成功';
                $data['data']['nex_layer'] = $layer + 1;
                $data['data']['subject1'] = $question[0]['subject1'];
                $data['data']['subject2'] = $question[0]['subject2'];
                if ($layer > 23) {
                    $odds = ($layer - 23) * 100;
                } else {
                    $odds = 0;
                }
                $rand = rand(0, 500);
                if ($rand > $odds) {
                    $data['data']['answer'] = $question[0]['answer'];
                } else {
                    $data['data']['answer'] = 3;
                }
            } else {
                $data['code'] = 400;
                $data['msg'] = '题库出错';
            }

        } else {
            $data['code'] = 400;
            $data['msg'] = '没有此等级';
        }
        return $data;
    }

    //设置session
    public function set_session()
    {
        session('user_id', 1);
    }

    //开始 挑战
    public function begin_challenge()
    {
        $openId = input("post.openId");
        $UserGame = new UserGame();
        $userdao = new User();
        $user = $userdao->findByOpenid($openId);
        $user_id = $user['id'];
        $user_game = $UserGame->find_by_user_id($user_id);
        if ($user_game) {
            db('user_game')->where(array('uid' => $user_id))->setInc('challenge_num');
            $result['code'] = 200;
            $result['msg'] = '开始挑战成功';
            $result['data'] = null;

        } else {
            $result['code'] = 400;
            $result['msg'] = '用户不存在';
            $result['data'] = null;
        }
        return $result;
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
        if (db('xcx_formid')->add(array(
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

    protected function deal_user_game_data($userInfo, $openid)
    {
        $userdao = new User();
        $user = $userdao->findByOpenid($openid);
        if (!$user) {
            $user_data['openid'] = $openid;
            $user_data['gender'] = $userInfo['gender'];
            $user_data['city'] = $userInfo['city'];
            $user_data['login_time'] = time();
            $user_data['province'] = $userInfo['province'];
            $user_data['country'] = $userInfo['country'];
            $user_data['avatar_url'] = $userInfo['avatarUrl'];
            $user_data['name'] = $userInfo['nickName'];
            $uid = db("users")->insertGetId($user_data);
            $user_game['uid'] = $uid;
            $user_game['nickname'] = $userInfo['nickName'];
            $user_game['login_time'] = time();
            $user_game['avatar_url'] = $userInfo['avatarUrl'];
            db("user_game")->insert($user_game);
        } else {
            if ($user['status'] == 0) {
                $data['code'] = 403;//已经被拉黑
                $data['msg'] = '已经被拉黑';
                $data['data']['user_id'] = $user['id'];
                return $data;
            }
            $user_data['id'] = $user['id'];
            $user_data['openid'] = $openid;
            $user_data['gender'] = $userInfo['gender'];
            $user_data['city'] = $userInfo['city'];
            $user_data['login_time'] = time();
            $user_data['province'] = $userInfo['province'];
            $user_data['country'] = $userInfo['country'];
            $user_data['avatar_url'] = $userInfo['avatarUrl'];
            $user_data['name'] = $userInfo['nickName'];
            db("users")->where('id', $user['id'])->update($user_data);
            $user_game['uid'] = $user['id'];
            $user_game['nickname'] = $userInfo['nickName'];
            $user_game['login_time'] = time();
            $user_game['avatar_url'] = $userInfo['avatarUrl'];
            db("user_game")->where('uid', $user['id'])->update($user_game);
            $uid = $user['id'];
        }
        return $uid;
    }
}