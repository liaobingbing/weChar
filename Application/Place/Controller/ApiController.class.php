<?php
namespace Place\Controller;

use Common\Controller\ApiBaseController;
use Place\Model\AnswerModel;
use Place\Model\UsersModel;

class ApiController extends ApiBaseController{



    public function index(){
        $user_id=session('user_id');

        $userdao=new UsersModel();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            $data['code']=200;
            $data['msg']='获取成功';
            $data['data']['avatar_url']=$user_game['avatar_url'];
            $data['data']['nickname']=$user_game['nickname'];
            $data['data']['layer']=$user_game['layer']+1;
            $data['data']['gold_num']=$user_game['gold_num'];
        }else{
            $data['code']=401;
            $data['msg']='重新登录';
        }

        $this->ajaxReturn($data,'JSON');
    }

    //检查签到
    public function check_sign(){
        $user_id=session('user_id');

        $userdao=new UsersModel();
        $user_info=$userdao->findByuid($user_id);
        $user_game=$userdao->findGame($user_id);
        if($user_info&&$user_game){
            if($user_game['sign']==1){
                $data['code']=200;
                $data['msg']='执行签到';
                if($user_info['last_time']<mktime(0,0,0,date('m'),date('d')-1,date('Y'))){
                    $data['data']['sign_day']=1;
                    $user_game['sign_day']=0;
                    M('user_game')->save($user_game);
                }else{
                   $data['data']['sign_day']=$user_game['sign_day']+1;
                }

            }else{
                $data['code']=400;
                $data['msg']='今天已签到';
            }
        }else{
            $data['code']=401;
            $data['msg']='重新登录';
        }


        $this->ajaxReturn($data,'JSON');
    }

    //执行签到
    public function sign(){
        $user_id=session('user_id');

        $userdao=new UsersModel();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            if($user_game['sign']==1){
                $day_num=$user_game['sign_day']+1;
                if($day_num<7){
                    $user_game['sign_day']+=1;
                }else{
                    $user_game['sign_day']=0;
                }
                $user_game['gold_num']+=C('SIGN_GOLD_'.$day_num);
                $user_game['sign']=0;
                $info=M('user_game')->save($user_game);
                if($info){
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
            $data['msg']='重新登录';
        }

        $this->ajaxReturn($data,'JSON');
    }

    //闯关准备页面
    public function ready(){
        $user_id=session('user_id');

        $userdao=new UsersModel();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            $data['code']=200;
            $data['msg']='获取成功';
            $data['data']['avatar_url']=$user_game['avatar_url'];
            $data['data']['nickname']=$user_game['nickname'];
            $data['data']['layer']=$user_game['layer']+1;
        }else{
            $data['code']=401;
            $data['msg']='重新登录';
        }

        $this->ajaxReturn($data,'JSON');
    }

    //获取题目
    public function get_question(){
        $user_id=session('user_id');

        $userdao= new UsersModel();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            $layer=$this->check_int(I('post.layer',0));
            if($layer==0){
                $layer=$user_game['layer']+1;
            }
            $max_layer=M('answer')->where('status=1')->count();
            if($user_game['layer']==$max_layer){
                $layer=$max_layer;
            }
            $answer=new AnswerModel();
            $question=M('answer')->where('status=1 and layer=%d',$layer)->cache(7200)->find();
            if($question){

                $data['code']=200;
                $data['msg']='成功';
                $data['data']['type']=I('post.type',1);
                $data['data']['recommend_user_id']=I('post.recommend_id',0);
                if($data['data']['type']!==1){
                    $data['data']['gold_num']='';
                }else{
                    $data['data']['gold_num']=$user_game['gold_num'];
                }
                if($layer==$max_layer){
                    $data['data']['is_max']=1;
                }else{
                    $data['data']['is_max']=0;
                }
                $data['data']['layer']=$question['layer'];
                $data['data']['answer']=$this->get_answer($question['answer']);
                $data['data']['help_answer']=$answer->get_help_answer($user_game['uid'],$layer);
                $data['data']['img_url']=$question['img_url'];
                $data['data']['interfere_answer']=$this->get_interfere_answer($question['answer'],$question['interfere_answer']);
            }else{
                $data['code']=400;
                $data['msg']='没有此关';
            }

        }else{
            $data['code']=401;
            $data['msg']='重新登录';
        }

        $this->ajaxReturn($data,'JSON');
    }

    //验证猜歌答案
    public function check_answer(){
        $user_id=session('user_id');

        $userdao= new UsersModel();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            $u_answer=$this->check_idiom(I('post.answer'));
            $layer=$this->check_int(I('post.layer'));

            if($u_answer&&$layer){
                $answer=new AnswerModel();
                $question=M('answer')->where('status=1 and layer=%d',$layer)->cache(86400)->find();
                if($question){
                    if($u_answer==$question['answer']){
                        $type=I('post.type',1);
                        if($type==1){
                            if($this->check_add_gold($user_game['uid'],$layer)){
                                $info=$answer->answer_success($user_game['uid'],$layer);
                                if($info['code']==200){
                                    $data['code']=200;
                                    $data['msg']='答案正确';
                                    $data['data']['up_status']=$info['up_status'];
                                    $data['data']['up_layer']=$info['up_layer'];
                                    $data['data']['this_layer']=$layer;
                                    $data['data']['next_layer']=$layer+1;
                                    $data['data']['type']=$type;
                                    $data['data']['add_gold_num']=$info['add_gold_num'];
                                    $data['data']['gold_num']= $user_game['gold_num']+$info['add_gold_num'];
                                    $data['data']['answer']=$question['answer'];
                                }else{
                                    $data['code']=400;
                                    $data['msg']='出错了，请联系管理员';
                                }
                            }else{
                                $data['code']=200;
                                $data['msg']='答案正确';
                                $data['data']['up_status']=0;
                                $data['data']['this_layer']=$layer;
                                $data['data']['next_layer']=$layer+1;
                                $data['data']['type']=$type;
                                $data['data']['add_gold_num']=0;
                                $data['data']['gold_num']= $user_game['gold_num'];
                                $data['data']['answer']=$question['answer'];
                            }
                        }else{
                            $recommend_user_id=I('post.recommend_id');
                            if($recommend_user_id){
                                $data2['uid']=$recommend_user_id;
                                $data2['help_user']=$user_game['uid'];
                                $data2['help_answer']=$u_answer;
                                $data2['user_avatarUrl']=$user_game['avatar_url'];
                                $data2['layer']=$layer;
                                $info2=M('user_help')->add($data2);
                                if($info2){
                                    $data['code']=200;
                                    $data['msg']='答案正确';
                                    $data['data']['type']=$type;
                                    $data['data']['this_layer']=$layer;
                                    $data['data']['add_gold_num']=0;
                                    $data['data']['answer']=$question['answer'];
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
            $data['msg']='重新登录';
        }

        $this->ajaxReturn($data,'JSON');

    }

    //猜歌答案提示
    public function prompt(){
        $user_id=session('user_id');

        $userdao=new UsersModel();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            if($user_game['gold_num']>=C('PROMPT_GOLD')){

                $answer=new AnswerModel();
                $info=$answer->prompt($user_game['uid']);
                if($info['code']==200){
                    $data['code']=200;
                    $data['msg']='提示成功';
                    $data['data']['gold_num']=$info['gold_num'];

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
            $data['msg']='重新登录';
        }

        $this->ajaxReturn($data,'JSON');
    }

    //获取等级页面
    public function get_level(){
        $user_id=session('user_id');

        $userdao=new UsersModel();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            $answer=new AnswerModel();
            $level_arr=$answer->get_all_level();
            if($level_arr){
                if($user_game['layer']==0){
                    $user_game['layer']=1;
                }
                foreach($level_arr as $k=>$v){
                    if($user_game['layer']<$v['layer_min']){
                        $level_arr[$k]['status']=0;
                    }else{
                        $level_arr[$k]['status']=1;
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
            $data['msg']='重新登录';
        }

        $this->ajaxReturn($data,'JSON');
    }

    //获取关卡页面
    public function get_layer(){
        $user_id=session('user_id');

        $userdao=new UsersModel();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            $level_id=$this->check_int(I('post.level_id'));
            if($level_id){
                $answer=new AnswerModel();
                $layer_arr=$answer->get_all_layer();
                $level_arr=M('level')->where('status=1 and id=%d',$level_id)->cache(86400)->find();
                $num=$level_arr['layer_max']-$level_arr['layer_min']+1;
                $layer_arr2=array_slice($layer_arr,$level_arr['layer_min'],$num);
                $page=$this->check_int(I('post.page',1));
                $page_size=50;
                $count=count($layer_arr2);
                $start=($page-1)*$page_size;
                $total = ceil($count/$page_size);
                $layer_arr3=array_slice($layer_arr2,$start,$page_size);
                $nex_layer=$user_game['layer']+1;
                if($layer_arr3&&$level_arr){
                   foreach($layer_arr3 as $k=>$v){
                       if($user_game['layer']<$v['layer']){
                           $layer_arr3[$k]['status']=0;
                       }else{
                           $layer_arr3[$k]['status']=1;
                       }
                       if($v['layer']==$nex_layer){
                           $layer_arr3[$k]['status']=2;
                       }
                   }

                    $data['code']=200;
                    $data['msg']='成功';
                    $data['data']['page']=$page;
                    $data['data']['page_size']=$page_size;
                    $data['data']['count']=$count;
                    $data['data']['total']=$total;
                    $data['data']['layer']=$layer_arr3;
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
            $data['msg']='重新登录';
        }

        $this->ajaxReturn($data,'JSON');
    }

    //获取好友排行榜
    public function get_friend_ranking(){
        $user_id=session('user_id');

        $userdao=new UsersModel();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            $answer=new AnswerModel();
            $friend_detail=$answer->get_one_friend($user_game['uid']);
            if($friend_detail){
                $page=$this->check_int(I('post.page',1));
                $page_size=10;
                $count=count($friend_detail['data']);
                $start=($page-1)*$page_size;
                $total = ceil($count/$page_size);
                $data['code']=200;
                $data['msg']='成功';
                $data['data']['my_ranking']=$friend_detail['my_ranking'];
                $data['data']['my_success']=$user_game['success_num'];
                $data['data']['nickname']=$user_game['nickname'];
                $data['data']['page']=$page;
                $data['data']['page_size']=$page_size;
                $data['data']['count']=$count;
                $data['data']['total']=$total;
                $data['data']['friend']=array_slice($friend_detail['data'],$start,$page_size);
            }else{
                $data['code']=400;
                $data['msg']='出错了，请联系管理员';
            }
        }else{
            $data['code']=401;
            $data['msg']='重新登录';
        }

        $this->ajaxReturn($data,'JSON');
    }

    //获取世界排行榜
    public function get_world_ranking(){
        $user_id=session('user_id');

        $userdao=new UsersModel();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            $page=$this->check_int(I('post.page',1));
            $answer=new AnswerModel();
            $all_ranking=$answer->get_world_ranking($user_game['uid']);
            if($all_ranking){
                $page_size=10;
                $count=count($all_ranking['data']);
                $start=($page-1)*$page_size;
                $total = ceil($count/$page_size);
                $data['code']=200;
                $data['msg']='成功';
                $data['data']['my_ranking']=$all_ranking['my_ranking'];
                $data['data']['my_success']=$user_game['success_num'];
                $data['data']['nickname']=$user_game['nickname'];
                $data['data']['page']=$page;
                $data['data']['page_size']=$page_size;
                $data['data']['count']=$count;
                $data['data']['total']=$total;
                $data['data']['world']=array_slice($all_ranking['data'],$start,$page_size);
            }else{
                $data['code']=400;
                $data['msg']='出错了，请联系管理员';
            }
        }else{
            $data['code']=401;
            $data['msg']='重新登录';
        }

        $this->ajaxReturn($data,'JSON');
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

            $num=21-$a1;
            shuffle($arr2);
            $arr3=array_slice($arr2,0,$num);
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
    public function check_add_gold($user_id,$layer){
        if($user_id&&$layer){
            $userdao= new UsersModel();
            $user_game=$userdao->findGame($user_id);
            if($user_game){
                $u_layer=$user_game['layer']+1;
                if($layer==$u_layer){
                    return true;
                }else{
                    return false;
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

    //验证答案
    public function check_idiom($str){
        if(preg_match('/^[\x7f-\xff]+$/', $str)){
            return $str;
        }else{
            return '';
        }
    }

    //获取用户ID
    public function get_user_id(){
        $user_id=session('user_id');
        if($user_id){
            $data['code']='200';
            $data['msg']='成功';
            $data['data']['user_id']=$user_id;
        }else{
            $data['code']=401;
            $data['msg']='重新登录';
        }
        $this->ajaxReturn($data,'JSON');
    }

    //分享求助成功获得金币
    public function user_share(){
        $user_id=session('user_id');

        $userdao=new UsersModel();
        $info=$userdao->share_gold($user_id);
        if($info['code']==200) {
            $data['code'] = 200;
            $data['msg'] = '分享成功';
            $data['data']['gold_num'] = $info['gold_num'];
            $data['data']['add_gold_num'] = $info['add_gold_num'];
        }elseif($info['code']==400){
            $data['code'] = 400;
            $data['msg'] = '分享次数已达上限';
        }else{
            $data['code']=401;
            $data['msg']='重新登录';
        }

        $this->ajaxReturn($data,'JSON');
    }

    //添加好友
    public function add_friend(){
        $user_id=session('user_id');
        if($user_id){
            $recommend_user_id=I("post.recommend_id",0);
            if($recommend_user_id!==0){

                $this->friend_add($user_id,$recommend_user_id);

                $data['code']=200;
            }else{
                $data['code']=400;
            }
        }else{
            $data['code']=401;
        }
        $this->ajaxReturn($data,'JSON');
    }

    public function friend_add($uid,$recommend_user_id){
        if($recommend_user_id&&$uid){
            $has=M('user_friend')->where('uid=%d and recommend_user_id=%d',$uid,$recommend_user_id)->find();
            if(!$has){
                $recommend_arr['uid']=$uid;
                $recommend_arr['recommend_user_id']=$recommend_user_id;
                M('user_friend')->data($recommend_arr)->add();
            }
            $has2=M('user_friend')->where('uid=%d and recommend_user_id=%d',$recommend_user_id,$uid)->find();
            if(!$has2){
                $recommend_arr['uid']=$recommend_user_id;
                $recommend_arr['recommend_user_id']=$uid;
                M('user_friend')->data($recommend_arr)->add();
            }
        }
    }


    //用户群分享
    public function share_group(){
        $user_id=session('user_id');

        $encryptedData = I("post.encryptedData");
        $iv = I("post.iv");
        if($encryptedData&&$iv){
            $session_key=session('session_key');
            if($session_key){
                vendor("wxaes.WXBizDataCrypt");
                $wxBizDataCrypt = new \WXBizDataCrypt(C("WECHAT_APPID"), $session_key);
                $data_arr = array();
                $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data_arr);
                if($errCode==0){
                    $json_data = json_decode($data_arr, true);

                    $answer=new AnswerModel();
                    $info=$answer->user_share_group($user_id,$json_data['openGId']);
                    if($info){
                        $data['code']=200;
                        $data['msg']='分享成功';
                        $data['add_status']=$info['add_status'];
                        $data['add_gold_num']=$info['add_gold_num'];
                        $data['user_gold_num']=$info['user_gold_num'];
                    }else{
                        $data['code']=400;
                        $data['msg']='分享群失败';
                    }
                }else{
                    $data['code']=400;
                    $data['msg']='微信session_key过期';
                }
            }else{
                $data['code']=401;
                $data['msg']='重新登录';
            }
        }else{
            $data['code']=400;
            $data['msg']='参数不全';
        }

        $this->ajaxReturn($data,'JSON');
    }

    public function test(){
       session('user_id',1);
    }

    public function test2(){
        $max_layer=M('mx_answer')->where('status=1')->count();
        print_r($max_layer);
    }

}