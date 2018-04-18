<?php

namespace Common\Util\OpenOauth;

use Common\Util\OpenOauth\Core\Core;
use Common\Util\Http\Http;

class Authorized extends Core
{
    const GET_API_QUERY_AUTH            = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth'; //使用授权码换取公众号的接口调用凭据和授权信息
    const GET_API_AUTHORIZER_TOKEN_URL  = 'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token'; //获取（刷新）授权公众号的接口调用凭据（令牌）
    const GET_API_MODIFY_DOMAIN_URL     = 'https://api.weixin.qq.com/wxa/modify_domain'; //修改服务器地址
    const GET_API_COMMIT_URL            = 'https://api.weixin.qq.com/wxa/commit'; //为授权的小程序帐号上传小程序代码
    const GET_API_GET_QRCODE_URL        = 'https://api.weixin.qq.com/wxa/get_qrcode'; //获取体验小程序的体验二维码
    const GET_API_GET_PAGE_URL          = 'https://api.weixin.qq.com/wxa/get_page'; //获取小程序的第三方提交代码的页面配置
    const GET_API_GET_CATEGORY_URL      = 'https://api.weixin.qq.com/wxa/get_category'; //获取授权小程序帐号的可选类目
    const GET_API_SUBMIT_AUDIT_URL      = 'https://api.weixin.qq.com/wxa/submit_audit'; //将第三方提交的代码包提交审核
    const GET_API_GET_LATEST_AUDITSTAUS = 'https://api.weixin.qq.com/wxa/get_latest_auditstatus'; //查询最新一次提交的审核状态
    const GET_API_GET_AUDITSTAUS        = 'https://api.weixin.qq.com/wxa/get_auditstatus'; //查询某个指定版本的审核状态
    const GET_API_RELEASE               = 'https://api.weixin.qq.com/wxa/release'; //发布已通过审核的小程序
    const GET_API_CHANGE_VISITSTATUS    = 'https://api.weixin.qq.com/wxa/change_visitstatus'; //发布已通过审核的小程序
    const GET_SUMMARYTREND              = 'https://api.weixin.qq.com/datacube/getweanalysisappiddailysummarytrend'; //概况趋势
    const GET_DAILY_VISITTREND          = 'https://api.weixin.qq.com/datacube/getweanalysisappiddailyvisittrend'; //日访问趋势
    const GET_WEEKLY_VISITTREND         = 'https://api.weixin.qq.com/datacube/getweanalysisappidweeklyvisittrend'; //周访问趋势
    const GET_MONTHLY_VISITTREND        = 'https://api.weixin.qq.com/datacube/getweanalysisappidmonthlyvisittrend'; //月访问趋势
    const GET_VISITPAGE                 = 'https://api.weixin.qq.com/datacube/getweanalysisappidvisitpage'; //访问页面
    const GET_WXOPEN_TEMPLATE_LIST      = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/list'; //获取帐号下已存在的模板列表
    const CUSTOM_SEND_URL               = 'https://api.weixin.qq.com/cgi-bin/message/custom/send';//发送客服消息


    //weanalysisappiddailysummarytrend
    /**
     * @param $redirect_path
     */
    function getAuthHTML($redirect_path)
    {
        $component_app_id = $this->configs->component_app_id;
        $pre_auth_code    = $this->getComponentPreAuthCode();

        $redirect_uri     = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $redirect_path;
        $editorSrc = <<<HTML
         <script language="JavaScript" type="text/javascript">
           window.location.href="https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=$component_app_id&pre_auth_code=$pre_auth_code&redirect_uri=$redirect_uri";
    </script>
HTML;
        exit($editorSrc);
    }


    function getAuthInfo($authorization_code = ''){
        $query_data = http_build_query(['component_access_token' => $this->getComponentAccessToken()]);
        $request_data = [
            'component_appid'    => $this->configs->component_app_id,
            'authorization_code' => $authorization_code,
        ];

        $response_data = Http::_post(self::GET_API_QUERY_AUTH . '?' . $query_data, $request_data);

        if (!$response_data) {
            $this->setError(Http::$error);

            return false;
        }
        $query_auth_key         = 'query_auth:' . $this->configs->component_app_id . ':' . $response_data['authorization_info']['authorizer_appid'];

        parent::$databaseDriver->_set($query_auth_key, $response_data);

        return $response_data;
    }


