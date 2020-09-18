<?php
namespace Admin\Controller;

use Common\Controller\BackendController;

class PersonalController extends BackendController
{
    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * [index 简历列表]
     */
    public function index()
    {
        $this->_name = 'Resume';
        $this->sort  = 'refreshtime';
        $key_type    = I('request.key_type', 0, 'intval');
        $orderby_str = I('get.orderby', 'addtime', 'trim');
        $key         = I('request.key', '', 'trim');
        if ($key_type && $key) {
            switch ($key_type) {
                case 1:
                    $where['fullname'] = array('like', '%' . $key . '%');
                    break;
                case 2:
                    $where['id'] = intval($key);
                    break;
                case 3:
                    $where['uid'] = intval($key);
                    break;
                case 4:
                    $where['telephone'] = array('like', '%' . $key . '%');
                    break;
                case 5:
                    $where['qq'] = array('like', '%' . $key . '%');
                    break;
                case 6:
                    $where['residence'] = array('like', '%' . $key . '%');
                    break;
            }
        } else {
            $tabletype = I('request.tabletype', 0, 'intval');
            $audit     = $tabletype == 1 ? 0 : I('request.audit', '', 'trim');
            if (I('request.photo', 0, 'intval') || I('request.photo_display', 0, 'intval')) {
                $this->sort = 'addtime';
            }
            if ($addtimesettr = I('request.addtimesettr', 0, 'intval')) {
                $where['addtime'] = array('gt', strtotime("-" . $addtimesettr . " day"));
                $this->sort       = 'addtime';
            }
            if ($settr = I('request.settr', 0, 'intval')) {
                $where['refreshtime'] = array('gt', strtotime("-" . $settr . " day"));
            }
            if ($photos = I('photos', '', 'intval')) {
                $photos == 1 && $where['photo_img'] = array('neq', '');
                $photos == 2 && $where['photo_img'] = array('eq', '');
            }
            if ($tabletype == 1) {
                $where['display'] = 1;
                //$where['audit'] = 1;
            } elseif ($tabletype == 2) {
                if ($audit != 3) {
                    if ($audit == '') {
                        $where['_string'] = '`display`=0 or `audit`=3';
                    } else {
                        $where['display'] = 0;
                    }
                }
            } elseif ($tabletype == 0) {
            }
        }
        $this->order      = 'field(audit,2) desc,' . $orderby_str . ' desc,id desc';
        $this->where      = $where;
        $this->custom_fun = '_format_resume_list';
        $this->_after_search_resume($tabletype);
        !$this->_tpl && $this->_tpl = 'index';
        parent::index();
    }
    /**
     * [photo 照片简历]
     */
    public function photo()
    {
        $_REQUEST['photos'] = $_GET['photos'] = 1;
        $this->_tpl         = 'photo';
        $this->distinct     = 'uid';
        $this->index();
    }
    /**
     * 待审核简历
     */
    public function index_noaudit()
    {
        $_REQUEST['audit'] = $_GET['audit'] = I('request.audit', 2, 'intval');
        $this->index();
    }
    /**
     * [resume_delete 删除简历]
     */
    public function resume_delete()
    {
        $id = I('request.id');
        if (!$id) {
            $this->error('请选择简历');
        }
        if ($n = D('Resume')->admin_del_resume($id)) {
            $this->success("删除成功！共删除{$n}行");
        } else {
            $this->error("删除失败！");
        }
    }
    /**
     * 审核简历
     */
    public function set_audit()
    {
        $id = I('request.id');
        if (!$id) {
            $this->error('请选择简历');
        }
        $audit      = I('post.audit', 0, 'intval');
        $pms_notice = I('post.pms_notice', 0, 'intval');
        $reason     = I('post.reason', '', 'trim');
        !D('Resume')->admin_edit_resume_audit($id, $audit, $reason, $pms_notice, C('visitor.username')) ? $this->error('设置失败！') : $this->success("设置成功！");
    }
    /**
     * 审核照片简历
     */
    public function set_photo_audit()
    {
        $id = I('request.id');
        if (!$id) {
            $this->error('请选择简历');
        }
        $audit      = I('post.photo_audit', 0, 'intval');
        $pms_notice = I('post.pms_notice', 0, 'intval');
        $reason     = I('post.reason', '', 'trim');
        !D('Resume')->admin_edit_resume_photo_audit($id, $audit, $reason, $pms_notice, C('visitor.username')) ? $this->error('设置失败！') : $this->success("设置成功！");
    }
    /**
     * 刷新简历
     */
    public function refresh()
    {
        $id = I('request.id');
        if (!$id) {
            $this->error('请选择简历');
        }
        if ($n = D('Resume')->admin_refresh_resume($id)) {
            $this->success("刷新成功！响应行数 {$n}");
        } else {
            $this->error("刷新失败！");
        }
    }
    /**
     * 个人会员
     */
    public function member_list()
    {
        $this->_name    = 'Members';
        $where['utype'] = 2;
        $key_type       = I('request.key_type', 0, 'intval');
        $key            = I('request.key', '', 'trim');
		$faire_id = I('request.faire_id', 0, 'intval');
		$source = I('request.source', '', 'trim');
        if ($key_type && $key) {
            switch ($key_type) {
                case 1:
                    $where['username'] = array('like', '%' . $key . '%');
                    break;
                case 2:
                    $where['uid'] = intval($key);
                    break;
                case 3:
                    $where['email'] = array('like', '%' . $key . '%');
                    break;
                case 4:
                    $where['mobile'] = array('like', '%' . $key . '%');
                    break;
            }
        } else {
            if ($settr = I('request.settr', 0, 'intval')) {
                $where['reg_time'] = array('gt', strtotime("-" . $settr . " day"));
            }
            if ($verification = I('get.verification', 0, 'intval')) {
                switch ($verification) {
                    case 1:
                        $where['email_audit'] = array('eq', 1);
                        break;
                    case 2:
                        $where['email_audit'] = array('eq', 0);
                        break;
                    case 3:
                        $where['mobile_audit'] = array('eq', 1);
                        break;
                    case 4:
                        $where['mobile_audit'] = array('eq', 0);
                        break;
                }
            }
            if ($photo_audit = I('get.photo_audit', 0, 'intval')) {
                $db_pre                 = C('DB_PREFIX');
                $this_t                 = C('DB_PREFIX') . 'members';
                $this->join             = 'left join ' . $db_pre . 'members_info i on i.uid=' . $this_t . '.uid';
                $where['i.photo_audit'] = $photo_audit;
                $this->fields           = $db_pre . 'members.*,i.photo_audit';
                $this->sort             = $this_t . '.uid';
            }
        }
		if($faire_id && $source=='sign'){//招聘会签到的企业
			$hold_time_info = M('jobfair')->where(array('id'=>$faire_id))->field('holddate_start,holddate_end')->find();
			$wh = array('jobfair_id'=>$faire_id,'utype'=>2);
			if($hold_time_info){
				isset($hold_time_info['holddate_start']) && $wh['createtime'][]=array('egt',$hold_time_info['holddate_start']);
				isset($hold_time_info['holddate_end']) && $wh['createtime'][]=array('elt',$hold_time_info['holddate_end']);
			}
			$ids = M('jobfairsignin')->where($wh)->getField('uid',true);
			$ids && $where['uid'] = array('in',$ids);
		}
        $this->where      = $where;
        $this->custom_fun = '_format_member_list';
        $this->_tpl       = 'member_list';
        parent::index();
    }
    /**
     * 删除会员
     */
    public function member_delete()
    {
        $tuid = I('post.tuid', '', 'trim');
        !$tuid && $this->error('你没有选择会员！');
        if (I('post.delete_user') == 'yes' && false === D('Members')->delete_member($tuid)) {
            $this->error('删除会员失败！');
        }
        if (I('post.delete_resume') == 'yes') {
            D('Resume')->admin_del_resume_for_uid($tuid);
        }
        admin_write_log('删除会员' . $tuid, C('visitor'), 3);
        $this->success('删除成功！');
    }
    /**
     * 添加会员
     */
    public function member_add()
    {
        $this->_name = 'Members';
        parent::add();
    }
    public function _before_insert($data)
    {
        if (fieldRegex($data['username'], 'number')) {
            $this->error('用户名不能是纯数字！');
        }
        $data['password'] = D('Members')->make_md5_pwd($data['password'], $data['pwd_hash']);
        return $data;
    }
    public function _after_insert($id, $data)
    {
        D('Members')->user_register($data);
    }
    /**
     * 编辑会员信息
     */
    public function member_edit()
    {
        $this->_name = 'Members';
        if (!IS_POST) {
            $uid    = I('get.uid', 0, 'intval');
            $resume = D('Resume')->where(array('uid' => $uid))->select();
            $this->assign('resume', $resume);
        }
        parent::edit();
    }
    public function _after_update($id, $data)
    {
        if ($this->_name == 'Members') {
            if (2 == I('request.status', 0, 'intval')) {
                M('ResumeSearchFull')->where(array('uid' => $data['uid']))->delete();
                M('ResumeSearchPrecise')->where(array('uid' => $data['uid']))->delete();
            } elseif (1 == I('request.status', 0, 'intval')) {
                $resume = M('Resume')->field('id,uid,key_full,key_precise,stime,refreshtime')->where(array('uid' => $data['uid']))->select();
                foreach ($resume as $key => $val) {
                    $full[]    = array('id' => $val['id'], 'uid' => $val['uid'], 'key' => $val['key_full'], 'stime' => $val['stime'], 'refreshtime' => $val['refreshtime']);
                    $precise[] = array('id' => $val['id'], 'uid' => $val['uid'], 'key' => $val['key_precise'], 'stime' => $val['stime'], 'refreshtime' => $val['refreshtime']);
                }
                M('ResumeSearchFull')->addAll($full);
                M('ResumeSearchPrecise')->addAll($precise);
            }
            $members_info = D('Members')->find($data['uid']);
            D('Members')->update_user_info($data, $members_info);
        }
    }
    public function _before_update($data)
    {
        if (isset($_POST['password'])) {
            $model            = D('Members');
            $member           = $model->find(I('post.uid', 0, 'intval'));
            $data['password'] = $model->make_md5_pwd(I('post.password', '', 'trim'), $member['pwd_hash']);
        }
        return $data;
    }
    /**
     * 加载会员详情
     */
    public function ajax_get_user_info()
    {
        $id  = I('get.id', 0, 'intval');
        $rst = D('Members')->admin_ajax_get_user_info($id);
        exit($rst['msg']);
    }
    /**
     * 加载委托详情
     */
    public function ajax_get_entrust_info()
    {
        //会员id
        $uid = I('get.uid', 0, 'intval');
        //简历id
        $rid  = I('get.rid', 0, 'intval');
        $info = D('Members')->get_user_one(array('uid' => $uid));
        if (empty($info)) {
            exit("会员信息不存在！可能已经被删除！");
        }
        $resume_info = D('Resume')->where(array('id' => $rid))->find();
        if (empty($resume_info)) {
            exit("简历信息不存在！可能已经被删除！");
        }
        $entrust_info                  = D('ResumeEntrust')->where(array('resume_id' => $rid, 'uid' => $uid))->find();
        $entrust_info['entrust_start'] = $entrust_info['entrust_start'] ? date("Y/m/d", $entrust_info['entrust_start']) : '----';
        $html                          = "委托开始时间：{$entrust_info['entrust_start']}<br/>";
        $entrust_info['entrust_end']   = $entrust_info['entrust_end'] ? date("Y/m/d", $entrust_info['entrust_end']) : '----';
        $html .= "委托结束时间：{$entrust_info['entrust_end']}<br/>";
        exit($html);
    }
    /**
     * 查看会员中心
     */
    public function management()
    {
        $id                            = I('get.id', 0, 'intval');
        $action                        = I('get.action', 'home/members/index', 'trim');
        $action == 'resume' && $action = 'home/personal/resume_list';
        $u                             = D('Members')->get_user_one(array('uid' => $id));
        if (!empty($u)) {
            $user_visitor = new \Common\qscmslib\user_visitor;
            $user_visitor->logout();
            $user_visitor->assign_info($u);
            redirect(U($action));
        }
    }
    /**
     * 查看简历
     */
    public function resume_show()
    {
        $id = I('get.id', 0, 'intval');
        if (!$id) {
            $this->error('参数错误！');
        }
        $uid    = I('get.uid', 0, 'intval');
        $resume = D('Resume')->get_resume_one($id);
        if (empty($resume)) {
            $this->error('简历不存在或已经被删除！');
        }
        $this->assign('resume', $resume);
        $this->assign('resume_education', D('ResumeEducation')->get_resume_education($id));
        $this->assign('resume_work', D('ResumeWork')->get_resume_work($id));
        $this->assign('resume_training', D('ResumeTraining')->get_resume_training($id));
        $this->assign('resumeaudit', M('AuditReason')->where(array('resume_id' => $id)));
        $this->display();
    }
    /**
     * 删除审核日志
     */
    public function del_auditreason()
    {
        $id = I('request.a_id');
        if (!$id) {
            $this->error('你没有选择日志！');
        }
        $n = D('AuditReason')->delete($id);
        if ($n > 0) {
            admin_write_log('删除审核日志' . $id, C('visitor'), 3);
            adminmsg("删除成功！共删除 {$n} 行", 2);
        } else {
            adminmsg("删除失败！", 0);
        }
    }
    /**
     * 匹配
     */
    public function match()
    {
        $id     = I('get.id', '0', 'intval');
        $uid    = I('get.uid', '0', 'intval');
        $resume = M('Resume')->where(array('id' => $id, 'uid' => $uid))->find();
        $this->assign('resume', $resume);
        $this->display();
    }
    /**
     * [apply 匹配结果投递简历]
     */
    public function apply()
    {
        $jid = I('request.jid', '', 'trim');
        $rid = I('request.id', 0, 'intval');
        $uid = I('request.uid', 0, 'intval');
        !$jid && $this->error('请选择要投递的职位！');
        !$rid && $this->error('请选择要投递的简历！');
        !$uid && $this->error('请选择要投递的个人用户！');
        $user = D('Members')->get_user_one(array('uid' => $uid));
        $reg  = D('PersonalJobsApply')->jobs_apply_add($jid, $user, $rid);
        !$reg['state'] && $this->error($reg['error']);
        $this->success('投递成功！');
    }
    /**
     * 职位订阅
     */
    public function jobs_subscribe()
    {
        $this->_name = 'PersonalJobsSubscribe';
        $key_type    = I('request.key_type', 0, 'intval');
        $key         = I('request.key', '', 'trim');
        if ($key && $key_type > 0) {
            switch ($key_type) {
                case 1:
                    $where['intention_jobs'] = array('like', '%' . $key . '%');
                    break;
                case 2:
                    $where['trade_cn'] = array('like', '%' . $key . '%');
                    break;
                case 3:
                    $where['district_cn'] = array('like', '%' . $key . '%');
                    break;
                case 4:
                    $where['email'] = array('like', '%' . $key . '%');
                    break;
            }
        } else {
            if ($addtime = I('get.addtime', 0, 'intval')) {
                $tmpaddtime       = strtotime("-" . $addtime . " day");
                $where['addtime'] = array('gt', $tmpaddtime);
            }
        }
        $this->where = $where;
        $this->order = 'id desc';
        parent::index();
    }
    /**
     * 删除职位订阅
     */
    public function subscribe_del($id)
    {
        $r = D('PersonalJobsSubscribe')->delete($id);
        if ($r) {
            admin_write_log('删除职位订阅信息' . $id, C('visitor'), 3);
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }
    /**
     * [promotion 推广]
     */
    public function promotion()
    {
        $type = I('request.type', 'stick', 'trim');
        $name = 'promotion_' . $type;
        $this->$name();
    }
    /**
     * [promotion_stick 个人置顶推广]
     */
    protected function promotion_stick()
    {
        $this->_name = 'PersonalServiceStickLog';
        $db_pre      = C('DB_PREFIX');
        $this_t      = $db_pre . 'personal_service_stick_log';
        $this->join  = 'left join ' . $db_pre . 'resume r on r.id=' . $this_t . '.resume_id';
        $this->field = $this_t . '.*,r.title,r.fullname';
        $settr       = I('request.settr');
        if ($settr) {
            $where['endtime'] = array('lt', strtotime(intval($settr) . " day"));
        } else if ($settr == '0') {
            $where['endtime'] = array('lt', time());
        }
        $uid                         = I('request.uid', 0, 'intval');
        $uid && $where['resume_uid'] = $uid;
        $key_type                    = I('request.key_type', 0, 'intval');
        $key                         = I('request.key', '', 'trim');
        if ($key_type && $key) {
            switch ($key_type) {
                case 1:
                    $where['r.title'] = array('like', '%' . $key . '%');
                    break;
                case 2:
                    $where[$this_t . '.resume_id'] = intval($key);
                    break;
                case 3:
                    $where[$this_t . '.resume_uid'] = intval($key);
                    break;
            }
        }
        $this->where      = $where;
        $this->custom_fun = '_format_resume_list';
        $this->_tpl       = 'promotion_stick';
        parent::index();
    }
    /**
     * [promotion_stick_edit 修改个人简历置顶推广]
     */
    public function promotion_stick_edit()
    {
        $id = I('request.id', '', 'trim');
        !$id && $this->error('你没有选择简历！');
        if (IS_POST) {
            $days = I('request.days', 0, 'intval');
            !$days && $this->error('请填写要延长推广的天数！');
            $time = $days * (3600 * 24);
            $reg  = D('PersonalServiceStickLog')->where(array('id' => $id))->save(array('days' => array('exp', 'days+' . $days), 'endtime' => array('exp', 'endtime+' . $time)));
            !$reg && $this->error('设置失败，请重新操作！');
            $this->success('保存成功！');
        } else {
            $info = M('PersonalServiceStickLog')->find($id);
            !$info && $this->error('简历置顶推广已删除！');
            $resume          = M('Resume')->field('title,fullname')->find($info['resume_id']);
            $resume && $info = array_merge($info, $resume);
            $this->assign('info', $info);
            $this->display();
        }
    }
    /**
     * [promotion_stick_deltet 删除个人简历置顶推广]
     */
    public function promotion_stick_deltet()
    {
        $id = I('post.id', '', 'trim');
        if (!$id) {
            $this->error('你没有选择简历！');
        }
        if (false === D('PersonalServiceStickLog')->del_promotion_stick($id)) {
            $this->error('取消简历推广失败！');
        }
        $this->success('取消简历推广成功');
    }
    /**
     * [promotion_tag 个人标签推广]
     */
    protected function promotion_tag()
    {
        $this->_name = 'PersonalServiceTagLog';
        $db_pre      = C('DB_PREFIX');
        $this_t      = $db_pre . 'personal_service_tag_log';
        $this->join  = 'left join ' . $db_pre . 'resume r on r.id=' . $this_t . '.resume_id';
        $this->field = $this_t . '.*,r.title,r.fullname';
        $settr       = I('request.settr');
        if ($settr) {
            $where['endtime'] = array('lt', strtotime(intval($settr) . " day"));
        } else if ($settr == '0') {
            $where['endtime'] = array('lt', time());
        }
        $uid                         = I('request.uid', 0, 'intval');
        $uid && $where['resume_uid'] = $uid;
        $key_type                    = I('request.key_type', 0, 'intval');
        $key                         = I('request.key', '', 'trim');
        if ($key_type && $key) {
            switch ($key_type) {
                case 1:
                    $where['r.title'] = array('like', '%' . $key . '%');
                    break;
                case 2:
                    $where[$this_t . '.resume_id'] = intval($key);
                    break;
                case 3:
                    $where[$this_t . '.resume_uid'] = intval($key);
                    break;
            }
        }
        $this->where      = $where;
        $this->custom_fun = '_format_resume_list';
        if (false === $tag_list = F('service_tag_category')) {
            $tag_list = D('PersonalServiceTagCategory')->tag_category_cache();
        }
        $this->assign('tag_list', $tag_list);
        $this->_tpl = 'promotion_tag';
        parent::index();
    }
    /**
     * [promotion_stick_add 添加简历置顶推广]
     */
    public function promotion_add()
    {
        if (false === $tag_list = F('service_tag_category')) {
            $tag_list = D('PersonalServiceTagCategory')->tag_category_cache();
        }
        if (IS_POST) {
            $type = I('request.type', 'stick', 'trim');
            $days = I('request.days', 0, 'intval');
            !$days && $this->error("请填写推广时间！");
            if ($type == 'stick') {
                $this->_name = 'PersonalServiceStickLog';
                if (D($this->_name)->check_stick_log(array('resume_id' => I('request.resume_id')))) {
                    $this->error("此简历正在执行此推广！请选择其他简历或者其他推广方案");
                }
                $setsqlarr['points']     = 0;
                $setsqlarr['resume_id']  = I('post.resume_id', 0, 'intval');
                $setsqlarr['days']       = $days;
                $setsqlarr['resume_uid'] = I('post.resume_uid', 0, 'intval');
                $setsqlarr['endtime']    = strtotime("+{$setsqlarr['days']} day");
                $resume_info             = D('Resume')->find($setsqlarr['resume_id']);
                $setsqlarr['subsite_id'] = $resume_info['subsite_id'];
                $rst                     = D('PersonalServiceStickLog')->add_stick_log($setsqlarr);
                if ($rst['state'] == 1 && $resume_info) {
                    $refreshtime = $resume_info['refreshtime'];
                    $stime       = intval($refreshtime) + 100000000;
                    D('Resume')->where(array('id' => $setsqlarr['resume_id']))->save(array('stick' => 1, 'stime' => $stime));
                    D('ResumeSearchPrecise')->where(array('id' => $setsqlarr['resume_id']))->setField('stime', $stime);
                    D('ResumeSearchFull')->where(array('id' => $setsqlarr['resume_id']))->setField('stime', $stime);
                    $this->success('设置成功！');
                    exit;
                }
                $this->error('设置失败！' . $rst['error']);
            } else {
                $tag_id = I('request.tag_id', 0, 'intval');
                !$tag_list[$tag_id] && $this->error("请正确选择醒目标签！");
                $this->_name = 'PersonalServiceTagLog';
                if (D($this->_name)->check_tag_log(array('resume_id' => I('request.resume_id')))) {
                    $this->error("此简历正在执行此推广！请选择其他简历或者其他推广方案");
                }
                $setsqlarr['points']     = 0;
                $setsqlarr['resume_id']  = I('post.resume_id', 0, 'intval');
                $resume_info             = D('Resume')->find($setsqlarr['resume_id']);
                $setsqlarr['tag_id']     = $tag_id;
                $setsqlarr['days']       = $days;
                $setsqlarr['resume_uid'] = I('post.resume_uid', 0, 'intval');
                $setsqlarr['endtime']    = strtotime("+{$setsqlarr['days']} day");
                $setsqlarr['subsite_id'] = $resume_info['subsite_id'];
                $rst                     = D('PersonalServiceTagLog')->add_tag_log($setsqlarr);
                if ($rst['state'] == 1 && $resume_info) {
                    D('Resume')->where(array('id' => array('eq', $setsqlarr['resume_id'])))->setField('strong_tag', $tag_id);
                    $this->success('设置成功！');
                    exit;
                }
                $this->error('设置失败！' . $rst['error']);
            }
        } else {
            $this->_name = 'PersonalServiceStickLog';
            $this->assign('tag_list', $tag_list);
            parent::add();
        }
    }
    /**
     * [promotion_stick_edit 修改个人简历置顶推广]
     */
    public function promotion_tag_edit()
    {
        $id = I('request.id', '', 'trim');
        !$id && $this->error('你没有选择简历！');
        if (false === $tag_list = F('service_tag_category')) {
            $tag_list = D('PersonalServiceTagCategory')->tag_category_cache();
        }
        if (IS_POST) {
            $tag_id    = I('request.tag_id', 0, 'intval');
            $resume_id = I('request.resume_id', 0, 'intval');
            !$tag_list[$tag_id] && $this->error('请正确选择标签！');
            if ($days = I('request.days', 0, 'intval')) {
                $time            = $days * (3600 * 24);
                $data['days']    = array('exp', 'days+' . $days);
                $data['endtime'] = array('exp', 'endtime+' . $time);
            }
            $data['tag_id'] = $tag_id;
            $reg            = D('PersonalServiceTagLog')->where(array('id' => $id))->save($data);
            false === $reg && $this->error('设置失败，请重新操作！');
            D('Resume')->where(array('id' => $resume_id))->setField('strong_tag', $tag_id);
            $this->success('保存成功！');
        } else {
            $info = M('PersonalServiceTagLog')->find($id);
            !$info && $this->error('简历标签推广已删除！');
            $resume          = M('Resume')->field('title,fullname')->find($info['resume_id']);
            $resume && $info = array_merge($info, $resume);
            $this->assign('tag_list', $tag_list);
            $this->assign('info', $info);
            $this->display();
        }
    }
    /**
     * [promotion_stick_deltet 删除个人简历置顶推广]
     */
    public function promotion_tag_deltet()
    {
        $id = I('post.id', '', 'trim');
        if (!$id) {
            $this->error('你没有选择简历！');
        }
        if (false === D('PersonalServiceTagLog')->del_promotion_tag($id)) {
            $this->error('取消简历标签失败！');
        }
        $this->success('取消简历推广成功');
    }
    /**
     * ajax获取简历
     */
    public function ajax_get_resume()
    {
        $type = I('get.type', '', 'trim');
        $key  = I('get.key', '', 'trim');
        switch ($type) {
            case 'get_fullname':
                $where = array('fullname' => array('like', '%' . $key . '%'));
                break;
            case 'get_resumeid':
                $where = array('id' => intval($key));
                $limit = 1;
                break;
            case 'get_uid':
                $where = array('uid' => intval($key));
                $limit = 30;
                break;
        }
        if ($this->apply['Subsite']) {
            $field = D('Resume')->getDbFields();
            if (in_array('subsite_id', $field) && C('visitor.subsite')) {
                $where['subsite_id'] = array('in', C('visitor.subsite'));
            }
        }
        $result = D('Resume')->where($where)->limit($limit)->select();
        $info   = array();
        foreach ($result as $key => $value) {
            $value['addtime']     = date("Y-m-d", $value['addtime']);
            $value['refreshtime'] = date("Y-m-d", $value['refreshtime']);
            $value['resume_url']  = url_rewrite('QS_resumeshow', array('id' => $value['id']));
            $info[]               = $value['id'] . "%%%" . $value['fullname'] . "%%%" . $value['resume_url'] . "%%%" . $value['addtime'] . "%%%" . $value['refreshtime'] . "%%%" . $value['uid'];
        }
        if (!empty($info)) {
            exit(implode('@@@', $info));
        } else {
            exit();
        }
    }
    /**
     * [user_points_edit 用户积分操作]
     */
    public function user_points_edit()
    {
        if (IS_POST) {
            $points_type = I('post.points_type', 1, 'intval');
            $t           = $points_type == 1 ? "+" : "-";
            $points      = I('post.points', 1, 'intval');
            $uid         = I('post.uid', 1, 'intval');
            D('MembersPoints')->report_deal($uid, $points_type, $points);
            $userinfo = D('Members')->get_user_one(array('uid' => $uid));
            //会员积分变更记录。管理员后台修改会员的积分。3表示：管理员后台修改
            if (I('post.is_money', 0, 'intval') && I('post.log_amount')) {
                $amount  = round(I('post.log_amount'), 2);
                $ismoney = 2;
            } else {
                $amount  = '0.00';
                $ismoney = 1;
            }
            $notes = "操作人：" . C('visitor.username') . ",说明：修改会员 {$userinfo['username']} {C('qscms_points_byname')} ({$t}{$points})。收取{C('qscms_points_byname')}金额：{$amount} 元，备注：{I('post.points_notes','','trim')}";
            admin_write_log("修改会员uid为" . $userinfo['uid'] . C('qscms_points_byname'), C('visitor.username'), 3);
            $this->success('保存成功！');
        } else {
            $this->_name  = 'Members';
            $where['uid'] = I('get.uid', 0, 'intval');
            $list         = D('MembersHandsel')->get_handsel_list($where);
            $this->assign('userpoints', D('MembersPoints')->get_user_points($where['uid']));
            $this->assign('list', $list);
            $this->edit();
        }
    }
    /**
     * [user_log 用户日志]
     */
    public function user_log()
    {
        $this->_name = 'MembersLog';
        $this->assign('type_arr', D('MembersLog')->type_arr);
        $where['log_uid'] = I('request.uid', 0, 'intval');
        if ($settr = I('request.settr', 0, 'intval')) {
            $where['log_addtime'] = array('gt', strtotime("-" . $settr . " day"));
        }
        $this->where = $where;
        parent::index();
    }
    /**
     * [user_apply_jobs 申请职位]
     */
    public function user_apply_jobs()
    {
        $this->_name           = 'PersonalJobsApply';
        $db_pre                = C('DB_PREFIX');
        $this_t                = $db_pre . 'personal_jobs_apply';
        $this->join            = 'left join ' . $db_pre . 'jobs j on j.id=' . $this_t . '.jobs_id';
        $this->field           = 'did,resume_id,resume_name,jobs_id,apply_addtime,personal_look,is_reply,is_apply,j.id,j.jobs_name,j.company_id,j.companyname,j.tdistrict,j.sdistrict,j.district';
        $where['personal_uid'] = I('request.uid', 0, 'intval');
        if ($settr = I('request.settr', 0, 'intval')) {
            $where['apply_addtime'] = array('gt', strtotime("-" . $settr . " day"));
        }
        $key_type = I('request.key_type', 0, 'intval');
        $key      = I('request.key', '', 'trim');
        if ($key_type && $key) {
            switch ($key_type) {
                case 1:
                    $where[$this_t . '.jobs_name'] = array('like', '%' . $key . '%');
                    break;
                case 2:
                    $where[$this_t . '.jobs_id'] = intval($key);
                    break;
                case 3:
                    $where[$this_t . '.company_name'] = array('like', '%' . $key . '%');
                    break;
                case 4:
                    $where[$this_t . '.company_id'] = intval($key);
                    break;
                case 5:
                    $where[$this_t . '.resume_name'] = array('like', '%' . $key . '%');
                    break;
                case 6:
                    $where[$this_t . '.resume_id'] = intval($key);
                    break;
            }
        }
        $this->where      = $where;
        $this->custom_fun = '_format_resume_apply_list';
        parent::index();
    }
    /**
     * [user_apply_delete 删除申请职位信息]
     */
    public function user_apply_delete()
    {
        $this->_name = 'PersonalJobsApply';
        parent::delete();
    }
    /**
     * [user_nterview 面试邀请]
     */
    public function user_nterview()
    {
        $this->_name         = 'CompanyInterview';
        $db_pre              = C('DB_PREFIX');
        $this_t              = $db_pre . 'company_interview';
        $this->join          = 'left join ' . $db_pre . 'jobs j on j.id=' . $this_t . '.jobs_id';
        $this->field         = 'did,resume_id,resume_name,jobs_id,interview_addtime,personal_look,j.id,j.jobs_name,j.company_id,j.companyname,j.tdistrict,j.sdistrict,j.district';
        $where['resume_uid'] = I('request.uid', 0, 'intval');
        if ($settr = I('request.settr', 0, 'intval')) {
            $where['interview_addtime'] = array('gt', strtotime("-" . $settr . " day"));
        }
        $key_type = I('request.key_type', 0, 'intval');
        $key      = I('request.key', '', 'trim');
        if ($key_type && $key) {
            switch ($key_type) {
                case 1:
                    $where[$this_t . '.jobs_name'] = array('like', '%' . $key . '%');
                    break;
                case 2:
                    $where[$this_t . '.jobs_id'] = intval($key);
                    break;
                case 3:
                    $where[$this_t . '.company_name'] = array('like', '%' . $key . '%');
                    break;
                case 4:
                    $where[$this_t . '.company_id'] = intval($key);
                    break;
                case 5:
                    $where[$this_t . '.resume_name'] = array('like', '%' . $key . '%');
                    break;
                case 6:
                    $where[$this_t . '.resume_id'] = intval($key);
                    break;
            }
        }
        $this->where      = $where;
        $this->custom_fun = '_format_resume_apply_list';
        parent::index();
    }
    /**
     * [increment 个人增值服务]
     */
    public function increment()
    {
        $type = I('request.type', 'stick', 'trim');
        $name = 'increment_' . $type;
        $this->$name();
    }
    protected function increment_stick()
    {
        $this->_name = 'PersonalServiceStick';
        $this->_tpl  = 'increment_stick';
        $this->order = 'sort desc';
        parent::index();
    }
    protected function increment_tag()
    {
        $this->_name = 'PersonalServiceTag';
        $tag_list    = D('PersonalServiceTagCategory')->order('sort desc')->select();
        // if(false === $tag_list = F('service_tag_category')) $tag_list = D('PersonalServiceTagCategory')->tag_category_cache();
        $this->assign('tag_list', $tag_list);
        $this->_tpl  = 'increment_tag';
        $this->order = 'sort desc';
        parent::index();
    }
    public function increment_stick_add()
    {
        $this->_name = 'PersonalServiceStick';
        parent::add();
    }
    public function increment_stick_edit()
    {
        $this->_name = 'PersonalServiceStick';
        parent::edit();
    }
    public function increment_stick_del()
    {
        $this->_name = 'PersonalServiceStick';
        parent::delete();
    }
    public function increment_stick_save_sort()
    {
        $id   = I('post.id');
        $sort = I('post.sort');
        foreach ($id as $key => $value) {
            D('PersonalServiceStick')->where(array('id' => array('eq', intval($value))))->setField('sort', $sort[$key]);
        }
        $this->success('保存成功！');
    }
    public function increment_tag_add()
    {
        $this->_name = 'PersonalServiceTag';
        parent::add();
    }
    public function increment_tag_edit()
    {
        $this->_name = 'PersonalServiceTag';
        parent::edit();
    }
    public function increment_tag_del()
    {
        $this->_name = 'PersonalServiceTag';
        parent::delete();
    }
    public function increment_tag_save_sort()
    {
        $id   = I('post.id');
        $sort = I('post.sort');
        foreach ($id as $key => $value) {
            D('PersonalServiceTag')->where(array('id' => array('eq', intval($value))))->setField('sort', $sort[$key]);
        }
        $this->success('保存成功！');
    }
    public function increment_tag_cat_save_sort()
    {
        $id   = I('post.id');
        $sort = I('post.sort');
        foreach ($id as $key => $value) {
            D('PersonalServiceTagCategory')->where(array('id' => array('eq', intval($value))))->setField('sort', $sort[$key]);
        }
        $this->success('保存成功！');
    }
    public function tag_category_add()
    {
        $this->_name = 'PersonalServiceTagCategory';
        parent::add();
    }
    public function tag_category_edit()
    {
        $this->_name = 'PersonalServiceTagCategory';
        parent::edit();
    }
    public function tag_category_del()
    {
        $this->_name = 'PersonalServiceTagCategory';
        parent::delete();
    }
    /**
     * [user_interview_delete 删除面试邀请信息]
     */
    public function user_interview_delete()
    {
        $this->_name = 'CompanyInterview';
        parent::delete();
    }
    /**
     * [_after_search_resume 统计简历列表，各状态下简历列表数量]
     */
    protected function _after_search_resume($tabletype)
    {
        $total_all_resume = parent::_pending('Resume');
        $count[0]         = $total_all_resume;
        $count[1]         = parent::_pending('Resume', array('display' => 1, 'audit' => array('neq', 3)));
        $count[2]         = $total_all_resume - $count[1];
        if ($tabletype == 0) {
        } elseif ($tabletype == 1) {
            $where['display'] = 1;
        } elseif ($tabletype == 2) {
            $where['display'] = 0;
        }
        $where['audit'] = 1;
        $count[3]       = parent::_pending('Resume', $where);
        $where['audit'] = 2;
        $count[4]       = parent::_pending('Resume', $where);
        $where['audit'] = 3;
        if ($tabletype == 2) {
            unset($where['display']);
        }
        $count[5]             = parent::_pending('Resume', $where);
        $where                = array('photo_img' => array('neq', ''));
        $count[6]             = parent::_pending('Resume', $where, 'uid');
        $where['photo_audit'] = 2;
        $count[7]             = parent::_pending('Resume', $where, 'uid');
        $where['photo_audit'] = 1;
        $count[8]             = parent::_pending('Resume', $where, 'uid');
        $where['photo_audit'] = 3;
        $count[9]             = parent::_pending('Resume', $where, 'uid');
        $this->assign('count', $count);
    }
    /**
     * [_format_member_list 解析用户注册地址]
     */
    protected function _format_member_list($list)
    {
        $Ip = new \Common\ORG\IpLocation('UTFWry.dat');
        foreach ($list as $key => $val) {
            $rst                     = $Ip->getlocation($val['reg_ip']);
            $list[$key]['ipAddress'] = $rst['country'];
        }
        return $list;
    }
    /**
     * [_format_resume_list 解析简历跳转链接(简历列表页用)]
     */
    protected function _format_resume_list($list)
    {
        foreach ($list as $key => $val) {
            $id                       = $val['resume_id'] ?: $val['id'];
            $list[$key]['resume_url'] = url_rewrite('QS_resumeshow', array('id' => $id));
        }
        return $list;
    }
    /**
     * [_format_resume_apply_list 解析简历跳转链接(申请职位/面试邀请列表页用)]
     */
    protected function _format_resume_apply_list($list)
    {
        if (C('apply.Subsite')) {
            if (false === $subsite_district = F('subsite_district')) {
                $subsite_district = D('Subsite')->subsite_district_cache();
            }
        }
        foreach ($list as $key => $val) {
            if (empty($val['company_id'])) {
                $jobs       = M('JobsTmp')->field('id,jobs_name,company_id,companyname,tdistrict,sdistrict,district')->where(array('id' => $val['jobs_id']))->find();
                $list[$key] = array_merge($val, $jobs);
            }
            $list[$key]['resume_url']         = url_rewrite('QS_resumeshow', array('id' => $val['resume_id']));
            C('apply.Subsite') && $subsite_id = $subsite_district[$val['tdistrict']] ?: ($subsite_district[$val['sdistrict']] ?: $subsite_district[$val['district']]);
            $list[$key]['jobs_url']           = url_rewrite('QS_jobsshow', array('id' => $val['id']), $subsite_id);
            $list[$key]['company_url']        = url_rewrite('QS_companyshow', array('id' => $val['company_id']));
        }
        return $list;
    }
    /**
     * 照片/作品
     */
    public function resume_img()
    {
        $this->display();
    }

    public function export()
    {
        if (IS_POST) {
            $from_date = strtotime($_POST['laodongStartDate']);
            $to_date   = strtotime($_POST['laodongEndDate']);

            $map['addtime'] = array(array('EGT', $from_date), array('ELT', $to_date));

            $res = M('resume')
                ->alias('a')
                ->join('job_resume_education_view as b ON a.uid = b.uid')
                ->where($map)
                ->field('a.fullname,a.addtime,a.refreshtime,a.sex_cn,a.birthdate,a.major_cn,a.experience_cn,a.district_cn,a.intention_jobs,a.trade_cn,a.telephone,a.email,b.education_cn,b.speciality,b.school')->order('addtime desc')->select();
            foreach ($res as $key => $val) {
                $personal[$key]['fullname']       = $val['fullname'];
                $personal[$key]['addtime']        = date("Y-m-d", $val['addtime']);
                $personal[$key]['refreshtime']    = date("Y-m-d", $val['refreshtime']);
                $personal[$key]['sex_cn']         = $val['sex_cn'];
                $personal[$key]['birthdate']      = (date('Y') - $val['birthdate']);
                $personal[$key]['education_cn']   = $val['education_cn'];
                $personal[$key]['school']         = $val['school'];
                $personal[$key]['speciality']     = $val['speciality'];
                $personal[$key]['experience_cn']  = $val['experience_cn'];
                $personal[$key]['district_cn']    = $val['district_cn'];
                $personal[$key]['intention_jobs'] = $val['intention_jobs'];
                $personal[$key]['trade_cn']       = $val['trade_cn'];
                $personal[$key]['telephone']      = $val['telephone'];
                $personal[$key]['email']          = $val['email'];
            }
            $fileName = date("Y-m-d", time()) . '简历信息';
            $property = array('title' => $fileName . '简历');
            $headArr  = array('姓名', '创建时间', '刷新时间', '性别', '年龄', '学历', '学校', '专业', '工作经验', '期望工作地', '期望从事的岗位', '希望从事行业', '联系电话', '联系邮箱');
            getExcel($property, $headArr, $personal, $fileName);
        } else {
            $this->display();
            die;
        }
    }
}
