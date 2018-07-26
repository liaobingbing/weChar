<?php
/**
 * Created by sub.
 * User: wei
 * Date: 2018/6/7
 * Time: 10:01
 */

namespace app\fortune\controller;
use app\fortune\controller\Wap;
use app\fortune\model\User;
use think\Controller;
class Api extends Wap
{
    public function test()
    {
        $userInfo['month'] = '7月';
        $month=db('month')->where(array('month'=>array('like','%'.$userInfo['month'].'%')))->whereOr(array('new_month'=>array('like','%'.$userInfo['month'].'%')))->find();//月事业成就
        // $month=db('month')->where('month',$userInfo['month'])->whereOr('new_month',$userInfo['month'])->find();//月事业成就
        // var_dump(db()->getLastSql());exit();
        var_dump($month);
    }
    // 首页
    public function index()
    { 
        $accuracy= array(
            0=>array(
                "accuracy"=>"98.83%",
                "describe"=>"本来怕自己对现在所做的事业不顺利，测算八字看到我的事业方向后发现，刚好我适合干这行便去做了，没想到后面会如此顺利，真的挺准的。",
                "id"=>1,
                "test_time"=>"1分钟",
                "user_name"=>"李**",
            ),
            1=>array(
                "accuracy"=>"97.66%",
                "describe"=>"去年运势不怎么好，想看看今年的效果怎么样，看了之后了解了今年的运势，然后有规避不好的建议，对自己今年的运势也有了相应的了解和对策，发现还挺有效果。",
                "id"=>2,
                "test_time"=>"1分钟",
                "user_name"=>"石**",
            ),
            2=>array(
                "accuracy"=>"96.22%",
                "describe"=>"我以前没有算过这个，这次算了一下，发现还挺准的，尤其是关于感情的，希望我在以后的婚姻生活中能够幸福，也希望大家能祝福我！",
                "id"=>3,
                "test_time"=>"3分钟",
                "user_name"=>"李*",
            ),
            2=>array(
                "accuracy"=>"99.89%",
                "describe"=>"上半年做生意赔的一塌糊涂，感觉整个人都废了，好在现在慢慢回转了，算了之后，发现今年的财运确实不佳，对自己今年的运势也有了相应的了解和对策，然后有规避不好的，发现还挺有效果的。",
                "id"=>4,
                "test_time"=>"5分钟",
                "user_name"=>"张**",
            ),
            3=>array(
                "accuracy"=>"96.26%",
                "describe"=>"我是个对财富很狂热的人，希望能赚很多钱给家人带来安全感，测算结果中也证实了这一点，我希望自己能很快的富有起来。",
                "id"=>5,
                "test_time"=>"6分钟",
                "user_name"=>"钟*",
            ),
            4=>array(
                "accuracy"=>"99.24%",
                "describe"=>"我再来给儿子算一下，之前给我算的结果挺准确的，我对财运不怎么看重，比较在意家人的健康，希望儿子能健健康康。",
                "id"=>6,
                "test_time"=>"8分钟",
                "user_name"=>"王**",
            ),
            5=>array(
                "accuracy"=>"97.62%",
                "describe"=>"我给正在处的对象算了一下，她的性格很温柔，而且人很开朗，希望我们可以一直走下去。",
                "id"=>7,
                "test_time"=>"8分钟",
                "user_name"=>"兰**",
            ),
            6=>array(
                "accuracy"=>"97.65%",
                "describe"=>"小儿子去年去当兵，心里一直挂念，最近给他算了一下，说他今年的事业挺好的，有贵人扶持，相信我儿子一定会干的很好的！",
                "id"=>8,
                "test_time"=>"10分钟",
                "user_name"=>"赵**",
            ),
            7=>array(
                "accuracy"=>"98.84%",
                "describe"=>"任何事情都要敢于去争取，不能等待机会从天上掉下来，把握自己的命运，没有什么是不可战胜的，测算结果和我之前的经历挺吻合的，不过我以后会更好的！",
                "id"=>9,
                "test_time"=>"16分钟",
                "user_name"=>"吴**",
            ),
            8=>array(
                "accuracy"=>"96.88%",
                "describe"=>"朋友推荐，说挺准的，我也来试一试。算之前我还问了一下朋友，算出来的结果挺丰富的，很不错。",
                "id"=>10,
                "test_time"=>"20分钟",
                "user_name"=>"钱**",
            ),
            9=>array(
                "accuracy"=>"99.62%",
                "describe"=>"大家都是给自己算的吗，我给妈妈算了一下，这些年她一个人带我很不容易，希望妈妈能永远健康。",
                "id"=>11,
                "test_time"=>"28分钟",
                "user_name"=>"丁**",
            ),
            10=>array(
                "accuracy"=>"98.98%",
                "describe"=>"每个人都有不同的境遇，我也测算过了，结果还不错，我能发大财哦！这种东西就是信其有不信其无，反正也没多少钱，来算一下又如何。",
                "id"=>12,
                "test_time"=>"30分钟",
                "user_name"=>"方**",
            ),
            11=>array(
                "accuracy"=>"98.12%",
                "describe"=>"不知道你们你们算出来的结果是怎么样的，反正我的是是挺好的，很开心，大家都可以来试一试。",
                "id"=>13,
                "test_time"=>"30分钟",
                "user_name"=>"袁*",
            ),
            12=>array(
                "accuracy"=>"99.68%",
                "describe"=>"老公之前身体一直不好，到医院也检查不出什么毛病，来算了一下，说是明年会好起来的，不知道有没有用。",
                "id"=>14,
                "test_time"=>"1小时",
                "user_name"=>"钟**",
            ),
            13=>array(
                "accuracy"=>"98.41%",
                "describe"=>"之前做建材生意赔得一塌糊涂，感觉人生无望，算了之后，发现我不适合做建材，算命结果说我比较适合做艺术方面的工作，准备去试试看。",
                "id"=>15,
                "test_time"=>"2小时",
                "user_name"=>"梁**",
            ),
            14=>array(
                "accuracy"=>"99.29%",
                "describe"=>"任何事情都要敢于去争取，不能等待机会从天上掉下来，把握自己的命运，没有什么是不可战胜的，测算结果和我之前的经历挺吻合的，不过我以后会更好的！",
                "id"=>16,
                "test_time"=>"2小时",
                "user_name"=>"何**",
            ),
            15=>array(
                "accuracy"=>"98.88%",
                "describe"=>"朋友给推荐给我的，觉得挺有趣，就算了一下，测算结果分析我的性格很直，确实是这样的，还提到了我的财运，我觉得都挺准的。",
                "id"=>17,
                "test_time"=>"2小时",
                "user_name"=>"白*",
            )
        );
        $hourArray = array(
            // '点击选择你的时辰',
            '时辰不清楚',
            '子时（23:00-00:59）',
            '丑时（01:00-02:59）',
            '寅时（03:00-04:59）',
            '卯时（05:00-06:59）',
            '辰时（07:00-08:59）',
            '己时（09:00-10:59）',
            '午时（11:00-12:59）',
            '未时（13:00-14:59）',
            '申时（15:00-16:59）',
            '酉时（17:00-18:59）',
            '戊时（19:00-20:59）',
            '亥时（21:00-22:59）',
        );
        // 获取路径
        $url = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"];
        $url = dirname($url);
        // 获取随机数
        $play_person_count = rand(100000,999999);
        $this->assign('url',$url);
        $this->assign('title','生辰八字测算');
        $this->assign('accuracy',$accuracy);
        $this->assign('hourArray',$hourArray);
        $this->assign('play_person_count',$play_person_count);
        return view("index",[],["__PUBLIC__"=>config('__PUBLIC__')]);
    }

