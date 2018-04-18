<?php
namespace Admin\Model;
//use Api\Controller\UploadFile;
use Think\Model;
use Think\Page;
class BaseModel extends Model{
    protected $explain_rule=array();
    const EXPLAIN_ARRAY="array";
    const EXPLAIN_TIMESTAMP="timestamp";
    const EXPLAIN_MONEY="money";
    const EXPLAIN_IP="ip";
    const EXPLAIN_URL="url";
    const EXPLAIN_TYPE="type";
    const EXPLAIN_FOO='foo';
    public $find='https://';
    //补全地址
    public function url(){

        return 'http://'.$_SERVER['HTTP_HOST'].'/FantasticStar/';
    }
    //缓存时间
    public $cache_time=3600;
    //每页显示条数
    public $page_num=15;
    //跳过检测方法
    private $allow_action=array(
        'login','do_login',"verify", 'error',
    );
    //手机号码正则
    public $phone_rule="/^1[34578]{1}[0-9]{1}[0-9]{8}$/";
    //banner 类型数组
    public $banner_type_list=array(
        0=>array('id'=>1,'name'=>'启动'),
        1=>array('id'=>2,'name'=>'首页'),
        2=>array('id'=>3,'name'=>'医疗'),
        3=>array('id'=>4,'name'=>'经纪'),
    );
    //os 类型数组
    public $os_type_list=array(
        0=>array('id'=>1,'name'=>'安卓'),
        1=>array('id'=>2,'name'=>'苹果'),
    );
    //path
    public function path(){
        return $_SERVER['DOCUMENT_ROOT'].'/FantasticStar/';
    }
    //json格式
    public function list_result_json($status,$msg,$result){
        return json_encode(array('status'=>$status,'msg'=>$msg,'result'=>$result));
    }
    //数据表获取
    public function get_table($value){
        switch ($value) {
            case 'orders': return M('orders'); break;
            case 'account': return M('cy_users');break;
            case 'cy': return M('cy_users');break;
            case 'mx': return M('mx_users');break;
            case "admin":return M("admin");break;
            case "score":return M()->table('config');break;
            case "log":return M("admin_log");break;
            case 'admin&&log':return M()->table("admin_log al,admin a");break;
            case 'conf': return M()->table('voice_txt');break;
            case 'faq': return M()->table('faq');break;
            case 'cy_answer': return M()->table('cy_answer');break;
            case 'cy_level': return M()->table('cy_level');break;
            case 'mx_answer': return M()->table('mx_answer');break;
            case 'mx_level': return M()->table('mx_level');break;

        }
    }
    //过滤数据库关键字
    public function filter_str($string){
        $find = "and,or,select,update,insert,delete";$keys = explode(",", $find);
        if ($keys) {
            foreach ($keys as &$one) {
                if (strstr($string, $one) != '') {$this->error($string." 含有敏感字眼！");break;
                }
            }
        }return $string;
    }

