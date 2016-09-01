<?php
/**
 * Created by PhpStorm.
 * User: mr
 * Date: 15/10/29
 * Time: 下午2:46
 */

namespace Common\Common\Wechat\base;


class WechatBase {

    /**
     *
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     *
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = C('CORP_ID');
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_base";
        $urlObj["state"] = "STATE"."#wechat_redirect";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
    }

    /**
     *
     * 拼接签名字符串
     * @param array $urlObj
     *
     * @return 返回已经拼接好的字符串
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign"){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     *
     * 通过跳转获取用户的openid，跳转流程如下：
     * 1、设置自己需要调回的url及其其他参数，跳转到微信服务器https://open.weixin.qq.com/connect/oauth2/authorize
     * 2、微信服务处理完成之后会跳转回用户redirect_uri地址，此时会带上一些参数，如：code
     *
     * @return 用户的openid
     */
    public function GetUserID()
    {
        //通过code获得openid
        if (empty($_GET['code'])){
            //触发微信返回code码
            $baseUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].$_SERVER['QUERY_STRING']);
            $url = $this->__CreateOauthUrlForCode($baseUrl);
            Header("Location: $url");
            exit();
        } else {
            //获取code码，以获取userid
            $code = $_GET['code'];
            $userid = $this->getUserFromApi($code);
            if($userid['UserId']){
                return $userid['UserId'];
            }else{
                throw_exception("不是企业用户");
            }
        }
    }

    private function getUserFromApi($code){
        $url = 'https://api.weixin.qq.com/cgi-bin/user/getuserinfo?access_token='.$this->getToken().'&code='.$code;
        $result = $this::weixin_curl_post($url,null);
        return json_decode($result,true);
    }

    /*
     * CURL Post
     */
    protected function weixin_curl_post($url,$param){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
//        curl_setopt($ch, CURLOPT_NOBODY, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        //默认注释掉此项,不做openssl验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;

    }

    protected function weixin_curl_down($url,$filename = ""){
        $url = preg_replace( '/(?:^[\'"]+|[\'"\/]+$)/', '', $url );//去除URL连接上面可能的引号
        $hander = curl_init();
        $fp = fopen($filename,'wb');
        curl_setopt($hander,CURLOPT_URL,$url);
        curl_setopt($hander,CURLOPT_FILE,$fp);
        curl_setopt($hander,CURLOPT_HEADER,0);
        curl_setopt($hander,CURLOPT_FOLLOWLOCATION,1);
        //curl_setopt($hander,CURLOPT_RETURNTRANSFER,false);//以数据流的方式返回数据,当为false是直接显示出来
        curl_setopt($hander,CURLOPT_TIMEOUT,60);
        $result = curl_exec($hander);
        curl_close($hander);
        fclose($fp);
        return $result;
    }


    protected function getResult($url,$param){
        try{
            $param['access_token'] = $this->getToken();
            $result = $this::weixin_curl_post($url,$param);
            return json_decode($result,true);
        }catch(Exception $e){
            throw_exception("连接错误");
        }
    }

    /*
     * 获取token
     *
     * UrlParam corpid企业号
     * UrlParam corpsecret企业secret
     */
    protected function getToken()
    {
        return getToken()['access_token'];
//        if (!S('GSFARM_TOKEN')) {
//            $url_get = 'https://api.weixin.qq.com/cgi-bin/token';
//            $result = $this->weixin_curl_post($url_get, array(grant_type => 'client_credential', appid => C('APPID'), corpsecret => C('SECRET')));
//            $obj = json_decode($result,true);
//            S('GSFARM_TOKEN', $obj['access_token'], 3600);
//        }
//
//        return S('GSFARM_TOKEN');
    }


}