<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/12
 * Time: 18:25
 */

namespace Admin\Controller;

Vendor('QiniuSdk.autoload');

use Admin\Model\AppDailyRecordModel;
use Admin\Model\AppStatisticsModel;
use Think\Controller;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;


class AdminController extends Controller
{
    const GET_API_WECHAT_TOKEN = 'https://api.weixin.qq.com/cgi-bin/token'; // 获取token
    const GET_SUMMARYTREND     = 'https://api.weixin.qq.com/datacube/getweanalysisappiddailysummarytrend'; //概况趋势
    const GET_VISITTREND       = 'https://api.weixin.qq.com/datacube/getweanalysisappiddailyvisittrend';   //日趋势

    protected function _initialize(){
        $this->is_login();
    }

    // 登录判断
    public function is_login(){
        $admin_id = session('admin_id');

        if( !$admin_id ){
            session(null);
            $this->redirect('Admin/Login/index');
        }
    }

    //退出登录
    public function logout(){
        session(null);
        if( !session('admin_id') ){
            $this->redirect('Admin/Login/index');
        }

    }

    // 获取状态提示信息
    public function get_msg($status){
        switch ($status){

            //success
            case 'save_success': $result = array('msg'=>"保存成功",'code'=>200); break;
            case 'login_success':$result = array('msg'=>"登录成功",'code'=>200); break;
            case 'add_success': $result = array('msg'=>"添加成功",'code'=>200); break;
            case 'logout_success': $result = array('msg'=>"登出成功",'code'=>200);break;
            case 'cache_success':$result = array('msg'=>'系统缓存清除成功！','code'=>200); break;
            case 'del_success':$result = array('msg'=>'删除成功','code'=>200);break;

            //fail
            case 'logout_fail':  $result = array('msg'=>"登出失败",'code'=>400);break;
            case 'admin_status':$result = array('msg'=>"账户已被冻结",'code'=>400);break;
            case 'pwd_error':$result = array('msg'=>"密码错误",'code'=>400);break;
            case 'login_fail':$result = array('msg'=>"登录失败,未知错误",'code'=>400);break;
            case 'save_fail': $result = array('msg'=>"保存失败",'code'=>400);break;
            case 'img_fail': $result = array('msg'=>"图片保存失败",'code'=>400);break;
            case 'add_fail': $result = array('msg'=>"添加失败",'code'=>400);break;
            case 'data_same': $result = array('msg'=>"新旧资料一致，不需要修改",'code'=>400);break;
            case 'no_account': $result = array('msg'=>"账户不存在",'code'=>400);break;
            case 'has_account': $result = array('msg'=>"账户名已存在",'code'=>400);break;
            case 'no_admin':$result = array('msg'=>"账户不存在",'code'=>400);break;
            case 'login_value': $result = array('msg'=>'请正确填写登陆信息','code'=>400);break;
            case 'pwd==': $result = array('msg'=>"新旧密码不能一致",'code'=>400);break;
            case 'pwd<>': $result = array('msg'=>'修改密码与重复密码不一致','code'=>400);break;
            case 'del_fail':$result = array('msg'=>'删除失败','code'=>400);break;


            //public
            case 'no_data': $result = array('msg'=>"数据不存在",'code'=>400);break;
            case 'lv': $result = array('msg'=>"权限不足",'code'=>400);break;
            case 'no_login': $result = array('msg'=>"请重新登录",'code'=>400);break;
            case 'the_same': $result = array('msg'=>'新旧数据一致,不需要保存','code'=>400);break;
            case 'empty': $result = array('msg'=>'无效访问','code'=>400);break;
            case 'time_error': $result = array('msg'=>"结束时间必须大于等于开始时间",'code'=>400);break;
            case "post_fail": $result = array('msg'=>"参数不全",'code'=>400);break;
            case "post_error": $result = array('msg'=>'参数错误','code'=>400);break;
            case "phone_format": $result = array('msg'=>'手机号码格式错误','code'=>400);break;

            default: $result = array('msg'=>"未知错误00",'code'=>400);break;
        }
        
        return $result;
        
    }

    // 分页样式自定义
    public function page_change($count,$pagesize,$prev='上一页',$next='下一页',$last='末页',$firsh='首页'){
        $page = new \Think\Page($count, $pagesize);

        $page->setConfig('prev', $prev);
        $page->setConfig('next', $next);
        $page->setConfig('last', $last);
        $page->setConfig('first', $firsh);
        $page->lastSuffix = false;//最后一页不显示为总页数
        return $page;
    }


