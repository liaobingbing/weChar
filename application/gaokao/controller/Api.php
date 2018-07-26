<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/23
 * Time: 11:08
 */

namespace app\gaokao\controller;


use app\gaokao\model\Game;
use app\gaokao\model\User;
use common\controller\ApiLogin;
use think\Request;

class Api extends ApiLogin
{
    public function get_question()
    {
        $type=input("post.type");
        $openId=input("post.openId");
        $layer=input("post.layer");
        if($layer>15){
            $arr=resCode(400,"题目已经答完",null);
        }else{
            $game=new Game();
            $question=$game->get_question($type,$layer,$openId);
            if(empty($question)){
                $arr=resCode(400,"题库出错,联系开发者",null);
            }else{
                $arr=resCode(200,"获取成功",$question);
            }
        }
        return $arr;
    }
    //返回上一题
    public function up_question()
    {
        $type=input("post.type");
        $openId=input("post.openId");
        $layer=input("post.layer");
        if($type==1){
            $question=cache("scence".$openId);
        }else{
            $question=cache("arts".$openId);
        }
        if(empty($question)){
            $arr=resCode(400,"题目为空",null);
            return $arr;
        }else{
            $arr=resCode(200,"查询成功",$question[$layer]);
            return  $arr;
        }
    }

//分享群
    public function share_group(){
        $data=array("code"=>200,"msg"=>"success","data"=>null);
        return $data;
    }
    //获取小程序的openid
    public function get_openid()
    {
        $code = input('post.code');
        $rand="201806".rand_string(8,1);
        $login_data = $this->test_weixin($code);
        if (empty($login_data['400'])&&$login_data['openid']) {
            $openid = $login_data['openid'];
            $session_key=$login_data['session_key'];
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key,"rand"=>$rand));
            $userdao=new User();
            $user = $userdao->findByOpenid($openid);
            if(empty($user)){
                $data['openid']=$openid;

                $data['students_num']=$rand;
                db("users")->insert($data);
            }else{
                db("users")->where("openid",$openid)->update(["students_num"=>$rand]);
            }
            return $arr;
        }
        else{
            return $login_data;

        }
    }
    //保存用户姓名
    public function set_name()
    {
        $name=input("post.userName");
        $openId=input("post.openId");
        $db=db("users")->where("openid",$openId)->setField("user_name",$name);
        if($db){
            $arr=resCode(200,"更新成功",null);
        }else{
            $arr=resCode(400,"更新失败",null);
        }
        return $arr;
    }
    //获取用户名,准考证号
    public function get_name()
    {
        $openId=input("post.openId");
        $db=db("users")->where("openid",$openId)->fetchSql(true)->value("user_name,students_num,img_url");
        $data=db()->query($db);
        if($db){
            $arr=resCode(200,"查询成功",array("data"=>$data[0],"status"=>2));
        }else{
            $arr=resCode(400,"查询失败",null);
        }
        return $arr;
    }
//结果
    public function case_result()
    {
        $type=input("post.type");
        $data=db("grade")->alias('a')->join('gaokao_college w','a.type= w.type')->where("a.type",$type)->field("a.result as grade,w.result as college")->fetchSql(false)->select();
       //echo $data;die;
        shuffle($data);
        $data=array_slice($data,0,1);
        $arr=resCode(200,"查询成功",$data);
        return $arr;
    }

    public function get_time()
    {
        $time=date("Y-m-d");
        $arr=resCode(200,"查询成功",$time);
        return $arr;
    }
    //获取头像图片
    public function get_img()
    {
        $img=input("imgUrl");
        $openId=input("openId");
        $s=db("users")->where("openid",$openId)->update(['img_url'=>$img]);
       if($s){
           $arr=resCode(200,"更新头像成功",null);
       }else{
           $arr=resCode(400,"更新头像失败",null);
       }
       return $arr;

    }
    //获取access_token;
    public function accessToken()
    {
        $admin=model('Admin');
        //$appId=config('WECHAT_APPID');
       // $secrt=config('WECHAT_APPSECRET');
        $appId='wx038a63af17ad0c8e';
        $secrt="e94833d170c03875f8a7603557241e12";
        $result=$admin->where('app_id', $appId)->find();
        if(empty($result)){
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$secrt";
            $res=$this->post_url($url);
            $access_token=$res['access_token'];
            if(!empty($access_token)){
                $admin->app_id = $appId;
                $admin->expires_at = time()+7200;
                $admin->access_token = $access_token;
                $admin->save();
            }
        }
        else{
            if($result['expires_at']>time()){
                $access_token=$result['access_token'];
            }else{
                $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$secrt";
                $res=$this->post_url($url);
              //  $res=json_decode($res,true);
                $access_token=$res['access_token'];
                $admin->save([
                    'expires_at' => time()+7200,
                    'access_token' => $access_token,
                ],['app_id' => $appId]);
            }
        }
       // $arr=resCode(200,"ok",$access_token);
        return $access_token;
    }
    function post_url($url,$parameter=null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT,15);   //只需要设置一个秒的数量就可以
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if(!empty($parameter)){
            // post数据
            curl_setopt($ch, CURLOPT_POST, 1);
            // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameter);
        }
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output,true);
        return $output;
    }
    /**
     * 小程序formid写入
     * @param form_id 小程序formid
     * @param open_id 微信openid
     */
    public function addXcxFormId() {
        $form_id = input('form_id');
        $open_id = input('open_id');

        if (empty($form_id) || empty($open_id) || $form_id == 'the formId is a mock one' || $form_id == 'undefined') {
            $arr=resCode(200,"SUCCESS");
            return $arr;
        }
        if (db('xcx_formid')->insert(array(
            'form_id' => $form_id,
            'open_id' => $open_id,
            'add_time' => time()
        ))) {
            $arr=resCode(200,"SUCCESS");
        }else{
            $arr=resCode(400,"网络错误");
        }
        return $arr;
    }
}