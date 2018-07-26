<?php

namespace app\star\controller;

use app\star\model\Answer;
use app\star\model\Users;
use common\controller\ApiLogin;

class Auth extends ApiLogin
{
    //获取好友排行榜
    public function get_friend_ranking()
    {
        $this->check_openid();

        $openid = input("post.openid");
        $userdao = new Users();
        $user = $userdao->findByOpenid($openid);
        $user_id = $user['id'];

        $user_game = $userdao->findGame($user_id);

        if ($user_game) {
            $answer = new Answer();
            $friend_detail = $answer->get_one_friend($user_game['uid']);
            if ($friend_detail) {
                $page = $this->check_int(input('post.page', 1));
                $page_size = 10;
                $count = count($friend_detail['data']);
                $start = ($page - 1) * $page_size;
                $total = ceil($count / $page_size);
                $data['code'] = 200;
                $data['msg'] = '成功';
                $data['data']['my_ranking'] = $friend_detail['my_ranking'];
                $data['data']['my_idiom'] = $user_game['idiom_num'];
                $data['data']['nickname'] = $user_game['nickname'];
                $data['data']['page'] = $page;
                $data['data']['page_size'] = $page_size;
                $data['data']['count'] = $count;
                $data['data']['total'] = $total;
                $data['data']['friend'] = array_slice($friend_detail['data'], $start, $page_size);
            } else {
                $data['code'] = 400;
                $data['msg'] = '出错了，请联系管理员';
            }
        } else {
            $data['code'] = 401;
            $data['msg'] = '重新登录';
        }
        $data = json_encode($data);
        $data = json_decode($data);
        return $data;
    }

    //获取世界排行榜
    public function get_world_ranking()
    {
        $this->check_openid();

        $openid = input("post.openid");
        $userdao = new Users();
        $user = $userdao->findByOpenid($openid);
        $user_id = $user['id'];

        $userdao = new Users();
        $user_game = $userdao->findGame($user_id);
        if ($user_game) {
            $page = $this->check_int(input('post.page', 1));
            $answer = new Answer();
            $all_ranking = $answer->get_world_ranking($user_game['uid']);
            if ($all_ranking) {
                $page_size = 10;
                $count = count($all_ranking['data']);
                $start = ($page - 1) * $page_size;
                $total = ceil($count / $page_size);
                $data['code'] = 200;
                $data['msg'] = '成功';
                $data['data']['my_ranking'] = $all_ranking['my_ranking'];
                $data['data']['my_idiom'] = $user_game['idiom_num'];
                $data['data']['nickname'] = $user_game['nickname'];
                $data['data']['page'] = $page;
                $data['data']['page_size'] = $page_size;
                $data['data']['count'] = $count;
                $data['data']['total'] = $total;
                $data['data']['world'] = array_slice($all_ranking['data'], $start, $page_size);
            } else {
                $data['code'] = 400;
                $data['msg'] = '出错了，请联系管理员';
            }
        } else {
            $data['code'] = 401;
            $data['msg'] = '重新登录';
        }

        return $data;
    }

    public function check_openid()
    {
        $openid = session('openid');    //todo 哪里放进session的

        if (!$openid) {
            $result['code'] = 401;
            $result['msg'] = '未登录OPenId未获取';
            return $result;
        }
    }

    //验证整数
    public function check_int($num)
    {
        if (floor($num) == $num) {
            return $num;
        } else {
            return '';
        }
    }
}
