<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/16
 * Time: 14:31
 */

namespace app\dailylaugh\controller;

use app\dailylaugh\model\User;
use common\controller\ApiBase;
use think\Cache;
use think\Db;

class Api extends ApiBase
{
    private $key = 'kuaiyu666666';

    public function __construct()
    {
        //
    }

    //判断微信登陆
    protected function test_weixin($code = null)
    {
        if ($code) {
            $arr = array(
                'appid' => config('WECHAT_APPID'),
                'secret' => config('WECHAT_APPSECRET'),
                'js_code' => $code,
                'grant_type' => 'authorization_code'
            );
            $code_session = post_url('https://api.weixin.qq.com/sns/jscode2session', $arr);
            if (!empty($code_session['errcode'])) {
                $data['code'] = 400;
                $data['msg'] = $code_session['errcode'] . "==" . $code_session['errmsg'];;
                return $data;
            } else {
                return $code_session;
            }
        } else {
            $data['code'] = 400;
            $data['msg'] = '参数code为空';
            return $data;
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
                $status = 0;
                $data['openid'] = $openid;
                $data['status'] = $status;
                $data['add_time'] = time();
                $user_id = db("users")->insertGetId($data);
            } else {
                $status = $user['status'];
            }
            $arr = array("code" => 200, "msg" => "success", "data" => array("openId" => $openid, "wx_session_key" => $session_key, "user_id" => $user_id, "status" => $status));
            return $arr;
        } else {
            return $login_data;
        }
    }

    //视频列表
    public function index()
    {
        //$list = cache('daily_laugh_index');
        //if (empty($list)) {
        $page = input('get.page', 1);
        $query = db('video')->field(['id', 'name', 'logo', 'vid', 'content', 'player_count', 'duration', 'share_logo'])
            ->where('status', 1)
            ->where('is_index', 1);
        $list = $query->order('id', 'desc')->page("$page, 10")->select();

        $res = Cache::init();
        $redis = $res->handler();

        foreach ($list as $key => $item) {
            $list[$key]['logo'] = config('IMG_URL') . $item['logo'];
            $list[$key]['share_logo'] = config('IMG_URL') . $item['share_logo'];
            $player_count = $redis->zscore('daily_laugh_player_count', $item['id']);
            if ($player_count) $list[$key]['player_count'] = $player_count;
        }
        //cache('daily_laugh_index', $list, 3600);
        //}
        return resCode(200, "获取成功", $list);
    }

    public function userInfo()
    {
        $open_id = input('post.openid');
        $user = db('users')->field(['id', 'openid', 'status'])->where('openid', $open_id)->find();
        if (!$user) return resCode(400, "用户不存在", null);
        return resCode(200, "获取成功", $user);
    }

    public function subscription()
    {
        $open_id = input('post.openid');

        $user = db('users')->field(['id', 'openid', 'status'])->where('openid', $open_id)->find();
        if (!$user) return resCode(400, "用户不存在", null);

        //不传subscribe 表示获取订阅状态，传subscribe 则表示修改订阅状态
        if ($user['status'] == 0) {
            db('users')->where('openid', $open_id)->update(['status' => 1]);
        } else {
            db('users')->where('openid', $open_id)->update(['status' => 0]);
        }
        return $user['status'] == 0 ? resCode(200, "订阅成功", ['status' => 1]) : resCode(200, "订阅取消成功", ['status' => 0]);
    }

    //视频收藏与取消
    public function collect()
    {
        $open_id = input('post.openid');
        $video_id = input('post.video_id');
        if (!$video_id || !is_numeric($video_id)) return resCode(400, "视频不存在", null);

        $user = db('users')->field(['id', 'openid', 'status'])->where('openid', $open_id)->find();
        if (!$user) return resCode(400, "用户不存在", null);

        $video = db('video')->field(['id'])->where('status', 1)->where('id', $video_id)->find();
        if (!$video) return resCode(400, "视频不存在", null);

        $repeat_collect = db('collect')->field('id')->where('user_id', $user['id'])->where('video_id', $video_id)->find();
        $collect_status = 0;
        if (empty($repeat_collect)) {
            $data = [
                'user_id' => $user['id'],
                'video_id' => $video_id,
                'add_time' => time(),
            ];
            db('collect')->insert($data);
            $collect_status = 1;
        } else {
            db('collect')->where('user_id', $user['id'])->where('video_id', $video_id)->delete();
        }
        return $collect_status == 1 ? resCode(200, "收藏成功", ['collect_status' => $collect_status]) : resCode(200, "取消收藏成功", ['collect_status' => $collect_status]);
    }

    //播放界面信息
    public function player()
    {
        $open_id = input('post.openid');
        $video_id = input('post.video_id');
        $page = input('post.page', 1);
        if (!$video_id || !is_numeric($video_id)) return resCode(400, "视频ID有误", null);

        $user = db('users')->field(['id', 'openid', 'status'])->where('openid', $open_id)->find();
        if (!$user) return resCode(400, "用户不存在", null);

        $video = db('video')->field(['id', 'type_id'])->where('id', $video_id)->where('status', 1)->find();
        if (!$video) return resCode(400, "视频不存在", null);

        //更新播放量
        $res = Cache::init();
        $redis = $res->handler();
        //获取该id的计数
        $count = $redis->zscore('daily_laugh_player_count', $video['id']);
        //判断计数是否存在
        if ($count) {
            //存在 直接累加
            $redis->zincrby('daily_laugh_player_count', 1, $video['id']);
        } else {
            //不存在则创建，并设置值为1
            $redis->zadd('daily_laugh_player_count', 1, $video['id']);
        }

        //用户是否收藏
        $collect = db('collect')->field('id')->where('user_id', $user['id'])->where('video_id', $video['id'])->find();
        $collect_status = $collect ? 1 : 0;

        //获取视频列表
        $video_list = db('video')->field(['id', 'name', 'logo', 'share_logo', 'vid', 'content', 'player_count', 'duration'])
            ->where('status', 1)
            ->where('type_id', $video['type_id'])
            ->where('id', '<>', $video['id'])
            ->order('id', 'desc')
            ->page("$page, 10")
            ->select();
        foreach ($video_list as $key => $item) {
            $video_list[$key]['logo'] = config('IMG_URL') . $item['logo'];
            $video_list[$key]['share_logo'] = config('IMG_URL') . $item['share_logo'];
            $player_count = $redis->zscore('daily_laugh_player_count', $item['id']);
            if ($player_count) $video_list[$key]['player_count'] = $player_count;
        }

        return resCode(200, "获取成功", ['list' => $video_list, 'collect_status' => $collect_status, 'status' => $user['status']]);
    }

    //收藏列表
    public function collectList()
    {
        $open_id = input('post.openid');
        $page = input('post.page', 1);
        $user = db('users')->field(['id', 'openid', 'status'])->where('openid', $open_id)->find();
        if (!$user) return resCode(400, "用户不存在", null);

        $list = db('collect')->field('c.id,collect_id,v.id,video_id,logo,share_logo,name,vid,content,duration')
            ->alias('c')
            ->join('daily_laugh_video v', 'c.video_id = v.id')
            ->where('c.user_id', $user['id'])
            ->where('v.status', 1)
            ->order('c.id', 'desc')
            ->page("$page, 10")
            ->select();

        foreach ($list as $key => $item) {
            $list[$key]['logo'] = config('IMG_URL') . $item['logo'];
            $list[$key]['share_logo'] = config('IMG_URL') . $item['share_logo'];
        }

        return resCode(200, "获取成功", $list);
    }

    //分类视频
    public function videoType()
    {
        $list = [];
        $types = db('type')->order('sort')->select();

        foreach ($types as $key => $type) {

            $video_list = db('video')->field(['id', 'name', 'logo', 'vid', 'content', 'duration'])
                ->where('type_id', $type['id'])
                ->where('status', 1)
                ->order('id', 'desc')
                ->limit(2)
                ->select();
            if (empty($video_list)) continue;

            $list[$key]['type_id'] = $type['id'];
            $list[$key]['type_name'] = $type['name'];

            foreach ($video_list as $k => $item) {
                $video_list[$k]['logo'] = config('IMG_URL') . $item['logo'];
            }
            $list[$key]['list'] = $video_list;
        }

        return resCode(200, "获取成功", $list);
    }

    //单分类列表
    public function typeList()
    {
        $type_id = input('post.type_id');
        $page = input('post.page', 1);
        if (!$type_id || !is_numeric($type_id)) return resCode(400, "分类ID有误", null);

        $types = db('type')->field(['name', 'logo'])->where('id', $type_id)->find();
        if (!$types) return resCode(400, "分类不存在！", null);

        //当前分类视频总数
        $video_type_count = db('video')->where('status', 1)->where('type_id', $type_id)->count();

        $res = Cache::init();
        $redis = $res->handler();

        $list = db('video')->field(['id', 'name', 'logo', 'share_logo', 'vid', 'content', 'player_count', 'duration', 'type_id', 'add_time'])
            ->where('status', 1)
            ->where('type_id', $type_id)
            ->order('id', 'desc')
            ->page("$page, 10")
            ->select();

        foreach ($list as $key => $item) {
            $list[$key]['logo'] = config('IMG_URL') . $item['logo'];
            $list[$key]['share_logo'] = config('IMG_URL') . $item['share_logo'];
            $list[$key]['add_time'] = date('m月d日');
            $player_count = $redis->zscore('daily_laugh_player_count', $item['id']);
            if ($player_count) $list[$key]['player_count'] = $player_count;
        }

        return resCode(200, "获取成功", [
                'list' => $list,
                'name' => $types['name'],
                'video_count' => $video_type_count,
                'logo' => config('IMG_URL') . $types['logo'],
            ]
        );
    }

    public function addXcxFormId()
    {
        $open_id = input('openid');
        $form_id = input('form_id');

        if (empty($form_id) || empty($open_id) || $form_id == 'the formId is a mock one' || $form_id == 'undefined') {
            $arr = resCode(200, "SUCCESS");
            return $arr;
        }
        $arr = ['form_id' => $form_id,
            'open_id' => $open_id,
            'add_time' => time()
        ];
        $data = cache("daily_laugh_formid");
        if (empty($data)) {
            $data[] = $arr;
            cache("daily_laugh_formid", $data);
        } else if (count($data) < 5000) {
            array_push($data, $arr);
            cache("daily_laugh_formid", $data);
        } else {
            Db::name('xcx_formid')->insertAll($data);
            cache("daily_laugh_formid", null);
        }
    }

    public function cache_formid()
    {
        $data = cache("daily_laugh_formid");
        if (!empty($data)) {
            Db::name('xcx_formid')->insertAll($data);
            cache("daily_laugh_formid", null);
        }
    }

    //把缓存的播放量更新到mysql
    public function updatePlayerCountToMysql()
    {
        $res = Cache::init();
        $redis = $res->handler();
        $daily_laugh_player_count = $redis->zrange('daily_laugh_player_count', 0, -1, true);
        foreach ($daily_laugh_player_count as $key => $item) {
            Db::name('video')->where('id', $key)->update(['player_count' => $item]);
            $redis->zadd('daily_laugh_player_count', 0, $key);
        }
    }

    //设置播放量
    public function setPlayerCount()
    {
        $key = input('post.key');
        if ($key == $this->key) {
            $res = Cache::init();
            $redis = $res->handler();
            $video_id = input('post.video_id');
            $player_count = input('post.player_count');

            $count = $redis->zscore('daily_laugh_player_count', $video_id);
            //判断计数是否存在  存在则删除
            if ($count) $redis->zrem('daily_laugh_player_count', $video_id);
            $redis->zadd('daily_laugh_player_count', $player_count, $video_id);
            return ['code' => 0, 'msg' => '更新成功', 'data' => ['video_id' => $video_id, 'player_count' => $player_count]];
        }
        return ['code' => -1, 'msg' => '更新失败'];
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
        $redis->zrem('daily_laugh_player_count', '');
        $player_count = $redis->zrange('daily_laugh_player_count', 0, -1, true);
        return ['code' => 0, 'msg' => '获取成功',
            'data' => [
                'daily_laugh_player_count' => $player_count,
                'player_count 19' => $count = $redis->zscore('daily_laugh_player_count', 19),
            ]
        ];
    }

}