    /**
     * 使用授权码换取公众号的接口调用凭据和授权信息
     *
     * @param string $authorizer_app_id
     *
     * @return array|bool|null|string|void
     */
    function getApiQueryAuth($authorizer_app_id = '')
    {
        $time = time();

        $authorization_info_key = 'authorized:' . $this->configs->component_app_id . ':' . $authorizer_app_id;
        $query_auth_key         = 'query_auth:' . $this->configs->component_app_id . ':' . $authorizer_app_id;

        $query_auth_info = parent::$databaseDriver->_get($query_auth_key);
        $authorization_info = parent::$databaseDriver->_get($authorization_info_key);

        //如果存在数据
        if (!empty($query_auth_info)) {

            //没超时 返回数据
            if ($query_auth_info['expired_time'] >= ($time - 600) && $query_auth_info['authorization_state'] == 'authorized') {
                $query_auth_info['authorization_code'] = $authorization_info['AuthorizationCode'];
                return $query_auth_info;
            } else {
                //如果超时了 获取新的 access_token 和 新的 刷新令牌 refresh_token
                $api_authorizer_token = $this->getApiAuthorizerToken($query_auth_info['authorization_info']['authorizer_appid'], $query_auth_info['authorization_info']['authorizer_refresh_token']);
                if (!empty($api_authorizer_token)) {
                    $query_auth_info['authorization_info']['authorizer_access_token']  = $api_authorizer_token['authorizer_access_token'];
                    $query_auth_info['authorization_info']['authorizer_refresh_token'] = $api_authorizer_token['authorizer_refresh_token'];
                    $query_auth_info['authorization_info']['expires_in']               = $api_authorizer_token['expires_in'];
                    $query_auth_info['expired_time']                                   = $time + $api_authorizer_token['expires_in'];
                    $query_auth_info['authorization_state']                            = 'authorized';
                    $query_auth_info['authorization_code']                             = $authorization_info['AuthorizationCode'];

                    parent::$databaseDriver->_set($query_auth_key, $query_auth_info);
                    return $query_auth_info;
                }
            }
        }



        $query_data = http_build_query(['component_access_token' => $this->getComponentAccessToken()]);

        if ($authorization_info['AuthorizationCodeExpiredTime'] <= $time) {
            $this->setError('授权Code超时');

            return false;
        }
        $request_data = [
            'component_appid'    => $authorization_info['AppId'],
            'authorization_code' => $authorization_info['AuthorizationCode'],
        ];

        $response_data = Http::_post(self::GET_API_QUERY_AUTH . '?' . $query_data, $request_data);

        if (!$response_data) {
            $this->setError(Http::$error);

            return false;
        }
        $response_data['authorization_state'] = 'authorized';
        $response_data['expired_time']        = $time + $response_data['authorization_info']['expires_in'];
        $response_data['authorization_code'] = $authorization_info['AuthorizationCode'];


        parent::$databaseDriver->_set($query_auth_key, $response_data);

        return $response_data;
    }

    /**
     * 获取（刷新）授权公众号的接口调用凭据（令牌)
     *
     * @param  string $authorizer_app_id        公众号app_id
     * @param  string $authorizer_refresh_token 刷新TOKEN的 authorizer_refresh_token
     *
     * @return array authorization_info
     */
    function getApiAuthorizerToken($authorizer_app_id = '', $authorizer_refresh_token = '')
    {
        $query_data   = http_build_query(['component_access_token' => $this->getComponentAccessToken()]);
        $request_data = [
            'component_appid'          => $this->configs->component_app_id,
            'authorizer_appid'         => $authorizer_app_id,
            'authorizer_refresh_token' => $authorizer_refresh_token,
        ];
        $response_data = Http::_post(self::GET_API_AUTHORIZER_TOKEN_URL . '?' . $query_data, $request_data);
        if (!$response_data) {
            $this->setError(Http::$error);

            return false;
        }

        return $response_data;
    }

