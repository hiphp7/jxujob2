<?php
namespace Home\Controller;

use Common\Controller\FrontendController;

class MembersController extends FrontendController
{
    public function _initialize()
    {
        parent::_initialize();
        //访问者控制
        if (!$this->visitor->is_login) {
            if (in_array(ACTION_NAME, array('index','pms','sign_in'))) {
                IS_AJAX && $this->ajaxReturn(0, L('login_please'), '', 1);
                //非ajax的跳转页面
                $this->redirect('members/login');
            }
        } else {
            $urls = array('1'=>'company/index','2'=>'personal/index');
            !IS_AJAX && !in_array(ACTION_NAME, array('logout','varify_email')) && $this->redirect($urls[C('visitor.utype')]);
        }
    }
    public function index()
    {
    }
    /**
     * [login 用户登录]
     */
    public function login()
    {
        if (!I('get.org', '', 'trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']) {
            redirect(build_mobile_url(array('c'=>'Members','a'=>'login')));
        }
        if (IS_AJAX && IS_POST) {
            $expire = I('post.expire', 0, 'intval');
            $index_login = I('post.index_login', 0, 'intval');
            $url = I('post.url', '', 'trim');
            if (C('qscms_captcha_open')==1 && (C('qscms_captcha_config.user_login')==0 || (session('?error_login_count') && session('error_login_count')>=C('qscms_captcha_config.user_login')))) {
                if (true !== $reg = \Common\qscmslib\captcha::verify()) {
                    $this->ajaxReturn(0, $reg);
                }
            }
            $passport = $this->_user_server();
            if ($mobile = I('post.mobile', '', 'trim')) {
                if (!fieldRegex($mobile, 'mobile')) {
                    $this->ajaxReturn(0, '手机号格式错误！');
                }
                $smsVerify = session('login_smsVerify');
                !$smsVerify && $this->ajaxReturn(0, '验证码错误！');//验证码错误！
                if ($mobile != $smsVerify['mobile']) {
                    $this->ajaxReturn(0, '手机号不一致！');//手机号不一致
                }
                if (time()>$smsVerify['time']+600) {
                    $this->ajaxReturn(0, '验证码过期！');//验证码过期
                }
                $vcode_sms = I('post.mobile_vcode', 0, 'intval');
                $mobile_rand=substr(md5($vcode_sms), 8, 16);
                if ($mobile_rand!=$smsVerify['rand']) {
                    $this->ajaxReturn(0, '验证码错误！');//验证码错误！
                }
                $user = M('Members')->where(array('mobile'=>$smsVerify['mobile']))->find();
                !$user && $err = '帐号不存在！';
                $uid = $user['uid'];
                if (!$user['mobile_audit']) {
                    $setsqlarr['mobile'] = $smsVerify['mobile'];
                    $setsqlarr['mobile_audit']=1;
                    if (false !== $reg = M('Members')->where(array('uid'=>$uid))->save($setsqlarr)) {
                        D('Members')->update_user_info($setsqlarr, $user);
                        if ($user['utype']=='1') {
                            $rule=D('Task')->get_task_cache($user['utype'], 22);
                            D('TaskLog')->do_task($user, 22);
                        } else {
                            $rule=D('Task')->get_task_cache($user['utype'], 7);
                            D('TaskLog')->do_task($user, 7);
                        }
                        write_members_log($user, 8002);
                    }
                }
                session('login_smsVerify', null);
            } else {
                $username = I('post.username', '', 'trim');
                $password = I('post.password', '', 'trim');
                if (false === $uid = $passport->uc('default')->auth($username, $password)) {
                    $err = $passport->get_error();
                    if ($err == L('auth_null') && false !== $passport->uc('ucenter')->auth($username, $password)) {
                        cookie('members_uc_info', $passport->_user);
                        $this->ajaxReturn(1, '5', U('members/apilogin_ucenter'));
                    }
                } elseif (C('apply.Ucenter') && C('qscms_uc_open')) {
                    $user = $passport->_user;
                    if (!$user['uc_uid']) {
                        $passport->uc('ucenter')->register($user);
                    }
                }
            }
            if ($uid) {
                if (false === $this->visitor->login($uid, $expire)) {
                    $this->ajaxReturn(0, $this->visitor->getError());
                }
                $urls = array('1'=>'company/index','2'=>'personal/index');
                $login_url = $url ? $url : U($urls[$this->visitor->info['utype']]);
                //同步登陆
                $passport->uc('ucenter')->synlogin($uid);
                $this->ajaxReturn(1, '登录成功！', $login_url);
            }
            //记录登录错误次数
            if (C('qscms_captcha_open')==1) {
                if (C('qscms_captcha_config.user_login')>0) {
                    $error_login_count = session('?error_login_count')?(session('error_login_count')+1):1;
                    session('error_login_count', $error_login_count);
                    if (session('error_login_count')>=C('qscms_captcha_config.user_login')) {
                        $verify_userlogin = 1;
                    } else {
                        $verify_userlogin = 0;
                    }
                } else {
                    $verify_userlogin = 1;
                }
            } else {
                $verify_userlogin = 0;
            }
            
            $this->ajaxReturn(0, $err, $verify_userlogin);
        } else {
            if ($this->visitor->is_login) {
                $urls = array('1'=>'company/index','2'=>'personal/index');
                $this->redirect($urls[C('visitor.utype')]);
            }
            if (false === $oauth_list = F('oauth_list')) {
                $oauth_list = D('Oauth')->oauth_cache();
            }
            $this->assign('oauth_list', $oauth_list);
            $this->assign('title', '会员登录 - '.C('qscms_site_name'));
            $this->assign('weixin_login', C('qscms_weixin_apiopen') && C('qscms_weixin_scan_login'));//微信扫描登录是否开启
            $this->assign('verify_userlogin', $this->check_captcha_open(C('qscms_captcha_config.user_login'), 'error_login_count'));
            $this->display();
        }
    }
    /**
     * 用户退出
     */
    public function logout()
    {
        $this->visitor->logout();
        //同步退出
        $passport = $this->_user_server();
        $synlogout = $passport->synlogout();
        $this->redirect('members/login');
    }
    /**
     * [register 会员注册]
     */
    public function register()
    {
        if (!I('get.org', '', 'trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']) {
            redirect(build_mobile_url(array('c'=>'Members','a'=>'register')));
        }
        if (C('qscms_closereg')) {
            IS_AJAX && $this->ajaxReturn(0, '网站暂停会员注册，请稍后再次尝试！');
            $this->error("网站暂停会员注册，请稍后再次尝试！");
        }
        if (IS_POST && IS_AJAX) {
            $data['reg_type'] = I('post.reg_type', 0, 'intval');//注册方式(1:手机，2:邮箱，3:微信)
            $array = array(1 => 'mobile',2 => 'email');
            if (!$reg = $array[$data['reg_type']]) {
                $this->ajaxReturn(0, '正确选择注册方式！');
            }
            $data['utype'] = I('post.utype', 0, 'intval');
            if ($data['utype'] != 1 && $data['utype'] != 2) {
                $this->ajaxReturn(0, '请正确选择会员类型!');
            }
            if ($data['reg_type'] == 1) {
                $data['mobile'] = I('post.mobile', 0, 'trim');
                $smsVerify = session('reg_smsVerify');
                if (!$smsVerify) {
                    $this->ajaxReturn(0, '验证码错误！');
                }
                if ($data['mobile'] != $smsVerify['mobile']) {
                    $this->ajaxReturn(0, '手机号不一致！', $smsVerify);//手机号不一致
                }
                if (time()>$smsVerify['time']+600) {
                    $this->ajaxReturn(0, '验证码过期！');//验证码过期
                }
                $vcode_sms = I('post.mobile_vcode', 0, 'intval');
                $mobile_rand=substr(md5($vcode_sms), 8, 16);
                if ($mobile_rand!=$smsVerify['rand']) {
                    $this->ajaxReturn(0, '验证码错误！');//验证码错误！
                }
                $data['password'] = I('post.password', '', 'trim');
                $passwordVerify = I('post.passwordVerify', '', 'trim');
            } elseif ('bind' == $ucenter = I('post.ucenter', '', 'trim')) {
                $uc_user = cookie('members_uc_info');
                $data = array_merge($data, $uc_user);
                $passwordVerify = $data['password'];
                $data['utype']==1 && $data['mobile'] = I('post.telephone', '', 'trim,badword');
                C('qscms_check_reg_email') && $data['status'] = 0;
            } else {
                if ($data['utype'] == 1) {
                    $data['password'] = I('post.cpassword', '', 'trim');
                    $passwordVerify = I('post.cpasswordVerify', '', 'trim');
                } else {
                    $data['password'] = I('post.emailpassword', '', 'trim');
                    $passwordVerify = I('post.emailpasswordVerify', '', 'trim');
                }
                $data['username'] = I('post.username', '', 'trim,badword');
                $data['email'] = I('post.email', '', 'trim,badword');
                $data['utype']==1 && $data['mobile'] = I('post.telephone', '', 'trim,badword');
                C('qscms_check_reg_email') && $data['status'] = 0;
            }
            !$data['password'] && $this->ajaxReturn(0, '请输入密码!');
            $data['password'] != $passwordVerify && $this->ajaxReturn(0, '两次密码输入不一致!');
            if ($data['utype']==1) {
                $com_setarr['audit'] = 0;
                $com_setarr['email']=$data['email'];
                $com_setarr['companyname']=I('post.companyname', '', 'trim,badword');
                $com_setarr['contact']=I('post.contact', '', 'trim,badword');
                $com_setarr['telephone']=I('post.telephone', '', 'trim,badword');
                $com_setarr['landline_tel']=I('post.landline_tel', '', 'trim,badword');
                $company_mod = D('CompanyProfile');
                if (false === $company_mod->create($com_setarr)) {
                    $this->ajaxReturn(0, $company_mod->getError());
                }
            }
            $us = (!$uc_user && C('apply.Ucenter') && C('qscms_uc_open')) ? 'ucenter' : 'default';
            $passport = $this->_user_server($us);
            if (false === $data = $passport->register($data)) {
                if ($user = $passport->get_status()) {
                    $this->ajaxReturn(1, '会员注册成功！', array('url'=>U('members/reg_email_activate', array('uid'=>$user['uid']))));
                }
                $this->ajaxReturn(0, $passport->get_error());
            }
            // 添加企业信息
            if ($data['utype']==1) {
                $company_mod->uid=$data['uid'];
                C('SUBSITE_VAL.s_id') && $company_mod->subsite_id = C('SUBSITE_VAL.s_id');
                $insert_company_id = $company_mod->add();
                if ($insert_company_id) {
                    switch ($com_setarr['audit']) {
                        case 1:
                            $audit_str = '认证通过';
                            break;
                        case 2:
                            $audit_str = '认证中';
                            break;
                        case 3:
                            $audit_str = '认证未通过';
                            break;
                        default:
                            $audit_str = '';
                            break;
                    }
                    if ($audit_str) {
                        $auditsqlarr['company_id']=$insert_company_id;
                        $auditsqlarr['reason']='自动设置';
                        $auditsqlarr['status']=$audit_str;
                        $auditsqlarr['addtime']=time();
                        $auditsqlarr['audit_man']='系统';
                        M('AuditReason')->data($auditsqlarr)->add();
                    }
                }
            }
            if ('bind' == I('post.org', '', 'trim') && cookie('members_bind_info')) {
                $user_bind_info = object_to_array(cookie('members_bind_info'));
                $user_bind_info['uid'] = $data['uid'];
                $oauth = new \Common\qscmslib\oauth($user_bind_info['type']);
                $oauth->bindUser($user_bind_info);
                $this->_save_avatar($user_bind_info['temp_avatar'], $data['uid']);//临时头像转换
                cookie('members_bind_info', null);//清理绑定COOKIE
            }
            session('reg_smsVerify', null);
            D('Members')->user_register($data);
            $incode = I('post.incode', '', 'trim');
            if ($data['reg_type'] == 2 && C('qscms_check_reg_email')) {//是否需要邮箱激活
                $time=time();
                $key=substr(md5($data['email'].$time), 8, 16);
                $str = encrypt(http_build_query(array('e'=>$data['email'],'k'=>$key,'t'=>$time,'p'=>$data['password'],'n'=>$data['username'],'i'=>$incode)));
                $send_mail['send_type']='set_auth';
                $send_mail['sendto_email']=$data['email'];
                $send_mail['subject']='set_auth_title';
                $send_mail['body']='set_auth';
                $replac_mail['auth_url']=C('qscms_site_domain').U('home/members/activate', array('key'=>$str), true, false, true);
                if (true !== $reg = D('Mailconfig')->send_mail($send_mail, $replac_mail)) {
                    $this->ajaxReturn(0, $reg);
                }
                $this->ajaxReturn(1, '会员注册成功！', array('url'=>U('members/reg_email_activate', array('uid'=>$data['uid']))));
            }
            //如果是推荐注册，赠送积分
            $this->_incode($incode);
            $this->_correlation($data);
            $points_rule = D('Task')->get_task_cache(2, 1);
            $result['url'] = $data['utype']==2 ? U('personal/resume_add', array('points'=>$points_rule['points'],'first'=>1)) : U('members/index');
            $this->ajaxReturn(1, '会员注册成功！', $result);
        } else {
            $utype = I('get.utype', 0, 'intval');
            $utype == 0 && $type = 'reg';
            $utype == 1 && $type = 'reg_company';
            $utype == 2 && $type = 'reg_personal';
            //第三方登录
            if (false === $oauth_list = F('oauth_list')) {
                $oauth_list = D('Oauth')->oauth_cache();
            }
            $this->assign('utype', $utype);//注册会员类型
            $this->assign('user_bind', $user_bind);
            $this->assign('oauth_list', $oauth_list);
            $this->assign('company_repeat', C('qscms_company_repeat'));//企业注册名称是否可以重复
            $this->_config_seo(array('title'=>'会员注册 - '.C('qscms_site_name')));
            $this->display($type);
        }
    }
    /**
     * [_incode 注册赠送积分]
     */
    protected function _incode($incode)
    {
        if ($incode) {
            if (preg_match('/^[a-zA-Z0-9]{8}$/', $incode)) {
                $inviter_info = M('Members')->where(array('invitation_code'=>$incode))->find();
                if ($inviter_info) {
                    $task_id = $inviter_info['utype']==1?31:14;
                    D('TaskLog')->do_task($inviter_info, $task_id);
                }
            }
        }
    }
    /**
     * [_correlation 用户注册相关]
     */
    protected function _correlation($data)
    {
        $members_mod = D('Members');
        if (false === $this->visitor->login($data['uid'])) {
            IS_AJAX && $this->ajaxReturn(0, $this->visitor->getError());
            $this->error($this->visitor->getError(), 'register');
        }
        if ($data['reg_type'] == 2) {
            if (false === $mailconfig = F('mailconfig')) {
                $mailconfig = D('Mailconfig')->get_cache();//邮箱系统配置参数
            }
            if ($mailconfig['set_reg']==1) {
                $utype_cn = array('1'=>'企业','2'=>'个人');
                $send_mail['send_type']='set_reg';
                $send_mail['sendto_email']=$data['email'];
                $send_mail['subject']='set_reg_title';
                $send_mail['body']='set_reg';
                $replac_mail['username']=$this->visitor->info['username'];
                $replac_mail['password']=$data['password'];
                $replac_mail['utype']=$utype_cn[$data['utype']];
                D('Mailconfig')->send_mail($send_mail, $replac_mail);
            }
        }
        //同步登陆
        $this->_user_server()->synlogin($data['uid']);
    }
    /**
     * [activate 邮箱注册激活]
     */
    public function activate()
    {
        if (!I('get.org', '', 'trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']) {
            redirect(U('mobile/members/activate', array('key'=>I('get.key', '', 'trim')), true, false, true));
        }
        parse_str(decrypt(I('get.key', '', 'trim')), $data);
        !fieldRegex($data['e'], 'email') && $this->error('邮箱格式错误！', U('members/register'));
        $end_time=$data['t']+24*3600;
        if ($end_time<time()) {
            $this->error('注册失败,链接过期!', U('members/register'));
        }
        $key_str=substr(md5($data['e'].$data['t']), 8, 16);
        if ($key_str!=$data['k']) {
            $this->error('注册失败,key错误', U('members/register'));
        }
        $members_mod = D('Members');
        $user = $members_mod->field('uid,utype,email,status')->where(array('email'=>$data['e']))->find();
        !$user && $this->error('帐号不存在！', U('members/register'));
        $points_rule = D('Task')->get_task_cache(2, 1);
        $urls = array('1'=>'company/index','2'=>U('personal/resume_add', array('points'=>$points_rule['points'],'first'=>1)));
        if ($user['status'] == 0) {
            $d = array('username'=>$data['n'],'password'=>$data['p'],'status'=>1,'email_audit'=>1);
            $passport = $this->_user_server();
            if (false === $uid = $passport->edit($user['uid'], $d)) {
                $this->error('帐号激活失败，请重新操作！', U('members/register'));
            }
            $user['reg_type'] = 2;
            $user['password'] = $data['p'];
            $data['i'] && $this->_incode($data['i']);
            $this->_correlation($user);
            $this->success('帐号激活成功！', $urls[$this->visitor->info['utype']]);
        } else {
            $this->success('帐号已经激活,请登录！', U('members/login'));
        }
    }
    /**
     * [reg_email_activate description]
     */
    public function reg_email_activate()
    {
        $uid = I('get.uid', 0, 'intval');
        !$uid && $this->redirect('members/register');
        $user = M('Members')->field('uid,email')->where(array('uid'=>$uid,'status'=>0))->find();
        !$user && $this->redirect('members/register');
        $this->assign('user', $user);
        $this->assign('title', '会员注册 - '.C('qscms_site_name'));
        $this->display();
    }
    /**
     * [send_sms 注册验证短信]
     */
    public function verify_sms()
    {
        $mobile=I('get.mobile', '', 'trim');
        if (!fieldRegex($mobile, 'mobile')) {
            $this->ajaxReturn(0, '手机号格式错误！');
        }
        $smsVerify = session('reg_smsVerify');
        if ($mobile!=$smsVerify('mobile')) {
            $this->ajaxReturn(0, '手机号不一致！');//手机号不一致
        }
        if (time()>$smsVerify('time')+600) {
            $this->ajaxReturn(0, '验证码过期！');//验证码过期
        }
        $vcode_sms = I('get.mobile_vcode', 0, 'intval');
        $mobile_rand=substr(md5($vcode_sms), 8, 16);
        if ($mobile_rand!=$smsVerify('rand')) {
            $this->ajaxReturn(0, '验证码错误！');//验证码错误！
        }
        $this->ajaxReturn(1, '手机验证成功！');
    }
    // 注册发送短信/找回密码 短信
    public function reg_send_sms()
    {
        if (C('qscms_captcha_open') && C('qscms_captcha_config.varify_mobile') && true !== $reg = \Common\qscmslib\captcha::verify()) {
            $this->ajaxReturn(0, $reg);
        }
        if ($uid = I('post.uid', 0, 'intval')) {
            $mobile=M('Members')->where(array('uid'=>$uid))->getfield('mobile');
            !$mobile && $this->ajaxReturn(0, '用户不存在！');
        } else {
            $mobile = I('post.mobile', '', 'trim');
            !$mobile && $this->ajaxReturn(0, '请填手机号码！');
        }
        if (!fieldRegex($mobile, 'mobile')) {
            $this->ajaxReturn(0, '手机号错误！');
        }
        $sms_type = I('post.sms_type', 'reg', 'trim');
        $rand=mt_rand(100000, 999999);
        switch ($sms_type) {
            case 'reg':
                $sendSms['tpl']='set_register';
                $sendSms['data']=array('rand'=>$rand.'','sitename'=>C('qscms_site_name'));
                break;
            case 'gsou_reg':
                $sendSms['tpl']='set_register';
                $sendSms['data']=array('rand'=>$rand.'','sitename'=>C('qscms_site_name'));
                break;
            case 'getpass':
                $sendSms['tpl']='set_retrieve_password';
                $sendSms['data']=array('rand'=>$rand.'','sitename'=>C('qscms_site_name'));
                break;
            case 'login':
                if (!$uid=M('Members')->where(array('mobile'=>$mobile))->getfield('uid')) {
                    $this->ajaxReturn(0, '您输入的手机号未注册会员');
                }
                $sendSms['tpl']='set_login';
                $sendSms['data']=array('rand'=>$rand.'','sitename'=>C('qscms_site_name'));
                break;
        }
        $smsVerify = session($sms_type.'_smsVerify');
        if ($smsVerify && $smsVerify['mobile']==$mobile && time()<$smsVerify['time']+180) {
            $this->ajaxReturn(0, '180秒内仅能获取一次短信验证码,请稍后重试');
        }
        $sendSms['mobile']=$mobile;
        if (true === $reg = D('Sms')->sendSms('captcha', $sendSms)) {
            session($sms_type.'_smsVerify', array('rand'=>substr(md5($rand), 8, 16),'time'=>time(),'mobile'=>$mobile));
            $this->ajaxReturn(1, '手机验证码发送成功！');
        } else {
            $this->ajaxReturn(0, $reg);
        }
    }
    /**
     * 检测用户信息是否存在或合法
     */
    public function ajax_check()
    {
        $type = I('post.type', 'trim', 'email');
        $param = I('post.param', '', 'trim');
        if (in_array($type, array('username','mobile','email'))) {
            $type != 'username' && !fieldRegex($param, $type) && $this->ajaxReturn(0, L($type).'格式错误！');
            $where[$type] = $param;
            $reg = M('Members')->field('uid,status')->where($where)->find();
            if ($reg['uid'] && $reg['status'] != 0) {
                $this->ajaxReturn(0, L($type).'已经注册');
            } elseif (C('apply.Ucenter') && C('qscms_uc_open')) {
                $passport = $this->_user_server();
                $name = 'check_'.$type;
                if (false === $passport->$name($param)) {
                    $this->ajaxReturn(0, $passport->get_error());
                }
            }
            $this->ajaxReturn(1);
        } elseif ($type == 'companyname') {
            if (C('qscms_company_repeat')==0) {
                $reg = M('CompanyProfile')->where(array('companyname'=>$param))->getfield('id');
                $reg ? $this->ajaxReturn(0, '企业名称已经注册') : $this->ajaxReturn(1);
            } else {
                $this->ajaxReturn(1);
            }
        }
    }
    /**
     * [waiting_weixin_login 循环检测微信是否扫码登录]
     */
    public function waiting_weixin_login()
    {
        $scene_id = session('login_scene_id');
        if ($uid = F('/weixin/'.($scene_id%10).'/'.$scene_id)) {
            if (false === $this->visitor->login($uid, $expire)) {
                $this->ajaxReturn(0, $this->visitor->getError());
            }
            $urls = array('1'=>'company/index','2'=>'personal/index','3'=>'hunter/index','4'=>'train/index');
            $login_url = $url ? $url : U($urls[$this->visitor->info['utype']]);
            session('login_scene_id', null);
            F('/weixin/'.($scene_id%10).'/'.$scene_id, null);
            $this->ajaxReturn(1, '微信扫码登录成功！', $login_url);
        } else {
            $this->ajaxReturn(0, '微信没有登录！');
        }
    }
    /**
     * [waiting_weixin_reg 循环检测微信是否扫码注册]
     */
    public function waiting_weixin_reg()
    {
        $scene_id = session('reg_scene_id');
        if ($openid = F('/weixin/'.($scene_id%10).'/'.$scene_id)) {
            $this->ajaxReturn($reg['state'], $reg['tip']);
        } else {
            $this->ajaxReturn(0, '微信没有绑定！');
        }
    }
    /**
     * [waiting_weixin_bind 循环检测微信是否扫码绑定]
     */
    public function waiting_weixin_bind()
    {
        $scene_id = session('bind_scene_id');
        if ($openid = F('/weixin/'.($scene_id%10).'/'.$scene_id)) {
            $reg = \Common\qscmslib\weixin::bind($openid, C('visitor'));
            $this->ajaxReturn($reg['state'], $reg['tip']);
        } else {
            $this->ajaxReturn(0, '微信没有绑定！');
        }
    }
    /**
     * [user_getpass 忘记密码]
     */
    public function user_getpass()
    {
        if (IS_POST) {
            $type = I('post.type', 0, 'intval');
            $array = array(1 => 'mobile',2 => 'email');
            if (!$reg = $array[$type]) {
                $this->error('请正确选择找回密码方式！');
            }
            $retrievePassword = session('retrievePassword');
            if ($retrievePassword['token'] != I('post.token', '', 'trim')) {
                $this->error('非法参数！');
            }
            if ($type == 1) {
                $mobile = I('post.mobile', 0, 'trim');
                if (!$user = M('Members')->field('uid,username')->where(array('mobile'=>$mobile,'mobile_audit'=>1))->find()) {
                    $this->error('该手机号没有绑定帐号！');
                }
                $smsVerify = session('getpass_smsVerify');
                if ($mobile != $smsVerify['mobile']) {
                    $this->error('手机号不一致！');//手机号不一致
                }
                if (time()>$smsVerify['time']+600) {
                    $this->error('验证码过期！');//验证码过期
                }
                $vcode_sms = I('post.mobile_vcode', 0, 'intval');
                $mobile_rand=substr(md5($vcode_sms), 8, 16);
                if ($mobile_rand!=$smsVerify['rand']) {
                    $this->error('验证码错误！');//验证码错误！
                }
                $tpl = 'user_setpass';
                session('smsVerify', null);
            } else {
                $email = I('post.email', 0, 'trim');
                if (!$user = M('Members')->field('uid,username')->where(array('email'=>$email,'email_audit'=>1))->find()) {
                    $this->error('该邮箱没有绑定帐号！');
                }
                $time=time();
                $key=substr(md5($email.$time), 8, 16);
                $str = encrypt(http_build_query(array('e'=>$email,'k'=>$key,'t'=>$time)), C('PWDHASH'));
                $email_str.="{$user['username']}您好：<br>";
                $email_str.="请在24小时内点击以下链接重新设置您的密码：<br>";
                $url = C('qscms_site_domain').U('members/user_setpass', array('key'=>$str), true, false, true);
                $email_str.="<a href='".$url."' target='_blank'>".$url."</a><br>";
                $email_str.="如果链接无法点击,请复制粘贴到浏览器访问！<br>";
                $email_str.="本邮件由系统发出,请勿回复！<br>";
                $email_str.="如有任何疑问请联系网站官方：".C('qscms_top_tel');
                $email_data = array('sendto_email'=>$email,'subject'=>C('qscms_site_name')." - 会员找回密码",'body'=>$email_str);
                if (true !== $reg = D('Mailconfig')->send_mail($email_data)) {
                    $this->ajaxReturn(0, $reg);
                }
                $tpl = 'user_retrieve_email';
                $this->assign('email', $email);
            }
        }
        $token=substr(md5(mt_rand(100000, 999999)), 8, 16);
        session('retrievePassword', array('uid'=>$user['uid'],'token'=>$token));
        $this->assign('token', $token);
        $this->_config_seo(array('title'=>'找回密码 - '.C('qscms_site_name')));
        $this->display($tpl);
    }
     /**
     * [find_pwd 重置密码]
     */
    public function user_setpass()
    {
        if (IS_POST) {
            $retrievePassword = session('retrievePassword');
            if ($retrievePassword['token'] != I('post.token', '', 'trim')) {
                $this->error('非法参数！');
            }
            $user['password']=I('post.password', '', 'trim,badword');
            !$user['password'] && $this->error('请输入新密码！');
            if ($user['password'] != I('post.password1', '', 'trim,badword')) {
                $this->error('两次输入密码不相同，请重新输入！');
            }
            $passport = $this->_user_server();
            if (false === $uid = $passport->edit($retrievePassword['uid'], $user)) {
                $this->error($passport->get_error());
            }
            $tpl = 'user_setpass_sucess';
            session('retrievePassword', null);
        } else {
            parse_str(decrypt(I('get.key', '', 'trim'), C('PWDHASH')), $data);
            !fieldRegex($data['e'], 'email') && $this->error('找回密码失败,邮箱格式错误！', 'user_getpass');
            $end_time=$data['t']+24*3600;
            if ($end_time<time()) {
                $this->error('找回密码失败,链接过期!', 'user_getpass');
            }
            $key_str=substr(md5($data['e'].$data['t']), 8, 16);
            if ($key_str!=$data['k']) {
                $this->error('找回密码失败,key错误!', 'user_getpass');
            }
            if (!$uid = M('Members')->where(array('email'=>$data['e']))->getfield('uid')) {
                $this->error('找回密码失败,帐号不存在!', 'user_getpass');
            }
            $token=substr(md5(mt_rand(100000, 999999)), 8, 16);
            session('retrievePassword', array('uid'=>$uid,'token'=>$token));
            $this->assign('token', $token);
        }
        $this->_config_seo(array('title'=>'找回密码 - '.C('qscms_site_name')));
        $this->display($tpl);
    }
    /**
     * 账号申诉
     */
    public function appeal_user()
    {
        $mod = D('MembersAppeal');
        if (IS_POST && IS_AJAX) {
            if (false === $data = $mod->create()) {
                $this->ajaxReturn(0, $mod->getError());
            }
            if ($this->apply['Subsite']) {
                $where['mobile'] = I('post.mobile', '', 'trim');
                $where['email'] = I('post.email', '', 'trim');
                $where['_logic'] = 'OR';
                $subsite_id = M('Members')->where($where)->getfield('subsite_id');
                $data['subsite_id'] = $subsite_id?:(C('SUBSITE_VAL.s_id')?:0);
            }
            if (false !== $mod->add($data)) {
                $this->ajaxReturn(1, L('operation_success'));
            } else {
                $this->ajaxReturn(0, L('operation_failure'));
            }
        }
        $this->_config_seo(array('title'=>'账号申诉 - '.C('qscms_site_name')));
        $this->display();
    }
    /**
     * [binding ucenter绑定]
     */
    public function apilogin_ucenter()
    {
        $user_bind_info = object_to_array(cookie('members_uc_info'));
        if (!$this->visitor->is_login && !$user_bind_info) {
            $this->redirect('members/login');
        }
        $user_bind_info['keyavatar_big'] = attach('no_photo_male.png', 'resource');
        $this->assign('third_name', 'Ucenter');
        $this->assign('user_bind_info', $user_bind_info);
        $this->_config_seo();
        $this->display();
    }
    /**
     * [binding 第三方绑定]
     */
    public function apilogin_binding()
    {
        $user_bind_info = object_to_array(cookie('members_bind_info'));
        if (!$this->visitor->is_login && !$user_bind_info) {
            $this->redirect('members/login');
        }
        if (false === $oauth_list = F('oauth_list')) {
            $oauth_list = D('Oauth')->oauth_cache();
        }
        $this->assign('third_name', $oauth_list[$user_bind_info['type']]['name']);
        $this->assign('user_bind_info', $user_bind_info);
        $this->_config_seo();
        $this->display();
    }
    /**
     * [oauth_reg 第三方登录注册]
     */
    public function oauth_reg()
    {
        if (cookie('members_bind_info')) {
            $user_bind_info = object_to_array(cookie('members_bind_info'));
        } else {
            $this->error('第三方授权失败，请重新操作！');
        }
        //第三方帐号绑定
        $username = I('post.username', '', 'trim');
        $password = I('post.password', '', 'trim');
        $passport = $this->_user_server();
        if (false === $uid = $passport->auth($username, $password)) {
            $this->error($passport->get_error());
        }
        if (false === $this->visitor->login($uid)) {
            $this->error($this->visitor->getError());
        }
        $oauth = new \Common\qscmslib\oauth($user_bind_info['type']);
        $bind_user = $oauth->_checkBind($user_bind_info['type'], $user_bind_info['keyid']);
        if ($bind_user['uid'] && $bind_user['uid'] != $uid) {
            $this->error('此帐号已经绑定过本站！');
        }
        $user_bind_info['uid'] = $uid;
        if (false === $oauth->bindUser($user_bind_info)) {
            $this->error('帐号绑定失败，请重新操作！');
        }
        $this->visitor->get('avatars');
        if (!$this->visitor->get('avatars')) {
            $this->_save_avatar($user_bind_info['temp_avatar'], $uid);//临时头像转换
        }
        cookie('members_bind_info', null);//清理绑定COOKIE
        $urls = array('1'=>'company/index','2'=>'personal/index');
        $this->redirect($urls[$this->visitor->info['utype']]);
    }
    /**
     * [_save_avatar 第三方头像保存]
     */
    protected function _save_avatar($avatar, $uid)
    {
        if (!$avatar) {
            return false;
        }
        $path = C('qscms_attach_path').'avatar/temp/'.$avatar;
        $image = new \Common\ORG\ThinkImage();
        $date = date('ym/d/');
        $save_avatar=C('qscms_attach_path').'avatar/'.$date;//图片存储路径
        if (!is_dir($save_avatar)) {
            mkdir($save_avatar, 0777, true);
        }
        $savePicName = md5($uid.time()).".jpg";
        $filename = $save_avatar.$savePicName;
        $size = explode(',', C('qscms_avatar_size'));
        copy($path, $filename);
        foreach ($size as $val) {
            $image->open($path)->thumb($val, $val, 3)->save("{$filename}._{$val}x{$val}.jpg");
        }
        M('Members')->where(array('uid'=>$uid))->setfield('avatars', $date.$savePicName);
        @unlink($path);
    }
    /**
     * [save_username 修改帐户名]
     */
    public function save_username()
    {
        if (IS_POST) {
            $user['username']=I('post.username', '', 'trim,badword');
            $passport = $this->_user_server();
            if (false === $uid = $passport->edit(C('visitor.uid'), $user)) {
                $this->ajaxReturn(0, $passport->get_error());
            }
            $this->visitor->update();//刷新会话
            $this->ajaxReturn(1, '用户名修改成功！');
        } else {
            $data['html']=$this->fetch('ajax_modify_uname');
            $this->ajaxReturn(1, '修改用户名弹窗获取成功！', $data);
        }
    }
    /**
     * [save_password 修改密码]
     */
    public function save_password()
    {
        if (IS_POST) {
            $oldpassword=I('post.oldpassword', '', 'trim,badword');
            !$oldpassword && $this->ajaxReturn(0, '请输入原始密码!');
            $password=I('post.password', '', 'trim,badword');
            !$password && $this->ajaxReturn(0, '请输入新密码！');
            if ($password != I('post.password1', '', 'trim,badword')) {
                $this->ajaxReturn(0, '两次输入密码不相同，请重新输入！');
            }
            $data['oldpassword'] = $oldpassword;
            $data['password'] = $password;
            $reg = D('Members')->save_password($data, C('visitor'));
            !$reg['state'] && $this->ajaxReturn(0, $reg['error']);
            $this->ajaxReturn(1, '密码修改成功！');
        } else {
            $data['html']=$this->fetch('ajax_modify_pwd');
            $this->ajaxReturn(1, '修改密码弹窗获取成功！', $data);
        }
    }
    /**
     * [user_email 获取邮箱验证弹窗]
     */
    public function user_email()
    {
        $this->assign('members_info', D('Members')->get_user_one(array('uid'=>C('visitor.uid'))));
        $tpl=$this->fetch('ajax_auth_email');
        $this->ajaxReturn(1, '邮箱验证弹窗获取成功！', $tpl);
    }
    /**
     * [send_code 验证邮箱_发送验证链接]
     */
    public function send_email_varify_url()
    {
        $email=I('post.email', '', 'trim,badword');
        if (!fieldRegex($email, 'email')) {
            $this->ajaxReturn(0, '邮箱格式错误!');
        }
        $user=M('Members')->field('uid,email,email_audit')->where(array('email'=>$email))->find();
        $user && $user['uid'] <> C('visitor.uid') && $this->ajaxReturn(0, '邮箱已经存在,请填写其他邮箱!');
        if ($user['email'] && $user['email_audit'] == 1 && $user['email'] == $email) {
            $this->ajaxReturn(0, "你的邮箱 {$email} 已经通过验证！");
        }
        if (session('verify_email.time') && (time()-session('verify_email.time'))<60) {
            $this->ajaxReturn(0, '请60秒后再进行验证！');
        }
        $token = encrypt(C('visitor.uid')).'-'.encrypt($email).'-'.time();
        $send_mail['send_type']='set_auth';
        $send_mail['sendto_email']=$email;
        $send_mail['subject']='set_auth_title';
        $send_mail['body']='set_auth';
        $replac_mail['auth_url']=C('qscms_site_domain').U('Members/varify_email', array('token'=>$token), true, false, true);
        if (true === $reg = D('Mailconfig')->send_mail($send_mail, $replac_mail)) {
            $this->ajaxReturn(1, '验证邮件发送成功！');
        } else {
            $this->ajaxReturn(0, $reg);
        }
    }
    /**
     * 邮箱链接验证
     */
    public function varify_email()
    {
        $token = I('get.token', '', 'trim');
        $return_url_arr = array('1'=>U('Company/user_security'),'2'=>U('Personal/user_safety'));
        if ($token) {
            $token = str_replace(C('URL_HTML_SUFFIX'), '', $token);
            $verify = explode("-", $token);
            $uid = decrypt($verify[0]);
            $email = decrypt($verify[1]);
            $time = $verify[2];
            if ($time+3600*24>=time()) {//24小时内有效
                $userinfo = D('Members')->where(array('uid'=>$uid))->find();
                if (!$userinfo) {
                    $this->error('邮箱验证失败!', $return_url_arr[$userinfo['utype']]);
                }
                $setsqlarr['email']=$email;
                $setsqlarr['email_audit']=1;
                if (false === $reg = M('Members')->where(array('uid'=>$uid))->save($setsqlarr)) {
                    $this->error('邮箱验证失败!', $return_url_arr[$userinfo['utype']]);
                }
                if (!$reg) {
                    $this->success("你的邮箱 {$email} 已经通过验证！", $return_url_arr[$userinfo['utype']]);
                    return;
                }
                $user_visitor = new \Common\qscmslib\user_visitor;
                $user_visitor->logout();
                $user_visitor->assign_info($userinfo);
                D('Members')->update_user_info($setsqlarr, $userinfo);
                if ($userinfo['utype']=="1") {
                    $r = D('TaskLog')->do_task($userinfo, 23);
                } else {
                    $r = D('TaskLog')->do_task($userinfo, 16);
                }
                write_members_log($userinfo, 8001);
                if ($r['data']) {
                    $sub = '增加'.$r['data'].C('qscms_points_byname');
                } else {
                    $sub = '';
                }
                $this->success('邮箱验证通过!'.$sub, $return_url_arr[$userinfo['utype']]);
            } else {
                $this->error('该链接已过期', $return_url_arr[$userinfo['utype']]);
            }
        } else {
            $this->error('链接无效', $return_url_arr[$userinfo['utype']]);
        }
    }
    