    //获取ip
    public function get_ip(){
        if ($_SERVER["HTTP_X_FORWARDED_FOR"]) {$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif ($_SERVER["HTTP_CLIENT_IP"]) {$ip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif ($_SERVER["REMOTE_ADDR"]) {$ip = $_SERVER["REMOTE_ADDR"];
        } elseif (getenv("HTTP_X_FORWARDED_FOR")) {$ip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("HTTP_CLIENT_IP")) {$ip = getenv("HTTP_CLIENT_IP");
        } elseif (getenv("REMOTE_ADDR")) {$ip = getenv("REMOTE_ADDR");
        } else {$ip = "Unknown";
        }return bindec(decbin(ip2long($ip)));
    }
    //日志
    public function write_log($content){
        $data['uid']=session('admin_id');
        $data['content']=$content;
        $data['time']=time();
        $this->get_table("log")->add($data);
    }
    //分页
    public function get_page($count, $num){
        $page = new Page($count, $num);
        $page->lastSuffix = false;
        $page->setConfig('header', '&nbsp;第%NOW_PAGE%页/共%TOTAL_PAGE%页&nbsp;（' . $num . '条记录/页&nbsp;&nbsp;共%TOTAL_ROW%条记录）');
        $page->setConfig('prev', '上一页');
        $page->setConfig('next', '下一页');
        $page->setConfig('last', '末页');
        $page->setConfig('first', '首页');
        $page->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $page->parameter = I('get.');
        return $page;
    }

    //通用解释时间
    public function explain_time($time){
        return (($time && $time > 0) ? date("Y-m-d H:i:s", $time) : '--');
    }

    public function explain_money($money){
        if($money == null){$money=0;}
        return sprintf("%.2f",$money);
    }
    //通用解释ip
    public function explain_ip($ip){
        return (($ip && $ip > 0) ? long2ip($ip) : '--');
    }

    //通用解释性别
    public function explain_sex($sex){
        switch($sex){
            case 1:case '1': $sex_name = '男'; break;
            case 0:case '0': $sex_name = '女'; break;
            default: $sex_name = '未知'; break;
        }
        return $sex_name;
    }

    //通用解释图片
    public function explain_img($img){
        if ($img) {
            if (!strstr($img, $this->find)) {
                $img = $this->url() . $img;
            }
        } else {
            $img = '';
        }
        return $img;
    }

    //通用解释启用或禁用状态
    public function explain_status($status){
        switch($status){
            case 1:case '1': $status_name = '启用中'; break;
            case 0:case '0': $status_name = '已禁用'; break;
            default: $status_name = '未知'; break;
        }
        return $status_name;
    }

    public function explain_redpocket_status2($status){
        switch($status){
            case 1:case '1': $status_name = '已完成'; break;
            case 0:case '0': $status_name = '未完成'; break;
            default: $status_name = '未知'; break;
        }
        return $status_name;
    }

    public function explain_redpocket_status($status){
        switch($status){
            case 0:case '0': $status_name = '未支付'; break;
            case 1:case '1': $status_name = '已支付未抢完'; break;
            case 2:case '2': $status_name = '已支付已抢完'; break;
            case 3:case '3': $status_name = '已支付已过期'; break;
            default: $status_name = '未知'; break;
        }
        return $status_name;
    }

    public function explain_num($num){
        return $num.' 个';
    }

    public function explain_lv($lv){
        switch($lv){
            case 1:case '1': $lv_name = '超级管理员'; break;
            case 2:case '2': $lv_name = '运营'; break;
            case 3:case '3': $lv_name = '财务'; break;
            default: $lv_name = '未知'; break;
        }
        return $lv_name;
    }

    //上传
    public function upload_pic($pic,$path){
        $user_id=session("admin_id");
        require_once(dirname(__FILE__)."../Controller/UploadFile.class.php");
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = 10240000; // 设置附件上传大小 ,单位b 1024000 =1M
        $upload->allowExts = array('jpg', 'gif', 'png', 'jpeg','apk'); // 设置附件上传类型
        $upload->savePath = $path; // 设置附件上传目录
        $upload->userFunction=true;        //开启使用自定义函数;
        $upload->saveRule = $user_id."_".time()."_".rand(1,99);
        if (!$upload->upload()) {
            $result['status']=-1;
            $result['error']=("上传错误：".$upload->getErrorMsg());// 上传错误提示错误信息
        } else {
            $result['status']=1;
            $result['info'] = $upload->getUploadFileInfo();
            // 上传成功
        }
        return $result;
    }
    //压缩图片
    public function img_thumb_fn($link,$name,$path){
        require(dirname(__FILE__)."../Controller/Image.class.php");
        $Img = new Image();
        $m_name = "m_" . $name;
        $thumbname = $path . $m_name;
        $image = $Img->thumb($link, $thumbname, '', $maxWidth = 300, $maxHeight = 300, $interlace = true);
        return $image;
    }


    public function explain_rule($data){
        if (count($this->explain_rule) > 0) {
            $explain_text = array();
            foreach ($data as $key => &$val) {
                $rule = $this->explain_rule[$key];
                if ($rule) {
                    switch ($rule[BaseModel::EXPLAIN_TYPE]) {
                        case BaseModel::EXPLAIN_ARRAY: $explain_text[$key.'_explain']=$rule[BaseModel::EXPLAIN_FOO][$val]; break;
                        case BaseModel::EXPLAIN_TIMESTAMP: $explain_text[$key.'_explain']=$this->explain_time($val); break;
                        case BaseModel::EXPLAIN_MONEY: $explain_text[$key.'_explain']=$this->explain_money($val); break;
                        case BaseModel::EXPLAIN_IP: $explain_text[$key.'_explain']=$this->explain_ip($val); break;
                        case BaseModel::EXPLAIN_URL: $explain_text[$key.'_explain']=$this->explain_img($val); break;
                    }
                }
            }
            $data = array_merge($data, $explain_text);
        }
        return $data;
    }




    //检测
    public function check_title($sql,$title){
        $has=$this->get_table($sql)->where("title='%s'",$title)->find();
        if($has){$this->error($title."已存在");
        }
    }

    public function change_time($time,$value){
        $date=explode("-",$time);
        return (mktime(0,0,0,$date[0],$date[1],$date[2])+$value);
    }
}
