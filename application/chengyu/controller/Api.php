<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/29
 * Time: 11:29
 */

namespace app\chengyu\controller;
use app\chengyu\model\Answer;
use think\Db;
use app\chengyu\model\Users;
use think\Controller;

class Api extends Controller
{
    private $key='kuaiyu666666';

    //检查签到
    public function check_sign(){
        $openId=input('openId');
        $userdao=new Users();
        $user_id=$userdao->get_user_id($openId);
        //$user_id=session('user_id');
        if($user_id){
            $user_game=$userdao->findGame($user_id);
            if($user_game){
                if($user_game['sign']==1){
                    $data['code']=200;
                    $data['sign_gold']=config('SIGN_GOLD');
                }else{
                    $data['code']=400;
                    $data['msg']='今天已签到';
                }
            }else{
                $data['code']=401;

            }
        }else{
            $data['code']=401;
        }
        return $data;
    }

    //执行签到
    public function sign(){
        $openId=input('openId');
        $userdao=new Users();
        $user_id=$userdao->get_user_id($openId);
        if($user_id){
            $user_game=$userdao->findGame($user_id);
            if($user_game){
                if($user_game['sign']==1){
                    $user_game['gold_num']+=config('SIGN_GOLD');
                    $user_game['sign']=0;
                    $info=Db::name('user_game')->update($user_game);
                    if($info){
                       /* $data2['uid']=$user_game['id'];
                        $data2['gold_change']=config('SIGN_GOLD');
                        $data2['change_type']='签到成功';
                        $data2['add_time']=time();
                        Db::name('user_income')->insert($data2);*/
                        $data['code']=200;
                        $data['msg']='签到成功';
                    }else{
                        $data['code']=400;
                        $data['msg']='签到失败';
                    }
                }else{
                    $data['code']=400;
                    $data['msg']='无需签到';
                }
            }else{
                $data['code']=401;
            }
        }else{
            $data['code']=401;
        }
        return $data;
    }

    //获取成语题目
    public function get_question(){
        $openId=input('openId');
        $userdao=new Users();
        $user_id=$userdao->get_user_id($openId);
       // $user_id=session('user_id');
        if($user_id){
            $user_game=$userdao->findGame($user_id);
            if($user_game){
                $level=$this->check_int(input('post.level',0));
                $layer=$this->check_int(input('post.layer',0));
                if($level==0||$layer==0){

                    $level=$user_game['level'];
                    $layer=$user_game['layer']+1;
                }
                $answer=new Answer();
                $idiom_arr=$answer->get_all_answer();
                if($idiom_arr[$level]){
                    $arr= array_slice($idiom_arr,-1,1,true);
                    $max_level=key($arr);
                    $arr2=array_slice($idiom_arr[$level],-1,1,true);
                    $max_layer=key($arr2);
                    if($layer>$max_layer){
                        $level+=1;
                        $layer=1;
                    }
                    if($level==$max_level+1&&$layer==1){
                        $level=$max_level;
                        $layer=$max_layer;
                    }
                    if($level>$max_level){
                        $data['code']=400;
                        $data['msg']='词库错误';
                    }else{
                        $idiom=$idiom_arr[$level][$layer];
                        if($idiom){
                            $data['code']=200;
                            $data['msg']='成功';
                            $data['type']=input('post.type',1);
                            $data['recommend_user_id']=input('post.recommend_user_id',0);
                            if($data['type']!==1){
                                $data['data']['gold_num']='';
                            }else{
                                $data['data']['gold_num']=$user_game['gold_num'];
                            }
                            if($level==$max_level&&$layer==$max_layer){
                                $data['is_max']=1;
                            }else{
                                $data['is_max']=0;
                            }
                            $data['data']['idiom_id']=$idiom['id'];
                            $data['data']['level']=$idiom['level'];
                            $data['data']['level_name']=$idiom['level_name'];
                            $data['data']['layer']=$idiom['layer'];
                            $data['data']['max_layer']=$max_layer;
                            $data['data']['answer']=$this->get_answer($idiom['answer']);
                            $data['data']['help_answer']=$answer->get_help_answer($user_game['uid'],$level,$layer);
                            $data['data']['img_url']=$idiom['img_url'];
                            $data['data']['interfere_answer']=$this->get_interfere_answer($idiom['answer'],$idiom['interfere_answer']);
                        }else{
                            $data['code']=400;
                            $data['msg']='没有此关';
                        }
                    }
                }else{
                    $data['code']=400;
                    $data['msg']='没有此等级';
                }
            }else{
                $data['code']=401;
            }
        }else{
            $data['code']=401;
        }


       return $data;
    }

