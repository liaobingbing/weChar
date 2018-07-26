<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/16
 * Time: 14:31
 */

namespace app\box\controller;

use app\box\model\AppList;
use app\box\model\Banner;
use common\controller\ApiBase;
use think\Cache;
use think\Db;

class Api extends ApiBase
{
    public function __construct()
    {
        //
    }

    public function index()
    {
        $list = cache('box_list');
        if (empty($list)) {
            $appList = Db::table('app_list')->field(['id', 'logo', 'name', 'app_id', 'desc', 'online_users'])
                ->where('status=1')
                ->order('order')
                ->select();

            $admin_host = config("IMG_URL");
            foreach ($appList as $key => $item) {
                $list[$key]['program_id'] = $item['id'];
                $list[$key]['program_src'] = $admin_host . $item['logo'];
                $list[$key]['program_name'] = $item['name'];
                $list[$key]['program_describe'] = $item['desc'];
                $list[$key]['program_appid'] = $item['app_id'];
                $list[$key]['program_online'] = $item['online_users'];
            }
            cache('box_list', $list, 3600);
        }
        return ['programlist' => $list, 'code' => 200, 'msg' => 'success'];
    }

    //游戏盒子app点击统计
    public function boxAppClickCount()
    {
        $id = input('program_id');
        if (empty($id) || !is_numeric($id)) return resCode(-1, "program_id错误");
        $res = Cache::init();
        $redis = $res->handler();
        //获取该id的计数
        $count = $redis->zscore('boxAppClickCount', $id);
        //判断计数是否存在
        if ($count) {
            //存在 直接累加
            $redis->zincrby('boxAppClickCount', 1, $id);
        } else {
            //不存在则创建，并设置值为1
            $redis->zadd('boxAppClickCount', 1, $id);
        }
        $arr = resCode(200, "更新成功");
        return $arr;
    }

    public function hot()
    {
        $mysql_config = [
            'type'            => 'mysql',
            'hostname'        => '47.106.198.229',
            'database'        => 'kuaiyu_admin',
            'username'        => 'root',
            'password'        => '3zprYtPzHrd3AsYa',
            // 数据库编码默认采用utf8
            'charset'     => 'utf8',
            // 数据库表前缀
            'prefix'      => '',
        ];
//        $list = cache('applets_hot');
        $list = [];
        if (empty($list)) {
            $applets_hot = Db::connect($mysql_config)->table('admin_applets_hot')
                ->field(['id', 'app_id'])
                ->where('status=1')
                ->order('sort')
                ->select();

            if (!empty($applets_hot)) {
                $admin_host = config("IMG_URL");
                foreach ($applets_hot as $key => $item) {
                    $appList = Db::table('app_list')->find($item['app_id']);
                    $list[$key]['id'] = $item['id'];
                    $list[$key]['name'] = $appList['name'];
                    $list[$key]['appid'] = $appList['app_id'];
                    $list[$key]['imgsrc'] = $admin_host . $appList['logo'];
                }
//                cache('applets_hot', $list, 3600);
            }
        }
        return ['list' => $list, 'code' => 200, 'msg' => 'success'];
    }

    //热门推荐点击统计
    public function appHotClickCount()
    {
        $id = input('id');
        if (empty($id) || !is_numeric($id)) return resCode(-1, "ID错误");
        $res = Cache::init();
        $redis = $res->handler();
        //获取该id的计数
        $count = $redis->zscore('appHotClickCount', $id);
        //判断计数是否存在
        if ($count) {
            //存在 直接累加
            $redis->zincrby('appHotClickCount', 1, $id);
        } else {
            //不存在则创建，并设置值为1
            $redis->zadd('appHotClickCount', 1, $id);
        }
        $arr = resCode(200, "更新成功");
        return $arr;
    }

    public function banner()
    {
        $list = cache('banner_list');
        if (empty($list)) {
            $banner = Banner::table('admin_banner')
                ->field(['id', 'picture_path', 'app_id'])
                ->where('status=1')
                ->order('order')
                ->select();

            if (!empty($banner)) {
                $admin_host = config("IMG_URL");
                foreach ($banner as $key => $item) {
                    $appList = Db::table('app_list')->find($item['app_id']);
                    $list[$key]['id'] = $item['id'];
                    $list[$key]['appid'] = $appList['app_id'];
                    $list[$key]['imgsrc'] = $admin_host . $item['picture_path'];
                }
                cache('banner_list', $list, 3600);
            }
        }
        return ['bannerlist' => $list, 'code' => 200, 'msg' => 'success'];
    }

    //轮播图曝光率
    public function bannerExposureCount()
    {
        $id = input('id');
        //验证ID的数据格式是否正确
        if (empty($id) || !is_numeric($id)) return resCode(400, "输入ID有误！");
        $res = Cache::init();
        $redis = $res->handler();
        $count = $redis->zscore('bannerExposureCount', $id);
        //判断计数是否存在
        if ($count) {
            //存在 直接累加
            $redis->zincrby('bannerExposureCount', 1, $id);
        } else {
            //不存在则创建，并设置值为1
            $redis->zadd('bannerExposureCount', 1, $id);
        }
        $arr = resCode(200, "更新成功");
        return $arr;
    }

    //轮播图点击率
    public function bannerClickCount()
    {
        $id = input('id');
        if (empty($id) || !is_numeric($id)) return resCode(-1, "ID错误");
        $res = Cache::init();
        $redis = $res->handler();
        //获取该id的计数
        $count = $redis->zscore('bannerClickCount', $id);
        //判断计数是否存在
        if ($count) {
            //存在 直接累加
            $redis->zincrby('bannerClickCount', 1, $id);
        } else {
            //不存在则创建，并设置值为1
            $redis->zadd('bannerClickCount', 1, $id);
        }
        $arr = resCode(200, "更新成功");
        return $arr;
    }

    //获取opend_id
    public function get_openid()
    {
        $code = input('post.code');
        $login_data = $this->test_weixin($code);
        if ($login_data['code'] != 400) {
            $openid = $login_data['openid'];
            $arr = array("code" => 200, "msg" => "success", "data" => array("openId" => $openid));
            return $arr;
        } else {
            return $login_data;

        }
    }

}