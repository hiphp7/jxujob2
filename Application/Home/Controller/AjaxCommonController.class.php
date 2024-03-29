<?php
namespace Home\Controller;

use Common\Controller\FrontendController;

class AjaxCommonController extends FrontendController
{
    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * [jobs_list 分页获取职位列表信息]
     */
    public function jobs_list()
    {
        $where = array(
            '排序' => 'rtime',
            '显示数目' => '20',
            '分页显示' => 1,
            '职位数量' => 3
        );
        $jobs_mod = new \Common\qscmstag\company_jobs_listTag($where);
        $jobs_list = $jobs_mod->run();
        $this->assign('new_jobs', $jobs_list['list']);
        $data['html'] = $this->fetch('AjaxCommon/index_jobs_list');
        $data['isfull'] = $jobs_list['page_params']['nowPage'] >= $jobs_list['page_params']['totalPages'];
        $this->ajaxReturn(1, '职位列表信息获取成功！', $data);
    }
    /**
     * [jobs_click 职位查看次数加一]
     */
    public function jobs_click()
    {
        $id = I('id', 0, 'intval');
        !$id && $this->ajaxReturn(0, '请选择要查看的职位！');
        $where = array('id'=>$id);
        if (C('apply.Jobclickup')) {
            $range = explode(",", C('qscms_job_clickup_range'));
            $inc_num = rand($range[0], $range[1]);
        } else {
            $inc_num = 1;
        }
        if ($jobs = M('Jobs')->where($where)->find()) {
            $mod = M('Jobs');
            M('JobsSearch')->where($where)->setInc('click', $inc_num);
            M('JobsSearchKey')->where($where)->setInc('click', $inc_num);
        } else {
            $mod = M('JobsTmp');
        }
        $mod->where($where)->setInc('click', $inc_num);
        $click = $mod->where($where)->getfield('click');
        $this->ajaxReturn(1, '查看次数', $click);
    }
    /**
     * [resume_list 分页获取简历列表信息]
     */
    public function resume_list()
    {
        $where = array(
            '照片' => 1,
            '显示数目' => 5,
            '分页显示' => 1
        );
        $resume_mod = new \Common\qscmstag\resume_listTag($where);
        $resume_list = $resume_mod->run();
        $this->assign('resume_list', $resume_list['list']);
        $data['html'] = $this->fetch('AjaxCommon/index_resume_list');
        $data['isfull'] = $resume_list['page_params']['nowPage'] >= $resume_list['page_params']['totalPages'];
        $this->ajaxReturn(1, '简历列表信息获取成功！', $data);
    }
    /**
     * [news_list 资讯列表]
     */
    public function news_list()
    {
        $type_id = I('get.type_id', 0, 'intval');
        !$type_id && $this->ajaxReturn(0, '请选择资讯类型！');
        $where = array(
            '显示数目' => '15',
            '资讯小类' => $type_id
        );
        $news_mod = new \Common\qscmstag\news_listTag($where);
        $news_list = $news_mod->run();
        $this->assign('article_list', $news_list['list']);
        $tpl=$this->fetch('index_news_list');
        $this->ajaxReturn(1, '资讯列表信息获取成功！', $tpl);
    }
    /**
     * [list_show_type 列表页显示方式]
     */
    public function list_show_type()
    {
        $action = I('get.action', '', 'trim');
        $type = I('get.type', null, 'intval');
        if (!$action || !in_array($action, array('jobs','resume'))) {
            return false;
        }
        $type = $type ? 1 : null;
        cookie($action.'_show_type', $type);
        $this->ajaxReturn(1, '设置成功！');
    }
    /**
     * [get_header_min description]
     */
    public function get_header_min()
    {
        if ($this->visitor->is_login) {
            if (C('visitor.utype') == 1) {
                $company = M('CompanyProfile')->field('id,companyname,contents')->where(array('uid'=>C('visitor.uid')))->find();
                $company['companyname'] = cut_str($company['companyname'], 5, 0, '…');
                $jobs = M('Jobs')->where(array('uid'=>C('visitor.uid')))->find();
                $setmeal=D('MembersSetmeal')->get_user_setmeal(C('visitor.uid'));
                // 统计有效职位数
                $jobs_num = D('Jobs')->where(array('uid'=>C('visitor.uid')))->count();
                if ($jobs_num>=$setmeal['jobs_meanwhile']) {
                    $upper_limit = 1;
                }
                $this->assign('company', $company);
                $this->assign('cominfo_flge', $cominfo_flge);
                $this->assign('jobs', $jobs);
                $this->assign('upper_limit', $upper_limit);
            } else {
                $realname = M('MembersInfo')->where(array('uid'=>C('visitor.uid')))->getfield('realname');
                $resume = M('Resume')->where(array('uid'=>C('visitor.uid'),'def'=>1))->getfield('id');
                $refrestime = M('RefreshLog')->where(array('uid'=>C('visitor.uid'),'type'=>2001))->order('addtime desc')->getfield('addtime');
                $this->assign('realname', $realname);
                $this->assign('refresh', $refrestime > strtotime('today') ? 1 : 0);
                $this->assign('resume', $resume);
            }
        }
        $data['html'] = $this->fetch('AjaxCommon/header_min');
        $this->ajaxReturn(1, '', $data);
    }
    /**
     * [get_login_dig 获取登录弹窗]
     */
    public function get_login_dig()
    {
        if (false === $oauth_list = F('oauth_list')) {
            $oauth_list = D('Oauth')->oauth_cache();
        }
        $this->assign('oauth_list', $oauth_list);
        $this->assign('verify_userlogin', $this->check_captcha_open(C('qscms_captcha_config.user_login'), 'error_login_count'));
        $type = I('get.type', '', 'trim');
        $tpl = $type=='job'?'job_login':'login';
        $data['html'] = $this->fetch('AjaxCommon/'.$tpl);
        $this->ajaxReturn(1, '快速登录窗口', $data);
    }
    /**
     * [jobs_click 资讯查看次数加一]
     */
    public function news_click()
    {
        $id = I('id', 0, 'intval');
        !$id && $this->ajaxReturn(0, '请选择要查看的资讯！');
        $where = array('id'=>$id);
        M('Article')->where($where)->setInc('click', 1);
        $click = M('Article')->where($where)->getfield('click');
        $this->ajaxReturn(1, '查看次数', $click);
    }
    /**
     * [jobs_click 公告查看次数加一]
     */
    public function notice_click()
    {
        $id = I('id', 0, 'intval');
        !$id && $this->ajaxReturn(0, '请选择要查看的公告！');
        $where = array('id'=>$id);
        M('Notice')->where($where)->setInc('click', 1);
        $click = M('Notice')->where($where)->getfield('click');
        $this->ajaxReturn(1, '查看次数', $click);
    }
    public function ajax_search_location()
    {
        $this->ajaxReturn(1, '', url_rewrite(I('get.type', 'QS_jobslist', 'trim'), I('post.')));
    }
    /**
     * [get_com_jobs 获取企业下职位列表]
     */
    public function get_com_jobs()
    {
        $uid = I('get.uid', 0, 'intval');
        $p = I('get.p', 2, 'intval');
        !$uid && $this->ajaxReturn(0, '请选择企业！');
        $where = array(
            '会员uid' => $uid,
            '显示数目' => '3',
            '分页显示' => 1
        );
        $jobs_mod = new \Common\qscmstag\jobs_listTag($where);
        $jobs_list = $jobs_mod->run();
        $jobs_list['com_jobs_url'] = url_rewrite('QS_companyjobs', array('id'=>$jobs_list['list'][0]['company_id']));
        $jobs_list['isfull'] = $p > $jobs_list['page_params']['totalPages'];
        $this->ajaxReturn(1, '', $jobs_list);
    }
    /**
     * 增加企业访客统计
     */
    public function company_statistics_add()
    {
        $data['comid'] = I('get.comid', 0, 'intval');
        $data['jobid'] = I('get.jobid', 0, 'intval');
        $data['uid'] = intval(C('visitor.uid'));
        $model = D('CompanyStatistics');
        $model->create($data);
        $model->add();
    }
    /**
     * [hotword 搜索关健字联想]
     */
    public function hotword()
    {
        $key = I('get.query', '', 'trim');
        !$key && $this->ajaxReturn(0, '请输入关健字！');
        $reg = D('Hotword')->get_hotword($key);
        if ($reg) {
            $this->ajaxReturn(1, '联想词获取成功！', array('query'=>$key,'suggestions'=>$reg));
        }
        $this->ajaxReturn(0);
    }
    /**
     * [ajax_login 获取登录弹窗]
     */
    public function ajax_login()
    {
        if (false === $oauth_list = F('oauth_list')) {
            $oauth_list = D('Oauth')->oauth_cache();
        }
        $this->assign('oauth_list', $oauth_list);
        $data['html'] = $this->fetch('AjaxCommon/ajax_login');
        $this->ajaxReturn(1, '登录窗口', $data);
    }
}
