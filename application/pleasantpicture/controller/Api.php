<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/16
 * Time: 14:31
 */

namespace app\pleasantpicture\controller;

use app\dailylaugh\model\User;
use common\controller\ApiLogin;
use think\Cache;
use think\Db;

class Api extends ApiLogin
{
    private $key = 'kuaiyu666666';

    //授权的接口
    public function login()
    {
        $openId = input("post.open_id");
        $userName = input("nick_name");
        $userImg = input("avatar_url");
        $userdao = new User();
        $user = $userdao->findByOpenid($openId);
        if ($user && empty($user['nick_name'])) {
            $data['nick_name'] = $userName;
            $data['avatar_url'] = $userImg;
            if (Db::name("users")->where("openid", $openId)->update($data)) {
                $arr = resCode(200, "ok", null);
                return $arr;
            } else {
                $arr = resCode(400, "error", null);
                return $arr;
            }
        } else {
            $arr = resCode(400, "无此人或已经更新头像", null);
            return $arr;
        }
    }

    //获取小程序的openid
    public function get_openid()
    {
        $code = input('post.code');
        $login_data = $this->test_weixin($code);
        if (empty($login_data['400']) && !empty($login_data['openid'])) {
            $openid = $login_data['openid'];
            $session_key = $login_data['session_key'];
            $userdao = new User();
            $user = $userdao->findByOpenid($openid);
            $user_id = $user['id'];
            if (empty($user)) {
                $data['openid'] = $openid;
                $data['add_time'] = time();
                $user_id = db("users")->insertGetId($data);
            }
            $arr = array("code" => 200, "msg" => "success", "data" => array("openId" => $openid, "wx_session_key" => $session_key, "user_id" => $user_id));
            return $arr;
        } else {
            return $login_data;
        }
    }

    //视频列表
    public function index()
    {
        $page = input('get.page', 1);
        $randoms = input('get.randoms', 1);

        if($randoms){
            $ids = [];
            $list = [];
            $nums = db('list')->where('status', 1)->where('is_index', 1)->count();
            if($nums != 0){
                $nums = $nums - 1;
                $flag = $nums > 5 ? 5: $nums;
                while (count($ids) < $flag){
                    $tmp = Db::query('select `id`,`title`,`logo`,`content` from pleasant_picture_list WHERE  `status` = 1  AND `is_index` = 1 and id>= ((SELECT MAX(id) FROM pleasant_picture_list)-(SELECT MIN(id) FROM pleasant_picture_list)) * RAND() + (SELECT MIN(id) FROM pleasant_picture_list) limit 1');
                    if(empty($tmp))break;
                    if( !in_array($tmp[0]['id'], $ids)){
                        $list[] = $tmp[0];
                        $ids[] = $tmp[0]['id'];
                    }
                }
            }
        }else{
            $query = db('list')->field(['id', 'title', 'logo', 'content'])
                ->where('status', 1)
                ->where('is_index', 1);
            $list = $query->order('id', 'desc')->page("$page, 10")->select();
        }

        foreach ($list as $key => $item) {
            $list[$key]['logo'] = config('IMG_URL') . $item['logo'];
        }
        return resCode(200, "获取成功", $list);
    }

    //换一批
    public function randomIndex()
    {

    }

    //栏目列表
    public function typeIndex()
    {
        $type_id = input('type_id');
        $page = input('page', 1);


        $list = db('list')->field(['id', 'title', 'logo', 'content'])->where('type_id', $type_id)->where('status', 1)->page("$page, 10")->select();
        foreach ($list as $key => $item) {
            $list[$key]['logo'] = config('IMG_URL') . $item['logo'];
        }

        return resCode(200, "获取成功", $list);
    }

    public function typeList()
    {
        $types = db('type')->field(['id', 'name'])->order('sort')->select();
        return resCode(200, "获取成功", $types);
    }

    //轮播图
    public function bannerList()
    {
//        $list = cache('pleasant_picture_banner');
//        if (empty($list)) {
        $query = db('banner')->field(['id', 'title', 'logo', 'content'])
            ->where('status', 1);
        $list = $query->order('id', 'desc')->select();

        foreach ($list as $key => $item) {
            $list[$key]['logo'] = config('IMG_URL') . $item['logo'];
        }
//            cache('pleasant_picture_banner', $list, 3600);
//        }
        return resCode(200, "获取成功", $list);
    }

    //收藏接口
    public function collect()
    {
        $pic_id = input('post.pic_id');
        $open_id = input('post.open_id');
        if (empty($pic_id) || !is_numeric($pic_id)) return resCode(400, "ID必须是整数", null);

        $user = db('users')->field(['id', 'openid'])->where('openid', $open_id)->find();
        if (!$user) return resCode(400, "用户不存在", null);

        $pic = db('list')->field('id')->find();
        if (!$pic) return resCode(400, "数据不存在", null);

        $repeat_collect = db('collect')->field('id')->where('user_id', $user['id'])->where('pic_id', $pic_id)->find();
        if (empty($repeat_collect)) {
            $data = [
                'user_id' => $user['id'],
                'pic_id' => $pic_id,
                'add_time' => time(),
            ];
            db('collect')->insert($data);
        }
        return resCode(200, "收藏成功", null);
    }