    /**
     * [user_mobile 获取手机验证弹窗]
     */
    public function user_mobile()
    {
        $audit = D('Members')->where(array('uid'=>C('visitor.uid')))->getField('mobile_audit');
        $this->assign('audit', $audit);
        $tpl=$this->fetch('ajax_auth_mobile');
        $this->ajaxReturn(1, '手机验证弹窗获取成功！', $tpl);
    }
    /**
     * [send_mobile_code 发送手机验证码]
     */
    public function send_mobile_code()
    {
        $mobile=I('post.mobile', '', 'trim,badword');
        if (!fieldRegex($mobile, 'mobile')) {
            $this->ajaxReturn(0, '手机格式错误!');
        }
        $user=M('Members')->field('uid,mobile,mobile_audit')->where(array('mobile'=>$mobile))->find();
        $user['uid'] && $user['uid']<>C('visitor.uid') && $this->ajaxReturn(0, '手机号已经存在,请填写其他手机号!');
        if ($user['mobile'] && $user['mobile_audit'] == 1 && $user['mobile'] == $mobile) {
            $this->ajaxReturn(0, "你的手机号 {$mobile} 已经通过验证！");
        }
        if (session('verify_mobile.time') && (time()-session('verify_mobile.time'))<180) {
            $this->ajaxReturn(0, '请180秒后再进行验证！');
        }
        $rand=mt_rand(100000, 999999);
        $sendSms = array('mobile'=>$mobile,'tpl'=>'set_mobile_verify','data'=>array('rand'=>$rand.'','sitename'=>C('qscms_site_name')));
        
        if (true === $reg = D('Sms')->sendSms('captcha', $sendSms)) {
            session('verify_mobile', array('mobile'=>$mobile,'rand'=>$rand,'time'=>time()));
            $this->ajaxReturn(1, '验证码发送成功！');
        } else {
            $this->ajaxReturn(0, $reg);
        }
    }
    /**
     * [verify_mobile_code 验证手机验证码]
     */
    public function verify_mobile_code()
    {
        $verifycode=I('post.verifycode', 0, 'intval');
        $verify = session('verify_mobile');
        if (!$verifycode || !$verify['rand'] || $verifycode<>$verify['rand']) {
            $this->ajaxReturn(0, '验证码错误!');
        }
        $setsqlarr['mobile'] = $verify['mobile'];
        $setsqlarr['mobile_audit']=1;
        $uid=C('visitor.uid');
        if (false === $reg = M('Members')->where(array('uid'=>$uid))->save($setsqlarr)) {
            $this->ajaxReturn(0, '手机验证失败!');
        }
        !$reg && $this->ajaxReturn(0, "你的手机 {$verify['mobile']} 已经通过验证！");
        D('Members')->update_user_info($setsqlarr, C('visitor'));
        if (C('visitor.utype')=='1') {
            $r = D('TaskLog')->do_task(C('visitor'), 22);
        } else {
            $r = D('TaskLog')->do_task(C('visitor'), 7);
        }
        write_members_log(C('visitor'), 8002);
        session('verify_mobile', null);
        $this->ajaxReturn(1, '手机验证通过!', array('mobile'=>$verify['mobile'],'points'=>$r['state']==1?$r['data']:0));
    }
    