    // 
    public function myCalculate()
    {
        $wechat_id=cache("wecha_id");
        $sql=db("game")->where(array('wechat_id'=>$wechat_id,'pay_status'=>1))->fetchSql(true)->column('user_name,id');
        $result=db()->query($sql);
        if(empty($result)){
            $no_list = true;
        }else{
            $no_list = false;
        }
        // 获取路径
        $url = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"];
        $url = dirname($url);
        $this->assign('url',$url);
        $this->assign('no_list',false);
        $this->assign('history_list',$result);
        $this->assign('title','生辰八字测算');
        return view('myCalculate',[],["__PUBLIC__"=>config('__PUBLIC__')]);
    }

    public function calculateConfirm()
    {
        $hourArray = array(
            // '点击选择你的时辰',
            '时辰不清楚',
            '子时（23:00-00:59）',
            '丑时（01:00-02:59）',
            '寅时（03:00-04:59）',
            '卯时（05:00-06:59）',
            '辰时（07:00-08:59）',
            '己时（09:00-10:59）',
            '午时（11:00-12:59）',
            '未时（13:00-14:59）',
            '申时（15:00-16:59）',
            '酉时（17:00-18:59）',
            '戊时（19:00-20:59）',
            '亥时（21:00-22:59）',
        );
        if($_REQUEST){
            // var_dump($_REQUEST);exit;
            // if($_REQUEST['shicheng'] == ''){
            //     $birthhour = $hourArray[0];
            // }else{
            //     $birthhour = $hourArray[$_REQUEST['shicheng']];
            // }
            $birthhourText = $hourArray[$_REQUEST['birthhour']];
            $this->assign('user_name',$_REQUEST['user_name']);
            $this->assign('birthday',$_REQUEST['birthday']);
            $this->assign('year',$_REQUEST['year']);
            $this->assign('month',$_REQUEST['month']);
            $this->assign('day',$_REQUEST['day']);
            $this->assign('birthhour',$_REQUEST['birthhour']);
            $this->assign('birthhourText',$birthhourText);
            $this->assign('sex',$_REQUEST['sex']);
        }
        // 获取路径
        $url = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"];
        $url = dirname($url);
        // 获取随机数
        $play_person_count = rand(100000,999999);
        $accuracy = (rand(0,1)/100*3+96.88).'%';
        $this->assign('play_person_count',$play_person_count);
        $this->assign('accuracy',$accuracy);
        $this->assign('title','生辰八字测算');
        $this->assign('url',$url);
        return view('calculateConfirm',[],["__PUBLIC__"=>config('__PUBLIC__')]);
    }