    /**
     * 获取授权服务号 AccessToken
     *
     * @param string $authorizer_app_id
     *
     * @return bool
     */
    public function getAuthorizerAccessToken($authorizer_app_id = '')
    {
        $query_auth_info = $this->getApiQueryAuth($authorizer_app_id);

        if (!empty($query_auth_info)) {

            if ($query_auth_info['authorization_state'] == 'authorized') {
                return $query_auth_info['authorization_info']['authorizer_access_token'];
            } else {
                $this->setError('已经取消授权的服务号:' . $query_auth_info['authorization_info']['authorizer_appid']);

                return false;
            }
        }

        return false;
    }


    /**
     * 修改服务器地址
     *
     * @param  string $access_token      小程序授权的authorizer_access_token
     * @param  array  $request_data      post参数
     */
    function ApiModifyDomain($authorizer_appid = '', $request_data =  array())
    {

        $authorizer_access_token = $this->getAuthorizerAccessToken($authorizer_appid);

        $query_data = http_build_query(['access_token' => $authorizer_access_token]);

        $response_data = Http::_post(self::GET_API_MODIFY_DOMAIN_URL . '?' . $query_data, $request_data);
        if (!$response_data) {
            $this->setError(Http::$error);

            return $this->getError();
        }


        return $response_data;
    }



    /**
     * 为授权的小程序帐号上传小程序代码
     * @param  string $access_token      小程序授权的authorizer_access_token
     * @param  array  $request_data      post参数
     */
    function ApiCommit($authorizer_appid = '', $request_data =  array())
    {

        $authorizer_access_token = $this->getAuthorizerAccessToken($authorizer_appid);
        $query_data = http_build_query(['access_token' => $authorizer_access_token]);
        $response_data = Http::_post(self::GET_API_COMMIT_URL . '?' . $query_data, $request_data);
        if (!$response_data) {
            $this->setError(Http::$error);

            return $this->getError();
        }

        return $response_data;
    }

    /**
     * 获取体验小程序的体验二维码
     * @param  string $access_token      小程序授权的authorizer_access_token
     */
    function ApiGetQrcode($authorizer_appid = '')
    {

        $authorizer_access_token = $this->getAuthorizerAccessToken($authorizer_appid);

        $query_data = http_build_query(['access_token' => $authorizer_access_token]);

        $response_data = Http::_imgGet(self::GET_API_GET_QRCODE_URL . '?' . $query_data);

        if ($response_data['status'] != 200) {
            $this->setError(Http::$error);

            return false;
        }


        return $response_data;
    }

    /**
     * 获取小程序的第三方提交代码的页面配置
     * @param  string $access_token      小程序授权的authorizer_access_token
     */
    function ApiGetPage($authorizer_appid = '')
    {


        $authorizer_access_token = $this->getAuthorizerAccessToken($authorizer_appid);

        $query_data = http_build_query(['access_token' => $authorizer_access_token]);

        $response_data = Http::_get(self::GET_API_GET_PAGE_URL . '?' . $query_data);

        if (!$response_data) {
            $this->setError(Http::$error);

            return false;
        }


        return $response_data;
    }


    /**
     * 获取授权小程序帐号的可选类目
     * @param  string $access_token      小程序授权的authorizer_access_token
     */
    function ApiGetCategory($authorizer_appid = '')
    {


        $authorizer_access_token = $this->getAuthorizerAccessToken($authorizer_appid);

        $query_data = http_build_query(['access_token' => $authorizer_access_token]);

        $response_data = Http::_get(self::GET_API_GET_CATEGORY_URL . '?' . $query_data);

        if (!$response_data) {
            $this->setError(Http::$error);

            return false;
        }


        return $response_data;
    }


