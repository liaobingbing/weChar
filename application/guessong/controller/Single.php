<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/29
 * Time: 9:21
 */

namespace app\guessong\controller;

use think\Db;
use app\guessong\model\GsUsers;
use think\Controller;

class Single extends Controller
{
// 积分增加值
    private $incr_fraction = 5;
    // 分享加积分
    private $share_fraction = 10;
    // 积分减少值
    private $decr_fraction = 60;
    // 每个级别的关卡数
    private $level_layer = 200;
    // 级别每页的关卡数
    private $level_page = 60;


    /**
     * 猜歌闯关 验证答案
     * 接收post传值: layer , key , type , help_seeker_id
     *
     */
    public function check_answer(){
        $openId=input('openId');
        $layer = input('layer');
        $song_key   = input('key');
        $type = input('type',1);
        $help_seeker_id = input('recommend_id');
        $user=new GsUsers();
        $user_id=$user->get_user_id($openId);
       // $user_id = session('user_info.id');
        $incr_fraction = $this->incr_fraction;

        // 初始返回信息
        $result = array('msg'=>'答题失败','code'=>400);

        if(empty($layer) || empty($song_key)){

            $result['msg'] = '参数错误';

        }else{
            $key= 'songs_'.$layer;
            $song = cache($key);
            if($song_key != $song['name']){
                $result['msg'] = '答案错误';
            }else{
                $result['msg']   = '答题成功';
                $result['code']  = 200;
                $result['layer'] = $layer;
                $result['next_layer'] = $layer + 1;
                $result['type'] = $type;
                $result['song_name'] = $song['name'];

                $user_info = $user->get_user_info($user_id);

                if($type == 1){

                    $result['up_status'] =0;

                    if($user_info['layer'] + 1 == $layer){
                        $user_info['layer']++;
                        $user_info['fraction'] += $incr_fraction;

                        if( Db::name('users')->update($user_info)) {
                            $result['fraction'] = $user_info['fraction'];
                            $result['incr_fraction'] = $incr_fraction;

                            if($user_info['layer'] % $this->level_layer == 0 && $result['next_layer'] !=0){
                                $result['up_status'] =1;
                            }

                        }
                    }else{
                        $result['fraction'] = $user_info['fraction'];
                        $result['incr_fraction'] = 0;
                    }


                }else if($type ==2){
                    if($help_seeker_id){
                        // 求助操作
                        $data['user_id'] = $help_seeker_id;
                        $data['help_id'] = $user_id;
                        $data['avatar_url'] = $user_info['avatarUrl'];
                        $data['layer'] = $layer;
                        $data['answer'] = $song['name'];

                        if($help_seeker_id != $user_id){
                            if( !Db::name("help")->where($data)->find() ){
                                if(Db::name("help")->insert($data)){
                                    $result['msg'] = '帮助成功';
                                }
                            }else{
                                $result['msg'] = '答题成功';
                            }
                        }else{
                            $result['msg'] = '答题成功';
                        }

                    }else{
                        $result['code'] = 400;
                        $result['msg'] = '求助者 recommend_id 不能为空';
                    }

                }else{
                    $result['msg'] = 'type 参数错误';
                }

            }


        }

       return $result;

    }


    /**
     * 获取求助弹幕
     * get layer : 歌曲关卡, number : 获取条数 默认3条
     */
    public function get_barrage(){
        $openId=input("openId");
        $layer = input('layer');
        $number = input('number',3);
        $number = empty($number) ? 3 :$number;
        $user=new GsUsers();
        $user_id=$user->get_user_id($openId);
       // $user_id = session('user_info.id');

        if(empty($layer) || $layer == 0){
            $layer = Db::name('users')->where(array('id'=>$user_id))->value('layer') + 1;
        }

        $result['code'] = 400;
        $result['msg']  = '获取失败';

        if( $layer ){
            $data =  Db::name('help')->where(array('user_id'=>$user_id,'layer'=>$layer))->order('id desc')->limit($number)->fetchSql(false)->select();
           // print_r($data);die;
            $helpList=[];
            foreach($data as $k => $v){
                $helpList[$k]['img'] = $v['avatar_url'];
                $helpList[$k]['answer'] = $v['answer'];
                $helpList[$k]['top'] = $k*50;
            }

            $result['helpList'] =$helpList;
            $result['code'] = 200;
            $result['msg']  = '获取成功';
        }

        return $result;

    }

