<?php
namespace Gworker\Controller;

use Common\Controller\FrontendController;

class IndexController extends FrontendController {
    public $authentication_user;
    public $cache_apply_info;

    public function _initialize() {
        parent::_initialize();
        $this->authentication_user = session('authentication_user') ? session('authentication_user') : cookie('authentication_user');
        $this->cache_apply_info = session('cache_apply_info') ? session('cache_apply_info') : cookie('cache_apply_info');
    }
    /**
     * 首页
     */
    public function index(){
        $this->display();
    }
    public function do_auth() {
        $this->auth_mobile_code();
        $this->ajaxReturn(1, '验证通过');
    }
    public function authenticate() {
        $this->assign('auth_url_referrer', session('auth_url_referrer'));
        $this->_config_seo(array('title' => '身份验证 - 普工招聘 - ' . C('qscms_site_name')));
        $this->display();
    }
    private function get_current_url() {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
        return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
    }
    private function check_auth() {
        if (!$this->authentication_user) {
            if (IS_AJAX) {
                $this->ajaxReturn(0, '请先验证身份！');
            } else {
                session('auth_url_referrer', $this->get_current_url());
                $this->redirect('authenticate');
            }
        } else {
            $auth_info = D('Authentication')->where(array('mobile' => $this->authentication_user['mobile'], 'secretkey' => $this->authentication_user['secretkey']))->find();
            if (!$auth_info) {
                if (IS_AJAX) {
                    $this->ajaxReturn(0, '请先验证身份！');
                } else {
                    session('auth_url_referrer', $this->get_current_url());
                    $this->redirect('authenticate');
                }
            }
        }
    }
    /**
     * 查询可发布数量
     */
    public function check_jobs_num(){
        if ($this->authentication_user) {
            $count_jobs = D('Gworker/GworkerJobs')->where(array('uid' => $this->authentication_user['id']))->count();
            if ($count_jobs >= C('qscms_gworker_max')) {
                $this->ajaxReturn(0, '你已发布的普工职位数已超出限制！');
            } else {
                $this->ajaxReturn(1, '可以发布');
            }
        } else {
            $this->ajaxReturn(1, '可以发布');
        }
    }
    
    /**
     * 新增招聘
     */
    public function add(){
        if(IS_POST){
            $post_data = I('post.');
            if(!$this->authentication_user){
                $this->auth_mobile_code();
                $post_data['uid'] = $this->authentication_user['id'];
            }else{
                $auth_info = D('Authentication')->where(array('mobile'=>$this->authentication_user['mobile'],'secretkey'=>$this->authentication_user['secretkey']))->find();
                if(!$auth_info){
                    $this->ajaxReturn(0,'请先验证身份！');
                }else{
                    $post_data['uid'] = $this->authentication_user['id'];
                }
            }
            if(C('SUBSITE_VAL.s_id') > 0){
                $post_data['subsite_id'] = C('SUBSITE_VAL.s_id');
            }
            
            if(false === $reg = D('Gworker/GworkerPublishApply')->create($post_data)){
                $this->ajaxReturn(0,D('Gworker/GworkerPublishApply')->getError());
            }else{
                if(false === D('Gworker/GworkerPublishApply')->add($reg)){
                    $this->ajaxReturn(0,D('Gworker/GworkerPublishApply')->getError());
                }else{
                    $this->ajaxReturn(1,'提交成功，稍候客服将联系您！',array('url'=>U('index')));
                }
            }
        }else{
            $count_jobs = D('Gworker/GworkerPublishApply')->where(array('uid'=>$this->authentication_user['id']))->count();
            if($count_jobs>=C('qscms_gworker_max')){
                exit('你已发布的职位数已超出限制！');
            }
            $category_wage = D('Gworker/GworkerCategory')->get_category_cache('QS_wage');
            $this->assign('need_mobile_audit',$this->authentication_user?0:1);
            $this->assign('contact',$this->authentication_user['contact']);
            $this->assign('mobile',$this->authentication_user['mobile']);
            $this->assign('new_record',1);
            $this->assign('category_wage',$category_wage);
            $this->assign('leave_jobs_num',C('qscms_gworker_max')-$count_jobs);
            $this->_config_seo(array('title' => '发布普工 - ' . C('qscms_site_name')));
            $this->display();
        }
    }
    // 发送短信验证码
    public function send_sms() {
        if(C('qscms_captcha_open') && C('qscms_captcha_config.varify_mobile') && true !== $reg = \Common\qscmslib\captcha::verify()) $this->ajaxReturn(0,$reg);
        $mobile = I('post.mobile', '', 'trim');
        !$mobile && $this->ajaxReturn(0, '请填手机号码！');
        if (!fieldRegex($mobile, 'mobile')) $this->ajaxReturn(0, '手机号错误！');
        $rand = getmobilecode();
        $sendSms['tpl'] = 'set_login';
        $sendSms['data'] = array('rand' => $rand . '', 'sitename' => C('qscms_site_name'));
        $smsVerify = session('login_smsVerify');
        if ($smsVerify && $smsVerify['mobile'] == $mobile && time() < $smsVerify['time'] + 180) $this->ajaxReturn(0, '180秒内仅能获取一次短信验证码,请稍后重试');
        $sendSms['mobile'] = $mobile;
        if (true === $reg = D('Sms')->sendSms('captcha', $sendSms)) {
            session('login_smsVerify', array('rand' => substr(md5($rand), 8, 16), 'time' => time(), 'mobile' => $mobile));
            $this->ajaxReturn(1, '手机验证码发送成功！');
        } else {
            $this->ajaxReturn(0, $reg);
        }
    }
    private function auth_mobile_code() {
        $expire = I('post.expire', 1, 'intval');
        if ($mobile = I('post.mobile', '', 'trim')) {
            if (!fieldRegex($mobile, 'mobile')) $this->ajaxReturn(0, '手机号格式错误！');
            $smsVerify = session('login_smsVerify');
            !$smsVerify && $this->ajaxReturn(0, '手机验证码错误！');//验证码错误！
            if ($mobile != $smsVerify['mobile']) $this->ajaxReturn(0, '手机号不一致！');//手机号不一致
            if (time() > $smsVerify['time'] + 600) $this->ajaxReturn(0, '验证码过期！');//验证码过期
            $vcode_sms = I('post.mobile_vcode', 0, 'intval');
            $mobile_rand = substr(md5($vcode_sms), 8, 16);
            if ($mobile_rand != $smsVerify['rand']) $this->ajaxReturn(0, '手机验证码错误！');//验证码错误！
            $user = D('Authentication')->where(array('mobile' => $smsVerify['mobile']))->find();
            if (!$user) {
                $user = D('Authentication')->add_auth_info($smsVerify['mobile']);
            }
            $user['contact'] = I('post.contact', '', 'trim');
            session('authentication_user', $user);
            cookie('authentication_user', $user);
            session('login_smsVerify', null);
            $this->authentication_user = $user;
        }
        if ($user) $this->apply_login($smsVerify['mobile'],$expire);
    }
    /**
     * 招聘详情
     */
    public function show($id){
        $this->display();
    }
    