    /**
     * [sign_in 签到]
     */
    public function sign_in()
    {
        if (IS_AJAX) {
            $reg = D('Members')->sign_in(C('visitor'));
            if ($reg['state']) {
                write_members_log(C('visitor'), 8003);
                $this->ajaxReturn(1, '成功签到！', $reg['points']);
            } else {
                $this->ajaxReturn(0, $reg['error']);
            }
        }
    }
    /**
     * 推荐注册
     */
    public function invitation_reg()
    {
        $taskid = C('visitor.utype')==1?31:14;
        $task_info = D('Task')->get_task_cache(C('visitor.utype'), $taskid);
        $this->assign('task_info', $task_info);
        $invitation_code = D('Members')->where(array('uid'=>C('visitor.uid')))->getField('invitation_code');
        $invitation_url = C('qscms_site_domain').U('Members/register', array('incode'=>$invitation_code));
        $this->assign('invitation_url', $invitation_url);
        if (C('visitor.utype')==1) {
            $css = '../../public/css/company/company_ajax_dialog.css';
        } else {
            $css = '../../public/css/personal/personal_ajax_dialog.css';
        }
        $this->assign('css', $css);
        $html = $this->fetch('ajax_invitation_reg');
        $this->ajaxReturn(1, '获取数据成功！', $html);
    }
    /**
     * 获取注册协议
     */
    public function agreement()
    {
        $agreement = htmlspecialchars_decode(M('Text')->where(array('name'=>'agreement'))->getField('value'), ENT_QUOTES);
        $this->assign('agreement', $agreement);
        $tpl = $this->fetch('Members/agreement');
        $this->ajaxReturn(1, '获取数据成功！', $tpl);
    }
    
    
    public function companylogin()
    {

        $ver_code = 'jxrcw';
        $code     = I('code', '', 'trim');
        //$username = $_GET['username'];
        $uid   = $_GET['uid'];
        $empid = $_GET['empid'];

        ($code != md5($empid . $uid . $ver_code)) && $this->ajaxReturn(0, '数据验证错误');
        $u = D('Members')->where(array('uid' => $uid))->find();
        if ($u) {
            $url = '/Index/comlogin/id/' . $u['uid'];
            redirect($url);
        } else {
            echo "无法登录";
        }
    }