    //分享统计接口
    public function shareCount()
    {
        $pic_id = input('pic_id');
        if (is_numeric($pic_id)) {
            $res = Cache::init();
            $redis = $res->handler();
            //获取该id的计数
            $count = $redis->zscore('pleasant_picture_share_count', $pic_id);
            //判断计数是否存在
            if ($count) {
                //存在 直接累加
                $redis->zincrby('pleasant_picture_share_count', 1, $pic_id);
            } else {
                //不存在则创建，并设置值为1
                $redis->zadd('pleasant_picture_share_count', 1, $pic_id);
            }
        }
        return resCode(200, "更新成功", null);
    }

    //播放界面信息
    public function detailPage()
    {
        $open_id = input('post.open_id');
        $pic_id = input('post.pic_id');
        if (!$pic_id || !is_numeric($pic_id)) return resCode(400, "图文ID必须是整数", null);

        $user = db('users')->field(['id', 'openid'])->where('openid', $open_id)->find();
        if (!$user) return resCode(400, "用户不存在", null);

        $pic = db('list')->field(['id', 'title','content','logo','type_id'])->where('id', $pic_id)->where('status', 1)->find();
        if (!$pic) return resCode(400, "数据不存在", null);

        //更新播放量
        $res = Cache::init();
        $redis = $res->handler();
        //获取该id的计数
        $count = $redis->zscore('pleasant_picture_click_count', $pic['id']);
        //判断计数是否存在
        if ($count) {
            //存在 直接累加
            $redis->zincrby('pleasant_picture_click_count', 1, $pic['id']);
        } else {
            //不存在则创建，并设置值为1
            $redis->zadd('pleasant_picture_click_count', 1, $pic['id']);
        }

        //用户是否收藏
        $collect = db('collect')->field('id')->where('user_id', $user['id'])->where('pic_id', $pic['id'])->find();
        $collect_status = $collect ? 1 : 0;

        //推荐列表
        $pic_list = db('list')->field(['id', 'title', 'logo','content'])
            ->where('status', 1)
            ->where('type_id', $pic['type_id'])
            ->where('id', '<>', $pic['id'])
            ->order('id', 'desc')
            ->select();
        foreach ($pic_list as $key => $item) {
            $pic_list[$key]['logo'] = config('IMG_URL') . $item['logo'];
        }

        $pic['logo'] = config('IMG_URL') . $pic['logo'];

        return resCode(200, "获取成功", ['list' => $pic_list,'current_data' => $pic, 'collect_status' => $collect_status]);
    }

    //收藏列表
    public function collectList()
    {
        $open_id = input('get.open_id');
        $page = input('get.page', 1);
        $user = db('users')->field(['id', 'openid'])->where('openid', $open_id)->find();
        if (!$user) return resCode(400, "用户不存在", null);

        $list = db('collect')->field('c.id,collect_id,v.id,type_id,logo,title,content')
            ->alias('c')
            ->join('pleasant_picture_list v', 'c.video_id = v.id')
            ->where('c.user_id', $user['id'])
            ->where('v.status', 1)
            ->order('c.id', 'desc')
            ->page("$page, 10")
            ->select();

        foreach ($list as $key => $item) {
            $list[$key]['logo'] = config('IMG_URL') . $item['logo'];
        }

        return resCode(200, "获取成功", $list);
    }

    public function addXcxFormId()
    {
        $open_id = input('open_id');
        $form_id = input('form_id');

        if (empty($form_id) || empty($open_id) || $form_id == 'the formId is a mock one' || $form_id == 'undefined') {
            $arr = resCode(200, "SUCCESS");
            return $arr;
        }
        $arr = ['form_id' => $form_id,
            'open_id' => $open_id,
            'add_time' => time()
        ];
        $data = cache("pleasant_picture_formid");
        if (empty($data)) {
            $data[] = $arr;
            cache("pleasant_picture_formid", $data);
        } else if (count($data) < 5000) {
            array_push($data, $arr);
            cache("pleasant_picture_formid", $data);
        } else {
            Db::name('xcx_formid')->insertAll($data);
            cache("pleasant_picture_formid", null);
        }
    }

    public function cache_formid()
    {
        $data = cache("pleasant_picture_formid");
        if (!empty($data)) {
            Db::name('xcx_formid')->insertAll($data);
            cache("pleasant_picture_formid", null);
        }
    }

    //把缓存的数据更新到mysql
    public function updateClickCountToMysql()
    {
        $res = Cache::init();
        $redis = $res->handler();
        $daily_laugh_player_count = $redis->zrange('pleasant_picture_click_count', 0, -1, true);
        foreach ($daily_laugh_player_count as $key => $item) {
            Db::name('video')->where('id', $key)->update(['click_count' => $item]);
            $redis->zadd('pleasant_picture_click_count', 0, $key);
        }
    }


    public function clear_index_cache()
    {
        cache('daily_laugh_index', null, 3600);
        return resCode(200, "清除成功", null);
    }

    public function show_cache()
    {
        $res = Cache::init();
        $redis = $res->handler();
        $redis->zrem('pleasant_picture_click_count', '');
        $redis->zrem('pleasant_picture_share_count', '');
        $click_count = $redis->zrange('pleasant_picture_click_count', 0, -1, true);
        $share_count = $redis->zrange('pleasant_picture_share_count', 0, -1, true);
        return ['code' => 0, 'msg' => '获取成功',
            'data' => [
                'pleasant_picture_click_count' => $click_count,
                'pleasant_picture_share_count' => $share_count,
            ]
        ];
    }

}