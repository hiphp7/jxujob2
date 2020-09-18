<?php
/**
 * 触屏控制器基类
 *
 * @author andery
 */
namespace Mobile\Controller;

use Common\Controller\FrontendController;

class MobileController extends FrontendController
{
    protected $visitor = null;
    protected $openid = '';
    protected $is_weixin;
    public function _initialize()
    {
        parent::_initialize();
        //网站状态
        if (C('qscms_mobile_isclose')) {
            header('Content-Type:text/html; charset=utf-8');
            exit('触屏端网站关闭:'.C('qscms_mobile_close_reason'));
        }
        $this->is_weixin = $this->is_weixin();
        $this->assign('is_weixin', $this->is_weixin);
    }
    public function is_weixin()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        }
        return false;
    }
    /**
     * 检测是否是微信，如果是微信，有openid，并且绑定，自动登录
     */
    public function check_weixin_login($openid = '')
    {
        $openid = $openid?:I('request.openid', '', 'trim');
        $this->openid = $openid;
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false && $openid) {
            $this->assign('openid', $openid);
            $userbind = M('MembersBind')->where(array('type'=>'weixin','keyid'=>$openid))->find();
            if (!$this->visitor->is_login) {
                if ($userbind) {
                    $this->visitor->login($userbind['uid']);
                    C('visitor', $this->visitor->info);
                    $this->_init_visitor();
                    return 0;
                }
                return 1;
            } else {
                if ($userbind) {
                    if ($userbind['uid'] != $this->visitor->info['uid']) {
                        $this->visitor->login($userbind['uid']);
                        C('visitor', $this->visitor->info);
                        $this->_init_visitor();
                        return 0;
                    }
                } else {
                    $this->visitor->logout();
                    //同步退出
                    $passport = $this->_user_server();
                    $synlogout = $passport->synlogout();
                    return 1;
                }
            }
        }
        return 0;
    }
    /**
     * 获取openid
     */
    public function get_weixin_openid($code)
    {
        if ($code && C('qscms_weixin_appid') && C('qscms_weixin_appsecret')) {
            $url ="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".C('qscms_weixin_appid')."&secret=".C('qscms_weixin_appsecret')."&code=".$code."&grant_type=authorization_code";
            $data = https_request($url);
            $data = json_decode($data, true);
            $openid = $data['openid'];
            if ($openid) {
                return $this->check_weixin_login($openid);
            }
        }
    }
    /**
     * 微信分享
     */
    public function wx_share()
    {
        $access_token = \Common\qscmslib\weixin::get_access_token();
        $jssdk = new \Common\qscmslib\Jssdk(C('qscms_weixin_appid'), C('qscms_weixin_appsecret'), $access_token);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign("signPackage", $signPackage);
    }
}
