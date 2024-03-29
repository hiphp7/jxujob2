<?php
namespace Mobile\Controller;

use Mobile\Controller\MobileController;

class AjaxPersonalController extends MobileController
{
    public function _initialize()
    {
        parent::_initialize();
        //访问者控制
        if (!$this->visitor->is_login && !in_array(ACTION_NAME, array('resume_add_reg','resume_go','resume_add_dig','resume_add'))) {
            IS_AJAX && $this->ajaxReturn(0, L('login_please'), '', 1);
            $this->_404(L('login_please'));
        }
        if ($this->visitor->is_login && C('visitor.utype') != 2) {
            IS_AJAX && $this->ajaxReturn(0, '请登录个人帐号！');
            $this->redirect('members/index');
        }
    }
    /**
     * 关注企业/取消关注
     */
    public function company_focus($company_id)
    {
        if (!$company_id) {
            $this->ajaxReturn(0, '请选择企业！');
        }
        $r = D('PersonalFocusCompany')->add_focus($company_id, C('visitor.uid'));
        $this->ajaxReturn($r['state'], $r['msg'], $r['data']);
    }
    /**
     * [jobs_favorites 收藏职位]
     */
    public function jobs_favorites()
    {
        $jid = I('request.jid', '', 'trim');
        !$jid && $this->ajaxReturn(0, '请选择职位！');
        $has = $this->_check_favor($jid, C('visitor.uid'));
        if ($has) {
            D('PersonalFavorites')->where(array('jobs_id'=>$jid,'personal_uid'=>C('visitor.uid')))->delete();
            $this->ajaxReturn(1, '取消收藏成功！', 'cancel');
        } else {
            $reg = D('PersonalFavorites')->add_favorites($jid, C('visitor'));
            !$reg['state'] && $this->ajaxReturn(0, $reg['error']);
            $this->ajaxReturn(1, '收藏成功！');
        }
    }
    //检测是否已收藏
    protected function _check_favor($jobs_id, $uid)
    {
        $r = D('PersonalFavorites')->where(array('jobs_id'=>$jobs_id,'personal_uid'=>$uid))->find();
        if ($r) {
            return 1;
        } else {
            return 0;
        }
    }
    public function resume_add_dig()
    {
        if ($this->visitor->is_login) {
            $this->redirect('personal/index');
        }
        $category = D('Category')->get_category_cache();
        $birthdate_arr = range(date('Y')-16, date('Y')-65);
        $this->assign('education', $category['QS_education']);//最高学历
        $this->assign('experience', $category['QS_experience']);//工作经验
        $this->assign('birthdate_arr', $birthdate_arr);
        $this->assign('wage', $category['QS_wage']);//期望薪资
        $this->assign('jid', I('request.jid', 0, 'intval'));
        $this->_config_seo(array('title'=>'快速注册 - '.C('qscms_site_name'),'header_title'=>'快速注册'));
        $this->display('AjaxCommon/resume_add_dig');
    }
    /**
     * [resume_add_reg 快速创简历，并手机注册帐号]
     */
    public function resume_add_reg()
    {
        if ($this->visitor->is_login) {
            $this->redirect('personal/index');
        }
        $category = D('Category')->get_category_cache();
        $birthdate_arr = range(date('Y')-16, date('Y')-65);
        $this->assign('education', $category['QS_education']);//最高学历
        $this->assign('experience', $category['QS_experience']);//工作经验
        $this->assign('birthdate_arr', $birthdate_arr);
        $this->assign('wage', $category['QS_wage']);//期望薪资
        $this->assign('jid', I('request.jid', 0, 'intval'));
        $this->_config_seo(array('title'=>'创建简历 - '.C('qscms_site_name'),'header_title'=>'创建简历'));
        $this->display('AjaxCommon/resume_add_reg');
    }

