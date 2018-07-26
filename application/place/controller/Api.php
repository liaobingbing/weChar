<?php
namespace app\place\controller;
use think\Controller;
use common\controller\ApiLogin;
use app\place\model\Answer;
use app\place\model\Users;
use think\Db;
class Api extends Controller{
    public function index(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');
        $userdao=new Users();
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

        // $this->ajaxReturn($data,'JSON');
        return $data;
    }

    //检查签到
    public function check_sign(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');
        // 测试
        $user_id = 10909;
        $userdao=new Users();
        $user_info=$userdao->findByuid($user_id);
        $user_game=$userdao->findGame($user_id);
        if($user_info&&$user_game){
            if($user_game['sign']==1){
                $data['code']=200;
                $data['msg']='执行签到';
                if($user_info['last_time']<mktime(0,0,0,date('m'),date('d')-1,date('Y'))){
                    $data['data']['sign_day']=1;
                    $user_game['sign_day']=0;
                    db('user_game')->where("uid",$user_id)->update($user_game);
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


        // $this->ajaxReturn($data,'JSON');
        return $data;
    }

    //执行签到
    public function sign(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');

        $userdao=new Users();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            if($user_game['sign']==1){
                $day_num=$user_game['sign_day']+1;
                if($day_num<7){
                    $user_game['sign_day']+=1;
                }else{
                    $user_game['sign_day']=0;
                }
                $user_game['gold_num']+=config('SIGN_GOLD_'.$day_num);
                $user_game['sign']=0;
                $info=db('user_game')->where("uid",$user_id)->update($user_game);
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

        // $this->ajaxReturn($data,'JSON');
        return $data;
    }

    //闯关准备页面
    public function ready(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');

        $userdao=new Users();
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

        // $this->ajaxReturn($data,'JSON');
        return $data;
    }

    //获取题目
    public function get_question(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');

        $userdao= new Users();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            $layer=input('post.layer',0);
            if($layer==0){
                $layer=$user_game['layer']+1;
            }
            $max_layer=db('answer')->where('status=1')->count();
            if($layer==$max_layer){
                $layer=$max_layer;
            }
            $answer=new Answer();
            $question=db('answer')->where(array('status'=>1,'layer'=>$layer))->find();
            if($question){

                $data['code']=200;
                $data['msg']='成功';
                $data['data']['type']=input('post.type',1);
                $data['data']['recommend_user_id']=input('post.recommend_id',0);
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
                str_shuffle($question['interfere_answer']);
                //$data['data']['interfere_answer']=$this->get_interfere_answer($question['answer'],$question['interfere_answer']);
                $data['data']['interfere_answer']=$this->option_splic($question['interfere_answer']);
            }else{
                $data['code']=400;
                $data['msg']='没有此关';
            }

        }else{
            $data['code']=401;
            $data['msg']='重新登录';
        }

        // $this->ajaxReturn($data,'JSON');
        return $data;
    }
//跳关
    public function jump_tip()
    {
        $openid = input("post.openid");
        $layer=input("post.layer");
        if($layer%100==0){
            $up_status=1;//什级状态
        }else{
            $up_status=0;
        }
        $userdao = new Users();
        $user = $userdao->findByOpenid($openid);
        $user_id = $user['id'];
        $info=Db::name("user_game")->where("uid",$user_id)->value("gold_num");
        if($info>=100){
            Db::name("user_game")->where("uid",$user_id)->update(['gold_num'=>$info-100,"layer"=>$layer]);
            $info=Db::name("user_game")->where("uid",$user_id)->value("gold_num");
            $next_layer=$layer+1;
            $arr=resCode(200,"ok",array("up_status"=>$up_status,"layer"=>$layer,"next_layer"=>$next_layer,"gold_num"=>$info));
            return $arr;
        }else{
            $arr=resCode(400,"金币不足",null);
            return $arr;
        }
    }
    //验证猜歌答案
    public function check_answer(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');

        $userdao= new Users();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            $u_answer=input('post.answer');
            $layer=$this->check_int(input('post.layer'));
           // print_r($u_answer);die;
            if($u_answer&&$layer){
                $answer=new Answer();
                $question=db('answer')->where(array('status'=>1,'layer'=>$layer))->cache(86400)->find();
                if($question){
                    if($u_answer==$question['answer']){
                        $type=input('post.type',1);
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
                            $recommend_user_id=input('post.recommend_id');
                            if($recommend_user_id){

                                $data2['uid']=$recommend_user_id;
                                $data2['help_user']=$user_game['uid'];
                                $data2['help_answer']=$u_answer;
                                $data2['user_avatarUrl']=$user_game['avatar_url'];
                                $data2['layer']=$layer;
                                if(!db('user_help')->where(array('uid'=>$data2['uid'],'help_user'=>$data2['help_user'],'layer'=>$layer))->find()){
                                    db('user_help')->insert($data2);
                                }



                                $data['code']=200;
                                $data['msg']='答案正确';
                                $data['data']['type']=$type;
                                $data['data']['this_layer']=$layer;
                                $data['data']['add_gold_num']=0;
                                $data['data']['answer']=$question['answer'];

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

        // $this->ajaxReturn($data,'JSON');
        return $data;

    }

    //猜歌答案提示
    public function prompt(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');

        $userdao=new Users();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            if($user_game['gold_num']>=config('PROMPT_GOLD')){

                $answer=new Answer();
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

        // $this->ajaxReturn($data,'JSON');
        return $data;
    }

    //获取等级页面
    public function get_level(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');

        $userdao=new Users();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            $answer=new Answer();
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

        // $this->ajaxReturn($data,'JSON');
        return $data;
    }

    //获取关卡页面
    public function get_layer(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');

        $userdao=new Users();
        $user_game=$userdao->findGame($user_id);
        if($user_game){
            $level_id=$this->check_int(input('post.level_id'));
            if($level_id){
                $answer=new Answer();
                $layer_arr=$answer->get_all_layer();
                $level_arr=db('level')->where(array('status'=>1,'id'=>$level_id))->cache(86400)->find();
                $num=$level_arr['layer_max']-$level_arr['layer_min']+1;
                $layer_arr2=array_slice($layer_arr,$level_arr['layer_min'],$num);
                $page=$this->check_int(input('post.page',1));
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

        // $this->ajaxReturn($data,'JSON');
        return $data;
    }

    //获取好友排行榜
    public function get_friend_ranking(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');

        $userdao=new Users();
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

        // $this->ajaxReturn($data,'JSON');
        return $data;
    }

    //获取世界排行榜
    public function get_world_ranking(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');

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

        // $this->ajaxReturn($data,'JSON');
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
            $userdao= new Users();
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
        // $user_id=session('user_id');
        $user_id=input('post.user_id');
        if($user_id){
            $data['code']='200';
            $data['msg']='成功';
            $data['data']['user_id']=$user_id;
        }else{
            $data['code']=401;
            $data['msg']='重新登录';
        }
        // $this->ajaxReturn($data,'JSON');
        return $data;
    }

    //分享求助成功获得金币
    public function user_share(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');

        $userdao=new Users();
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

        // $this->ajaxReturn($data,'JSON');
        return $data;
    }

    //添加好友
    public function add_friend(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');
        if($user_id){
            $recommend_user_id=input("post.recommend_id",0);
            if($recommend_user_id!==0){

                $this->friend_add($user_id,$recommend_user_id);

                $data['code']=200;
            }else{
                $data['code']=400;
            }
        }else{
            $data['code']=401;
        }
        // $this->ajaxReturn($data,'JSON');
        return $data;
    }

    public function friend_add($uid,$recommend_user_id){
        if($recommend_user_id&&$uid){
            $has=db('user_friend')->where(array('uid'=>$uid,'recommend_user_id'=>$recommend_user_id))->find();
            if(!$has){
                $recommend_arr['uid']=$uid;
                $recommend_arr['recommend_user_id']=$recommend_user_id;
                db('user_friend')->insert($recommend_arr);
            }
            $has2=db('user_friend')->where(array('uid'=>$uid,'recommend_user_id'=>$recommend_user_id))->find();
            if(!$has2){
                $recommend_arr['uid']=$recommend_user_id;
                $recommend_arr['recommend_user_id']=$uid;
                db('user_friend')->insert($recommend_arr);
            }
        }
    }


    //用户群分享
    public function share_group(){
        // $user_id=session('user_id');
        $user_id=input('post.user_id');
        $session_key=input('post.wx_session_key');
        $encryptedData = input("post.encryptedData");
        $iv = input("post.iv");
        if($encryptedData&&$iv&&$session_key){
            // $session_key=session('wx_session_key');
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

        // $this->ajaxReturn($data,'JSON');
        return $data;
    }

    public function test(){
       session('user_id',1);
    }

    public function test2(){
        $max_layer=M('mx_answer')->where('status=1')->count();
        print_r($max_layer);
    }

    /**
     * 将干扰项打乱顺序
     * @param $name
     * @param $option
     * @param int $len
     * @return array
     */
    public function option_splic($option){
        $option = $this->ch_to_arr($option);

        shuffle($option);
        foreach($option as $k => $v){
            $arr2[$k]['text'] = $v;
            $arr2[$k]['status'] = false;
        }
        return $arr2;
    }

    //中文字符串转为数组
    public function ch_to_arr($str)
    {
        $length = mb_strlen($str, 'utf-8');
        $array = array();
        for ($i=0; $i<$length; $i++)
            $array[] = mb_substr($str, $i, 1, 'utf-8');
        return $array;
    }
}