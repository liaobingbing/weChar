<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/16
 * Time: 14:31
 */

namespace app\ad\controller;

use common\controller\ApiBase;
use think\Cache;
use think\Db;

class Api extends ApiBase
{
    private $key = 'kuaiyu666666';

    protected $box_mysql_config = [
        'type' => 'mysql',
        'hostname' => '47.106.198.229',
        'database' => 'kuaiyu_box',
        'username' => 'root',
        'password' => '3zprYtPzHrd3AsYa',
    ];

    public function __construct()
    {
        //
    }

    public function index()
    {
        $list = [];

        $app_id = input('app_id');
        if (empty($app_id)) return [];

        $app_list = Db::connect($this->box_mysql_config)->table('app_list')->where('app_id', $app_id)->find();
        if (empty($app_list)) return [];
        $app_id = $app_list['id'];

        $ad_list = Db::table('admin_ad')->field(['id', 'picture_path', 'name', 'app_id', 'target_app_id', 'request_count'])
            ->where('status=1')
            ->where("app_id=$app_id")
            ->select();

        $res = Cache::init();
        $redis = $res->handler();
        $admin_host = config("IMG_URL");
        foreach ($ad_list as $key => $item) {

            $app_id = $app_list = Db::connect($this->box_mysql_config)->table('app_list')->where('id', $item['app_id'])->find();
            $target_app_id = $app_list = Db::connect($this->box_mysql_config)->table('app_list')->where('id', $item['target_app_id'])->find();
            $app_id = empty($app_id) ? '' : $app_id['app_id'];
            $target_app_id = empty($target_app_id) ? '' : $target_app_id['app_id'];

            $list[$key]['program_id'] = $item['id'];
            $list[$key]['program_name'] = $item['name'];
            $list[$key]['program_app_id'] = $app_id;
            $list[$key]['program_target_app_id'] = $target_app_id;
            $list[$key]['program_box_app_id'] = 'wx038a63af17ad0c8e';
            $list[$key]['program_src'] = $admin_host . $item['picture_path'];

            //获取该id的计数
            $count = $redis->zscore('requestCount', $item['id']);
            //判断计数是否存在
            if ($count) {
                //存在 直接累加
                $redis->zincrby('requestCount', 1, $item['id']);
            } else {
                //不存在则创建，并设置值为1
                $redis->zadd('requestCount', 1, $item['id']);
            }
        }
        return ['programlist' => $list];
    }

    public function clickCount()
    {
        $id = input('program_id');
        if (empty($id) || !is_numeric($id)) return resCode(-1, "program_id错误");
        $res = Cache::init();
        $redis = $res->handler();
        //获取该id的计数
        $count = $redis->zscore('clickCount', $id);
        //判断计数是否存在
        if ($count) {
            //存在 直接累加
            $redis->zincrby('clickCount', 1, $id);
        } else {
            //不存在则创建，并设置值为1
            $redis->zadd('clickCount', 1, $id);
        }
        $arr = resCode(200, "更新成功");
        return $arr;
    }

    public function exposureCount()
    {
        $id = input('program_id');
        //验证ID的数据格式是否正确
        if (empty($id)) return resCode(400, "输入ID有误！");
        if (is_array($id)) {
            foreach ($id as $item) {
                if (!is_numeric($item)) return ['code' => 400, 'ID必须是整数'];
            }
        } else {
            if (!is_numeric($id)) return ['code' => 400, 'ID必须是整数'];
            $id = [$id];
        }
        $res = Cache::init();
        $redis = $res->handler();
        foreach ($id as $value) {
            //获取该id的计数
            $count = $redis->zscore('exposureCount', $value);
            //判断计数是否存在
            if ($count) {
                //存在 直接累加
                $redis->zincrby('exposureCount', 1, $value);
            } else {
                //不存在则创建，并设置值为1
                $redis->zadd('exposureCount', 1, $value);
            }
        }
        $arr = resCode(200, "更新成功");
        return $arr;
    }