    public function calculateResult()
    {
        if(isset($_REQUEST['id'])){
            $id=$_REQUEST['id'];
            $res=db("game")->where("id",$id)->find();
            $res['result']=json_decode($res['result'],true);
            $userInfo = $res;
            $userInfo['birthday'] = $userInfo['year'].$userInfo['month'].$userInfo['day'];
            $userInfo['birthhourText'] = $userInfo['hour'];
            // 获取卡牌文字
            $day=$res['day'];
            $minl=db('day')->where('day',$day)->whereOr("new_day",$day)->value('chart');//八字命理
            $this->assign('card_content',json_encode($minl));
            $this->assign('userInfo',$userInfo);
            $this->assign('result',$res['result']);
        }
        if(isset($_REQUEST['payid'])){
            $save_data = [
                'pay_status'=>1,
                'pay_time'  =>time()
            ];
            $arr = db('game')->where('id',$_REQUEST['payid'])->update($save_data);
            $userInfo = db('game')->where('id',$_REQUEST['payid'])->find();
            $userInfo['birthday'] = $userInfo['year'].$userInfo['month'].$userInfo['day'];
            $userInfo['birthhourText'] = $userInfo['hour'];
            $minl=db('day')->where('day',$userInfo['day'])->whereOr("new_day",$userInfo['day'])->value('chart');//八字命理
            $bzi=db('year')->where('new_calendar',$userInfo['year'])->whereOr("lunar_calendar",$userInfo['year'])->find();//年测试 
            $month=db('month')->where(array('month'=>array('like','%'.$userInfo['month'].'%')))->whereOr(array('new_month'=>array('like','%'.$userInfo['month'].'%')))->find();//月事业成就
            $hour=db('hour')->where(array('hour'=>array('like',$userInfo['hour'])))->find();//时间 婚姻职业避凶
            $arr=[array("type"=>"1、八字命盘","score"=>get_rand(),"content"=>$minl),
                array("type"=>"2、事业成就","score"=>get_rand(),"content"=>$month['achievement']),
                array("type"=>"3、婚姻家庭","score"=>get_rand(),"content"=>$hour['marriage']),
                array("type"=>"4、适合职业","score"=>get_rand(),"content"=>$hour['occupation']),
                array("type"=>"5、避凶之年","score"=>get_rand(),"content"=>$hour['avoid']),
                array("type"=>"6、性格分析","score"=>get_rand(),"content"=>$bzi['features']),
                array("type"=>"7、财运荣富","score"=>get_rand(),"content"=>$bzi['wealth']),
                array("type"=>"8、健康养生","score"=>get_rand(),"content"=>$bzi['health']),
            ];

            $this->assign('card_content',json_encode($minl));
            $this->assign('userInfo',$userInfo);
            $this->assign('result',$arr);
        }
        // 获取路径
        $url = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"];
        $url = dirname($url);
        // echo dirname($url);exit();
        $this->assign('title','生辰八字测算');
        $this->assign('url',$url);
        return view('calculateResult',[],["__PUBLIC__"=>config('__PUBLIC__')]);
    }

