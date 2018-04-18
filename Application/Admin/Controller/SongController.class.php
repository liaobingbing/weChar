<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/13
 * Time: 13:02
 */

namespace Admin\Controller;



use Admin\Model\AppDailyRecordModel;
use Admin\Model\AppStatisticsModel;
use Think\Upload;

class SongController extends AdminController
{
    /*
     * 歌曲列表
     */
    public function song_list(){

        $p = I('get.p');
        $key = I('get.key');

        $where = 1;

        if($key){
            $this->assign('key',$key);
            $where = array('name'=>array('like',"%$key%"));
        }

        $Songs = M('GsSongs');  // 实例化User对象
        $list = $Songs->where($where)->where('status=1')->order('layer asc')->page($p.',25')->select();
        $this->assign('list',$list);// 赋值数据集
        $count = $Songs->where($where)->where('status=1')->count();// 查询满足要求的总记录数
        $this->assign('count',$count);
        $Page = $this->page_change($count,25); //获取自定义样式
        $Page->rollPage = 5;    // 设置显示页数


        $show = $Page->show();// 分页显示输出
        $this->assign('page',$show);// 赋值分页输出


        $this->display(); // 输出模板

    }

    // 歌曲添加页面
    public function song_add(){
        $this->display();
    }

    // 歌曲添加操作 未完成
    public function song_do_add(){

        /*$config = array(
            'maxSize'    =>    3145728,
            'rootPath'   =>    './Uploads/',
            'savePath'   =>    '',
            'saveName'   =>    array('uniqid',''),
            'exts'       =>    array('jpg', 'gif', 'png', 'jpeg'),
            'autoSub'    =>    true,
            'subName'    =>    array('date','Ymd'),
        );

        $qiniuConfig = array(
            'secretKey'=>'FWcEZVOnlvdm4k_Vmmj2L1veHVXECt6dhsekGNc0',//尤其是这个网上大多都写成‘secrectKey’多了个c，坑啊！
            'accessKey'=>'a4L1s02Tf4adQYm9-VZtlqrX0gR8ugAV8F_d2Fd9',//AK和SK 的顺序一定要写对
            'domain'=>'idv1phf.qiniudns.com',
            'bucket'=>'kuaiyuxcx',
        );*/
        $sets = C('UPLOAD_SITEIMG_QINIU');
        $upload = new Upload($sets);
        $result = $upload->upload($_FILES);
        $this->ajaxReturn($result);
    }

    // 数据统计页面
    public function statistics(){
        echo date('Y-m-01', strtotime('-1 month'));
        echo "<br/>";
        echo date('Y-m-t', strtotime('-1 month'));
        echo "<br/>";

    }

    // 更新所有统计
    public function save_statistics(){
        $appid = C('GUESS_SONG_WECHAT_APPID');
        $secret = C('GUESS_SONG_WECHAT_APPSECRET');
        $token = $this->get_wechat_token($appid,$secret,'song');

        $update_statistics_all = $this->update_statistics_all(1,$token);
    }

}