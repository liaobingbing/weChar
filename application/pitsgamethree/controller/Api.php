<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/6
 * Time: 9:17
 */

namespace app\pitsgamethree\controller;


use app\pitsgamethree\model\User;
use common\controller\ApiLogin;
use think\Db;
class Api extends ApiLogin
{

    //获取小程序的openid
    public function get_openid()
    {
        $user=new User();
        $code = input('post.code');
        $login_data = $this->test_weixin($code);
        if (empty($login_data['400']) && $login_data['openid']) {
            $openid = $login_data['openid'];
            $session_key = $login_data['session_key'];
            $info=$user->findByOpenid($openid);
            if(empty($info)){
                $data['openid']=$openid;
                Db::name("users")->insert($data);
            }
            $arr = array("code" => 200, "msg" => "success", "data" => array("openId" => $openid, "wx_session_key" => $session_key,"status"=>1));
            return $arr;
        }
    }
    //取出关卡和机会
    public function get_num()
    {
        $openId=input("openId");//
        $res=Db::name("users")->where("openid",$openId)->find();
        $arr=resCode(200,"ok",$res);
        return $arr;
    }
    //存关卡数和减少机会 1是存关卡 2.是减少机会
    public function desc_num()
    {
        $type=input("post.type");
        $openId=input("openId");
        $num=input("post.num");
        if($type==1){  //保存关卡数
            Db::name("users")->where("openid",$openId)->update(['layer'=>$num]);
        }else{
            Db::name("users")->where("openid",$openId)->setDec("chance_num");
        }
        $arr=resCode(200,"ok",null);
        return $arr;
    }
    //分享群
    public function share_group(){
        $openId=input("post.openId");
        $encryptedData = input("post.encryptedData");
        $iv = input("post.iv");
        $session_key=input("session_key");
        if(!$encryptedData||!$iv||!$session_key){
            $arr=resCode(400,"参数为空",null);
            return  $arr;
        }
        vendor("wxaes.wxBizDataCrypt");
        $wxBizDataCrypt = new \WXBizDataCrypt(config("WECHAT_APPID"), $session_key);
        $data_arr = array();
        $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data_arr);
        if($errCode==0) {
            $json_data = json_decode($data_arr, true);
            $openGid=$json_data['openGId'];
            $res=Db::name("share_group")->where("openGid",$openGid)->where("openid",$openId)->find();
            if(!empty($res)&&$res['share_time']<strtotime(date("Y-m-d"))){
                Db::name("share_group")->where("openGid",$openGid)->where("openid",$openId)->setField("share_time",time());
                Db::name("users")->where("openid",$openId)->setInc("chance_num");
                $arr=resCode(200,"ok",null);
                return $arr;
            }else if(empty($res)){
                $data['openid']=$openId;
                $data['share_time']=time();
                $data['openGid']=$openGid;
                Db::name("share_group")->insert($data);
                Db::name("users")->where("openid",$openId)->setInc("chance_num");
                $arr=resCode(200,"ok",null);
                return $arr;
            }else{
                $arr=resCode(400,"已经分享过",null);
                return $arr;
            }
        }

    }

    public function test_url()
    {
        $str='{"code":0,"data":{"ads":[{"sub_title":null,"is_active":true,"gender":0,"create_time":"2018-06-23 19:20:55","src":"http://cdn.shaonao.17fengguo.com/ads/2018-06-2355dba956-4a4a-42ac-abdd-71b303fd9483.jpeg","index":14,"type":0,"title":null,"path":"pages/main/main?navigateto=lammzyq","update_time":"2018-06-23 19:20:55","group_id":0,"appid":"wx845a2f34af2f4235","id":44,"desc":"恋爱喵喵"},{"sub_title":null,"is_active":true,"gender":0,"create_time":"2018-05-23 16:28:49","src":"http://cdn.shaonao.17fengguo.com/ads/lxwz.jpeg","index":10,"type":0,"title":"连线王者","path":"pages/main/main?navigateto=lxwz","update_time":"2018-05-23 16:28:49","group_id":0,"appid":"wx845a2f34af2f4235","id":40,"desc":"连线王者"}],"pay_ios":false,"tip_android":true,"my_ads_percent":1.0,"carousel_time":10,"force_share":true,"goods":[{"update_time":"2018-03-23 20:53:56","is_active":true,"create_time":"2018-03-23 20:53:56","price":2.0,"name":"2元1把钥匙","remark":"","id":19,"real_price":200,"key_num":1},{"update_time":"2018-03-23 20:54:05","is_active":true,"create_time":"2018-03-23 20:54:05","price":6.0,"name":"6.0元5把钥匙","remark":null,"id":20,"real_price":600,"key_num":5},{"update_time":"2018-03-23 20:54:14","is_active":true,"create_time":"2018-03-23 20:54:14","price":10.0,"name":"10.0元10把钥匙","remark":null,"id":21,"real_price":1000,"key_num":10},{"update_time":"2018-03-23 20:54:23","is_active":true,"create_time":"2018-03-23 20:54:23","price":20.0,"name":"20.0元20把钥匙","remark":null,"id":22,"real_price":2000,"key_num":20}],"pay_android":true,"lv_prompt":[{"name":"第1关","gameLevel":1,"prompt":"把按钮移开，找出真正的按钮","promptEnd":"哈哈，一定要找对按钮"},{"name":"第2关","gameLevel":2,"prompt":"用钉子在车的两个轮子上狂戳，把轮子戳破后不就能过去了吗。","promptEnd":"这车还能开吗？"},{"name":"第3关","gameLevel":3,"prompt":"把球门移到足球处","promptEnd":"世界杯冠军非你莫属啊~"},{"name":"第4关","gameLevel":4,"prompt":"使劲摇晃手机，Duang~","promptEnd":"Duang~加特技"},{"name":"第5关","gameLevel":5,"prompt":"摩擦生热","promptEnd":"涨知识了，瞬间暖和"},{"name":"第6关","gameLevel":6,"prompt":"把二层楼的房子叠在四层楼的房子上，组成六楼","promptEnd":"房子不是这样建的吗？嘻嘻嘻~"},{"name":"第7关","gameLevel":7,"prompt":"按住绳子往下滑","promptEnd":"我表示小时候我会花式跳绳"},{"name":"第8关","gameLevel":8,"prompt":"点击右下方的手电筒开关，再找找钉子在哪，点击它","promptEnd":"晚上找东西肯定得开灯啊"},{"name":"第9关","gameLevel":9,"prompt":"把电池中的图片移到电池外","promptEnd":"电池没电了要充电了"},{"name":"第10关","gameLevel":10,"prompt":"把光圈移到小王脸上","promptEnd":"自带猪脚光环。不不不，是主角"},{"name":"第11关","gameLevel":11,"prompt":"把杯底的牛奶移到人的嘴上","promptEnd":"这牛奶真好喝~"},{"name":"第12关","gameLevel":12,"prompt":"把题目中的米字移到锅中","promptEnd":"煮米饭肯定得先加米啊！"},{"name":"第13关","gameLevel":13,"prompt":"6倒过来就是9，所以输入的最大数字是98754321","promptEnd":"那么简单，不算套路了吧~"},{"name":"第14关","gameLevel":14,"prompt":"使劲摇晃手机，牛顿晕了就会躺下了","promptEnd":"牛顿：呵呵，你们开心就好"},{"name":"第15关","gameLevel":15,"prompt":"410","promptEnd":"你是个天才，真的"},{"name":"第16关","gameLevel":16,"prompt":"20","promptEnd":"想明白了吗"},{"name":"第17关","gameLevel":17,"prompt":"把题目中的鱼字移到小猫的嘴上","promptEnd":"不努力是没有鱼吃的"},{"name":"第18关","gameLevel":18,"prompt":"拖动鸡蛋，再多次撞击石头试试","promptEnd":"肯定是石头硬啊，这不是常识吗"},{"name":"第19关","gameLevel":19,"prompt":"长按纸上面的夹子，直到纸掉下来","promptEnd":"so easy~"},{"name":"第20关","gameLevel":20,"prompt":"依次点击雪人的身体-雪人的头-题目上的圆字-雪人帽子上的小圆圈","promptEnd":"又忘记我们的套路了吗？"},{"name":"第21关","gameLevel":21,"prompt":"把花丛中的花移到蜜蜂处","promptEnd":"必要时刻要学会自救啊"},{"name":"第22关","gameLevel":22,"prompt":"把病患上方的\u201C不要\u201D移到转盘\u201C放弃治疗\u201D中，再点击转动大转盘","promptEnd":"继续挑战，不要放弃治疗~"},{"name":"第23关","gameLevel":23,"prompt":"把题目中的\u201C一\u201D字挪到电线处，同时切断两根电线","promptEnd":"又是送分常识题啊"},{"name":"第24关","gameLevel":24,"prompt":"把题目中\u201C冰\u201D字上的两滴水擦掉","promptEnd":"城市套路深，我要回农村"},{"name":"第25关","gameLevel":25,"prompt":"点击题目中的\u201C你\u201D","promptEnd":"在我心中你永远最帅~嘻嘻嘻"},{"name":"第26关","gameLevel":26,"prompt":"0分钟","promptEnd":"认真看图啊兄dei~小明都在六楼了"},{"name":"第27关","gameLevel":27,"prompt":"把钥匙数的钥匙图片移到门的钥匙孔上","promptEnd":"开门肯定需要钥匙啊"},{"name":"第28关","gameLevel":28,"prompt":"把福字挪到门上，再把手机倒过来，福到啦~","promptEnd":"给你拜个晚年！"},{"name":"第29关","gameLevel":29,"prompt":"把题目中的梨字挪到孔融上","promptEnd":"好吧，最大的梨我吃吧"},{"name":"第30关","gameLevel":30,"prompt":"点击图片中的白雪公主","promptEnd":"世界上最美的人肯定是白雪公主啊！"},{"name":"第31关","gameLevel":31,"prompt":"肯定是小猪佩奇的右脸啊，向右滑动小猪佩奇的脸，点击它","promptEnd":"easy啦"},{"name":"第32关","gameLevel":32,"prompt":"移开箱子，点击小猪佩奇","promptEnd":"掌声送给社会人"},{"name":"第33关","gameLevel":33,"prompt":"点击皮皮虾","promptEnd":"皮皮虾我们走！"},{"name":"第34关","gameLevel":34,"prompt":"用手擦掉小纸条中内容的上半部分","promptEnd":"学霸也是套路王"},{"name":"第35关","gameLevel":35,"prompt":"输入你现在的钥匙数","promptEnd":"真的是送分题啊"},{"name":"第36关","gameLevel":36,"prompt":"把纸上的铅笔移开，1+2=3啊","promptEnd":"小学数学题啊，大兄弟"},{"name":"第37关","gameLevel":37,"prompt":"摸摸熊猫，当熊猫伸出舌头时，抓紧时间把拍照框移到熊猫处，点击拍照","promptEnd":"谁说熊猫拍不了彩色照片的"},{"name":"第38关","gameLevel":38,"prompt":"把符贴到小僵尸头上，按住铃铛，摇晃手机","promptEnd":"一招制胜"},{"name":"第39关","gameLevel":39,"prompt":"模拟太阳下山，把太阳往左边挪出屏幕","promptEnd":"狼都是晚上才行动的喔！"},{"name":"第40关","gameLevel":40,"prompt":"把纸上的\u201C无\u201D字擦掉，再狂点正下方的土地","promptEnd":"原来公告上说的都是真的"},{"name":"第41关","gameLevel":41,"prompt":"把屏幕的亮度调暗后会出现萤火虫，点击它（建议把手机自动调整亮度功能先关闭喔）","promptEnd":"白天怎么会有萤火虫"},{"name":"第42关","gameLevel":42,"prompt":"在输入框中输入等式 -1+9=0+8","promptEnd":"你的智商要爆表啦"},{"name":"第43关","gameLevel":43,"prompt":"把手机倒过来后，再往左滑动解锁","promptEnd":"兄dei你左右不分的吗？"},{"name":"第44关","gameLevel":44,"prompt":"把放大镜挪到火柴上，火柴点燃后再去点燃蜡烛","promptEnd":"放大镜对光的积聚性原理！"},{"name":"第45关","gameLevel":45,"prompt":"左边的福字；左下角的金币数量；右上角小孩手上拿着的对联；图二中下方的小鞭炮可以移动，用鞭炮点燃左上角小孩拿着的那串鞭炮","promptEnd":"论看图的重要性"},{"name":"第46关","gameLevel":46,"prompt":"把冰水挪到热水处变成温水，再移到雷明的嘴上","promptEnd":"运动完不要急着喝冰水喔~"},{"name":"第47关","gameLevel":47,"prompt":"把手指放在猪鼻子上变成斗鸡眼后，再把手机横屏放置","promptEnd":"眼睛都出现重影了"},{"name":"第48关","gameLevel":48,"prompt":"男儿膝下有黄金，狂点图中的男子","promptEnd":"花式捡钱法，一天不捡钱浑身难受！"},{"name":"第49关","gameLevel":49,"prompt":"把圣诞树上的袜子挪到床头，再把小明挪到床上，点击打开窗户","promptEnd":"wow 原来真的有圣诞老人的"},{"name":"第50关","gameLevel":50,"prompt":"0个，点击关卡50中的0","promptEnd":"树上那些都不是水果啊！"},{"name":"第51关","gameLevel":51,"prompt":"依次把小狗移到树旁，会撒尿的是真的小狗","promptEnd":"又给你科普了一下"},{"name":"第52关","gameLevel":52,"prompt":"注意，里面有两个西红柿啊","promptEnd":"苹果西红柿傻傻分不清"},{"name":"第53关","gameLevel":53,"prompt":"8个","promptEnd":"多来玩我们的挑战，可以提升你的记忆力"},{"name":"第54关","gameLevel":54,"prompt":"把左上角关卡数旁的金币挪到小女孩处","promptEnd":"您真是个好人~"},{"name":"第55关","gameLevel":55,"prompt":"把小偷的头像挪到人群的第二个人上","promptEnd":"这是最新的高科技办案手法吗？"},{"name":"第56关","gameLevel":56,"prompt":"把手机倒过来后，点击钱罐底部，打开钱罐后使劲摇晃手机直到出现金币","promptEnd":"这钱不够买游戏机啊！"},{"name":"第57关","gameLevel":57,"prompt":"把手机倒过来后，点击球","promptEnd":"一杆进洞佩服佩服"},{"name":"第58关","gameLevel":58,"prompt":"两个手指同时往两边掰开钱罐","promptEnd":"哇好多钱啊！"},{"name":"第59关","gameLevel":59,"prompt":"按手机home键退出到手机桌面后，再进入该关卡","promptEnd":"小case啊"},{"name":"第60关","gameLevel":60,"prompt":"错觉，当然是最后三个选项啊！","promptEnd":"恭喜通关！点击\u201C更多好玩\u201D，体验更精彩的游戏挑战！"}],"share_cfg":[{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-0477e643b1-3024-49ca-b85d-8c166747164c.png","content":"脑子烧坏了！群里谁智商在线的，帮我看下这个题！","id":1},{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-04a12c0365-85f5-4b79-8ee2-c8395135277e.png","content":"变态题目，了解一下？能答出来，请私聊我！","id":2},{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-04dcab1c85-485a-4b40-b403-c5ee05d4ff12.png","content":"确认过眼神！这题我不会！你会你帮我！","id":3},{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-040f23ab62-c5b1-411f-96a0-ab179b06caaa.png","content":"大神！有IQ吗？IQ高吗？帮我看看怎么答？","id":4},{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-044da55d72-906b-4208-82a5-27e30b04d5db.png","content":"这游戏，虐的人想抓狂！别怪我，我真不想分享！求解！","id":5}],"tip_ios":false}}';
       $str=json_decode($str,true);
        return $str;
    }

    public function share_url()
    {
        $str='{"code":0,"data":[{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-0477e643b1-3024-49ca-b85d-8c166747164c.png","id":1,"content":"脑子烧坏了！群里谁智商在线的，帮我看下这个题！"},{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-04a12c0365-85f5-4b79-8ee2-c8395135277e.png","id":2,"content":"变态题目，了解一下？能答出来，请私聊我！"},{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-04dcab1c85-485a-4b40-b403-c5ee05d4ff12.png","id":3,"content":"确认过眼神！这题我不会！你会你帮我！"},{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-040f23ab62-c5b1-411f-96a0-ab179b06caaa.png","id":4,"content":"大神！有IQ吗？IQ高吗？帮我看看怎么答？"},{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-044da55d72-906b-4208-82a5-27e30b04d5db.png","id":5,"content":"这游戏，虐的人想抓狂！别怪我，我真不想分享！求解！"}]}';
        $str=json_decode($str,true);
        return $str;
    }
    /**
     * 小程序formid写入
     * @param form_id 小程序formid
     * @param open_id 微信openid
     */
    //不知道为什么没有同部
    /*public function addXcxFormId() {
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
    }*/
    public function addXcxFormId()
    {
        $form_id = input('form_id');
        $open_id = input('open_id');

        if (empty($form_id) || empty($open_id) || $form_id == 'the formId is a mock one' || $form_id == 'undefined') {
            $arr=resCode(200,"SUCCESS");
            return $arr;
        }
        $arr=['form_id' => $form_id,
            'open_id' => $open_id,
            'add_time' => time()
        ];
        $data=cache("pitsgamethree_formid");
        if(empty($data)){
            $data[]=$arr;
            cache("pitsgamethree_formid",$data);
        }else if(count($data)<5000){
            array_push($data,$arr);
            cache("pitsgamethree_formid",$data);
        }else{
            Db::name('xcx_formid')->insertAll($data);
            cache("pitsgamethree_formid",null);
        }
    }
//从缓存中取
    public function cache_formid()
    {
        $data=cache("pitsgamethree_formid");
        if(!empty($data)){
            Db::name('xcx_formid')->insertAll($data);
            cache("pitsgamethree_formid",null);
        }
    }
}