    /**
     * 获取歌曲题目
     * 接收 get : layer
     */
    public function get_song(){
        $layer = input('layer',0);
        $openId=input("openId");
        $user=new GsUsers();
        $user_id=$user->get_user_id($openId);
        //$user_id = session('user_info.id');


        // 初始返回信息
        $result = array(
            'msg'  => '获取失败',
            'code' => 400
        );

        if(empty($layer) || $layer == 0){
            $layer = Db::name('users')->where(array('id'=>$user_id))->value('layer') + 1;
        }
        $key= 'songs_'.$layer;
        $song_info = Db::name("songs")->where(array('layer'=>$layer))->cache($key,60*60)->find();
        if(empty($song_info)){
            $result['msg']  = '查无此歌';
        }else{
            // 获取干扰项
            //$option = $this->option_splic($song_info['name'],$song_info['option'],21);
            $option = $this->option_splic($song_info['option']);
            $user_info = $user->get_user_info($user_id);

            $user_info['layer'] = $layer;

            $result['user_info'] =  $user_info;
            $result['opttion'] =  $option;
            $result['song_name'] =  $this->ch_to_arr($song_info['name']);
            $result['prelude'] =  $song_info['prelude'];
            $result['climax'] =  $song_info['climax'];


            $result['msg']  = '获取成功';
            $result['code'] = 200;
        }



        return $result;
    }


    /**
     * 提示 减积分
     */
    public function get_tips(){
       // $user_id = session('user_info.id');
        $openId=input("openId");
        $user=new GsUsers();
        $user_id=$user->get_user_id($openId);


        $user_info = $user->get_user_info($user_id);

        $decr_fraction = $this->decr_fraction;

        if($user_info['fraction'] >= $decr_fraction){

            $user_info['fraction'] -= $decr_fraction;

            if( Db::name("users")->where('id',$user_id)->update($user_info)){
                $result['code'] = 200;
                $result['msg']  = '已扣除积分';
                $result['decr_fraction'] = $decr_fraction;
                $result['fraction'] = $user_info['fraction'];
            }
        }else{
            $result = array(
                'code' => 400,
                'msg'  => '音符不足'
            );
        }

       return $result;
    }

    /**
     * 分享 加金币
     */
    public function get_share(){
        //$user_id = session('user_info.id');
        $openId=input("openId");
        $user=new GsUsers();
        $user_id=$user->get_user_id($openId);
        $user_data = Db::name("users")->where(array('id'=>$user_id))->field('fraction,share_mark,share_time')->find();
        $fraction = $user_data['fraction'] + $this->share_fraction;

        $today_0 = strtotime(date('Y-m-d',time()));

        if($user_data['share_time'] > $today_0){
            if($user_data['share_mark'] < 10){
                $data = array(
                    'fraction'  => $user_data['fraction'] + $this->share_fraction,
                    'share_mark'=> $user_data['share_mark'] + 1
                );
                if(Db::name("users")->where('id',$user_id)->update($data)){
                    $fraction = $user_data['fraction'] + $this->share_fraction;
                    $result['code'] = 200;
                    $result['msg']  = '分享成功,积分已添加';
                    $result['fraction'] = $fraction;
                    $result['add_fraction'] = $this->share_fraction;
                }

            }else{
                $fraction = $user_data['fraction'];
                $result['code'] = 400;
                $result['msg']  = '今天分享增加积分次数已达上限';
                $result['fraction'] = $fraction;
            }
        }else{
            $data = array(
                'fraction'  => $user_data['fraction'] + $this->share_fraction,
                'share_mark'=> 0,
                'share_time' => time()
            );
            if(Db::name("users")->where('id',$user_id)->update($data)){
                $result['code'] = 200;
                $result['msg']  = '分享成功,积分已添加';
                $result['fraction'] = $fraction;
                $result['add_fraction'] = $this->share_fraction;
            }
        }

        return $result;
    }