    //验证成语答案
    public function check_answer(){
        //$user_id=session('user_id');
        $openId=input('openId');
        $userdao=new Users();
        $user_id=$userdao->get_user_id($openId);
        if($user_id){
            $user_game=$userdao->findGame($user_id);
            if($user_game){
                $u_answer=$this->check_idiom(input('post.answer'));
                $level=$this->check_int(input('post.level'));
                $layer=$this->check_int(input('post.layer'));

                if($u_answer&&$level&&$layer){
                    $answer=new Answer();
                    $idiom_arr=$answer->get_all_answer();
                    $idiom=$idiom_arr[$level][$layer];
                    if($idiom){
                        if($u_answer==$idiom['answer']){
                            $type=input('post.type',1);
                            if($type==1){
                                $arr2=array_slice($idiom_arr[$level],-1,1,true);
                                $max_layer=key($arr2);
                                if($this->check_add_gold($user_game['uid'],$level,$layer)){
                                    $info=$answer->answer_success($user_game['uid'],$level,$layer);
                                    if($info['code']==200){
                                        $next_level=$level+1;
                                        if($layer==$max_layer){
                                            $data['data']['up_status']=1;
                                            $data['data']['next_level']=$next_level;
                                            $data['data']['next_layer']=1;
                                        }else{
                                            $data['data']['up_status']=0;
                                            $data['data']['next_level']=$level;
                                            $data['data']['next_layer']=$layer+1;
                                        }
                                        $data['data']['next_level_name']=$idiom_arr[$next_level][1]['level_name'];
                                        $data['code']=200;
                                        $data['msg']='答案正确';
                                        $data['type']=$type;
                                        $data['data']['up_medal']=$info['up_medal'];
                                        $data['data']['medal']=$info['medal'];
                                        $data['data']['add_gold_num']=$info['add_gold_num'];
                                        $data['data']['answer']=$idiom['answer'];
                                        $data['data']['explain']=$idiom['explain'];
                                    }else{
                                        $data['code']=400;
                                        $data['msg']='出错了，请联系管理员';
                                    }
                                }else{
                                    $next_level=$level+1;
                                    if($layer==$max_layer){

                                        $data['data']['next_level']=$next_level;
                                        $data['data']['next_layer']=1;
                                    }else{

                                        $data['data']['next_level']=$level;
                                        $data['data']['next_layer']=$layer+1;
                                    }
                                    $data['data']['up_status']=0;
                                    $data['data']['next_level_name']=$idiom_arr[$next_level][1]['level_name'];
                                    $data['code']=200;
                                    $data['msg']='答案正确';
                                    $data['type']=$type;
                                    $data['data']['add_gold_num']=0;
                                    $data['data']['answer']=$idiom['answer'];
                                    $data['data']['explain']=$idiom['explain'];
                                }
                            }else{
                                $recommend_user_id=input('post.recommend_user_id');
                                if($recommend_user_id){
                                    $has=Db::name('user_help')->where("uid=$recommend_user_id and help_user=".$user_game['uid']."and level=$level and layer=$layer")->find();
                                    $info2=true;
                                    if(!$has){
                                        $data2['uid']=$recommend_user_id;
                                        $data2['help_user']=$user_game['uid'];
                                        $data2['help_answer']=$u_answer;
                                        $data2['user_avatarUrl']=$user_game['avatarUrl'];
                                        $data2['level']=$level;
                                        $data2['layer']=$layer;
                                        $info2=Db::name('user_help')->insertGetId($data2);
                                    }
                                    if($info2){
                                        $data['code']=200;
                                        $data['msg']='答案正确';
                                        $data['type']=$type;
                                        $data['data']['add_gold_num']=0;
                                        $data['data']['answer']=$idiom['answer'];
                                        $data['data']['explain']=$idiom['explain'];
                                    }else{
                                        $data['code']=400;
                                        $data['msg']='出错了，请联系管理员';
                                    }

                                }else{
                                    $data['code']=400;
                                    $data['msg']='参数不全';
                                }

                            }

                        }else{
                            $data['code']=400;
                            $data['msg']='答案错误';
                        }
                    }else{
                        $data['code']=400;
                        $data['msg']='没有此关卡';
                    }
                }else{
                    $data['code']=400;
                    $data['msg']='参数不全';
                }
            }else{
                $data['code']=401;
            }
        }else{
            $data['code']=401;
        }
        return $data;

    }

