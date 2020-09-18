<?php

/**
 * 第三方验证码
 *
 * @author andery
 */
namespace Common\qscmslib;
class captcha {
    static function generate($type='pc',$verifyName='verify'){
        $captcha = $type == 'pc' ? C('qscms_captcha') : C('qscms_mobile_captcha');
        if(!$captcha) exit('验证码错误，请先配置验证参数！');
        $GtSdk = new \Common\qscmslib\captcha\GeetestLib($captcha['id'], $captcha['key']);
        $randval = \Common\ORG\String::randString(4);
        session($verifyName, array('id'=>$randval,'gtserver'=>$GtSdk->pre_process($randval)));
        echo $GtSdk->get_response_str();
    }
    static function verify($type='pc',$verifyName='verify'){
        $captcha = $type == 'pc' ? C('qscms_captcha') : C('qscms_mobile_captcha');
        if(!$captcha) return '验证码错误，请先配置验证参数！';
        $GtSdk = new \Common\qscmslib\captcha\GeetestLib($captcha['id'], $captcha['key']);
        $session = session($verifyName);
        $user_id = $_SESSION['user_id'];
        if ($session['gtserver'] == 1) {
            if ($result = $GtSdk->success_validate($_POST['geetest_challenge'], $_POST['geetest_validate'], $_POST['geetest_seccode'], $session['id'])) {
                return true;
            } else{
                return '验证码错误！';
            }
        }else{
            if ($GtSdk->fail_validate($_POST['geetest_challenge'],$_POST['geetest_validate'],$_POST['geetest_seccode'])) {
                return true;
            }else{
                return '验证码错误！';
            }
        }
    }
}