    /**
     * 将第三方提交的代码包提交审核
     * @param  string $access_token                  小程序授权的authorizer_access_token
     * @param  array  $data
     * $data 参数                                     tag:标签  title:小程序页面的标题
     */
    function ApiSubmitAudit($authorizer_appid = '',$data = array())
    {

        @header("Content-Type: text/html; charset=UTF-8");
        $authorizer_access_token = $this->getAuthorizerAccessToken($authorizer_appid);

        $page = $this -> ApiGetPage($authorizer_appid);
        if(empty($page)){
            return "ApiGetPage error";
        }

        $category = $this -> ApiGetCategory($authorizer_appid);
        if(empty($category)){
            return "ApiGetCategory error";
        }
        foreach($category['category_list'] as $k => $val){
            $item_list[$k] = array(
                "address"       => $page['page_list'][0],
                "tag"           => $data['tag'],
                "first_class"   => $val['first_class'],
                "second_class"  => $val['second_class'],
                "first_id"      => $val['first_id'],
                "second_id"     => $val['second_id'],
                "title"         => $data['title']
            );
            if(isset($val['third_id'])){
                $item_list[$k]['third_class'] = $val['third_class'];
                $item_list[$k]['third_id'] = $val['third_id'];
            }
        }



        $request_data = [
            'item_list'    => $item_list
        ];
        $query_data = http_build_query(['access_token' => $authorizer_access_token]);

        $response_data = Http::_post(self::GET_API_SUBMIT_AUDIT_URL . '?' . $query_data,$request_data);

        if (!$response_data) {
            $this->setError(Http::$error);

            return $this->getError();
        }


        return $response_data;
    }


    /**
     * 查询最新一次提交的审核状态
     * @param  string $access_token                  小程序授权的authorizer_access_token
     */
    function ApiGetLatestAuditstatus($authorizer_appid = '')
    {

        $authorizer_access_token = $this->getAuthorizerAccessToken($authorizer_appid);

        $query_data = http_build_query(['access_token' => $authorizer_access_token]);

        $response_data = Http::_get(self::GET_API_GET_LATEST_AUDITSTAUS . '?' . $query_data);

        if (!$response_data) {
            $this->setError(Http::$error);

            return false;
        }


        return $response_data;
    }


    /**
     * 查询某个指定版本的审核状态
     * @param  string $access_token                  小程序授权的authorizer_access_token
     * @param  string $auditid                       提交审核的代码ID
     */
    function ApiGetAuditstatus($authorizer_appid = '', $auditid ='')
    {

        $authorizer_access_token = $this->getAuthorizerAccessToken($authorizer_appid);

        $request_data = [
            'auditid'    => $auditid,
        ];
        $query_data = http_build_query(['access_token' => $authorizer_access_token]);

        $response_data = Http::_post(self::GET_API_GET_AUDITSTAUS . '?' . $query_data,$request_data);

        if (!$response_data) {
            $this->setError(Http::$error);

            return false;
        }


        return $response_data;
    }


    /**
     * 发布已通过审核的小程序
     * @param  string $access_token                  小程序授权的authorizer_access_token
     */
    function ApiRelease($authorizer_appid = '')
    {

        $authorizer_access_token = $this->getAuthorizerAccessToken($authorizer_appid);

        $request_data = json_encode((object)array());

        $query_data = http_build_query(['access_token' => $authorizer_access_token]);

        $response_data = Http::_post(self::GET_API_RELEASE . '?' . $query_data,$request_data);

        if (!$response_data) {
            $this->setError(Http::$error);

            return $this->getError();
        }


        return $response_data;
    }


    /**
     * 修改小程序线上代码的可见状态
     * @param  string $access_token                  小程序授权的authorizer_access_token
     * @param  string $action                        设置可访问状态，发布后默认可访问，close为不可见，open为可见
     */
    function ApiChangeVisitstatus($authorizer_appid = '',$action='open')
    {
        $authorizer_access_token = $this->getAuthorizerAccessToken($authorizer_appid);

        $request_data = [
            "action" => $action
        ];
        $query_data = http_build_query(['access_token' => $authorizer_access_token]);

        $response_data = Http::_post(self::GET_API_CHANGE_VISITSTATUS . '?' . $query_data,$request_data);
        if (!$response_data) {
            $this->setError(Http::$error);

            return false;
        }
        return $response_data;
    }