    public function calculateResults()
    {
        if(isset($_REQUEST['id'])){
            $id=$_REQUEST['id'];
            $res=db("game")->where("id",$id)->find();
            $res['result']=json_decode($res['result'],true);
            $userInfo = $res;
            $userInfo['birthday'] = $userInfo['year'].$userInfo['month'].$userInfo['day'];
            $userInfo['birthhourText'] = $userInfo['hour'];
            // 获取卡牌文字
            $day=$res['day'];
            $minl=db('day')->where('day',$day)->whereOr("new_day",$day)->value('chart');//八字命理
            $this->assign('card_content',json_encode($minl));
            $this->assign('userInfo',$userInfo);
            $this->assign('result',$res['result']);
        }else{
            if($_REQUEST){
                // var_dump($_REQUEST);exit;
                $userdao =new User();
                $arr = array(
                    'wechat_id'=>cache('wecha_id'),
                    'user_name'=>$_REQUEST['user_name'],
                    'birthday'=>$_REQUEST['birthday'],
                    'birthhourText'=>$_REQUEST['birthhourText'],
                    'hour'=>$_REQUEST['birthhour'],
                    'year'=>$_REQUEST['year'],
                    'month'=>$_REQUEST['month'],
                    'day'=>$_REQUEST['day'],
                    'sex'=>$_REQUEST['sex']
                );
                // $arr = $_REQUEST['userInfo'];
                $result = $userdao->get_result($arr); 
                // 获取卡牌文字
                $day=$_REQUEST['day'];
                $minl=db('day')->where('day',$day)->whereOr("new_day",$day)->value('chart');//八字命理
                $this->assign('card_content',json_encode($minl));
                $this->assign('userInfo',$arr);
                $this->assign('result',$result['data']);
            }
        }
        // 获取路径
        $url = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"];
        $url = dirname($url);
        // echo dirname($url);exit();
        $this->assign('title','生辰八字测算');
        $this->assign('url',$url);
        return view('calculateResult',[],["__PUBLIC__"=>config('__PUBLIC__')]);
    }

