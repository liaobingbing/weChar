<?php
namespace app\fortune\controller;
use think\Controller;
use app\fortune\model\Common;
class Wap extends Controller
{
    protected function _initialize()
    {
        //unset($_SESSION);
        parent::_initialize();
        $this->share_titles=array(
            "这是我见过最准的八字测算",
            "差点酿成大错，这个提醒来得太及时",
            "想知道2018年你的运势走向如何？",
            "我终于知道我为什么一直没有女朋友了",
            "难怪我一直这么穷，原来要这么做",
            "最近倒霉的很，原来是这个在捣鬼",
            "别人说算命是迷信，我迷信可我发大财了",
            "做生意遇到麻烦？八字能帮你趋吉避凶",
            "利用八字透露的天机，帮助自己掌握命运",
            "没有掌握不了的命运，只有找不准的时机",
            "你不知道自己为什么没房没钱没女朋友吗",
            "你知道什么影响了你的财运，姻缘吗？",
        );
        $this->imageUrls=array(
            'http://img.ky121.com/bazi/share.png',
            'http://img.ky121.com/bazi/share2.png',
            'http://img.ky121.com/bazi/share3.png',
        );
        /*微信浏览器进入*/
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            /*读取微信配置*/
            $this->wx_admin = array(
                'appid'=>config('APPID'),
                'appsecret'=>config('APPSECRET'),
            );
            /*微信获取用户信息*/
            // if (cache("wecha_id") == '') {
                $userInfo = $this->getUserInfo($this->wx_admin['appid'], $this->wx_admin['appsecret']);
                $this->saveWxInfo($userInfo);
            // }
                // var_dump(cache("wecha_id"));exit;
            $common =new Common();
            // var_dump($this->wx_admin);exit();
            $this->signPackage = $common->getSignPackage($this->wx_admin['appid'], $this->wx_admin['appsecret']);
            $this->assign('signPackage', $this->signPackage); //微信分享签名获取
            $this->assign('share_titles', $this->share_titles[array_rand($this->share_titles,1)]); //微信分享标题
            $this->assign('imageUrls', $this->imageUrls[array_rand($this->imageUrls,1)]); //微信分享图片
        }
            
    }

    /**
     *  添加/更新微信
     *  @param 授权过来的信息
     *  @return 成功状态
     *  @requires getUserInfo()方法
     */
    function saveWxInfo($wx_userInfo)
    {
        // var_dump($wx_userInfo);exit;
        $DB_wx_user = db('wechat_user');
        if ($wx_userInfo) {
            $wx_user_info            = $DB_wx_user->where(array('wecha_id' => cache("wecha_id")))->field('id')->find();
            $wx_id = $wx_user_info['id'];
            $wx_info['wecha_id']     = cache("wecha_id");
            if(isset($wx_userInfo['nickname'])){
                $wx_info['wecha_name']   = $wx_userInfo['nickname'];
                $wx_info['wecha_img']    = $wx_userInfo['headimgurl'];
                $wx_info['wecha_area']   = $wx_userInfo['country'] . '-' . $wx_userInfo['province'] . '-' . $wx_userInfo['city'];
                $wx_info['wecha_sex']    = $wx_userInfo['sex'];
                cache("cache_wecha",$wx_info);
            }
            if ($wx_id && $wx_info['wecha_id'] != '') {
                db('wechat_user')->where(array('id' => $wx_id))->update($wx_info);
            } else {
                $DB_wx_user->insert($wx_info);
            }
        }
    }

    /**
     * 微信授权
     *  @param string appid
     *  @param string appsecret
     *  @return array()
     */
    function getUserInfo($appid, $appsecret)
    {
        // var_dump($appsecret);exit();
        // global $_SESSION, $_GET;
        // $redirect_uri = urlencode((is_ssl() ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
        $redirect_uri = urlencode('https' . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
        $code         = input('code');
        // unset(input('code'));
        if ($code == '') {
            // redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appid&redirect_uri=$redirect_uri&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect");
            // $weixin_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.$redirect_uri.'&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect';
            $weixin_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.$redirect_uri.'&response_type=code&scope=snsapi_base&state=1#wechat_redirect';
            $this->redirect($weixin_url);
        }
        $access_token_url     = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$appsecret.'&code='.$code.'&grant_type=authorization_code';
        $access_token_json    = PHPPOST($access_token_url); //获取openid
        $access_token_array   = json_decode($access_token_json, true);
        $access_token         = $access_token_array['access_token'];
        // var_dump($access_token);exit();
        $openid = $access_token_array['openid'];
        cache("wecha_id",$openid);
        $userinfo_url         = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
        $userinfo_json        = PHPPOST($userinfo_url); //获取用户详情星系

        $userinfo_array = json_decode($userinfo_json, true);
        /**
         * @return array
         *唯一标识 openid
         *微信昵称 nickname
         *头像 headimgurl
         *国家 country
         *省份 province
         *城市 city
         *男 sex==1
         *女 sex==2
         */
        return $userinfo_array;
    }

}