    /**
     * 七牛上传 成功返回文件url路径
     * @param $file '一个文件数组'
     * @param $prefix '七牛前缀'
     * @return string
     * @throws \Exception
     */
    public function qiniu_upload($file,$prefix){

         // 用于签名的公钥和私钥
         $accessKey = C('QI_NIU_ACCESS_KEY');
         $secretKey = C('QI_NIU_SECRET_KEY');

         // 初始化签权对象
         $auth = new Auth($accessKey, $secretKey);
         // 空间名  https://developer.qiniu.io/kodo/manual/concepts
         // http://img.ky121.com/Song/MP3V1/a001.mp3
         $bucket = 'kuaiyuxcx';

         // 生成上传Token
         $token = $auth->uploadToken($bucket);

         // 构建 UploadManager 对象
         $uploadMgr = new UploadManager();
         // 上传文件到七牛

         $filePath = $file['tmp_name'];
         $suffix   = pathinfo($file['name'], PATHINFO_EXTENSION);
         $key = $prefix.time().'.'.$suffix;

         list($ret,$err) = $uploadMgr->putFile($token,$key,$filePath);

         if ($err !== null) {
             $result = false;
         } else {
             $result = C('QI_NIU_URL_PREFIX').'/'.$ret['key'];
         }

        return $result;
    }

    /**
     * 获取小程序token
     * @param $appid
     * @param $appsecret
     * @param $app_key '小程序标志'
     * @return bool|mixed
     */
    public function get_wechat_token($appid,$appsecret,$app_key){
        $key = 'get_wechat_token_'.$app_key;
        $token = S($key);
        if( !$token ){
            $url = self::GET_API_WECHAT_TOKEN;
            $parameter = array(
                'grant_type'=>  'client_credential',
                'appid'     =>  $appid,
                'secret'    =>  $appsecret
            );
            $data = post_url($url,$parameter);
            if($data['errcode']){
                $result = false;
            }else{
                $result = $data['access_token'];
                S($key,$data['access_token'],$data['expires_in']);
            }
        }else{
            $result = $token;
        }

        return $result;
    }

    /**
     * 获取小程序概况趋势
     * @param $token
     * @param string $time
     * @return array ['list'=> ['visit_total'=>'累计用户数','share_pv'=>'转发次数','share_uv'=>'转发人数']]
     */
    public function get_summary_trend($token,$time = ''){

        $time = empty($time) ? date('Ymd',time()-60*60*24) : $time;

        $parameter = array(
            "begin_date" => $time,
            "end_date"  =>  $time
            );
        $parameter = json_encode($parameter);

        $result = post_url(self::GET_SUMMARYTREND.'?access_token='.$token,$parameter);

        if($result['list']){
            $result = $result['list'][0];
        }else{
            $result = false;
        }

        return $result;

    }

    /**
     * 获取小程序日趋势
     * @param $token
     * @param string $time
     * @return array
     *  ['list'     =>    [
     *      'ref_date'      =>  '时间： 如:"20170313"',
     *      'session_cnt'   =>  '打开次数',
     *      'visit_pv'      =>  '访问次数',
     *      'visit_uv'      =>  '访问人数',
     *      'visit_uv_new'  =>  '新用户数',
     *      'stay_time_uv'  =>  '人均停留时长:秒',
     *      'stay_time_session'  =>  '次均停留时长:秒',
     *      'visit_depth'   =>  '平均访问深度(浮点型)'
     *      ]
     * ]
     */
    public function get_visit_trend($token,$time = ''){

        $time = empty($time) ? date('Ymd',time()-60*60*24) : $time;

        $parameter = array(
            "begin_date" => $time,
            "end_date"  =>  $time
        );
        $parameter = json_encode($parameter);

        $result = post_url(self::GET_VISITTREND.'?access_token='.$token,$parameter);

        if($result['list']){
            $result = $result['list'][0];
        }else{
            $result = false;
        }

        return $result;

    }

    /**
     * 对某小程序的每天数据进行保存
     * @param $app_id
     * @param $token
     * @return bool
     */
    public function update_statistics_all($app_id,$token){

        $app = M('AppList')->find($app_id);
        if($app){

            $yester = date('Ymd',time()-24*60*60);
            $list = $this->get_visit_trend($token);

            if ($list) {
                $AppDailyRecord = new AppDailyRecordModel();
                $AppStatistics = new AppStatisticsModel();

                $i = 0;
                $total[$i] = $list;

                // 对有数据的时间段进行查询
                while ($list){

                    $yester -= 1;
                    $list = $this->get_visit_trend($token,$yester);

                    if($list){
                        $i++;
                        $total[$i] = $list;
                    }

                }
                $total = array_reverse($total);

                //将数据保存到数据库
                foreach($total as $v){
                    $re = $AppDailyRecord->update_data($app_id,$v);
                    if($re){
                        $date = $this->get_summary_trend($token,$v['ref_date']);
                        $result = $AppStatistics->update_data($app_id,$date);
                    }
                }
            }


        }else{
            $result = false;
        }

        return $result;
    }

}