    /**
     * 概况趋势
     * 获取 累计用户数,转发次数,转发人数
     * @param  string $access_token                  小程序授权的authorizer_access_token
     * @param  string $action                        设置可访问状态，发布后默认可访问，close为不可见，open为可见
     */
    function ApiGetSummaryTrend($authorizer_appid = '',$request_data = array())
    {
        $authorizer_access_token = $this->getAuthorizerAccessToken($authorizer_appid);

        $query_data = http_build_query(['access_token' => $authorizer_access_token]);

        $response_data = Http::_post(self::GET_SUMMARYTREND . '?' . $query_data,$request_data);
        if (!$response_data) {
            $this->setError(Http::$error);

            return false;
        }
        return $response_data;
    }


    /**
     * 日访问趋势
     * 获取 时间,打开次数,访问次数,访问人数,新用户数,人均停留时长 (浮点型，单位：秒),次均停留时长 (浮点型，单位：秒),平均访问深度 (浮点型)
     * @param  string $access_token                  小程序授权的authorizer_access_token
     * @param
     */
    function ApiGetDailyVisitTrend($authorizer_appid = '',$request_data = array())
    {
        $authorizer_access_token = $this->getAuthorizerAccessToken($authorizer_appid);

        $query_data = http_build_query(['access_token' => $authorizer_access_token]);

        $response_data = Http::_post(self::GET_DAILY_VISITTREND . '?' . $query_data,$request_data);

        if (!$response_data) {
            $this->setError(Http::$error);

            return false;
        }
        return $response_data;
    }


    /**
     * 访问页面
     * 获取 时间,打开次数,访问次数,访问人数,新用户数,人均停留时长 (浮点型，单位：秒),次均停留时长 (浮点型，单位：秒),平均访问深度 (浮点型)
     * @param  string $access_token                  小程序授权的authorizer_access_token
     */
    function getApiVisitPage($authorizer_appid = '',$request_data = array())
    {
        $authorizer_access_token = $this->getAuthorizerAccessToken($authorizer_appid);

        $query_data = http_build_query(['access_token' => $authorizer_access_token]);

        $response_data = Http::_post(self::GET_VISITPAGE . '?' . $query_data,$request_data);
        if (!$response_data) {
            $this->setError(Http::$error);

            return false;
        }
        return $response_data;
    }

    /*
     * 获取帐号下已存在的模板列表
     * */
    function getWxopenTemplateList($authorizer_appid = '',$request_data = array()){
        $authorizer_access_token = $this->getAuthorizerAccessToken($authorizer_appid);

        $query_data = http_build_query(['access_token' => $authorizer_access_token]);

        $response_data = Http::_post(self::GET_WXOPEN_TEMPLATE_LIST . '?' . $query_data,$request_data);
        if (!$response_data) {
            $this->setError(Http::$error);

            return $this->getError();
        }
        return $response_data;
    }


    /**
     * 发送客服消息
     * @param array $data 消息结构{"touser":"OPENID","msgtype":"news","news":{...}}
     * @return boolean|array
     */
    public function sendCustomMessage($authorizer_appid = '',$request_data = array(),$authorizer_access_token='')
    {

        if(empty($authorizer_access_token)){
            $authorizer_access_token = $this->getAuthorizerAccessToken($authorizer_appid);
        }

        $query_data = http_build_query(['access_token' => $authorizer_access_token]);

        $response_data = Http::_post(self::CUSTOM_SEND_URL . '?' . $query_data,$request_data);
        if (!$response_data) {
            $this->setError(Http::$error);

            return $this->getError();
        }
        $data = array(
            "access_token" => $authorizer_access_token,
            "query_data" => $query_data,
            "response_data" => $response_data,
        );
        return $data;
        //return $response_data;
    }
}