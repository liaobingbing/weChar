<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/6
 * Time: 9:17
 */

namespace app\pitsgame\controller;


use app\pitsgame\model\User;
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
        $openId=input("openId");
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
        $str='{"code":0,"data":{"ads":[{"sub_title":null,"is_active":true,"gender":0,"create_time":"2018-05-23 16:28:49","src":"http://cdn.shaonao.17fengguo.com/ads/rxdgl.jpeg","index":88,"type":0,"title":"高手大灌篮","path":"pages/main/main?navigateto=gsdgl","update_time":"2018-05-23 16:28:49","group_id":0,"appid":"wx845a2f34af2f4235","id":42,"desc":"高手大灌篮"},{"sub_title":null,"is_active":true,"gender":0,"create_time":"2018-05-23 16:28:49","src":"http://cdn.shaonao.17fengguo.com/ads/lxwz.jpeg","index":10,"type":0,"title":"连线王者","path":"pages/main/main?navigateto=lxwz","update_time":"2018-05-23 16:28:49","group_id":0,"appid":"wx845a2f34af2f4235","id":40,"desc":"连线王者"}],"pay_ios":false,"tip_android":true,"my_ads_percent":0.8,"carousel_time":10,"force_share":true,"goods":[{"update_time":"2018-03-23 20:53:56","is_active":true,"create_time":"2018-03-23 20:53:56","price":2.0,"name":"2元1把钥匙","remark":"","id":19,"real_price":200,"key_num":1},{"update_time":"2018-03-23 20:54:05","is_active":true,"create_time":"2018-03-23 20:54:05","price":6.0,"name":"6.0元5把钥匙","remark":null,"id":20,"real_price":600,"key_num":5},{"update_time":"2018-03-23 20:54:14","is_active":true,"create_time":"2018-03-23 20:54:14","price":10.0,"name":"10.0元10把钥匙","remark":null,"id":21,"real_price":1000,"key_num":10},{"update_time":"2018-03-23 20:54:23","is_active":true,"create_time":"2018-03-23 20:54:23","price":20.0,"name":"20.0元20把钥匙","remark":null,"id":22,"real_price":2000,"key_num":20}],"pay_android":true,"lv_prompt":[{"name":"第1关","gameLevel":1,"prompt":"移动小河到小马的左边！","promptEnd":"So easy~"},{"name":"第2关","gameLevel":2,"prompt":"移动题目中的\u201C大公鸡\u201D到烤箱里","promptEnd":"大吉大利，今晚吃鸡"},{"name":"第3关","gameLevel":3,"prompt":"\u201C黑眼圈\u201D大熊猫","promptEnd":"辛苦了，今晚早点休息喔。"},{"name":"第4关","gameLevel":4,"prompt":"题目上还有一个球，一共7个","promptEnd":"忘记我们的套路了吗？哈哈哈"},{"name":"第5关","gameLevel":5,"prompt":"将题目上的\u201C禁止吸烟\u201D直接拉动到红色圈里","promptEnd":"吸烟有害健康"},{"name":"第6关","gameLevel":6,"prompt":"\u201C图穷匕见\u201D，\u201C图\u201D字你得仔细找找！","promptEnd":"图穷匕见：比喻事情发展到最后，真相或本意显露了出来。"},{"name":"第7关","gameLevel":7,"prompt":"试一试拖动蝙蝠","promptEnd":"眼见不一定为实哦！"},{"name":"第8关","gameLevel":8,"prompt":"找找哪里有\u201C足球\u201D。","promptEnd":"论仔细审题的重要性~"},{"name":"第9关","gameLevel":9,"prompt":"饭碗不能吃","promptEnd":"要把饭碗保住啊，兄dei"},{"name":"第10关","gameLevel":10,"prompt":"点击人的右手","promptEnd":"和左手最像的当然是他的右手啦。"},{"name":"第11关","gameLevel":11,"prompt":"把手机倒过来就可以看到Tony的笑脸啦","promptEnd":"高兴就是那么简单！"},{"name":"第12关","gameLevel":12,"prompt":"用手指用力把瓶子上的污渍擦掉","promptEnd":"我表示，这些我都喝过"},{"name":"第13关","gameLevel":13,"prompt":"我可没有说一个数字不可以点两次，1+1+8等于10！","promptEnd":"是不是又研究了半天标题，嘿嘿！"},{"name":"第14关","gameLevel":14,"prompt":"认真看看下面哪个物品与题目中的\u201C我们\u201D最远。","promptEnd":"当然是灯泡离\u201C我们\u201D最远啦"},{"name":"第15关","gameLevel":15,"prompt":"伸出你的两根手指按在两支蜡烛上，就能愉快地庆祝生日啦","promptEnd":"生日快乐！朋友"},{"name":"第16关","gameLevel":16,"prompt":"使劲摇晃你的手机，小心，别湿身~","promptEnd":"以后喝可乐小心点，别使劲摇晃"},{"name":"第17关","gameLevel":17,"prompt":"点击开始，按住小球拖到题目\u201C终点\u201D文字的地方即可过关。","promptEnd":"又被我套路了吧，哈哈哈！"},{"name":"第18关","gameLevel":18,"prompt":"重要事情说三遍，水果水果水果~ 草莓-梨子-西瓜","promptEnd":"科普一下，南瓜可不是水果喔~"},{"name":"第19关","gameLevel":19,"prompt":"当然是太阳最亮啊，只是晚上我们看不到而已","promptEnd":"眼见不一定为实啊，朋友"},{"name":"第20关","gameLevel":20,"prompt":"把手机倒过来看看，86-**-88-89-90-91，找到规律了吧。","promptEnd":"是不是比小学课本里面的找规律要简单呢"},{"name":"第21关","gameLevel":21,"prompt":"点击图中的大象即可顺利过关！","promptEnd":"根据近大远小的原理，那么大的大象在图中却非常小！"},{"name":"第22关","gameLevel":22,"prompt":"点击头发，再点击题目上的\u201C小丸子\u201D就能连起来了~","promptEnd":"小丸子可不是光头的！"},{"name":"第23关","gameLevel":23,"prompt":"试一下用云朵把太阳遮住。","promptEnd":"包拯额头上的月亮可是会发光的喔"},{"name":"第24关","gameLevel":24,"prompt":"1=5，那么5肯定等于1啊","promptEnd":"又不认真看题了吧"},{"name":"第25关","gameLevel":25,"prompt":"连续点击完整的苹果5次","promptEnd":"我是要5个完整的苹果！！！"},{"name":"第26关","gameLevel":26,"prompt":"最前面的\u2014\u2014是可以向上拉动的，-99就是最小的。","promptEnd":"就喜欢看到你惊恐的样子"},{"name":"第27关","gameLevel":27,"prompt":"最大的数字是999啊","promptEnd":"送分题啊，大兄弟"},{"name":"第28关","gameLevel":28,"prompt":"把相同的字母约去，2sinxcotx/2ncotx=six，six就是6啦。","promptEnd":"6到起飞！"},{"name":"第29关","gameLevel":29,"prompt":"摇晃手机，加上掉下来的苹果，一共有6个","promptEnd":"套路，套路，一切都是套路"},{"name":"第30关","gameLevel":30,"prompt":"把下面的星星都拉动到题目上的\u2018星星\u2019二字上。","promptEnd":"\u201C星星\u201D也是星星"},{"name":"第31关","gameLevel":31,"prompt":"9个","promptEnd":"新三年旧三年，缝缝补补又三年"},{"name":"第32关","gameLevel":32,"prompt":"点击图上的\u201CX\u201D","promptEnd":"看图也是很重要的。"},{"name":"第33关","gameLevel":33,"prompt":"认真审题，请输入\u201C答案\u201D","promptEnd":"又是送分题啊~"},{"name":"第34关","gameLevel":34,"prompt":"旋转手机方向，使箭头指向左右。","promptEnd":"小case啦"},{"name":"第35关","gameLevel":35,"prompt":"向左慢慢倾斜你的手机","promptEnd":"嘀~学生卡。"},{"name":"第36关","gameLevel":36,"prompt":"把云朵移到同一位置，下雨后狮子就溜了，移动小明安全回家。","promptEnd":"出入注意安全，大兄dei。"},{"name":"第37关","gameLevel":37,"prompt":"把手机倒过来，点击从水桶中倒出来的水。","promptEnd":"So easy~"},{"name":"第38关","gameLevel":38,"prompt":"屏幕向下盖住。","promptEnd":"没有阳光了太阳能灯泡肯定关闭啦。"},{"name":"第39关","gameLevel":39,"prompt":"用手拨开乌云，雨停后移动小明回家。","promptEnd":"以后出门记得带伞，下次我可帮不了你。"},{"name":"第40关","gameLevel":40,"prompt":"想想前面关卡，哪个水桶装了水。","promptEnd":"因为这只水桶装了水啊。"},{"name":"第41关","gameLevel":41,"prompt":"鸟也是动物，所以先依次点6只动物，再依次点3只鸟！","promptEnd":"什么？这么简单都不会？"},{"name":"第42关","gameLevel":42,"prompt":"长按绿按钮，再仔细审题。","promptEnd":"认真审题很重要！"},{"name":"第43关","gameLevel":43,"prompt":"移走前轮胎，换上新的轮胎。","promptEnd":"是\u201C换\u201D轮胎，亲。"},{"name":"第44关","gameLevel":44,"prompt":"无（蜈）功（蚣）不受禄！","promptEnd":"无（蜈）功（蚣）不受禄！"},{"name":"第45关","gameLevel":45,"prompt":"贵妃醉酒被改编成歌了","promptEnd":"不要被美女扰乱了思路。"},{"name":"第46关","gameLevel":46,"prompt":"猪（蛛）思（丝）马（马）鸡（迹）","promptEnd":"猪（蛛）思（丝）马（马）鸡（迹）"},{"name":"第47关","gameLevel":47,"prompt":"减轻孙悟空身上的重量就能救他了。","promptEnd":"孙悟空去取西经咯~"},{"name":"第48关","gameLevel":48,"prompt":"再认真看看白色多还是黄色多？","promptEnd":"又是一道送分题"},{"name":"第49关","gameLevel":49,"prompt":"注意图中望远镜和水的数量变化","promptEnd":"有图有真相"},{"name":"第50关","gameLevel":50,"prompt":"先把命令切换到下一关，再同时按下两个按钮喔~","promptEnd":"请严格执行指令！"},{"name":"第51关","gameLevel":51,"prompt":"用头砸","promptEnd":"这是一个很严谨的挑战~"},{"name":"第52关","gameLevel":52,"prompt":"长按关机键。","promptEnd":"生活常识啊，兄弟"},{"name":"第53关","gameLevel":53,"prompt":"悬崖乐（勒）马","promptEnd":"悬崖勒马比喻到了危险的边缘及时清醒回头。（不是站在悬崖上笑马）"},{"name":"第54关","gameLevel":54,"prompt":"把断的火柴移到第一根火柴末端上，组成最长的火柴。","promptEnd":"easy 啦！"},{"name":"第55关","gameLevel":55,"prompt":"把\u201C水果\u201D移到\u201C我\u201D上。","promptEnd":"还不了解我的套路吗。"},{"name":"第56关","gameLevel":56,"prompt":"点击\u201C它\u201D","promptEnd":"别告诉我，你点击了按钮250下。"},{"name":"第57关","gameLevel":57,"prompt":"把车的前轮和破的后轮对换。","promptEnd":"我可没说不能对换啊。"},{"name":"第58关","gameLevel":58,"prompt":"1一1，把前面的1移到一上面，把后面的1移到一下面，于是就是一分之一等于一","promptEnd":"我又没说只能移动一根火柴。"},{"name":"第59关","gameLevel":59,"prompt":"倒过来的大象是9，3+9=12","promptEnd":"so easy"},{"name":"第60关","gameLevel":60,"prompt":"努力回想一下，前面关卡是不是有一个类似的按钮，按钮上面的数字是多少呢","promptEnd":"新关卡正在努力开发中，请期待我继续套路你~"}],"share_cfg":[{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-0477e643b1-3024-49ca-b85d-8c166747164c.png","content":"脑子烧坏了！群里谁智商在线的，帮我看下这个题！","id":1},{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-04a12c0365-85f5-4b79-8ee2-c8395135277e.png","content":"变态题目，了解一下？能答出来，请私聊我！","id":2},{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-04dcab1c85-485a-4b40-b403-c5ee05d4ff12.png","content":"确认过眼神！这题我不会！你会你帮我！","id":3},{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-040f23ab62-c5b1-411f-96a0-ab179b06caaa.png","content":"大神！有IQ吗？IQ高吗？帮我看看怎么答？","id":4},{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-044da55d72-906b-4208-82a5-27e30b04d5db.png","content":"这游戏，虐的人想抓狂！别怪我，我真不想分享！求解！","id":5}],"tip_ios":false}}';
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
        $data=cache("pitsgame_formid");
        if(empty($data)){
            $data[]=$arr;
            cache("pitsgame_formid",$data);
        }else if(count($data)<5000){
            array_push($data,$arr);
            cache("pitsgame_formid",$data);
        }else{
            Db::name('xcx_formid')->insertAll($data);
            cache("pitsgame_formid",null);
        }
    }
//从缓存中取
    public function cache_formid()
    {
        $data=cache("pitsgame_formid");
        if(!empty($data)){
            Db::name('xcx_formid')->insertAll($data);
            cache("pitsgame_formid",null);
        }
    }
}