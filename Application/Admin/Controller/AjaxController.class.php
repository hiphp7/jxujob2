<?php
namespace Admin\Controller;

use Common\Controller\BackendController;

class AjaxController extends BackendController
{
    public function _initialize()
    {
        parent::_initialize();
    }
    public function userinfo()
    {
        $uid = I('get.uid', 0, 'intval');
        if (!$uid) {
            $this->ajaxReturn(0, '参数错误！');
        }
        $userinfo = D('Members')->get_user_one(array('uid'=>$uid));
        $manage_url = $userinfo['utype']==1?U('Company/management', array('id'=>$userinfo['uid'])):U('Personal/management', array('id'=>$userinfo['uid']));
        if ($userinfo['utype']==1) {
            $consultant = D('Consultant')->find($userinfo['consultant']);
            $this->assign('consultant', $consultant);
            $company_profile = D('CompanyProfile')->where(array('uid'=>$userinfo['uid']))->find();
            !$company_profile && $company_profile['companyname'] = $userinfo['username'];
            $this->assign('company_profile', $company_profile);
        } else {
            $userinfo['realname'] = M('MembersInfo')->where(array('uid'=>$uid))->getfield('realname');
            $this->assign('resume_manage', U('personal/management', array('id'=>$userinfo['uid'],'action'=>'resume')));
        }
        $this->assign('userinfo', $userinfo);
        $this->assign('manage_url', $manage_url);
        $html = $this->fetch('userinfo');
        $this->ajaxReturn(1, '获取数据成功！', $html);
    }
    public function ajax_send_sms()
    {
        $uid = I('request.uid', 0, 'intval');
        $mobile = I('request.mobile', '', 'trim');
        $service_info = D('Sms')->where(array('alias'=>C('qscms_sms_other_service')))->find();
        if (!$service_info) {
            $this->ajaxReturn(0, '短信配置错误，请修改后再发送！');
        }
        if (IS_POST) {
            if (!$uid) {
                $this->error('用户UID错误！');
            }
            $setsqlarr['s_mobile']=$mobile?$mobile:$this->error('手机不能为空！');
            $setsqlarr['s_body']=I('request.txt', '', 'trim')?:$this->error('短信内容不能为空！');
            $setsqlarr['s_addtime']=time();
            $setsqlarr['s_uid']=$uid;
            $setsqlarr['s_tplid']=I('request.tplid', '', 'trim');
            if ($setsqlarr['s_tplid']) {
                $data = array('mobile'=>$setsqlarr['s_mobile'],'tpl'=>$setsqlarr['s_body'],'tplId'=>$setsqlarr['s_tplid'],'data'=>array());
                $service = C('qscms_sms_other_service');
                $sms = new \Common\qscmslib\sms($service);
                $r = $sms->sendTemplateSMS('other', $data);
            } else {
                $r = D('Sms')->sendSms('other', array('mobile'=>$setsqlarr['s_mobile'],'tplStr'=>$setsqlarr['s_body']));
            }
            if (true===$r) {
                $setsqlarr['s_sendtime']=time();
                $setsqlarr['s_type']=2;//发送成功
                D('Smsqueue')->add($setsqlarr);
                unset($setsqlarr);
                $this->success('发送成功！');
            } else {
                $setsqlarr['s_sendtime']=time();
                $setsqlarr['s_type']=3;//发送失败
                D('Smsqueue')->add($setsqlarr);
                unset($setsqlarr);
                $this->error('发送失败，错误未知！');
            }
        } else {
            $this->assign('uid', $uid);
            $this->assign('mobile', $mobile);
            $this->assign('service_info', $service_info);
            $this->assign('need_tpl', $service_info['replace']?1:0);
            $html = $this->fetch('send_sms');
            $this->ajaxReturn(1, '获取数据成功！', $html);
        }
    }
    public function ajax_send_email()
    {
        $uid = I('request.uid', 0, 'intval');
        $email = I('request.email', '', 'trim');
        if (IS_POST) {
            $subject = I('request.subject', '', 'trim');
            $body = I('request.body', '', 'trim');
            if (!$uid) {
                $this->error('用户UID错误！');
            }
            $setsqlarr['m_mail']=$email?$email:$this->error('邮件地址必须填写！');
            if (!$email || !fieldRegex($setsqlarr['m_mail'], 'email')) {
                $this->error('邮箱格式错误！');
            }
            $setsqlarr['m_subject']=$subject?$subject:$this->error('邮件标题必须填写！');
            $setsqlarr['m_body']=$body?$body:$this->error('邮件内容必须填写！');
            $setsqlarr['m_addtime']=time();
            $setsqlarr['m_uid']=$uid;
            if (D('Mailconfig')->send_mail(array('sendto_email'=>$setsqlarr['m_mail'],'subject'=>$setsqlarr['m_subject'],'body'=>$setsqlarr['m_body']))) {
                $setsqlarr['m_sendtime']=time();
                $setsqlarr['m_type']=2;//发送成功
                D('Mailqueue')->add($setsqlarr);
                unset($setsqlarr);
                $this->success('发送成功！', $url);
            } else {
                $setsqlarr['m_sendtime']=time();
                $setsqlarr['m_type']=3;//发送失败
                D('Mailqueue')->add($setsqlarr);
                unset($setsqlarr);
                $this->error('发送失败，错误未知！', $url);
            }
        } else {
            $this->assign('uid', $uid);
            $this->assign('email', $email);
            $html = $this->fetch('send_email');
            $this->ajaxReturn(1, '获取数据成功！', $html);
        }
    }
    public function ajax_send_pms()
    {
        $tousername = I('request.tousername', '', 'trim');
        if (IS_POST) {
            if (!$tousername) {
                $this->error('用户名填写错误！');
                exit;
            } else {
                $s=0;
                $msg=I('post.msg', '', 'trim');
                $time=time();
                $data = array();
                $userinfo = D('Members')->where(array('username'=>$tousername))->find();
                if (intval($userinfo['uid'])>0) {
                    $data['msgtype'] = 1;
                    $data['msgtouid'] = $userinfo['uid'];
                    $data['msgtoname'] = $userinfo['username'];
                    $data['message'] = $msg;
                    $data['dateline']=$time;
                    $data['replytime']=$time;
                    $data['new']=1;
                }
                D('Pms')->add($data);
                admin_write_log("发送消息,共发给了 1 个会员", C('visitor.username'), 3);
                $this->success("发送成功！");
                exit;
            }
        } else {
            $this->assign('tousername', $tousername);
            $html = $this->fetch('send_pms');
            $this->ajaxReturn(1, '获取数据成功！', $html);
        }
    }
    /**
     * 审核日志
     */
    public function audit_log()
    {
        $type = I('get.type', 'jobs_id', 'trim');
        $id = I('get.id', 0, 'intval');
        switch ($type) {
            case 'jobs_id':
            case 'resume_id':
            case 'company_id':
                $list = D('AuditReason')->where(array($type=>$id,'famous'=>0))->order('id desc')->select();
                break;
            default:
                $list = null;
                break;
        }
        $this->assign('list', $list);
        $html = $this->fetch('audit_log');
        $this->ajaxReturn(1, '获取数据成功！', $html);
    }
    /**
     * [famous_log description]
     */
    public function famous_log()
    {
        $id = I('get.id', 0, 'intval');
        $list = D('AuditReason')->where(array('company_id'=>$id,'famous'=>1))->order('id desc')->select();
        $this->assign('list', $list);
        $html = $this->fetch('famous_log');
        $this->ajaxReturn(1, '获取数据成功！', $html);
    }
    /**
     * 职位日志
     */
    public function jobs_log()
    {
        $id = I('get.id', 0, 'intval');
        $list = D('MembersLog')->where(array('param_id'=>$id))->order('log_id desc')->select();
        $this->assign('list', $list);
        $html = $this->fetch('jobs_log');
        $this->ajaxReturn(1, '获取数据成功！', $html);
    }
    /**
     * 登录日志
     */
    public function login_log()
    {
        $id = I('get.id', 0, 'intval');
        $list = D('MembersLog')->where(array('log_uid'=>$id,'log_type'=>1001))->order('log_addtime desc')->limit('5')->select();
        $this->assign('list', $list);
        $html = $this->fetch('login_log');
        $this->ajaxReturn(1, '获取数据成功！', $html);
    }
    /**
     * 套餐详情
     */
    public function setmeal_detail()
    {
        $uid = I('get.uid', 0, 'intval');
        $info = D('MembersSetmeal')->get_user_setmeal($uid);
        $list = M('MembersChargeLog')->field('log_uid,log_addtime,log_value,log_mode')->where(array('log_uid'=>$uid))->select();
        $this->assign('info', $info);
        $this->assign('list', $list);
        $html = $this->fetch('setmeal_detail');
        $this->ajaxReturn(1, '获取数据成功！', $html);
    }
    /**
     * [ajax_subsite description]
     */
    public function ajax_subsite()
    {
        $uid = I('get.id', 0, 'intval');
        $subsites = D('Admin')->where(array('id'=>$uid))->getfield('subsite');
        $this->assign('subsites', explode(',', $subsites));
        $html = $this->fetch('subsite');
        $this->ajaxReturn(1, '获取数据成功！', $html);
    }
    /**
     * [affair_stat 待处理事务统计]
     */
    public function affair_stat()
    {
        $affair = I('request.affair', '', 'trim');
        foreach ($affair as $key => $val) {
            $name = '_affair_'.$val;
            $d = $this->$name();
            $d && $list[$val] = $d;
        }
        $this->ajaxReturn(1, '获取成功！', $list);
    }
    /**
     * [_affair_resume 待处理简历]
     */
    protected function _affair_resume()
    {
        $count = parent::_pending('Resume', array('audit'=>2));
        return $count ?:false;
    }
    /**
     * [_affair_resume_img 待处理简历作品]
     */
    protected function _affair_resume_img()
    {
        $count = parent::_pending('ResumeImg', array('audit'=>2));
        return $count ?:false;
    }
    /**
     * [_affair_photo 待处理简历照片]
     */
    protected function _affair_photo()
    {
        $count = parent::_pending('Resume', array('photo_img'=>array('neq',''),'photo_audit'=>2), 'uid');
        return $count ?:false;
    }
    /**
     * [_affair_jobs 待处理职位]
     */
    protected function _affair_jobs()
    {
        $count = parent::_pending('Jobs', array('audit'=>2));
        $count1 = parent::_pending('JobsTmp', array('audit'=>2));
        $count = $count + $count1;
        return $count ?:false;
    }
    /**
     * [_affair_company 待认证企业]
     */
    protected function _affair_company_audit()
    {
        $count = parent::_pending('CompanyProfile', array('audit'=>2));
        return $count ?:false;
    }
    /**
     * [_affair_company_img 待处理企业风采]
     */
    protected function _affair_company_img()
    {
        $count = parent::_pending('CompanyImg', array('audit'=>2));
        return $count ?:false;
    }
    /**
     * [_affair_company_order 待处理企业线下支付订单]
     */
    protected function _affair_company_order()
    {
        $count = parent::_pending('Order', array('payment'=>'remittance','is_paid'=>1));
        return $count ?:false;
    }
    /**
     * [_affair_report 待处理举报信息]
     */
    protected function _affair_report()
    {
        $count = parent::_pending('Report', array('audit'=>1));
        $count1 = parent::_pending('ReportResume', array('audit'=>1));
        $count = $count + $count1;
        return $count ?:false;
    }
    /**
     * [_affair_feedback 待处理意见与建议]
     */
    protected function _affair_feedback()
    {
        $count = parent::_pending('Feedback', array('audit'=>1));
        return $count ?:false;
    }
    /**
     * [_affair_appeal 待处理帐号申诉]
     */
    protected function _affair_appeal()
    {
        $count = parent::_pending('MembersAppeal', array('status'=>0));
        return $count ?:false;
    }
    /**
     * [_affair_exhibitors 待处理参会企业]
     */
    protected function _affair_exhibitors()
    {
        $count = parent::_pending('JobfairExhibitors', array('audit'=>2));
        return $count ?:false;
    }
    /**
     * [_affair_mall_order 待处理商城订单]
     */
    protected function _affair_mall_order()
    {
        $count = parent::_pending('MallOrder', array('status'=>1));
        return $count ?:false;
    }
    /**
     * [_affair_order_invoice 待处理发票]
     */
    protected function _affair_order_invoice()
    {
        $count = parent::_pending('OrderInvoice', array('audit'=>0));
        return $count ?:false;
    }
    /**
     * [affair 主菜单待处理事务统计]
     */
    public function affair()
    {
        $affair = I('request.affair', '', 'trim');
        foreach ($affair as $key => $val) {
            $name = '_total_affair_'.$val;
            $d = $this->$name();
            $d && $list[$val] = $d;
        }
        $this->ajaxReturn(1, '获取成功！', $list);
    }
    protected function _subsite($mod, $where)
    {
        $field = M($mod)->getDbFields();
        if ($this->apply['Subsite'] && in_array('subsite_id', $field) && C('visitor.subsite')) {
            $where['subsite_id'] = array('in',C('visitor.subsite'));
        }
        return M($mod)->where($where)->find();
    }
    /**
     * [_total_affair_personal 个人事务]
     */
    protected function _total_affair_personal()
    {
        $s = $this->_subsite('Resume', array('audit'=>2));
        if ($s) {
            return 1;
        }
        $s = $this->_subsite('ResumeImg', array('audit'=>2));
        if ($s) {
            return 1;
        }
        $s = $this->_subsite('Resume', array('photo_img'=>array('neq',''),'photo_audit'=>2));
        return false;
    }
    /**
     * [_total_affair_company 企业事务]
     */
    protected function _total_affair_company()
    {
        $s = $this->_subsite('Jobs', array('audit'=>2));
        if ($s) {
            return 1;
        }
        $s = $this->_subsite('JobsTmp', array('audit'=>2));
        if ($s) {
            return 1;
        }
        $s = $this->_subsite('CompanyProfile', array('audit'=>2));
        if ($s) {
            return 1;
        }
        $s = $this->_subsite('CompanyImg', array('audit'=>2));
        if ($s) {
            return 1;
        }
        $s = $this->_subsite('OrderInvoice', array('audit'=>2));
        if ($s) {
            return 1;
        }
        $s = $this->_subsite('Order', array('payment'=>'remittance','is_paid'=>1));
        if ($s) {
            return 1;
        }
        return false;
    }
    /**
     * [_total_affair_content 待处理内容]
     */
    protected function _total_affair_content()
    {
        $s = $this->_subsite('Report', array('audit'=>1));
        if ($s) {
            return 1;
        }
        $s = $this->_subsite('ReportResume', array('audit'=>1));
        if ($s) {
            return 1;
        }
        $s = $this->_subsite('Feedback', array('audit'=>1));
        if ($s) {
            return 1;
        }
        $s = $this->_subsite('MembersAppeal', array('status'=>0));
        if ($s) {
            return 1;
        }
        return false;
    }
    /**
     * [_total_affair_jobfair 待处理招聘会]
     */
    protected function _total_affair_jobfair()
    {
        $s = $this->_subsite('JobfairExhibitors', array('audit'=>2));
        if ($s) {
            return 1;
        }
        return false;
    }
    /**
     * [_total_affair_mall 待处理商城]
     */
    protected function _total_affair_mall()
    {
        $s = $this->_subsite('MallOrder', array('status'=>1));
        if ($s) {
            return 1;
        }
        return false;
    }
}