    public function check_apply_cache() {
        $pid = I('request.pid', 0, 'intval');
        if ($this->authentication_user) {
            $apply = D('Gworker/GworkerJobsApply')->where(array('uid' => $this->authentication_user['id'], 'pid' => $pid))->find();
            if ($apply) {
                $this->ajaxReturn(1, '你已经报名过该职位了！');
            }
            if ($this->cache_apply_info) {
                $addsql = $this->cache_apply_info;
                $addsql['pid'] = $pid;
                $addsql['addtime'] = time();
                D('Gworker/GworkerJobsApply')->add($addsql);
                $this->ajaxReturn(1, '报名成功！');
            } else {
                $this->ajaxReturn(0, '');
            }
        } else {
            $this->ajaxReturn(0, '');
        }
    }
    /**
     * 报名普工
     */
    public function apply() {
        if (IS_POST) {
            $post_data = I('post.');
            if (!$this->authentication_user) {
                $this->auth_mobile_code();
                $post_data['uid'] = $this->authentication_user['id'];
            } else {
                $auth_info = D('Authentication')->where(array('mobile' => $this->authentication_user['mobile'], 'secretkey' => $this->authentication_user['secretkey']))->find();
                if (!$auth_info) {
                    $this->ajaxReturn(0, '请先验证身份！');
                } else {
                    $post_data['uid'] = $this->authentication_user['id'];
                }
            }
            if (false === $reg = D('Gworker/GworkerJobsApply')->create($post_data)) {
                $this->ajaxReturn(0, D('Gworker/GworkerJobsApply')->getError());
            } else {
                if (false === D('Gworker/GworkerJobsApply')->add($reg)) {
                    $this->ajaxReturn(0, D('Gworker/GworkerJobsApply')->getError());
                } else {
                    unset($reg['pid'], $reg['addtime']);
                    session('cache_apply_info', $reg);
                    cookie('cache_apply_info', $reg);
                    $this->cache_apply_info = $reg;
                    $this->ajaxReturn(1, '报名成功！');
                }
            }
        } else {
            $id = I('get.id', 0, 'intval');
            $info = D('Gworker/GworkerJobs')->find($id);
            !$info && $this->ajaxReturn(0,'参数错误！');
            $birthdate_arr[] = date('Y') - 18;
            for ($i = 1; $i <= 42; $i++) {
                $birthdate_arr[] = $birthdate_arr[0] - $i;
            }
            $this->assign('authentication_user', $this->authentication_user);
            $this->assign('need_audit', $this->authentication_user ? 0 : 1);
            $this->assign('birthdate_arr', $birthdate_arr);
            $html = $this->fetch('apply');
            $this->ajaxReturn(1,'弹窗获取成功！',$html);
        }
    }
    
}

?>