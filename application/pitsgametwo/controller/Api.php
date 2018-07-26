<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/6
 * Time: 9:17
 */

namespace app\pitsgametwo\controller;


use app\pitsgametwo\model\User;
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
        $str='{"code":0,"data":{"ads":[{"sub_title":null,"is_active":true,"gender":0,"create_time":"2018-06-23 19:18:50","src":"http://cdn.shaonao.17fengguo.com/ads/2018-06-2333e9db72-51a0-44ae-a702-de47e20a2250.jpeg","index":14,"type":0,"title":null,"path":"pages/main/main?navigateto=lammzyq","update_time":"2018-06-23 19:18:50","group_id":0,"appid":"wx845a2f34af2f4235","id":44,"desc":"恋爱喵喵"},{"sub_title":null,"is_active":true,"gender":0,"create_time":"2018-06-23 18:50:18","src":"http://cdn.shaonao.17fengguo.com/ads/2018-06-2384f6dbc7-dbcf-4a12-a188-ab6231995478.jpeg","index":13,"type":0,"title":null,"path":"pages/index/index","update_time":"2018-06-23 18:50:18","group_id":0,"appid":"wx38f33e0d58498c27","id":43,"desc":"最囧第三季"},{"sub_title":null,"is_active":true,"gender":0,"create_time":"2018-05-28 18:58:34","src":"http://cdn.shaonao.17fengguo.com/ads/2018-05-28f43c93a8-2bfc-474c-91a4-8e56e6cd9215.jpeg","index":11,"type":0,"title":null,"path":"pages/main/main?navigateto=gsdgl","update_time":"2018-05-28 18:58:34","group_id":0,"appid":"wx845a2f34af2f4235","id":41,"desc":"高手大灌篮"},{"sub_title":null,"is_active":true,"gender":0,"create_time":"2018-05-23 16:28:49","src":"http://cdn.shaonao.17fengguo.com/ads/lxwz.jpeg","index":10,"type":0,"title":"连线王者","path":"pages/main/main?navigateto=lxwz","update_time":"2018-05-23 16:28:49","group_id":0,"appid":"wx845a2f34af2f4235","id":40,"desc":"连线王者"}],"pay_ios":true,"tip_android":true,"my_ads_percent":0.8,"carousel_time":10,"force_share":true,"goods":[{"update_time":"2018-03-23 20:53:56","is_active":true,"create_time":"2018-03-23 20:53:56","price":2.0,"name":"2元1把钥匙","remark":"","id":19,"real_price":200,"key_num":1},{"update_time":"2018-03-23 20:54:05","is_active":true,"create_time":"2018-03-23 20:54:05","price":6.0,"name":"6.0元5把钥匙","remark":null,"id":20,"real_price":600,"key_num":5},{"update_time":"2018-03-23 20:54:14","is_active":true,"create_time":"2018-03-23 20:54:14","price":10.0,"name":"10.0元10把钥匙","remark":null,"id":21,"real_price":1000,"key_num":10},{"update_time":"2018-03-23 20:54:23","is_active":true,"create_time":"2018-03-23 20:54:23","price":20.0,"name":"20.0元20把钥匙","remark":null,"id":22,"real_price":2000,"key_num":20}],"pay_android":true,"lv_prompt":[{"name":"第1关","gameLevel":1,"prompt":"关键时刻牺牲一个手指吧，按住小恐龙们就能发现那只萌萌哒的食肉兽了。","promptEnd":"没见过那么萌的食肉恐龙吧"},{"name":"第2关","gameLevel":2,"prompt":"用手指狂擦掉所有白色油漆条，和强迫症说再见。","promptEnd":"强迫症？我当没看见。"},{"name":"第3关","gameLevel":3,"prompt":"冰箱那么小，真的能把大象装进去吗？放大冰箱后再试试","promptEnd":"仿佛看到越来越pang的自己，哭唧唧"},{"name":"第4关","gameLevel":4,"prompt":"用钱蒙蔽住双眼","promptEnd":"我愿意被蒙蔽一辈子啊！！！"},{"name":"第5关","gameLevel":5,"prompt":"542B","promptEnd":"请大声喊出 542B"},{"name":"第6关","gameLevel":6,"prompt":"把第二个空白的框移到题目\u201C停车位\u201D上组成真正的停车位后，再把车移进去。","promptEnd":"考验老司机停车技术的时候到了。"},{"name":"第7关","gameLevel":7,"prompt":"长按关机键。","promptEnd":"生活常识啊，兄弟"},{"name":"第8关","gameLevel":8,"prompt":"当然是靠嘴吃饭啊","promptEnd":"这题不算套路你了吧"},{"name":"第9关","gameLevel":9,"prompt":"狂点电脑屏幕啊","promptEnd":"赔钱！你把小明的电脑弄坏了。"},{"name":"第10关","gameLevel":10,"prompt":"审题，是\u201C输入\u201D，不是让你选择下面的字","promptEnd":"有不有趣，开不开心~"},{"name":"第11关","gameLevel":11,"prompt":"把箭头的前半部分移出屏幕","promptEnd":"是不是太简单了？继续挑战啊，不信你全秒过。"},{"name":"第12关","gameLevel":12,"prompt":"捂住小猪的鼻子","promptEnd":"皮这一下好玩吗，小猪也有起床气的。"},{"name":"第13关","gameLevel":13,"prompt":"把题目中的\u201C百\u201D字移到A选项中，组合成1百，再点击它","promptEnd":"兄dei，这是百位数学题啊。"},{"name":"第14关","gameLevel":14,"prompt":"来回滑动火柴，火柴燃起后再点燃蜡烛","promptEnd":"我们是一个很有生活常识的挑战"},{"name":"第15关","gameLevel":15,"prompt":"注意，第五个\u201C是\u201D字将出现在倒计时结束后的提示框中，认真找找喔~","promptEnd":"年轻人不要轻易放弃啊"},{"name":"第16关","gameLevel":16,"prompt":"9个","promptEnd":"新三年旧三年，缝缝补补又三年"},{"name":"第17关","gameLevel":17,"prompt":"把\u201C水果\u201D移到\u201C我\u201D上。","promptEnd":"还不了解我的套路吗。"},{"name":"第18关","gameLevel":18,"prompt":"用手把小明（C选项）拉高","promptEnd":"吃了士X架，马上长高了"},{"name":"第19关","gameLevel":19,"prompt":"把火移到火箭尾部","promptEnd":"1，2，3，升天咯"},{"name":"第20关","gameLevel":20,"prompt":"把手机倒过来看看","promptEnd":"666翻了"},{"name":"第21关","gameLevel":21,"prompt":"拖动图片，找出真正的小明","promptEnd":"小明棒棒哒，既会写书又会打篮球"},{"name":"第22关","gameLevel":22,"prompt":"小心，留意，第三个球用手戳破","promptEnd":"This is tao lu."},{"name":"第23关","gameLevel":23,"prompt":"1=4，那么4肯定等于1啊","promptEnd":"又不认真看题了吧"},{"name":"第24关","gameLevel":24,"prompt":"前一关4等于几来着？4等于1啊笨蛋","promptEnd":"恭喜你输入正确密码，你已打败20%的玩家。"},{"name":"第25关","gameLevel":25,"prompt":"把手机倒过来，点击从水桶中倒出来的水。","promptEnd":"So easy~"},{"name":"第26关","gameLevel":26,"prompt":"-1不等于1（把第一根火柴移到等号上，等号不就变成不等号了吗）","promptEnd":"1根，只能移动1根"},{"name":"第27关","gameLevel":27,"prompt":"点击图上的\u201CX\u201D","promptEnd":"看图也是很重要的。"},{"name":"第28关","gameLevel":28,"prompt":"用图形盖住题目中的\u201C房子\u201D","promptEnd":"原来盖房子是那么简单的，不说了我去盖房子了"},{"name":"第29关","gameLevel":29,"prompt":"把右侧的\u201C7m\u201D移到板子上，世界记录变成了7m","promptEnd":"原来打破记录那么简单啊~"},{"name":"第30关","gameLevel":30,"prompt":"有一张小拼图可以长按复制喔","promptEnd":"看来你玩手机的时间不够长，复制都不懂吗？"},{"name":"第31关","gameLevel":31,"prompt":"疯狂点击左侧的世界纪录板子","promptEnd":"从此再也没人能打破我的纪录了"},{"name":"第32关","gameLevel":32,"prompt":"左上角关卡数旁边的按钮有什么不一样？点击试一下","promptEnd":"小猪佩奇，社会社会"},{"name":"第33关","gameLevel":33,"prompt":"用铅笔在纸的空白处疯狂的画画画\u2026","promptEnd":"密码怎么能那么容易让你发现"},{"name":"第34关","gameLevel":34,"prompt":"点击题目中的\u201C错\u201D字","promptEnd":"轻松过关~"},{"name":"第35关","gameLevel":35,"prompt":"题目中的\u201C鸡蛋\u201D也能移动","promptEnd":"哎呀套路不了你了"},{"name":"第36关","gameLevel":36,"prompt":"把题目中的\u201C六个数字\u201D挪到输入框中","promptEnd":"老王的老婆为什么把密码告诉了你？？？"},{"name":"第37关","gameLevel":37,"prompt":"把题目中的\u201C米\u201D字挪入锅里","promptEnd":"煮饭肯定先放米啊~"},{"name":"第38关","gameLevel":38,"prompt":"请刮开题目中的\u201C奖券\u201D","promptEnd":"来追我啊，追上就给你500w"},{"name":"第39关","gameLevel":39,"prompt":"仔细看一下蜘蛛的腿上几处不同","promptEnd":"小case啦"},{"name":"第40关","gameLevel":40,"prompt":"同时点击两个技能让技能合成","promptEnd":"哪位神人点击了1000000次，嘻嘻嘻。"},{"name":"第41关","gameLevel":41,"prompt":"把屏幕亮度调暗，制造黑夜","promptEnd":"真正的蝙蝠在晚上是会倒挂在树上睡觉的。"},{"name":"第42关","gameLevel":42,"prompt":"移动小河到小马的左边！","promptEnd":"So easy~"},{"name":"第43关","gameLevel":43,"prompt":"答案30。你再算算1+1+1+1+11+1+1+1+11+1X0+1","promptEnd":"窒息吗？后面的题目会让你刺激到无法呼吸。"},{"name":"第44关","gameLevel":44,"prompt":"同时点击两个位置，让三个棋子连成一条直线就能赢啦","promptEnd":"下次我一定凭实力赢老王，哈哈哈"},{"name":"第45关","gameLevel":45,"prompt":"贵妃醉酒被改编成歌了","promptEnd":"不要被美女扰乱了思路。"},{"name":"第46关","gameLevel":46,"prompt":"当时间停留在02：000-02：300时，把题目中的\u201C时间\u201D挪进计时器中","promptEnd":"恭喜你，时间已经停止在这一刻了~"},{"name":"第47关","gameLevel":47,"prompt":"把光圈移到小王脸上","promptEnd":"自带猪脚光环。不不不，是主角"},{"name":"第48关","gameLevel":48,"prompt":"把盾牌移到寺庙门前","promptEnd":"遁（盾）入空门"},{"name":"第49关","gameLevel":49,"prompt":"注意图中望眼镜和水的数量变化","promptEnd":"有图有真相"},{"name":"第50关","gameLevel":50,"prompt":"按住题目中的\u201C秘密\u201D直到消失","promptEnd":"是\u201C秘密\u201D不能被人发现啦"},{"name":"第51关","gameLevel":51,"prompt":"CD","promptEnd":"给你一张过去的CD，听听那时我们的真诚"},{"name":"第52关","gameLevel":52,"prompt":"摇晃手机","promptEnd":"摇啊摇，摇到外婆桥"},{"name":"第53关","gameLevel":53,"prompt":"把手机屏幕亮度调高（建议把手机自动调整亮度功能先关闭喔。如果手机亮度原是最高的，可以先调低再进行操作）","promptEnd":"以后会辨认真假币了吧"},{"name":"第54关","gameLevel":54,"prompt":"点击数字8后，全部数字将会消失。纯考记忆力（这是一个假的提示）","promptEnd":"只有记忆神人才能看到这句话，你是其中之一！"},{"name":"第55关","gameLevel":55,"prompt":"将手机倾斜，正方形也是菱形，点击它","promptEnd":"小学知识是体育老师教的吗？"},{"name":"第56关","gameLevel":56,"prompt":"尺子和人的头发摩擦产生静电后，再用尺子摩擦桌子上的头发即可拿开","promptEnd":"我表示小时候很喜欢这样玩"},{"name":"第57关","gameLevel":57,"prompt":"把车的前轮和破的后轮对换。","promptEnd":"我可没说不能对换啊。"},{"name":"第58关","gameLevel":58,"prompt":"长按图片，看我的影分身","promptEnd":"有我们陪你，不无聊了吧，嘻嘻嘻"},{"name":"第59关","gameLevel":59,"prompt":"把盘子和一颗糖同时送给其中一个小朋友","promptEnd":"排排坐，分糖果"},{"name":"第60关","gameLevel":60,"prompt":"图1-B；图2-D；图3-A；图4-C","promptEnd":"恭喜通关！点击\u201C更多好玩\u201D，体验更精彩的游戏挑战！"}],"share_cfg":[{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-0477e643b1-3024-49ca-b85d-8c166747164c.png","content":"脑子烧坏了！群里谁智商在线的，帮我看下这个题！","id":1},{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-04a12c0365-85f5-4b79-8ee2-c8395135277e.png","content":"变态题目，了解一下？能答出来，请私聊我！","id":2},{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-04dcab1c85-485a-4b40-b403-c5ee05d4ff12.png","content":"确认过眼神！这题我不会！你会你帮我！","id":3},{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-040f23ab62-c5b1-411f-96a0-ab179b06caaa.png","content":"大神！有IQ吗？IQ高吗？帮我看看怎么答？","id":4},{"src":"http://cdn.shaonao.17fengguo.com/document/2018-05-044da55d72-906b-4208-82a5-27e30b04d5db.png","content":"这游戏，虐的人想抓狂！别怪我，我真不想分享！求解！","id":5}],"tip_ios":true}}';
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
        $data=cache("pitsgametwo_formid");
        if(empty($data)){
            $data[]=$arr;
            cache("pitsgametwo_formid",$data);
        }else if(count($data)<5000){
            array_push($data,$arr);
            cache("pitsgametwo_formid",$data);
        }else{
            Db::name('xcx_formid')->insertAll($data);
            cache("pitsgametwo_formid",null);
        }
    }
//从缓存中取
    public function cache_formid()
    {
        $data=cache("pitsgametwo_formid");
        if(!empty($data)){
            Db::name('xcx_formid')->insertAll($data);
            cache("pitsgametwo_formid",null);
        }
    }
}