    //成语答案提示
    public function idiom_prompt(){
        //$user_id=session('user_id');
        $openId=input('openId');
        $userdao=new Users();
        $user_id=$userdao->get_user_id($openId);
        if($user_id){
            $user_game=$userdao->findGame($user_id);
            if($user_game){
                if($user_game['gold_num']>=config('PROMPT_GOLD')){

                    $answer=new Answer();
                    $info=$answer->prompt($user_game['uid']);
                    if($info['code']==200){
                        $data['code']=200;
                        $data['msg']='提示成功';
                        $data['gold_num']=$info['gold_num'];

                    }else{
                        $data['code']=400;
                        $data['msg']='出错了，请联系管理员';
                    }

                }else{
                    $data['code']=400;
                    $data['msg']='金币不足';
                }
            }else{
                $data['code']=401;
            }

        }else{
            $data['code']=401;
        }
        return $data;
    }

    //获取等级页面
    public function get_level(){
        $openId=input('openId');
        $userdao=new Users();
        $user_id=$userdao->get_user_id($openId);
        if($user_id){
            $user_game=$userdao->findGame($user_id);
            if($user_game){
                $answer=new Answer();
                $level_arr=$answer->get_all_level();
                $idiom_arr=$answer->get_all_answer();
                $arr= array_slice($idiom_arr,-1,1,true);
                $max_level=key($arr);
                $arr2=array_slice($idiom_arr[$user_game['level']],-1,1,true);
                $max_layer=key($arr2);
                if($level_arr){
                    foreach($level_arr as $k=>$v){
                        if($user_game['level']<$v['level']){
                            $level_arr[$k]['status']=0;
                        }else{
                            $level_arr[$k]['status']=1;
                        }
                    }
                    foreach($level_arr as $kk=>$vv){
                        if($user_game['level']==$vv['level']&&$user_game['layer']==$max_layer&&$user_game['level']!=$max_level){
                            $m=$kk+1;
                            $level_arr[$m]['status']=1;
                            break;
                        }
                    }

                    $data['code']=200;
                    $data['msg']='成功';
                    $data['data']=$level_arr;
                }else{
                    $data['code']=401;
                    $data['msg']='出错了，请联系管理员';
                }
            }else{
                $data['code']=401;
            }
        }else{
            $data['code']=401;
        }
       return $data;
    }

