<?php
namespace Parttime\Controller;

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
        $this->_config_seo(array('title' => '身份验证 - 兼职招聘 - ' . C('qscms_site_name')));
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
            $count_jobs = D('Parttime/ParttimeJobs')->where(array('uid' => $this->authentication_user['id']))->count();
            if ($count_jobs >= C('qscms_parttime_max')) {
                $this->ajaxReturn(0, '你已发布的兼职职位数已超出限制！');
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
            if($post_data['long_period']==1){
                $post_data['cycle_starttime'] = $post_data['cycle_endtime'] = 0;
            }else{
                if($post_data['cycle_starttime']){
                    $post_data['cycle_starttime'] = strtotime($post_data['cycle_starttime']);
                }
                if($post_data['cycle_endtime']){
                    $post_data['cycle_endtime'] = strtotime($post_data['cycle_endtime']);
                }
            }
            if(false === $reg = D('Parttime/ParttimeJobs')->create($post_data)){
                $this->ajaxReturn(0,D('Parttime/ParttimeJobs')->getError());
            }else{
                if(false === D('Parttime/ParttimeJobs')->add($reg)){
                    $this->ajaxReturn(0,D('Parttime/ParttimeJobs')->getError());
                }else{
                    $this->ajaxReturn(1,'发布成功！',array('url'=>U('manage')));
                }
            }
        }else{
            $count_jobs = D('Parttime/ParttimeJobs')->where(array('uid'=>$this->authentication_user['id']))->count();
            if($count_jobs>=C('qscms_parttime_max')){
                exit('你已发布的职位数已超出限制！');
            }
            $category_jobs = D('Parttime/ParttimeCategory')->get_category_cache('QS_category_jobs');
            $category_wage = D('Parttime/ParttimeCategory')->get_category_cache('QS_wage_type');
            $category_settlement = D('Parttime/ParttimeCategory')->get_category_cache('QS_settlement');
            $this->assign('need_mobile_audit',$this->authentication_user?0:1);
            $this->assign('contact',$this->authentication_user['contact']);
            $this->assign('mobile',$this->authentication_user['mobile']);
            $this->assign('new_record',1);
            $this->assign('category_jobs',$category_jobs);
            $this->assign('category_wage',$category_wage);
            $this->assign('category_settlement',$category_settlement);
            $this->assign('leave_jobs_num',C('qscms_parttime_max')-$count_jobs);
            $this->_config_seo(array('title' => '发布兼职 - ' . C('qscms_site_name')));
            $this->display();
        }
    }
    /**
     * 修改职位
     */
    public function edit($id){
        if(IS_POST){
            $post_data = I('post.');
            if(!$this->authentication_user){
                $this->ajaxReturn(0,'请先验证身份！');
            }else{
                $auth_info = D('Authentication')->where(array('mobile'=>$this->authentication_user['mobile'],'secretkey'=>$this->authentication_user['secretkey']))->find();
                if(!$auth_info){
                    $this->ajaxReturn(0,'请先验证身份！');
                }
            }
            if($post_data['long_period']==1){
                $post_data['cycle_starttime'] = $post_data['cycle_endtime'] = 0;
            }else{
                if($post_data['cycle_starttime']){
                    $post_data['cycle_starttime'] = strtotime($post_data['cycle_starttime']);
                }
                if($post_data['cycle_endtime']){
                    $post_data['cycle_endtime'] = strtotime($post_data['cycle_endtime']);
                }
            }
            if(false === $reg = D('Parttime/ParttimeJobs')->create($post_data)){
                $this->ajaxReturn(0,D('Parttime/ParttimeJobs')->getError());
            }else{
                if(false === D('Parttime/ParttimeJobs')->save($reg)){
                    $this->ajaxReturn(0,D('Parttime/ParttimeJobs')->getError());
                }else{
                    D('Parttime/ParttimeJobs')->update_search($reg['id']);
                    $this->ajaxReturn(1,'修改成功！',array('url'=>U('manage')));
                }
            }
        }else{
            $this->check_auth();
            $category_jobs = D('Parttime/ParttimeCategory')->get_category_cache('QS_category_jobs');
            $category_wage = D('Parttime/ParttimeCategory')->get_category_cache('QS_wage_type');
            $category_settlement = D('Parttime/ParttimeCategory')->get_category_cache('QS_settlement');
            $jobs = D('Parttime/ParttimeJobs')->where(array('id'=>$id,'uid'=>$this->authentication_user['id']))->find();
            $jobs['cycle_starttime'] = $jobs['cycle_starttime']?date('Y-m-d',$jobs['cycle_starttime']):'';
            $jobs['cycle_endtime'] = $jobs['cycle_endtime']?date('Y-m-d',$jobs['cycle_endtime']):'';
            $jobs['worktime'] = $jobs['worktime']?unserialize($jobs['worktime']):'';
            $this->assign('mobile',$this->authentication_user['mobile']);
            $this->assign('new_record',0);
            $this->assign('jobs',$jobs);
            $this->assign('category_jobs',$category_jobs);
            $this->assign('category_wage',$category_wage);
            $this->assign('category_settlement',$category_settlement);
            $this->_config_seo(array('title' => '修改兼职职位 - ' . C('qscms_site_name')));
            $this->display('add');
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
            $apply = D('Parttime/ParttimeJobsApply')->where(array('uid' => $this->authentication_user['id'], 'pid' => $pid))->find();
            if ($apply) {
                $this->ajaxReturn(1, '你已经报名过该职位了！');
            }
            if ($this->cache_apply_info) {
                $addsql = $this->cache_apply_info;
                $addsql['pid'] = $pid;
                $addsql['addtime'] = time();
                D('Parttime/ParttimeJobsApply')->add($addsql);
                $this->ajaxReturn(1, '报名成功！');
            } else {
                $this->ajaxReturn(0, '');
            }
        } else {
            $this->ajaxReturn(0, '');
        }
    }
    /**
     * 报名门店职位
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
            if (false === $reg = D('Parttime/ParttimeJobsApply')->create($post_data)) {
                $this->ajaxReturn(0, D('Parttime/ParttimeJobsApply')->getError());
            } else {
                if (false === D('Parttime/ParttimeJobsApply')->add($reg)) {
                    $this->ajaxReturn(0, D('Parttime/ParttimeJobsApply')->getError());
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
            $info = D('Parttime/ParttimeJobs')->find($id);
            !$info && $this->ajaxReturn(0,'参数错误！');
            $birthdate_arr[] = date('Y') - 18;
            for ($i = 1; $i <= 42; $i++) {
                $birthdate_arr[] = $birthdate_arr[0] - $i;
            }
            $identity_arr = D('Parttime/ParttimeCategory')->get_category_cache('QS_identity_apply');
            $this->assign('authentication_user', $this->authentication_user);
            $this->assign('need_audit', $this->authentication_user ? 0 : 1);
            $this->assign('birthdate_arr', $birthdate_arr);
            $this->assign('identity_arr', $identity_arr);
            $html = $this->fetch('apply');
            $this->ajaxReturn(1,'面试邀请弹窗获取成功！',$html);
        }
    }
    public function manage(){
        $this->check_auth();
        $model = D('Parttime/ParttimeJobs');
        $map['uid'] = $this->authentication_user['id'];
        $key = I('request.key','','trim');
        $key!='' && $map['jobs_name'] = array('like','%'.$key.'%');
        $total = $model->where($map)->count();
        $pager = pager($total, 10);
        $page = $pager->fshow();
        $limit = $pager->firstRow.','.$pager->listRows;
        $joblist = $model->where($map)->limit($limit)->order('refreshtime desc,id desc')->select();
        foreach ($joblist as $key => $val) {
            $subsite_id = get_jobs_subsite_id($val);
            $val['url']=url_rewrite('QS_parttime_show',array('id'=>$val['id']),$subsite_id);
            $val['refreshtime_cn'] = date('Y-m-d H:i',$val['refreshtime']);
            $val['amount']=$val['amount']=="0"?'若干':$val['amount'];
            $val['wage_cn'] = $val['wage'].'元/'.$val['wage_type_cn'].'（'.$val['settlement_cn'].'）';
            $joblist[$key] = $val;
        }
        $audit_status_cn = $model->audit_status;
        $this->assign('joblist',$joblist);
        $this->assign('page',$page);
        $this->assign('audit_status_cn',$audit_status_cn);
        $this->_config_seo(array('title' => '兼职管理 - ' . C('qscms_site_name')));
        $this->display();
    }
    /**
     * 刷新职位
     */
    public function refresh() {
        $yid = I('request.id');
        if(IS_POST){
            if (!$this->authentication_user) {
                $this->ajaxReturn(0, '请先验证身份！');
            }
            if (!$yid) {
                $this->ajaxReturn(0, '请选择职位！');
            } else {
                $yid = is_array($yid)?$yid:array($yid);
                D('Parttime/ParttimeJobs')->refresh_jobs($yid,$this->authentication_user['id']);
                $this->ajaxReturn(1, '刷新成功！');
            }
        }
    }
    /**
     * 删除职位
     */
    public function delete() {
        $id = I('request.id');
        if(IS_POST){
            if (!$this->authentication_user) {
                $this->error('请先验证身份！');
            }
            if (!$id) {
                $this->error('请选择职位！');
            } else {
                $id = is_array($id)?$id:array($id);
                D('Parttime/ParttimeJobs')->where(array('id'=>array('in',$id),'uid'=>$this->authentication_user['id']))->delete();
                $this->success('删除成功！');
            }
        }else{
            if (!$this->authentication_user) {
                $this->ajaxReturn(0, '请先验证身份！');
            }
            if (!$id) {
                $this->ajaxReturn(0, '请选择职位！');
            } else {
                $id = is_array($id)?$id:array($id);
                D('Parttime/ParttimeJobs')->where(array('id'=>array('in',$id),'uid'=>$this->authentication_user['id']))->delete();
                $this->ajaxReturn(1, '删除成功！');
            }
        }
    }
    /**
     * 收到的报名
     */
    public function receive() {
        $map = array();
        $pid = I('get.pid', 0, 'intval');
        $pid && $map['pid'] = $pid;
        $model = D('Parttime/ParttimeJobsApply');
        $total = $model->where($map)->count();
        $pager = pager($total, 10);
        $page = $pager->fshow();
        $limit = $pager->firstRow . ',' . $pager->listRows;
        $applylist = $model->join($join)->where($map)->limit($limit)->order('id desc')->select();
        foreach ($applylist as $key => $val) {
            $subsite_id = get_jobs_subsite_id($val);
            $val['addtime_cn'] = date('Y-m-d H:i',$val['addtime']);
            $applylist[$key] = $val;
        }
        $this->assign('jobslist',D('Parttime/ParttimeJobs')->where(array('uid'=>$this->authentication_user['id']))->getField('id,jobs_name'));
        $this->assign('applylist', $applylist);
        $this->assign('pid', $pid);
        $this->assign('page', $page);
        $this->_config_seo(array('title' => '收到的报名 - 兼职招聘 - ' . C('qscms_site_name')));
        $this->display();
    }
    /**
     * 删除报名
     */
    public function delete_receive() {
        $id = I('request.id');
        $pid = I('request.pid');
        if(IS_POST){
            if (!$this->authentication_user) {
                $this->error('请先验证身份！');
            }
            if (!$id) {
                $this->error('请选择信息！');
            } else {
                $id = is_array($id)?$id:array($id);
                if($n = D('Parttime/ParttimeJobsApply')->where(array('id' => array('in',$id), 'pid' => $pid))->delete()){
                    D('Parttime/ParttimeJobs')->where(array('id' => $pid))->setDec('apply_num', $n);
                }
                $this->success('删除成功！');
            }
        }else{
            if (!$this->authentication_user) {
                $this->ajaxReturn(0, '请先验证身份！');
            }
            if (!$id) {
                $this->ajaxReturn(0, '请选择信息！');
            } else {
                $id = is_array($id)?$id:array($id);
                if($n = D('Parttime/ParttimeJobsApply')->where(array('id' => array('in',$id), 'pid' => $pid))->delete()){
                    D('Parttime/ParttimeJobs')->where(array('id' => $pid))->setDec('apply_num', $n);
                }
                $this->ajaxReturn(1, '删除成功！');
            }
        }
    }
}

?>