    public function redisToMysqlAboutAdCount()
    {
        $key = input('get.key');
        if ($this->key == $key) {
            $res = Cache::init();
            $redis = $res->handler();
            $exposureCount = $redis->zrange('exposureCount', 0, -1, true);
            $clickCount = $redis->zrange('clickCount', 0, -1, true);
            $requestCount = $redis->zrange('requestCount', 0, -1, true);
            //更新曝光次数
            $timer = date('Y-m-d', time() - 3600 * 24);
            foreach ($exposureCount as $key => $item) {
                Db::table('admin_ad')->where('id', $key)->update(['exposure_count' => $item]);
                //记录日志
                $ad_log = Db::table('admin_ad_trend')->where('ad_id', $key)->where('ref_date', $timer)->find();
                if ($ad_log) {
                    Db::table('admin_ad_trend')->where('ad_id', $key)->where('ref_date', $timer)->update(['exposure_count' => $item]);
                } else {
                    Db::table('admin_ad_trend')->insert(['ad_id' => $key, 'exposure_count' => $item, 'request_count' => 0, 'click_count' => 0, 'ref_date' => $timer,]);
                }
                $redis->zadd('exposureCount', 0, $key);
            }
            //更新点击次数
            foreach ($clickCount as $key => $item) {
                Db::table('admin_ad')->where('id', $key)->update(['click_count' => $item]);
                //记录日志
                $ad_log = Db::table('admin_ad_trend')->where('ad_id', $key)->where('ref_date', $timer)->find();
                if ($ad_log) {
                    Db::table('admin_ad_trend')->where('ad_id', $key)->where('ref_date', $timer)->update(['click_count' => $item]);
                } else {
                    Db::table('admin_ad_trend')->insert(['ad_id' => $key, 'exposure_count' => 0, 'request_count' => 0, 'click_count' => $item, 'ref_date' => $timer,]);
                }
                $redis->zadd('clickCount', 0, $key);
            }
            //更新广告请求次数
            foreach ($requestCount as $key => $item) {
                Db::table('admin_ad')->where('id', $key)->update(['request_count' => $item]);
                //记录日志
                $ad_log = Db::table('admin_ad_trend')->where('ad_id', $key)->where('ref_date', $timer)->find();
                if ($ad_log) {
                    Db::table('admin_ad_trend')->where('ad_id', $key)->where('ref_date', $timer)->update(['request_count' => $item]);
                } else {
                    Db::table('admin_ad_trend')->insert(['ad_id' => $key, 'exposure_count' => 0, 'request_count' => $item, 'click_count' => 0, 'ref_date' => $timer,]);
                }
                $redis->zadd('requestCount', 0, $key);
            }
            $arr = resCode(200, "更新成功");
            return $arr;
        }
    }