    /**
     * 分享群
     */
    public function share_group(){
        //$user_id = session('user_info.id');
        $openId=input("openId");
        $user=new GsUsers();
        $user_id=$user->get_user_id($openId);
        $encryptedData = input("post.encryptedData");
        $iv = input("post.iv");

        if($encryptedData && $iv){
            $session_key=input('session_key');

            vendor("wxaes.wxBizDataCrypt");
            $wxBizDataCrypt = new \WXBizDataCrypt(config("WECHAT_APPID"), $session_key);
            $data_arr = array();
            $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data_arr);

            if($errCode==0){
                $json_data = json_decode($data_arr, true);

                $info = $user->user_share_group($user_id,$json_data['openGId']);
                if($info){
                    $result['code']=200;
                    $result['add_status']=$info['add_status'];
                    $result['add_fraction']=$info['add_fraction'];
                    $result['fraction']=$info['fraction'];
                }else{
                    $result['code']=400;
                    $result['msg']='分享群失败';
                }

            }else{
                $result['code']=402;
                $result['msg']='session_key过期，需重新登录获取';
            }


        }else{
            $result['code']=400;
            $result['msg']='参数不全';
        }

        return $result;
    }


    /**
     * 获取级别页面
     */
    public function get_level(){

        $key = 'songs_total';

        $result = array(
            'code' => 400,
            'msg'  => '获取失败',
        );
        $openId=input("openId");
        $user=new GsUsers();
        $user_id=$user->get_user_id($openId);
        //$user_id = session('user_info.id');
        $layer = Db::name("users")->where(array('id'=>$user_id))->value('layer');
        // 用户级别数
        $level = ceil(($layer+1)/$this->level_layer);

        $songs_count = cache($key);
        if(!$songs_count){
            $songs_count = Db::name('songs')->count('id');
            cache($key,$songs_count,7200);
        }

        //总级别数
        $total_level = ceil($songs_count/$this->level_layer);

        for($i=0;$i<$total_level;$i++){
            if($i < $level){
                $arr[$i]['status'] = 1;

            }else{
                $arr[$i]['status'] = 0;
            }
            $arr[$i]['start'] = $i * $this->level_layer + 1;
            if($i == $total_level -1 ){
                $arr[$i]['end']   = $songs_count;
            }else{
                $arr[$i]['end']   = ( $i + 1) * $this->level_layer;
            }
            $arr[$i]['level_id'] = $i +1;
        }

        if($arr){
            $result = array(
                'code' => 200,
                'msg'  => '获取成功',
                'level' => $arr
            );
        }


       return $result;
    }

    /**
     * 获取关卡页面
     * 接收 get : level
     */
    public function get_layer(){
        $level = input('level');
        $page = input('page');
       // $user_id = session('user_info.id');
        $openId=input("openId");
        $user=new GsUsers();
        $user_id=$user->get_user_id($openId);
        if(!$level || $level == 0) $level = 1;
        if(!$page || $level == 0) $page = 1;

        $result = array(
            'code' => 400,
            'msg'  => '获取失败'
        );

        if(empty($level)){
            $result['msg'] = 'level参数不能为空';
        }else{
            $layer = Db::name("users")->where(array('id'=>$user_id))->value('layer');
            $key = 'gs_song_lever_'.$level;
            $songs_layer = Db::name('songs')->cache($key,24*24*60)->field('id,layer')->page("$level,$this->level_layer")->select();

            $j = 0;
            for($i = $this->level_page*($page -1); $i < $this->level_page*$page ; $i++ ){

                if($songs_layer[$i]['layer'] < $layer + 1){
                    if($songs_layer[$i]['layer']){
                        $arr[$j]['layer'] = $songs_layer[$i]['layer'];
                        $arr[$j]['status'] = 1;
                    }
                }else if($songs_layer[$i]['layer'] == $layer + 1){
                    $arr[$j]['layer'] = $songs_layer[$i]['layer'];
                    $arr[$j]['status'] = 2;
                }else{
                    $arr[$j]['layer'] = $songs_layer[$i]['layer'];
                    $arr[$j]['status'] = 0;
                }
                $j++;
            }

            $result['data'] = $arr;
            $result['msg']  = '获取成功';
            $result['code'] = 200;
        }
        return  $result;

    }

    /**
     *  世界排行榜
     *  get page
     */
    public function get_world_rankings(){
        //$user_id = session('user_info.id');
        $openId=input("openId");
        $user=new GsUsers();
        $user_id=$user->get_user_id($openId);
        $page = input('page');

        if(empty($page)){
            $result = array(
                'code' => 400,
                'msg'  => 'page参数错误'
            );
        }else{
            $all_ranking = $user->get_world_ranking($user_id);
            if($all_ranking){
                $page_size=10;
                $count=count($all_ranking['data']);
                $start=($page-1)*$page_size;
                $total = ceil($count/$page_size);
                $result['code'] =200;
                $result['msg']  ='成功';
                $result['my_ranking']   =   $all_ranking['my_ranking'];
                $result['my_layer']     =   $all_ranking['my_layer'];
                $result['my_name']      =   $all_ranking['my_name'];
                $result['my_fraction']  =   $all_ranking['my_fraction'];
                $result['page']         =   $page;
                $result['page_size']    =   $page_size;
                $result['count']        =   $count;
                $result['total']        =   $total;
                $result['data']         =   array_slice($all_ranking['data'],$start,$page_size);
            }else{
                $result['code']=400;
                $result['msg']='出错了，请联系管理员';
            }
        }

       return $result;
    }

    /**
     * 好友排行
     */
    public function get_friend_rankings(){
        //$user_id = session('user_info.id');
        $openId=input("openId");
        $user=new GsUsers();
        $user_id=$user->get_user_id($openId);
        $page = input('page');

        if(empty($page)){
            $result = array(
                'code' => 400,
                'msg'  => 'page参数错误'
            );
        }else{
            $friends =$user->get_friend_rankings($user_id);
            if($friends){
                $page_size=10;
                $count=count($friends['data']);
                $start=($page-1)*$page_size;
                $total = ceil($count/$page_size);
                $result['code']=200;
                $result['msg']='成功';
                $result['my_ranking']   =$friends['my_ranking'];
                $result['my_layer']     =$friends['my_layer'];
                $result['my_name']      =$friends['my_name'];
                $result['my_fraction']  =$friends['my_fraction'];
                $result['page']         =$page;
                $result['page_size']    =$page_size;
                $result['count']        =$count;
                $result['total']        =$total;
                $result['data']         =array_slice($friends['data'],$start,$page_size);
            }else{
                $result['code']=400;
                $result['msg']='出错了，请联系管理员';
            }
        }

       return $result;

    }

    /**
     * 将干扰项打乱顺序
     * @param $name
     * @param $option
     * @param int $len
     * @return array
     */
    public function option_splic($option){
        $option =$this->ch_to_arr($option);

        shuffle($option);
        foreach($option as $k => $v){
            $arr2[$k]['text'] = $v;
            $arr2[$k]['status'] = false;
        }
        return $arr2;
    }
    //中文字符串转为数组
    function ch_to_arr($str)
    {
        $length = mb_strlen($str, 'utf-8');
        $array = array();
        for ($i=0; $i<$length; $i++)
            $array[] = mb_substr($str, $i, 1, 'utf-8');
        return $array;
    }


}