    public function login_switch2()
    {
        $url = I('get.url', '', 'trim');
        !$url && $this->error('非法操作！', U('members/login'));
        parse_str(decrypt($url, C('PWDHASH')), $data);
        $scene_id = S('_login_switch', '', '', true);

        $u = D('Members')->get_user_one(array('uid' => $data['uid'], 'utype' => $data['utype']));

        $this->visitor->logout();
        $passport = $this->_user_server();
        //同步退出
        $synlogout = $passport->synlogout();
        if (false === $this->visitor->login($u['uid'])) {
            $this->error($this->visitor->getError(), U('members/login'));
        }
        $urls = array('1' => 'company/index', '2' => 'personal/index');
        //同步登陆
        $passport->uc('ucenter')->synlogin($u['uid'], $data['utype']);
        S('_login_switch', null, '', true);
        if ($u['utype'] == 1 && !M('CompanyProfile')->where(array('uid' => $u['uid']))->find()) {
            $com_setarr['audit']     = 0;
            $com_setarr['email']     = $u['email'];
            $com_setarr['uid']       = $u['uid'];
            $com_setarr['telephone'] = $u['mobile'];
            $company_mod             = D('CompanyProfile');
            if (false === $company_mod->create($com_setarr)) {
                $this->ajaxReturn(0, $company_mod->getError());
            }

            if ($insert_company_id = $company_mod->add()) {
                switch ($com_setarr['audit']) {
                    case 1:
                        $audit_str = '认证通过';
                        break;
                    case 2:
                        $audit_str = '认证中';
                        break;
                    case 3:
                        $audit_str = '认证未通过';
                        break;
                    default:
                        $audit_str = '';
                        break;
                }
                if ($audit_str) {
                    $auditsqlarr['company_id'] = $insert_company_id;
                    $auditsqlarr['reason']     = '自动设置';
                    $auditsqlarr['status']     = $audit_str;
                    $auditsqlarr['addtime']    = time();
                    $auditsqlarr['audit_man']  = '系统';
                    M('AuditReason')->data($auditsqlarr)->add();
                }
            }
            D('Members')->user_register($u);
        }
        if ($u['utype'] == 2 && !M('MembersPoints')->where(array('uid' => $u['uid'], 'utype' => 2))->find()) {
            D('Members')->user_register($u);
        }
        if ($u['utype'] == 2 && $data['action'] == 'resume') {
            $this->redirect('personal/resume_list');
        } else {
            $this->redirect($urls[$u['utype']]);
        }
    }
}