    // 微信支付
    public function wxPay()
    {
        if($_REQUEST){
            // var_dump($_REQUEST);exit;
            // var_dump($_REQUEST);exit;
            $userdao =new User();
            $arr = array(
                'wechat_id'=>cache('wecha_id'),
                'user_name'=>$_REQUEST['user_name'],
                'birthday'=>$_REQUEST['birthday'],
                'birthhourText'=>$_REQUEST['birthhourText'],
                'hour'=>$_REQUEST['birthhour'],
                'year'=>$_REQUEST['year'],
                'month'=>$_REQUEST['month'],
                'day'=>$_REQUEST['day'],
                'sex'=>$_REQUEST['sex']
            );
            // $arr = $_REQUEST['userInfo'];
            $result = $userdao->get_result($arr); 
            $this->assign('payid',$result['msg']['id']);
            // 获取卡牌文字
            // $day=$_REQUEST['day'];
            // $minl=db('day')->where('day',$day)->whereOr("new_day",$day)->value('chart');//八字命理
            // $this->assign('card_content',json_encode($minl));
            // $this->assign('userInfo',$arr);
            // $this->assign('result',$result['data']);
        }
        $openId = cache('wecha_id');
        $free   = 1;
        $wxPayService = new WxPayService();
        // 异步回调地址
        $jsApiParameters = $wxPayService->wxpay($openId,$free);
        $this->assign('jsApiParameters',json_encode($jsApiParameters));
        return view('wx_pay',[],["__PUBLIC__"=>config('__PUBLIC__')]);
    }

    // 回调地址
    public function pay_return()
    {

        // if(isset($_REQUEST['payid'])){
        //     db('game')->where('id',$payid)->update();
        // }
    }

    /*
     * 支付接口
     * 
     *
     */
    public function aply()
    {
        header('Content-type:text/html; Charset=utf-8');
        $headers = array();
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $headers[] = 'Connection: Keep-Alive';
        $headers[] = 'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3';
        $headers[] = 'Accept-Encoding: gzip, deflate';
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:22.0) Gecko/20100101 Firefox/22.0';
        /** 请填写以下配置信息 */
        $mchid = '1508324821';          //微信支付商户号 PartnerID 通过微信支付商户资料审核后邮件发送
        $appid = 'wxc6ef70525489d95e';  //微信支付申请对应的公众号的APPID
        $apiKey = 'aRgzGh476ITcC2Cu6afn6FC2vHYIzO6O';   //https://pay.weixin.qq.com 帐户设置-安全设置-API安全-API密钥-设置API密钥
        $outTradeNo = uniqid();     //你自己的商品订单号
        $payAmount = 0.01;          //付款金额，单位:元
        $orderName = '快鱼支付商城';    //订单标题
        $notifyUrl = 'https://applets.ky311.com/fortune/Notify/index';     //付款成功后的回调地址(不要有问号)
        $wapUrl = 'https://applets.ky311.com/fortune/Api/calculateConfirm';   //WAP网站URL地址
        $wapName = 'H5支付';       //WAP 网站名
        /** 配置结束 */

        $wxPay = new WeixinPay($mchid,$appid,$apiKey);
        $wxPay->setTotalFee($payAmount);
        $wxPay->setOutTradeNo($outTradeNo);
        $wxPay->setOrderName($orderName);
        $wxPay->setNotifyUrl($notifyUrl);
        $wxPay->setWapUrl($wapUrl);
        $wxPay->setWapName($wapName);

        $mwebUrl= $wxPay->createJsBizPackage();
        $mwebUrl=(array)$mwebUrl['data'][0];
        //print_r($mwebUrl);die;
       $url=$mwebUrl[0];
        $redirect_url = urlencode($wapUrl);
        $url=$url."&redirect_url="."$redirect_url";
       // print_r($redirect_url);die;
        //$HTTP_REFERER = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '1234';
        //print_r($HTTP_REFERER);
        echo "<h1><a href='{$url}'>点击跳转至支付页面</a></h1>";
    }
        function http_post($url='',$timeout=5) {
            $headers = array();
            $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
            $headers[] = 'Connection: Keep-Alive';
            $headers[] = 'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3';
            $headers[] = 'Accept-Encoding: gzip, deflate';
            $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:22.0) Gecko/20100101 Firefox/22.0';
            $headers[] = 'Referer: https://applets.ky311.com';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

            $response = curl_exec($ch);

            curl_close($ch);

            return $response;
        }

}