    public function redisToMysqlAboutBannerCount()
    {
        $key = input('get.key');
        if ($this->key == $key) {
            $res = Cache::init();
            $redis = $res->handler();
            $timer = date('Y-m-d', time() - 3600 * 24);
            //更新轮播图曝光量
            $bannerExposureCount = $redis->zrange('bannerExposureCount', 0, -1, true);
            foreach ($bannerExposureCount as $key => $item) {
                Db::table('admin_banner')->where('id', $key)->update(['exposure_count' => $item]);
            }
            //更新轮播图点击量
            $bannerClickCount = $redis->zrange('bannerClickCount', 0, -1, true);
            foreach ($bannerClickCount as $key => $item) {
                $admin_banner = Db::table('admin_banner')->field(['click_count'])->where('id', $key)->find();
                if (empty($admin_banner)) continue;
                Db::table('admin_banner')->where('id', $key)->update(['click_count' => $admin_banner['click_count'] + $item]);
                //记录日志
                $banner_log = Db::table('admin_banner_trend')->field(['id'])->where('banner_id', $key)->where('ref_date', $timer)->find();
                if ($banner_log) {
                    Db::table('admin_banner_trend')->where('banner_id', $key)->where('ref_date', $timer)->update(['click_count' => $item]);
                } else {
                    Db::table('admin_banner_trend')->insert(['banner_id' => $key, 'click_count' => $item, 'ref_date' => $timer,]);
                }
                $redis->zadd('bannerClickCount', 0, $key);
            }
            //更新盒子中app列表点击量
            $bannerClickCount = $redis->zrange('boxAppClickCount', 0, -1, true);
            foreach ($bannerClickCount as $key => $item) {
                $app_list = Db::connect($this->box_mysql_config)->table('app_list')->field(['click_count'])->where('id', $key)->find();
                if (empty($app_list)) continue;
                Db::connect($this->box_mysql_config)->table('app_list')->where('id', $key)->update(['click_count' => $app_list['click_count'] + $item]);
                //记录日志
                $banner_log = Db::table('admin_app_trend')->field(['id'])->where('app_id', $key)->where('ref_date', $timer)->find();
                if ($banner_log) {
                    Db::table('admin_app_trend')->where('app_id', $key)->where('ref_date', $timer)->update(['click_count' => $item]);
                } else {
                    Db::table('admin_app_trend')->insert(['app_id' => $key, 'click_count' => $item, 'ref_date' => $timer,]);
                }
                $redis->zadd('boxAppClickCount', 0, $key);
            }
            $arr = resCode(200, "更新成功");
            return $arr;
        }
    }

    public function redisToMysqlAboutAppHotCount()
    {
        $key = input('get.key');
        if ($this->key == $key) {
            $res = Cache::init();
            $redis = $res->handler();
            $timer = date('Y-m-d', time() - 3600 * 24);
            //更新热门推荐点击量
            $appHotClickCount = $redis->zrange('appHotClickCount', 0, -1, true);
            foreach ($appHotClickCount as $key => $item) {
                $admin_applets_hot = Db::table('admin_applets_hot')->field(['click_count'])->where('id', $key)->find();
                if (empty($admin_applets_hot)) continue;
                Db::table('admin_applets_hot')->where('id', $key)->update(['click_count' => $admin_applets_hot['click_count'] + $item]);
                //记录日志
                $app_hot_log = Db::table('admin_app_hot_trend')->field(['id'])->where('app_hot_id', $key)->where('ref_date', $timer)->find();
                if ($app_hot_log) {
                    Db::table('admin_app_hot_trend')->where('app_hot_id', $key)->where('ref_date', $timer)->update(['click_count' => $item]);
                } else {
                    Db::table('admin_app_hot_trend')->insert(['app_hot_id' => $key, 'click_count' => $item, 'ref_date' => $timer,]);
                }
                $redis->zadd('appHotClickCount', 0, $key);
            }
        }
    }

    //查看数据  测试用 todo 后面删掉
    public function testCount()
    {
        $res = Cache::init();
        $redis = $res->handler();
        $bannerClickCount = $redis->zrange('bannerClickCount', 0, -1, true);
        $bannerExposureCount = $redis->zrange('bannerExposureCount', 0, -1, true);
        $exposureCount = $redis->zrange('exposureCount', 0, -1, true);
        $clickCount = $redis->zrange('clickCount', 0, -1, true);
        $requestCount = $redis->zrange('requestCount', 0, -1, true);
        $appHotClickCount = $redis->zrange('appHotClickCount', 0, -1, true);
        return ['data' => ['bannerClickCount' => $bannerClickCount, 'bannerExposureCount' => $bannerExposureCount,
            'exposureCount' => $exposureCount, 'clickCount' => $clickCount, 'requestCount' => $requestCount, 'appHotClickCount' => $appHotClickCount
        ], 'code' => 0, 'msg' => '获取成功'];
    }
}