    //获取关卡页面
    public function get_layer(){
        //$user_id=session('user_id');
        $openId=input('openId');
        $userdao=new Users();
        $user_id=$userdao->get_user_id($openId);
        if($user_id){
            $user_game=$userdao->findGame($user_id);
            if($user_game){
                $level_id=$this->check_int(input('post.level_id'));
                if($level_id){
                    $answer=new Answer();
                    $layer_arr=$answer->get_all_layer($level_id);
                    $level_arr=Db::name('level')->where('status=1')->find($level_id);

                    if($layer_arr&&$level_arr){
                        if($level_arr['level']<$user_game['level']){
                            foreach($layer_arr as $k=>$v){
                                $layer_arr[$k]['status']=1;
                                $layer_arr[$k]['level']=$level_arr['level'];
                            }
                        }elseif($level_arr['level']==$user_game['level']){
                            $next_layer=$user_game['layer']+1;
                            foreach($layer_arr as $k=>$v){
                                if($next_layer<$v['layer']){
                                    $layer_arr[$k]['status']=0;
                                }else{
                                    $layer_arr[$k]['status']=1;
                                }
                            }
                        }else{
                            $idiom_arr=$answer->get_all_answer();
                            $arr2=array_slice($idiom_arr[$user_game['level']],-1,1,true);
                            $max_layer=key($arr2);
                            if($user_game['layer']==$max_layer){
                                $next_layer=1;
                            }else{
                                $next_layer=$user_game['layer']+1;
                            }
                            foreach($layer_arr as $k=>$v){
                                if($next_layer<$v['layer']){
                                    $layer_arr[$k]['status']=0;
                                }else{
                                    $layer_arr[$k]['status']=1;
                                }
                            }
                        }
                        $page=$this->check_int(input('post.page',1));
                        $page_size=40;
                        $count=count($layer_arr);
                        $start=($page-1)*$page_size;
                        $total = ceil($count/$page_size);

                        $data['code']=200;
                        $data['msg']='成功';
                        $data['data']=array_slice($layer_arr,$start,$page_size);
                    }else{
                        $data['code']=401;
                        $data['msg']='出错了，请联系管理员';
                    }
                }else{
                    $data['code']=400;
                    $data['msg']='参数错误';
                }
            }else{
                $data['code']=401;
            }
        }else{
            $data['code']=401;
        }
        return $data;
    }

    //获取好友排行榜
    public function get_friend_ranking(){
        //$user_id=session('user_id');
        $openId=input('openId');
        $userdao=new Users();
        $user_id=$userdao->get_user_id($openId);
        if($user_id){
            $user_game=$userdao->findGame($user_id);
            if($user_game){
                $answer=new Answer();
                $friend_detail=$answer->get_one_friend($user_game['uid']);
                if($friend_detail){
                    $page=$this->check_int(input('post.page',1));
                    $page_size=10;
                    $count=count($friend_detail['data']);
                    $start=($page-1)*$page_size;
                    $total = ceil($count/$page_size);
                    $data['code']=200;
                    $data['msg']='成功';
                    $data['my_ranking']=$friend_detail['my_ranking'];
                    $data['my_idiom']=$user_game['idiom_num'];
                    $data['nickname']=$user_game['nickname'];
                    $data['page']=$page;
                    $data['page_size']=$page_size;
                    $data['count']=$count;
                    $data['total']=$total;
                    $data['data']=array_slice($friend_detail['data'],$start,$page_size);
                }else{
                    $data['code']=400;
                    $data['msg']='出错了，请联系管理员';
                }
            }else{
                $data['code']=401;
            }
        }else{
            $data['code']=401;
        }
        return $data;
    }

    //获取世界排行榜
    public function get_world_ranking(){
        //$user_id=session('user_id');
        $openId=input('openId');
        $userdao=new Users();
        $user_id=$userdao->get_user_id($openId);
        if($user_id){
            $userdao=new Users();
            $user_game=$userdao->findGame($user_id);
            if($user_game){
                $page=$this->check_int(input('post.page',1));
                $answer=new Answer();
                $all_ranking=$answer->get_world_ranking($user_game['uid']);
                if($all_ranking){
                    $page_size=10;
                    $count=count($all_ranking['data']);
                    $start=($page-1)*$page_size;
                    $total = ceil($count/$page_size);
                    $data['code']=200;
                    $data['msg']='成功';
                    $data['my_ranking']=$all_ranking['my_ranking'];
                    $data['my_idiom']=$user_game['idiom_num'];
                    $data['nickname']=$user_game['nickname'];
                    $data['page']=$page;
                    $data['page_size']=$page_size;
                    $data['count']=$count;
                    $data['total']=$total;
                    $data['data']=array_slice($all_ranking['data'],$start,$page_size);
                }else{
                    $data['code']=400;
                    $data['msg']='出错了，请联系管理员';
                }
            }else{
                $data['code']=401;
            }
        }else{
            $data['code']=401;
        }
        return $data;
    }