     /**
     * [resume_go 快速创简历，并手机登录帐号]
     */
    public function resume_go()
    {
        //$jid = I('request.jid','','trim');
        //!$jid && $this->ajaxReturn(0,'请选择要投递的职位！');
        if (C('qscms_closereg')) {
            $this->ajaxReturn(0, '网站暂停会员注册，请稍后再次尝试！');
        }
        $data['reg_type'] = 1;
        $data['utype'] = 2;
        $data['mobile'] = I('post.telephone', 0, 'trim');
        $smsVerify = session('gsou_reg_smsVerify');
        if (!$smsVerify) {
            $this->ajaxReturn(0, '验证码错误！');
        }
        if ($data['mobile'] != $smsVerify['mobile']) {
            $this->ajaxReturn(0, '手机号不一致！');//手机号不一致
        }
        if (time()>$smsVerify['time']+600) {
            $this->ajaxReturn(0, '验证码过期！');//验证码过期
        }
        $vcode_sms = I('post.mobilevcode', 0, 'intval');
        $mobile_rand=substr(md5($vcode_sms), 8, 16);
        if ($mobile_rand!=$smsVerify['rand']) {
            $this->ajaxReturn(0, '验证码错误！');//验证码错误！
        }
        $passport = $this->_user_server();
        if (false === $data = $passport->register($data)) {
            $this->ajaxReturn(0, $passport->get_error());
        }

        $sendSms['tpl']='set_register_resume';
        $sendSms['data']=array('username'=>$data['username'].'','password'=>$data['password']);
        $sendSms['mobile']=$data['mobile'];
        if (true !== $reg = D('Sms')->sendSms('captcha', $sendSms)) {
            $this->ajaxReturn(0, $reg);
        }

        session('gsou_reg_smsVerify', null);
        D('Members')->user_register($data);
        if (false === $this->visitor->login($data['uid'])) {
            $this->ajaxReturn(0, $this->visitor->getError());
        }
        $ints = array('sex','birthdate','education','experience','wage');
        $trims = array('telephone','fullname','intention_jobs_id','district');
        foreach ($ints as $val) {
            $setsqlarr[$val] = I('post.'.$val, 0, 'intval');
        }
        foreach ($trims as $val) {
            $setsqlarr[$val] = I('post.'.$val, '', 'trim,badword');
        }
        $setsqlarr['nature'] = 62;
        $setsqlarr['def'] = 1;
        $setsqlarr['display_name'] = C('qscms_default_display_name');
        $rst=D('Resume')->add_resume($setsqlarr, $this->visitor->info);
        if (!$rst['state']) {
            $this->ajaxReturn(0, $rst['error']);
        }
        //$this->_resume_apply_exe($jid);
        $this->ajaxReturn(1, '简历创建成功！');
    }
    
    
    
    /**
     * [resume_add 快速创简历，并登录帐号]
     */
    public function resume_add()
    {
        $jid = I('request.jid', '', 'trim');
        !$jid && $this->ajaxReturn(0, '请选择要投递的职位！');
        if (C('qscms_closereg')) {
            $this->ajaxReturn(0, '网站暂停会员注册，请稍后再次尝试！');
        }
        $data['reg_type'] = 1;
        $data['utype'] = 2;
        $data['mobile'] = I('post.telephone', 0, 'trim');
        $smsVerify = session('gsou_reg_smsVerify');
        if (!$smsVerify) {
            $this->ajaxReturn(0, '验证码错误！');
        }
        if ($data['mobile'] != $smsVerify['mobile']) {
            $this->ajaxReturn(0, '手机号不一致！');//手机号不一致
        }
        if (time()>$smsVerify['time']+600) {
            $this->ajaxReturn(0, '验证码过期！');//验证码过期
        }
        $vcode_sms = I('post.mobilevcode', 0, 'intval');
        $mobile_rand=substr(md5($vcode_sms), 8, 16);
        if ($mobile_rand!=$smsVerify['rand']) {
            $this->ajaxReturn(0, '验证码错误！');//验证码错误！
        }
        $passport = $this->_user_server();
        if (false === $data = $passport->register($data)) {
            $this->ajaxReturn(0, $passport->get_error());
        }

        $sendSms['tpl']='set_register_resume';
        $sendSms['data']=array('username'=>$data['username'].'','password'=>$data['password']);
        $sendSms['mobile']=$data['mobile'];
        if (true !== $reg = D('Sms')->sendSms('captcha', $sendSms)) {
            $this->ajaxReturn(0, $reg);
        }

        session('gsou_reg_smsVerify', null);
        D('Members')->user_register($data);
        if (false === $this->visitor->login($data['uid'])) {
            $this->ajaxReturn(0, $this->visitor->getError());
        }
        $ints = array('sex','birthdate','education','experience','wage');
        $trims = array('telephone','fullname','intention_jobs_id','district');
        foreach ($ints as $val) {
            $setsqlarr[$val] = I('post.'.$val, 0, 'intval');
        }
        foreach ($trims as $val) {
            $setsqlarr[$val] = I('post.'.$val, '', 'trim,badword');
        }
        $setsqlarr['nature'] = 62;
        $setsqlarr['def'] = 1;
        $setsqlarr['display_name'] = C('qscms_default_display_name');
        $rst=D('Resume')->add_resume($setsqlarr, $this->visitor->info);
        if (!$rst['state']) {
            $this->ajaxReturn(0, $rst['error']);
        }
        $this->_resume_apply_exe($jid);
        $this->ajaxReturn(1, '简历创建成功！');
    }
    /**
     * [ resume_apply 简历投递]
     */
    public function resume_apply($jid)
    {
        $jid = I('request.jid');
        !$jid && $this->ajaxReturn(0, '请选择要投递的职位！');
        $this->_resume_apply_exe($jid);
    }
    protected function _resume_apply_exe($jid)
    {
        $reg = D('PersonalJobsApply')->jobs_apply_add($jid, $this->visitor->info);
        !$reg['state'] && $this->ajaxReturn(0, $reg['error'], $reg['create']);
        $reg['data']['failure'] && $this->ajaxReturn(0, $reg['data']['list'][$jid]['tip']);
        $this->ajaxReturn(1, '投递成功！');
    }
}
