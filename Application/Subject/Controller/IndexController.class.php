<?php
namespace Subject\Controller;

use Common\Controller\FrontendController;

class IndexController extends FrontendController
{
    public function _initialize()
    {
        parent::_initialize();
        $url = __ROOT__ . '/' . APP_NAME . '/' . MODULE_NAME . '/View/public';
        $this->assign('url', $url);
    }
    /**
     * 招聘会列表
     */
    public function index()
    {
        if (!I('get.org', '', 'trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']) {
            echo "<script>alert('请使用电脑端进行报名！');
 window.location.href = 'http://www.jxujob.com';</script>";

            //redirect(build_mobile_url(array('c'=>'subject','a'=>'index')));
        }
        $this->display();
    }
    /**
     * 招聘会详情
     */
    public function subject_show()
    {
        if (!I('get.org', '', 'trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']) {
            echo "<script>alert('请使用电脑端进行报名！');
            window.location.href = 'http://www.jxujob.com';</script>";
        }
        //添加meta的链接
        $map['id'] = $_GET['id'];
        $info      = M('subject')->where($map)->find();
        $this->assign('id', $_GET['id']);
        $this->assign('xxsm_url', $inof['xxsm_url']);
        $this->assign('info', $info);
        //end
        $this->display();
    }
    /**
     * 招聘会详情
     */
    public function subject_p_show()
    {
        if (!I('get.org', '', 'trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']) {
            redirect(build_mobile_url(array('c' => 'subject', 'a' => 'personal_show', 'params' => 'id=' . intval($_GET['id']))));
        }

        $info = M('subject')->where($map)->find();

        //添加meta的链接
        $canonical = C('qscms_site_domain') . $_SERVER['REQUEST_URI'];
        $this->assign('canonical', $canonical);
        $this->assign('info', $info);

        //end
        $this->display();
    }
    /**
     * [index 专题招聘会搜索跳转]
     */
    public function search_location()
    {
        $act = I('post.act', '', 'trim');
        $key = I('post.key', '', 'trim');
        $id  = I('post.id', 0, 'intval');
        $this->ajaxReturn(1, '', url_rewrite('QS_subject_show', array('key' => $key, 'act' => $act, 'id' => $id)));
    }
    /**
     * [index 专题招聘会搜索跳转]
     */
    public function search_location_p()
    {
        $key = I('post.key', '', 'trim');
        $id  = I('post.id', 0, 'intval');
        $this->ajaxReturn(1, '', U('subject_p_show', array('key' => $key, 'id' => $id)));
    }
    /**
     * 招聘会详情页面滚动数据
     */
    public function ajax_scroll()
    {
        $subject_id = I('get.subject_id', 0, 'intval');
        !$subject_id && $this->ajaxReturn(0, '参数缺失~！');
        $subject_list  = M('SubjectCompany')->where(array('subject_id' => $subject_id, 's_audit' => 1))->select();
        $personal_list = M('SubjectPersonal')->where(array('subject_id' => $subject_id, 's_audit' => 1))->order('addtime desc')->limit(15)->select();
        foreach ($subject_list as $key => $value) {
            $uids[] = $value['company_uid'];
        }
        if ($personal_list) {
            foreach ($personal_list as $key => $value) {
                $resume = M('Resume')->where(array('uid' => $value['resume_uid']))->field('id,fullname,display_name,sex')->find();
                if (!$resume) {
                    continue;
                }
                if ($resume['display_name'] == "2") {
                    $resume['fullname'] = "N" . str_pad($resume['id'], 7, "0", STR_PAD_LEFT);
                } elseif ($resume['display_name'] == "3") {
                    if ($resume['sex'] == 1) {
                        $resume['fullname'] = cut_str($resume['fullname'], 1, 0, "先生");
                    } elseif ($val['sex'] == 2) {
                        $resume['fullname'] = cut_str($resume['fullname'], 1, 0, "女士");
                    }
                }
                $row['fullname']   = $resume['fullname'];
                $row['resume_url'] = url_rewrite('QS_resumeshow', array('id' => $resume['id']));
                $row['type']       = 'resume';
                $resumes[]         = $row;
            }
        }

        if ($uids) {
            $company = M('CompanyProfile')->where(array('uid' => array('in', $uids)))->order('refreshtime desc')->limit(10)->getField("id,companyname,'com' as type");
            if ($company) {
                foreach ($company as $key => $val) {
                    $val['company_url'] = url_rewrite('QS_companyshow', array('id' => $val['id']));
                    $companys[]         = $val;
                }
            }
            $apply = M('PersonalJobsApply')->where(array('company_uid' => array('in', $uids)))->order('apply_addtime desc')->limit(10)->getField('did,resume_id,jobs_name,jobs_id');
            if ($apply) {
                foreach ($apply as $key => $value) {
                    $resume = M('Resume')->where(array('id' => $value['resume_id']))->field('id,fullname,display_name,sex')->find();
                    if (!$resume) {
                        continue;
                    }
                    if ($resume['display_name'] == "2") {
                        $resume['fullname'] = "N" . str_pad($resume['id'], 7, "0", STR_PAD_LEFT);
                    } elseif ($resume['display_name'] == "3") {
                        if ($resume['sex'] == 1) {
                            $resume['fullname'] = cut_str($resume['fullname'], 1, 0, "先生");
                        } elseif ($val['sex'] == 2) {
                            $resume['fullname'] = cut_str($resume['fullname'], 1, 0, "女士");
                        }
                    }
                    $value['fullname']   = $resume['fullname'];
                    $value['resume_url'] = url_rewrite('QS_resumeshow', array('id' => $value['resume_id']));
                    $value['job_url']    = url_rewrite('QS_jobsshow', array('id' => $value['jobs_id']));
                    $value['type']       = 'apply';
                    $applys[]            = $value;
                }
            }
        }
        $applys   = $applys ?: array();
        $companys = $companys ?: array();
        $resumes  = $resumes ?: array();
        $data     = array_merge($applys, $companys, $resumes);

        if ($data) {
            $this->ajaxReturn(1, '获取动态成功！', $data);
        } else {
            $this->ajaxReturn(0, '暂无数据');
        }
    }
    public function ajax_chat()
    {
        if (!C('visitor')) {
            $this->ajaxReturn(0, '请登录！');
        }
        $uid        = C('visitor.uid');
        $subject_id = I('post.id', 0, 'intval');
        !$subject_id && $this->ajaxReturn(0, '招聘会ID不能为空~！');
        $s_time = M('Subject')->where(array('id' => $subject_id))->field('subject_time_start,subject_time_end')->find();
        if ($s_time['subject_time_start'] && $s_time['subject_time_end'] && $s_time['subject_time_start'] != $s_time['subject_time_end']) {
            $time  = strtotime(date('H:i:s'));
            $start = strtotime(date('H:i:s', $s_time['subject_time_start']));
            $end   = strtotime(date('H:i:s', $s_time['subject_time_end']));
            if ($time < $start || $time > $end) {
                $this->ajaxReturn(0, '现在时间聊天室还不允许发言哦~！');
            }
        }
        $note = I('post.note', '', 'trim');
        !$note && $this->ajaxReturn(0, '聊天内容不能为空~！');
        if (C('visitor.utype') == 1) {
            $company = M('CompanyProfile')->where(array('uid' => $uid))->find();
            !$company && $this->ajaxReturn(0, '请先去完善企业资料呦~！');
            $arr['name'] = $company['companyname'];
            if ($company['logo']) {
                $arr['head_img'] = $company['logo'];
            }
        } else {
            $resume = M('Resume')->where(array('uid' => $uid))->find();
            !$resume && $this->ajaxReturn(0, '请先去创建简历呦~！');
            $arr['name'] = $resume['fullname'];
            if ($resume['photo_img']) {
                $arr['head_img'] = $resume['photo_img'];
            }
            $arr['sex'] = $resume['sex'];
        }
        $arr['s_id']    = $subject_id;
        $arr['note']    = $note;
        $arr['uid']     = $uid;
        $arr['utype']   = C('visitor.utype');
        $arr['addtime'] = time();
        $insert_id      = M('SubjectMessageLog')->add($arr);
        if ($insert_id) {
            $this->ajaxReturn(1, '发送成功！');
        } else {
            $this->ajaxReturn(0, '发送失败！');
        }
    }
    public function ajax_chat_log()
    {
        $last_id = I('post.last_id', 0, 'intval');
        $id      = I('post.id', 0, 'intval');
        !$id && $this->ajaxReturn(0, '招聘会ID不能为空~！');
        $where['s_id'] = $id;
        $where['id']   = array('gt', $last_id);
        if ($uid = I('post.uid', '', 'intval')) {
            $where['uid'] = array('neq', $uid);
        }
        $list = M('SubjectMessageLog')->where($where)->limit(100)->select();
        foreach ($list as $key => $value) {
            $row = $value;
            if ($value['utype'] == 2) {
                $resume     = M('Resume')->where(array('uid' => $value['uid']))->field('id')->find();
                $row['url'] = url_rewrite('QS_resumeshow', array('id' => $resume['id']));
                if (!$value['head_img']) {
                    $avatar_default  = $value['sex'] == 1 ? 'no_photo_male.png' : 'no_photo_female.png';
                    $row['photosrc'] = attach($avatar_default, 'resource');
                } else {
                    $row['photosrc'] = attach($value['head_img'], 'avatar');
                }
            } else {
                $com_info   = M('CompanyProfile')->where(array('uid' => $value['uid']))->field('id')->find();
                $row['url'] = url_rewrite('QS_companyshow', array('id' => $com_info['id']));
                if (!$value['head_img']) {
                    $row['photosrc'] = attach('no_logo.png', 'resource');
                } else {
                    $row['photosrc'] = attach($value['head_img'], 'company_logo');
                }
            }
            $log_list[] = $row;
        }
        $this->assign('log_list', $log_list);
        $data['html'] = $this->fetch();
        $this->ajaxReturn(1, '获取聊天记录窗口', $data);
    }
}