    //获取勋章页面
    public function get_medal(){
        $openId=input('openId');
        $userdao=new Users();
        $user_id=$userdao->get_user_id($openId);
        if($user_id){
            $userdao=new Users();
            $user_game=$userdao->findGame($user_id);
            if($user_game){
                $answer=new Answer();
                $all_medal=$answer->get_all_medal();
                if($all_medal){
                    foreach($all_medal as $k=>$v){
                        if($user_game['medal_num']<$v['medal']){
                            $all_medal[$k]['u_medal_num']=$user_game['medal_num'];
                            $all_medal[$k]['v_medal']=$v['medal'];
                            $all_medal[$k]['status']=0;
                        }else{
                            $all_medal[$k]['status']=1;
                            $all_medal[$k]['u_medal_num']=$user_game['medal_num'];
                            $all_medal[$k]['v_medal']=$v['medal'];
                        }
                    }
                    $data['code']=200;
                    $data['msg']='成功';
                    $data['my_medal']=$user_game['medal_num'];
                    $data['data']=$all_medal;
                }else{
                    $data['code']=400;
                    $data['msg']='出错了，请联系管理员';
                }
            }else{
                $data['code']=401;
            }
        }else{
            $data['code']=401;
        }
        return $data;

    }

    //获取干扰字符
    public function get_interfere_answer($answer,$interfere_answer){
        if($answer&&$interfere_answer){
            $arr1=array();
            $arr2=array();
            //将字符串存入数组
            $a1=mb_strlen($answer,'UTF-8');//在mb_strlen计算时，选定内码为UTF8，则会将一个中文字符当作长度1来计算
            for($i=0;$i<$a1;$i++){
                $arr1[$i]['text']=mb_substr($answer,$i,1,'UTF-8');
                $arr1[$i]['status']=false;
            }
            $a2=mb_strlen($interfere_answer,'UTF-8');//在mb_strlen计算时，选定内码为UTF8，则会将一个中文字符当作长度1来计算
            for($i=0;$i<$a2;$i++){
                $arr2[$i]['text']=mb_substr($interfere_answer,$i,1,'UTF-8');
                $arr2[$i]['status']=false;
            }

            shuffle($arr2);
            $arr3=array_slice($arr2,0,17);
            $data=array_merge($arr1,$arr3);
            shuffle($data);
            return $data;
        }else{
            return '';
        }
    }


    //获取干扰字符
    public function get_answer($answer){
        if($answer){
            $arr1=array();

            //将字符串存入数组
            $a1=mb_strlen($answer,'UTF-8');//在mb_strlen计算时，选定内码为UTF8，则会将一个中文字符当作长度1来计算
            for($i=0;$i<$a1;$i++){
                $arr1[]=mb_substr($answer,$i,1,'UTF-8');

            }


            return $arr1;
        }else{
            return '';
        }
    }

    //验证答案正确后是否加金币
    public function check_add_gold($user_id,$level,$layer){
        if($user_id&&$level&&$layer){
            $userdao= new Users();
            $user_game=$userdao->findGame($user_id);
            if($user_game){

                $u_level=$user_game['level']+1;
                $u_layer=$user_game['layer']+1;
                if($level==$user_game['level']){
                    if($layer==$u_layer){
                        return true;
                    }else{
                        return false;
                    }
                }else{
                    if($level==$u_level&&$layer==1){
                        return true;
                    }else{
                        return false;
                    }
                }

            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    //验证整数
    public function check_int($num){
        if(floor($num)==$num){
            return $num;
        }else{
            return '';
        }
    }

    //验证成语
    public function check_idiom($str){
        if(preg_match('/^[\x7f-\xff]+$/', $str)){
            return $str;
        }else{
            return '';
        }
    }

    //获取用户ID
    public function get_user_id(){
        $openId=input('openId');
        $userdao=new Users();
        $user_id=$userdao->get_user_id($openId);
        if($user_id){
            $data['code']='200';
            $data['msg']='成功';
            $data['user_id']=$user_id;
        }else{
            $data['code']=401;
        }
        return $data;
    }

    //分享求助成功获得金币
    public function user_share(){
        $openId=input('openId');
        $userdao=new Users();
        $user_id=$userdao->get_user_id($openId);
        if($user_id){
            $info=$userdao->share_gold($user_id);
            if($info['code']==200) {
                $data['code'] = 200;
                $data['msg'] = '分享成功';
                $data['gold_num'] = $info['gold_num'];
                $data['add_gold_num'] = $info['add_gold_num'];
            }elseif($info['code']==400){
                $data['code'] = 400;
                $data['msg'] = '分享次数已达上限';
            }else{
                $data['code']=401;
            }
        }else{
            $data['code']=401;
        }
       return $data;
    }

    //添加好友
    public function add_friend(){
        $openId=input('openId');
        $userdao=new Users();
        $user_id=$userdao->get_user_id($openId);
        if($user_id){
            $recommend_user_id=input("post.recommend_user_id",0);
            if($recommend_user_id!==0){

                $this->friend_add($user_id,$recommend_user_id);

                $data['code']=200;
            }else{
                $data['code']=400;
            }
        }else{
            $data['code']=401;
        }
        return $data;
    }

    public function friend_add($uid,$recommend_user_id){
        if($recommend_user_id&&$uid){
            $has=Db::name('user_friend')->where("uid=$uid and recommend_user_id=$recommend_user_id")->find();
            if(!$has){
                $recommend_arr['uid']=$uid;
                $recommend_arr['recommend_user_id']=$recommend_user_id;
                Db::name('user_friend')->insert($recommend_arr);
            }
            $has2=Db::name('user_friend')->where("uid=$recommend_user_id and recommend_user_id=$uid")->find();
            if(!$has2){
                $recommend_arr['uid']=$recommend_user_id;
                $recommend_arr['recommend_user_id']=$uid;
                Db::name('cy_user_friend')->insert($recommend_arr);
            }
        }
    }

    //更新世界排行
    public function world_ranking(){
        $key=input('get.key');
        if($key==$this->key){
            $world_arr=array();
            $ranking_arr=Db::name('user_game')->field('uid,avatarUrl,nickname,gold_num,idiom_num')->order('idiom_num desc')->limit(200)->select();
            foreach($ranking_arr as $k=>$v){
                $ranking=$k+1;
                $world_arr[$ranking]=$v;
                $world_arr[$ranking]['ranking']=$ranking;
            }
            cache('cy_world_ranking',$world_arr);
        }
    }


    public function share_group(){
        $openId=input('openId');
        $userdao=new Users();
        $user_id=$userdao->get_user_id($openId);
        if($user_id){
            $encryptedData = input("post.encryptedData");
            $iv = input("post.iv");
            if($encryptedData&&$iv){
                $session_key=input('session_key');
                if($session_key){
                    vendor("wxaes.wxBizDataCrypt");
                    $wxBizDataCrypt = new \WXBizDataCrypt(config("WECHAT_APPID"), $session_key);
                    $data_arr = array();
                    $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data_arr);
                    if($errCode==0){
                        $json_data = json_decode($data_arr, true);
                        $answer=new Answer();
                        $info=$answer->user_share_group($user_id,$json_data['openGId']);
                        if($info){
                            $data['code']=200;
                            $data['add_status']=$info['add_status'];
                            $data['add_gold_num']=$info['add_gold_num'];
                            $data['user_gold_num']=$info['user_gold_num'];
                        }else{
                            $data['code']=400;
                            $data['msg']='分享群失败';
                        }
                    }else{
                        $data['code']=402;
                    }
                }else{
                    $data['code']=401;
                }
            }else{
                $data['code']=400;
                $data['msg']='参数不全';
            }
        }else{
            $data['code']=401;
        }
        return $data;
    }

}