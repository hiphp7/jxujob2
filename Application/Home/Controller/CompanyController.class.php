<?php
namespace Home\Controller;

use Common\Controller\FrontendController;

class CompanyController extends FrontendController
{
    public function _initialize()
    {
        parent::_initialize();
        //访问者控制
        if (!$this->visitor->is_login) {
            IS_AJAX && $this->ajaxReturn(0, L('login_please'), '', 1);
            //非ajax的跳转页面
            $this->redirect('members/login');
        }
        if (!IS_AJAX) {
            $this->_global_variable();
        } else {
            $this->_cominfo_flge();
        }
    }
    protected function _global_variable()
    {
        C('visitor.utype') !=1 && $this->redirect('members/login');
        // 顾问信息
        if (C('visitor.consultant')) {
            $consultant = M('Consultant')->where(array('id'=>C('visitor.consultant')))->find();
            $this->assign('consultant', $consultant);
        }
        // 帐号状态 为暂停
        if (C('visitor.status') == 2 && !in_array(ACTION_NAME, array('index'))) {
            $this->error('您的账号处于暂停状态，请联系管理员设为正常后进行操作！', U('Company/index'));
        }
        // 短信必须验证
        if (C('qscms_sms_open')==1 && C('qscms_login_com_audit_mobile')==1 && C('visitor.mobile_audit') == 0 && !in_array(ACTION_NAME, array('user_security','com_auth','com_info'))) {
            $this->error('您的手机未认证，认证后才能进行其他操作！', U('Company/user_security'));
        }
        $this->_cominfo_flge();
        // 强制认证营业执照
        if (C('qscms_login_com_audit_certificate')==1 && $this->company_profile['audit'] !=1 && !in_array(ACTION_NAME, array('user_security','com_auth','com_info'))) {
            $this->error('您的营业执照未认证，认证后才能进行其他操作！', U('Company/com_auth'));
        }
        $this->assign('company_profile', $this->company_profile);
        $this->assign('cominfo_flge', $this->cominfo_flge);
        // 第一次登录
        if (!S('personal_login_first_'.C('visitor.uid'))) {
            S('personal_login_first_'.C('visitor.uid'), 1, 86400-(time()-strtotime("today")));
            //快到期提醒
            $my_setmeal = D('MembersSetmeal')->get_user_setmeal(C('visitor.uid'));
            if ($my_setmeal['endtime']>0) {
                if (C('qscms_meal_min_remind')==0) {
                    $confirm_setmeal = 0;
                } else {
                    if ($my_setmeal['endtime'] - time()>C('qscms_meal_min_remind')) {
                        $confirm_setmeal = 0;
                    } else {
                        $confirm_setmeal = 1;
                    }
                }
                $this->assign('confirm_setmeal', $confirm_setmeal);
            }
        }
        $this->assign('company_nav', ACTION_NAME);
    }
    protected function _cominfo_flge()
    {
        //当前用户的企业信息
        $this->company_profile = M('CompanyProfile')->where(array('uid'=>C('visitor.uid')))->find();
        if ($this->company_profile) {
            // 判断是否需要完善信息
            $this->cominfo_flge=true;
            $array=array("companyname","nature","trade","district","scale","address","contact","email","contents");
            foreach ($this->company_profile as $key => $value) {
                if (in_array($key, $array) && empty($value)) {
                    $this->cominfo_flge=false;
                    break;
                }
            }
        } else {
            $this->cominfo_flge=false;
        }
        $this->assign('cominfo_flge', $this->cominfo_flge);
    }
    // 企业首页
    public function index()
    {
        session('error_login_count', 0);
        //首页顶部提示信息(套餐已失效或快失效时提醒)
        $message = array();
        $my_setmeal = D('MembersSetmeal')->get_user_setmeal(C('visitor.uid'));
        //已到期提醒
        if ($my_setmeal['expire']==1 && $my_setmeal['setmeal_id']>1) {
            $message[] = '提醒：您的套餐已到期，为避免造成不必要的麻烦，请<a href="__URL__/setmeal_list" target="_blank">升级套餐</a>';
        } elseif (intval(C('qscms_meal_min_remind'))>0 && ($my_setmeal['endtime']-time())/86400 <= C('qscms_meal_min_remind')  && $my_setmeal['endtime'] > 0) {
            $message[] = '提醒：您的套餐快到期，为避免造成不必要的麻烦，请<a href="__URL__/setmeal_list" target="_blank">升级套餐</a>';
        }
        $this->assign('setmeal', $my_setmeal);
        $this->assign('message', $message);
        $this->assign('week', array('日','一','二','三','四','五','六'));
        $weixin_bind = M('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->limit('10')->getfield('keyid');
        $this->assign('weixin_bind', $weixin_bind);
        // 统计职位
        $this->assign('total_audit_jobs', M('Jobs')->where(array('uid'=>C('visitor.uid')))->count());
        $join = 'join '.C('DB_PREFIX') .'jobs j on j.id='.C('DB_PREFIX').'personal_jobs_apply.jobs_id';
        $this->assign('total_nolook_resume', M('PersonalJobsApply')->join($join)->where(array('company_uid'=>C('visitor.uid'),'is_reply'=>array('eq',0)))->count());
        $this->assign('total_interview', M('CompanyInterview')->where(array('company_uid'=>C('visitor.uid')))->count());
        $jids = M('Jobs')->where(array('uid'=>C('visitor.uid')))->getField('id', true);
        $jids_tmp = M('JobsTmp')->where(array('uid'=>C('visitor.uid'),'display'=>1))->getField('id', true);
        if (count($jids)+count($jids_tmp)>=$my_setmeal['jobs_meanwhile']) {
            $upper_limit = 1;
        }

        $this->assign('total_view', $jids ? M('ViewJobs')->where(array('jobsid'=>array('in',$jids)))->count() : 0);
        $issign = D('MembersHandsel')->check_members_handsel_day(array('uid'=>C('visitor.uid'),'htype'=>'task_sign'));
        $this->assign('issign', $issign ? 1 : 0);
        //推荐简历
        $this->ajax_get_interest_resume();
        $surplus_jobs_num = D('Jobs')->count_surplus_jobs_num(C('visitor.uid'), $my_setmeal);
        $this->assign('surplus_jobs_num', $surplus_jobs_num);
        $hour=date('G');
        if ($hour<11) {
            $am_pm = '早上好';
        } else if ($hour<13) {
            $am_pm = '中午好';
        } else if ($hour<17) {
            $am_pm = '下午好';
        } else {
            $am_pm = '晚上好';
        }
        $this->assign('am_pm', $am_pm);
        $tag_arr = array();
        $company_tag = $this->company_profile['tag'];
        if ($company_tag) {
            $tag = explode(",", $company_tag);
            foreach ($tag as $key => $value) {
                $arr = explode("|", $value);
                $tag_arr[] = $arr[1];
            }
        }
        //微信扫描绑定
        $user_bind = M('MembersBind')->where(array('uid'=>C('visitor.uid')))->limit('1')->getfield('type,keyid');
        if (C('qscms_weixin_apiopen')==1 && C('qscms_weixin_scan_bind')==1) {
            if (!$user_bind['weixin']) {
                $html = \Common\qscmslib\weixin::qrcode_img('bind', 100, 100);
                $this->assign('qrcode', $html);
            }
        } else {
            $this->assign('wx_status');
        }
        $this->assign('tag_arr', $tag_arr);
        $this->assign('points', D('MembersPoints')->get_user_points(C('visitor.uid')));//当前用户积分数
        $this->assign('upper_limit', $upper_limit);
        $this->_config_seo(array('title'=>'企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    /**
     * ajax获取首页招聘效果统计
     */
    public function ajax_get_statistics()
    {
        $where['comid'] = $this->company_profile['id'];
        $type = I('get.type', 'visitor', 'trim');
        switch ($type) {
            case 'visitor':
                $where['apply'] = 0;
                break;
            case 'apply':
                $where['apply'] = 1;
                break;
            case 'viewjobs':
                $where['apply'] = 0;
                $where['jobid'] = array('gt',0);
                break;
        }
        $settr = I('get.settr', 7, 'intval');
        $model = D('CompanyStatistics');
        $today = strtotime(date('Y-m-d'));
        $where['addtime'] = array('lt',$today);
        if ($settr>0) {
            $settr_tmp = $today-$settr*3600*24;
            $where['addtime'] = array(array('egt',$settr_tmp),array('lt',$today));
        }
        $category = array();
        $set_total = $set_login = array();
        for ($i=$settr_tmp; $i < $today; $i=$i+3600*24) {
            $category[] = date('Y-m-d', $i);
            $set_total[$i] = 0;
            $set_login[$i] = 0;
        }
        $uidArr = array();

        $cache_name = ($type=='viewjobs'?($type.'_'):'').$where['comid'].'_'.$where['apply'].'_'.$settr.'_0_0_line_data.cache';

        $cache = check_cache($cache_name, $where['comid'].'/');
        if ($cache === false) {
            $list = $model->where($where)->select();
            write_cache($cache_name, $where['comid'].'/', json_encode($list));
        } else {
            $list = json_decode($cache, true);
        }
        foreach ($list as $key => $value) {
            if ($value['uid']>0) {
                $set_login[$value['addtime']]++;
            }
            $set_total[$value['addtime']]++;
        }
        $line_xml = '<chart palettecolors="#0075c2,#1aaf5d" bgcolor="#ffffff" showborder="0" showshadow="0" showcanvasborder="0" useplotgradientcolor="0" legendborderalpha="0" legendshadow="0" showaxislines="0" showalternatehgridcolor="0" divlinethickness="1" divlinedashed="1" divlinedashlen="1" showvalues="0">';
        $line_xml.= '<categories>';
        foreach ($category as $key => $value) {
            $line_xml.= '<category label="'.$value.'" />';
        }
        $line_xml.= '</categories>';
        if ($where['apply']==1) {
            $line_xml.= '<dataset seriesname="用户应聘次数">';
            foreach ($set_login as $key => $value) {
                $line_xml.= '<set value="'.$value.'" />';
            }
            $line_xml.= '</dataset>';
        } else {
            $line_xml.= '<dataset seriesname="总浏览次数">';
            foreach ($set_total as $key => $value) {
                $line_xml.= '<set value="'.$value.'" />';
            }
            $line_xml.= '</dataset>';
            $line_xml.= '<dataset seriesname="登录用户浏览次数">';
            foreach ($set_login as $key => $value) {
                $line_xml.= '<set value="'.$value.'" />';
            }
            $line_xml.= '</dataset>';
        }
        $line_xml.= '</chart>';
        $this->assign('line_xml', $line_xml);
        $tpl = $this->fetch('Company/ajax_tpl/ajax_get_statistics');
        $this->ajaxReturn(1, '统计数据获取成功！', $tpl);
    }
    /**
     * [ajax_get_interest_resume ajax推荐简历]
     */
    public function ajax_get_interest_resume()
    {
        $jobs = M('Jobs')->field('topclass,category')->where(array('uid'=>C('visitor.uid')))->limit(10)->select();
        foreach ($jobs as $key => $val) {
            $topclass[] = $val['topclass'];
            $category[] = $val['category'];
        }
        $p = I('get.p', 1, 'intval');
        $start = 10*($p-1);
        $resume = new \Common\qscmstag\resume_listTag(array('职位大类'=>$topclass,'职位中类'=>$category,'开始位置'=>$start,'显示数目'=>10,'分页显示'=>1));
        $resume_list = $resume->run();
        $resume_page = $resume_list['total']%10>0?(intval($resume_list['total']/10)+1):$resume_list['total']/10;
        $this->assign('resume_list', $resume_list['list']);
        if (IS_AJAX) {
            $tpl = $this->fetch('Company/ajax_tpl/ajax_resume_list');
            $this->ajaxReturn(1, '简历信息获取成功！', array('html'=>$tpl,'page'=>$resume_page));
        }
    }
    /**
     * [jobs 职位管理]
     */
    public function jobs_list()
    {
        $type = I('get.type', 0, 'intval');
        switch ($type) {
            case '1':
                $tabletype="jobs";
                break;
            case '2':
                $tabletype = $tpl = "jobs_tmp";
                break;
            default:
                $tabletype = "all";
                $tpl = 'jobs_list_all';
                break;
        }
        $jobs_list = D('Jobs')->get_jobs(array('uid'=>C('visitor.uid')), 'display asc,audit asc, refreshtime desc', $tabletype, 10, true, true, true);

        if (!APP_SPELL) {
            if (false === $city_cate = F('city_cate_list')) {
                $city_cate = D('CategoryDistrict')->city_cate_cache();
            }
            foreach ($jobs_list as $key => $val) {
                $jobs_list[$key]['jobcategory'] = $city_cate[$val['subclass'] || $val['category'] || $val['topclass']];
            }
        }
        $setmeal=D('MembersSetmeal')->get_user_setmeal(C('visitor.uid'));
        $total[0]=M('Jobs')->where(array('uid'=>C('visitor.uid')))->count();
        $jobs_count_tmp = M('JobsTmp')->where(array('uid'=>C('visitor.uid'),'display'=>1))->count();
        if ($total[0]+$jobs_count_tmp>=$setmeal['jobs_meanwhile']) {
            $upper_limit = 1;
        }
        $total[1]=M('JobsTmp')->where(array('uid'=>C('visitor.uid'),'display'=>array('neq',2)))->count();
        $total=$total[0]+$total[1];
        $promotion[0] = D('SetmealIncrement')->where(array('cat'=>'stick'))->order('value asc')->find();
        $promotion[1] = D('SetmealIncrement')->where(array('cat'=>'emergency'))->order('value asc')->find();
        $stick_discount = D('Setmeal')->get_increment_discount_by_array('stick', $setmeal);
        $emergency_discount = D('Setmeal')->get_increment_discount_by_array('emergency', $setmeal);
        $promotion[0]['price'] = $stick_discount>0?round($stick_discount*$promotion[0]['price']/10, 1):$promotion[0]['price'];
        $promotion[1]['price'] = $emergency_discount>0?round($emergency_discount*$promotion[1]['price']/10, 1):$promotion[1]['price'];
        $this->assign('jobs_list', $jobs_list);
        $this->assign('total', $total);
        $this->assign('setmeal', $setmeal);
        $this->assign('promotion', $promotion);
        $this->assign('upper_limit', $upper_limit);
        $this->_config_seo(array('title'=>'职位管理 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display($tpl);
    }
    // 发布职位
    public function jobs_add()
    {
        if (!$this->cominfo_flge) {
            if (IS_AJAX) {
                $this->ajaxReturn(0, '为了达到更好的招聘效果，请先完善您的企业资料！');
            } else {
                $this->error('为了达到更好的招聘效果，请先完善您的企业资料！', U('company/com_info'));
            }
        }
        //对座机进行分隔
        $telarray = explode('-', $this->company_profile['landline_tel']);
        
        $setmeal=D('MembersSetmeal')->get_user_setmeal(C('visitor.uid'));
    
        // 统计有效职位数
        $jobs_num = D('Jobs')->where(array('uid'=>C('visitor.uid')))->count();
        $jobs_num_tmp = D('JobsTmp')->where(array('uid'=>C('visitor.uid'),'display'=>1))->count();
        if ($jobs_num+$jobs_num_tmp>=$setmeal['jobs_meanwhile'] && I('request.id', 0, 'intval')==0) {
            if (IS_AJAX) {
                $this->ajaxReturn(0, '当前显示的职位已经达到最大限制，请升级服务套餐!');
            } else {
                $this->error('当前显示的职位已经达到最大限制，请升级服务套餐!', U('CompanyService/index'));
            }
        }
        if (IS_POST) {
            // 保存 POST 数据
            // 插入职位信息
            $jobs_info = '';
            if ($id = I('post.id', 0, 'intval')) {
                $setsqlarr['id'] = $id;
                $jobs_info = D('Jobs')->find($id);
                if (!$jobs_info) {
                    $jobs_info = D('JobsTmp')->find($id);
                }
                $name = 'edit_jobs';
            } else {
                $name = 'add_jobs';
            }
            $setsqlarr['setmeal_deadline']=$setmeal['endtime'];
            $setsqlarr['deadline']=$setsqlarr['setmeal_deadline'];
            $setsqlarr['setmeal_id']=$setmeal['setmeal_id'];
            $setsqlarr['setmeal_name']=$setmeal['setmeal_name'];
            $setsqlarr['uid']=C('visitor.uid');
            $setsqlarr['company_id']=$this->company_profile['id'];
            $setsqlarr['company_addtime']=$this->company_profile['addtime'];
            $setsqlarr['company_audit']=$this->company_profile['audit'];
            C('apply.Sincerity') && $setsqlarr['famous']=$this->company_profile['famous'];
            if (!$id) {
                $setsqlarr['audit']= 2;
            } else {
                if ($this->company_profile['audit']==1) {
                    $setsqlarr['audit'] = C('qscms_audit_verifycom_editjob')=='-1'?$jobs_info['audit']:C('qscms_audit_verifycom_editjob');
                } else {
                    $setsqlarr['audit'] = C('qscms_audit_unexaminedcom_editjob')=='-1'?$jobs_info['audit']:C('qscms_audit_unexaminedcom_editjob');
                }
            }
            
            $array = array('companyname','trade','trade_cn','scale','scale_cn','tpl','map_x','map_y','map_zoom');
            if ($setsqlarr['basis_contact']=I('post.basis_contact', 0, 'intval')) {//与企业联系方式同步
                $array = array_merge($array, array('contact','telephone','landline_tel','address','email','contact_show','email_show','telephone_show','landline_tel_show'));
            } else {
                $setsqlarr['contact']=I('post.contact', '', 'trim,badword');
                $setsqlarr['telephone']=I('post.telephone', '', 'trim,badword');
                $setsqlarr['landline_tel']=I('post.landline_tel', '', 'trim,badword');
                $setsqlarr['address']=I('post.address', '', 'trim,badword');
                $setsqlarr['email']=I('post.email', '', 'trim,badword');
                $setsqlarr['contact_show']=I('post.contact_show', 1, 'intval');
                $setsqlarr['email_show']=I('post.email_show', 1, 'intval');
                $setsqlarr['telephone_show']=I('post.telephone_show', 1, 'intval');
                $setsqlarr['landline_tel_show']=I('post.landline_tel_show', 1, 'intval');
            }
            foreach ($array as $val) {
                $setsqlarr[$val] = $this->company_profile[$val];
            }
            $array = array('nature','topclass','category','subclass','amount','district','sdistrict','tdistrict','minwage','maxwage','negotiable','sex','education','experience','graduate','minage','maxage','is_entrust','notify','notify_mobile');
            foreach ($array as $val) {
                $setsqlarr[$val] = I('post.'.$val, 0, 'intval');
            }
            $jobcategory = I('post.jobcategory');
            $jobcategory_arr = explode(".", $jobcategory);
            $setsqlarr['topclass']= $jobcategory_arr[0];
            $setsqlarr['category']= $jobcategory_arr[1];
            $setsqlarr['subclass']= $jobcategory_arr[2];
            $districtcategory = I('post.districtcategory');
            $districtcategory_arr = explode(".", $districtcategory);
            $setsqlarr['district']= $districtcategory_arr[0];
            $setsqlarr['sdistrict']= $districtcategory_arr[1];
            $setsqlarr['tdistrict']= $districtcategory_arr[2];
            $setsqlarr['jobs_name']= I('post.jobs_name', '', 'trim,badword');
            $setsqlarr['tag']=I('post.tag', '', 'trim,badword');// 标签
            $setsqlarr['contents']=I('post.contents', '', 'trim,badword');
            $setsqlarr['department'] = I('post.department', '', 'trim,badword');
            
            $rst = D('Jobs')->$name($setsqlarr, C('visitor'));
            if ($rst['state']==0) {
                if (IS_AJAX) {
                    $this->ajaxReturn(0, $rst['error']);
                } else {
                    $this->error($rst['error']);
                }
            }
            if ($jobs_info) {
                if ($setsqlarr['audit']!=$jobs_info['audit']) {
                    switch ($setsqlarr['audit']) {
                        case 1:
                            $audit_str = '审核通过';
                            break;
                        case 2:
                            $audit_str = '审核中';
                            break;
                        case 3:
                            $audit_str = '审核未通过';
                            break;
                        default:
                            $audit_str = '';
                            break;
                    }
                    if ($audit_str) {
                        $auditsqlarr['jobs_id']=$jobs_info['id'];
                        $auditsqlarr['reason']='自动设置';
                        $auditsqlarr['status']=$audit_str;
                        $auditsqlarr['addtime']=time();
                        $auditsqlarr['audit_man']='系统';
                        M('AuditReason')->data($auditsqlarr)->add();
                    }
                }
            } else {
                switch ($setsqlarr['audit']) {
                    case 1:
                        $audit_str = '审核通过';
                        break;
                    case 2:
                        $audit_str = '';
                        break;
                    case 3:
                        $audit_str = '审核未通过';
                        break;
                    default:
                        $audit_str = '';
                        break;
                }
                if ($audit_str) {
                    $auditsqlarr['jobs_id']=$rst['id'];
                    $auditsqlarr['reason']='自动设置';
                    $auditsqlarr['status']=$audit_str;
                    $auditsqlarr['addtime']=time();
                    $auditsqlarr['audit_man']='系统';
                    M('AuditReason')->data($auditsqlarr)->add();
                }
            }
            if (C('apply.Subsite')) {
                if (false === $subsite_district = F('subsite_district')) {
                    $subsite_district = D('Subsite')->subsite_district_cache();
                }
                $subsite_id = $subsite_district[$setsqlarr['tdistrict']]?:($subsite_district[$setsqlarr['sdistrict']]?:$subsite_district[$setsqlarr['district']]);
            }
            baidu_submiturl(url_rewrite('QS_jobsshow', array('id'=>$rst['id']), $subsite_id), 'addjob');
            if (IS_AJAX) {
                $this->ajaxReturn(1, $id?'修改成功！':'添加成功！', array('url'=>U('company/jobs_list'),'insertid'=>$rst['id']));
            } else {
                $this->redirect('company/jobs_list');
            }
        } else {
            $comtag = $this->company_profile['tag']?explode(",", $this->company_profile['tag']):array();
            $tagArr = array('id'=>array(),'cn'=>array());
            if (!empty($comtag)) {
                foreach ($comtag as $key => $value) {
                    $arr = explode("|", $value);
                    $tagArr['id'][] = $arr[0];
                    $tagArr['cn'][] = $arr[1];
                }
            }
            $tagStr = array('id'=>'','cn'=>'');
            if (!empty($tagArr['id']) && !empty($tagArr['cn'])) {
                $tagStr['id'] = implode(",", $tagArr['id']);
                $tagStr['cn'] = implode(",", $tagArr['cn']);
            }
            $this->assign('tagStr', $tagStr);
            $this->assign('jobs', $jobs);
            /* 分类*/
            $category = D('Category')->get_category_cache();
            $this->assign('category', $category);
            $this->assign('company_nav', 'jobs_list');
            $this->assign('telarray', $telarray);
            $total[0]=M('Jobs')->where(array('uid'=>C('visitor.uid')))->count();
            $total[1]=M('JobsTmp')->where(array('uid'=>C('visitor.uid'),'display'=>array('neq',2)))->count();
            $total=$total[0]+$total[1];
            $this->assign('total', $total);
            $this->assign('setmeal', $setmeal);
            $this->_config_seo(array('title'=>'发布职位 - 企业会员中心 - '.C('qscms_site_name')));
            $this->display();
        }
    }
    public function jobs_edit()
    {
        $id = I('get.id', 0, 'intval');
        $jobs = D('Jobs')->get_jobs_one(array('id'=>$id,'uid'=>C('visitor.uid')));
        $jobs['age'] = explode('-', $jobs['age']);
        $telarray = explode('-', $jobs['contact']['landline_tel']);
        $category = D('Category')->get_category_cache();
        $this->assign('category', $category);
        $this->assign('company_nav', 'jobs_list');
        $this->assign('jobs', $jobs);
        $this->assign('company_profile', $jobs['contact']);
        $this->assign('telarray', $telarray);
        $total[0]=M('Jobs')->where(array('uid'=>C('visitor.uid')))->count();
        $total[1]=M('JobsTmp')->where(array('uid'=>C('visitor.uid'),'display'=>array('neq',2)))->count();
        $total=$total[0]+$total[1];
        $this->assign('total', $total);
        $setmeal=D('MembersSetmeal')->get_user_setmeal(C('visitor.uid'));
        $this->assign('setmeal', $setmeal);
        $upper_limit = 0;
        $jids = M('Jobs')->where(array('uid'=>C('visitor.uid')))->getField('id', true);
        $jids_tmp = M('JobsTmp')->where(array('uid'=>C('visitor.uid'),'display'=>1))->getField('id', true);
        if (count($jids)+count($jids_tmp)>=$setmeal['jobs_meanwhile']) {
            $upper_limit = 1;
        }
        $this->assign('upper_limit', $upper_limit);
        $this->_config_seo(array('title'=>'修改职位 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display('jobs_add');
    }
    /*
        完善企业资料
        包括地图 标注
    */
    public function com_info()
    {
        $company_profile = $this->company_profile;
        if (!IS_POST) {
            //对座机进行分隔
            $telarray = explode('-', $company_profile['landline_tel']);
            if (intval($telarray[0]) > 0) {
                $company_profile['landline_tel_first'] = $telarray[0];
            }
            if (intval($telarray[1]) > 0) {
                $company_profile['landline_tel_next'] = $telarray[1];
            }
            if (intval($telarray[2]) > 0) {
                $company_profile['landline_tel_last'] = $telarray[2];
            }
            /* 分类*/
            $category = D('Category')->get_category_cache();
            $this->assign('category', $category);

            $jobs=M('Jobs')->where(array('uid'=>C('visitor.uid')))->find();
            $jobs && $this->assign('company_jobs', $jobs);
            $comtag = $company_profile['tag']?explode(",", $company_profile['tag']):array();
            $tagArr = array('id'=>array(),'cn'=>array());
            if (!empty($comtag)) {
                foreach ($comtag as $key => $value) {
                    $arr = explode("|", $value);
                    $tagArr['id'][] = $arr[0];
                    $tagArr['cn'][] = $arr[1];
                }
            }
            $tagStr = array('id'=>'','cn'=>'');
            if (!empty($tagArr['id']) && !empty($tagArr['cn'])) {
                $tagStr['id'] = implode(",", $tagArr['id']);
                $tagStr['cn'] = implode(",", $tagArr['cn']);
            }
            $this->assign('company_profile', $company_profile);
            $this->assign('tagStr', $tagStr);
            $this->assign('user', D('Members')->get_user_one(array('uid'=>C('visitor.uid')))); // 新注册会员 邮箱调取注册邮箱
            $this->_config_seo(array('title'=>'企业资料管理 - 企业会员中心 - '.C('qscms_site_name')));
            if (C('qscms_login_com_audit_certificate')==1 && $this->company_profile['audit']==0) {
                $this->assign('jump_certificate', 1);
            } else {
                $this->assign('jump_certificate', 0);
            }
            $this->display();
        } else //保存企业信息
        {
            $setsqlarr['id']=I('post.id', 0, 'intval');
            $setsqlarr['uid']=C('visitor.uid');
            $setsqlarr['companyname']=$company_profile['audit']==1?$company_profile['companyname']:I('post.companyname', 0, 'trim,badword');
            // 判断企业名称是否重复
            if (C('qscms_company_repeat')=="0") {
                $info = M('CompanyProfile')->where(array('uid'=>array('neq',C('visitor.uid')),'companyname'=>$setsqlarr['companyname']))->getField('uid');
                if ($info) {
                    $this->ajaxReturn(0, "{$setsqlarr['companyname']}已经存在，同公司信息不能重复注册");
                }
            }

            $data = array('nature','trade','scale');
            foreach ($data as $val) {
                $setsqlarr[$val] = I('post.'.$val, 0, 'intval');
            }
            $setsqlarr['districtcategory'] = I('post.districtcategory', '', 'trim');
            // 分类缓存
            $category = D('Category')->get_category_cache();
            $city = D('CategoryDistrict')->get_district_cache('all');
            $setsqlarr['nature_cn']=$category['QS_company_type'][$setsqlarr['nature']];
            $setsqlarr['trade_cn']=$category['QS_trade'][$setsqlarr['trade']];
            $district_arr = explode(".", $setsqlarr['districtcategory']);
            $setsqlarr['district'] = intval($district_arr[0]);
            $setsqlarr['sdistrict'] = intval($district_arr[1]);
            $setsqlarr['tdistrict'] = intval($district_arr[2]);
            $setsqlarr['district_cn']= $city[0][$setsqlarr['district']];
            $setsqlarr['sdistrict'] && $setsqlarr['district_cn'].= '/'.$city[$setsqlarr['district']][$setsqlarr['sdistrict']];
            $setsqlarr['tdistrict'] && $setsqlarr['district_cn'].= '/'.$city[$setsqlarr['sdistrict']][$setsqlarr['tdistrict']];
            // $setsqlarr['street_cn']=$category['QS_street'][$setsqlarr['street']];
            $setsqlarr['scale_cn']=$category['QS_scale'][$setsqlarr['scale']];
            // 字符串字段
            $setsqlarr['companyname']=I('post.companyname', '', 'trim,badword');
            $setsqlarr['registered']=I('post.registered', '', 'trim,badword');
            $setsqlarr['currency']=I('post.currency', '', 'trim,badword');
            $setsqlarr['address']=I('post.address', '', 'trim,badword');
            $setsqlarr['contact']=I('post.contact', '', 'trim,badword');
            $setsqlarr['telephone'] = C('visitor.mobile_audit') ? C('visitor.mobile') : I('post.telephone', '', 'trim,badword');
            $setsqlarr['email'] = C('visitor.email_audit') ? C('visitor.email') : I('post.email', '', 'trim,badword');
            $setsqlarr['website']=I('post.website', '', 'trim,badword');
            $setsqlarr['contents']=I('post.contents', '', 'trim,badword');
            $setsqlarr['contact_show']=I('post.contact_show', 1, 'intval');
            $setsqlarr['telephone_show']=I('post.telephone_show', 1, 'intval');
            $setsqlarr['landline_tel_show']=I('post.landline_tel_show', 1, 'intval');
            $setsqlarr['email_show']=I('post.email_show', 1, 'intval');
            $setsqlarr['qq']=I('post.qq', 0, 'intval');
            $setsqlarr['map_x']=I('post.map_x', 0, 'trim,badword');
            !$setsqlarr['map_x'] && $setsqlarr['map_x'] = 0;
            $setsqlarr['map_y']=I('post.map_y', 0, 'trim,badword');
            !$setsqlarr['map_y'] && $setsqlarr['map_y'] = 0;
            $setsqlarr['map_zoom']=I('post.map_zoom', 0, 'intval');
            
            //座机
            $landline_tel_first=I('post.landline_tel_first', 0, 'trim,badword');
            $landline_tel_next=I('post.landline_tel_next', 0, 'trim,badword');
            $landline_tel_last=I('post.landline_tel_last', 0, 'trim,badword');
            $setsqlarr['landline_tel']=$landline_tel_first.'-'.$landline_tel_next.($landline_tel_last?('-'.$landline_tel_last):'');
            $posttag = I('post.tag', '', 'trim,badword');

            if ($posttag) {
                $tagArr = explode(",", $posttag);
                $r_arr = array();
                foreach ($tagArr as $key => $value) {
                    $r_arr[] = $value.'|'.$category['QS_jobtag'][$value];
                }
                if (!empty($r_arr)) {
                    $setsqlarr['tag'] = implode(",", $r_arr);
                } else {
                    $setsqlarr['tag'] = '';
                }
            }

            if ($company_profile['contents']) {
                $setsqlarr['id']=$company_profile['id'];
                C('qscms_audit_edit_com')<>"-1"?$setsqlarr['audit']=C('qscms_audit_edit_com'):$setsqlarr['audit']=$company_profile['audit'];
            } else {
                $setsqlarr['audit']=0;
            }
            $setsqlarr['sync'] = I('post.sync', 0, 'intval');
            // 插入数据
            $rst = D('CompanyProfile')->add_company_profile($setsqlarr, C('visitor'));
            $rst['state']==0 && $this->ajaxReturn(0, $rst['error']);
            $r = D('TaskLog')->do_task(C('visitor'), 27);
            if ($setsqlarr['map_x'] && $setsqlarr['map_y'] && $setsqlarr['map_zoom']) {
                D('TaskLog')->do_task(C('visitor'), 29);
            }
            if ($setsqlarr['audit']!=$company_profile['audit']) {
                switch ($setsqlarr['audit']) {
                    case 0:
                        $audit_str = '未认证';
                        break;
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
                    $auditsqlarr['company_id']=$company_profile['id'];
                    $auditsqlarr['reason']='自动设置';
                    $auditsqlarr['status']=$audit_str;
                    $auditsqlarr['addtime']=time();
                    $auditsqlarr['audit_man']='系统';
                    M('AuditReason')->data($auditsqlarr)->add();
                }
            }
            
            if ($rst['id']) {
                $success ="添加成功！";
            } else {
                $success ="保存成功！";
            }
            if (!$this->cominfo_flge && C('apply.Statistics')) {
                $idata['pid'] = $company_profile['id'];
                $idata['nature'] = $setsqlarr['nature'];
                $idata['trade'] = $setsqlarr['trade'];
                $idata['trade_cn'] = $setsqlarr['trade_cn'];
                $idata['scale'] = $setsqlarr['scale'];
                $idata['addtime'] = time();
                $class = new \Statistics\Model\CModel($idata);
                $class->company_add();
            }
            $this->ajaxReturn(1, $success, $r['points']);
        }
    }
    /**
     * [authenticate 帐号安全]
     */
    public function user_security()
    {
        $uid=C('visitor.uid');
        $user_bind = M('MembersBind')->where(array('uid'=>$uid))->limit('10')->getfield('type,keyid,info');
        foreach ($user_bind as $key => $val) {
            $user_bind[$key] = unserialize($val['info']);
        }
        if (false === $oauth_list = F('oauth_list')) {
            $oauth_list = D('Oauth')->oauth_cache();
        }
        $this->assign('members_info', D('Members')->get_user_one(array('uid'=>$uid)));
        $this->assign('user_bind', $user_bind);
        $this->assign('oauth_list', $oauth_list);
        $this->assign('company_nav', 'com_info');
        $this->_config_seo(array('title'=>'账号安全 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    /**
     * [authentication 企业认证]
     */
    public function com_auth()
    {
        !$this->cominfo_flge && $this->error('为了达到更好的招聘效果，请先完善您的企业资料！', U('company/com_info'));
        if (IS_POST && $this->company_profile['audit']!=1 && $this->company_profile['audit']!=2) {
            $data['companyname_full'] = I('post.companyname', '', 'trim,badword');
            $data['license'] = I('post.license', '', 'trim,badword');
            $data['legal_person'] = I('post.legal_person', '', 'trim,badword');
            $data['certificate_img'] = I('post.certificate_img_up', '', 'trim,badword');
            $data['audit'] = 2;
            $rst = D('CompanyProfile')->add_certificate_img($data, C('visitor'));
            !$rst['state'] && $this->error($rst['error']);
            $this->assign('company_profile', array_merge($this->company_profile, $data));
        }
        $max_size = intval(C('qscms_certificate_max_size'));
        if ($max_size > 1000) {
            $max_size = intval($max_size/1000);
            $max_size .='MB';
        } else {
            $max_size .= 'kb';
        }
        $this->assign('max_size', $max_size);
        $this->assign('company_nav', 'com_info');
        $this->_config_seo(array('title'=>'企业认证 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    public function jobs_close()
    {
        if (IS_POST) {
            $yid =  I('request.y_id');
            if (!$yid) {
                IS_AJAX && $this->ajaxReturn(0, '请选择职位！');
                $this->error('请选择职位！');
            }
            $perform_type = 'close';
            $rst = D('Jobs')->jobs_perform(array('yid'=>$yid,'perform_type'=>$perform_type,'user'=>C('visitor')));
            $return_url = I('request.list_type', 0, 'intval')==0?U('jobs_list'):U('jobs_list', array('type'=>1));
            if ($rst['state']==1) {
                IS_AJAX && $this->ajaxReturn(1, '操作成功！', $return_url);
                $this->success('操作成功！', $return_url);
            } else {
                IS_AJAX && $this->ajaxReturn(0, $rst['error'], $return_url);
                $this->error($rst['error'], $return_url);
            }
        } else {
            $tip='职位关闭后将会暂停招聘，您确定要关闭选中的职位吗？';
            $this->ajax_warning($tip);
        }
    }
    public function jobs_display()
    {
        if (IS_POST) {
            $yid =  I('request.y_id');
            if (!$yid) {
                IS_AJAX && $this->ajaxReturn(0, '请选择职位！');
                $this->error('请选择职位！');
            }
            $perform_type = 'display';
            $rst = D('Jobs')->jobs_perform(array('yid'=>$yid,'perform_type'=>$perform_type,'user'=>C('visitor')));
            $return_url = I('request.list_type', 0, 'intval')==0?U('jobs_list'):U('jobs_list', array('type'=>1));
            if ($rst['state']==1) {
                IS_AJAX && $this->ajaxReturn(1, '操作成功！', $return_url);
                $this->success('操作成功！', $return_url);
            } else {
                IS_AJAX && $this->ajaxReturn(0, $rst['error'], $return_url);
                $this->error($rst['error'], $return_url);
            }
        } else {
            $tip='职位恢复后将会对外公开招聘，您确定要恢复选中的职位吗？';
            $this->ajax_warning($tip);
        }
    }
    public function jobs_delete()
    {
        $yid =  I('request.y_id');
        if (IS_POST) {
            if (!$yid) {
                IS_AJAX && $this->ajaxReturn(0, '请选择职位！');
                $this->error('请选择职位！');
            }
            $perform_type = 'delete';
            $rst = D('Jobs')->jobs_perform(array('yid'=>$yid,'perform_type'=>$perform_type,'user'=>C('visitor')));
            $return_url = I('request.list_type', 0, 'intval')==0?U('jobs_list'):U('jobs_list', array('type'=>1));
            if ($rst['state']==1) {
                IS_AJAX && $this->ajaxReturn(1, '操作成功！', $return_url);
                $this->success('操作成功！', $return_url);
            } else {
                IS_AJAX && $this->ajaxReturn(0, $rst['error'], $return_url);
                $this->error($rst['error'], $return_url);
            }
        } else {
            $tip='被删除后将无法恢复，您确定要删除选中的职位吗？';
            if (C('apply.Allowance')) {
                $has_allowance = D('Jobs')->where(array('id'=>array('in',$yid),'allowance_id'=>array('gt',0)))->select();
                $has_allowance_tmp = D('JobsTmp')->where(array('id'=>array('in',$yid),'allowance_id'=>array('gt',0)))->select();
                if ($has_allowance || $has_allowance_tmp) {
                    $tip='当前选中的职位中包含红包职位，删除后请联系客服退款，确定删除吗？';
                }
            }
            $this->ajax_warning($tip);
        }
    }
    
    /**
     * [jobs_interview 面试邀请]
     */
    public function jobs_interview()
    {
        $this->check_params();
        $where['company_uid']= C('visitor.uid');
        $stop =I('get.stop', 0, 'intval');
        $settr =I('get.settr', 0, 'intval');
        $settr && $where['interview_addtime']=array('gt',strtotime("-{$settr} day")); //筛选简历
        $look=I('get.look', 0, 'intval');
        $look>0 && $where['personal_look'] =$look;
        $jobs_id = I('get.jobs_id', 0, 'intval');
        $condition['uid'] = C('visitor.uid');
        (!$stop && C('qscms_jobs_display') == 1) && $condition['audit'] = 1;
        $jobs_list1 = M('Jobs')->where($condition)->getField('id,jobs_name');
        $jobs_list1 = $jobs_list1?$jobs_list1:array();
        if ($stop==1) {
            $jobs_list2 = M('JobsTmp')->where(array('uid'=>C('visitor.uid')))->getField('id,jobs_name');
            $jobs_list2 = $jobs_list2?$jobs_list2:array();
            $jobs_list = $jobs_list1 + $jobs_list2;
        } else {
            $jobs_list = $jobs_list1;
        }
        $jobs_id_arr = array();
        foreach ($jobs_list as $key => $value) {
            $jobs_id_arr[] = $key;
        }
        if ($jobs_id>0) {
            $where['jobs_id'] =$jobs_id;
        } else if (!empty($jobs_id_arr)) {
            $where['jobs_id'] = array('in',$jobs_id_arr);
        } else {
            $where['jobs_id'] = 0;
        }
        $company_interview_mod = D('CompanyInterview');
        $interview = $company_interview_mod->get_invitation_pre($where, 1);
        
        $this->assign('jobs_list', $jobs_list);
        $this->assign('interview', $interview);
        $this->assign('jobs_id', $jobs_id);
        $this->assign('company_nav', 'jobs_apply');
        $this->_config_seo(array('title'=>'我发起的面试邀请 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    public function jobs_money()
    {
        $this->display();
    }
    /**
     * [jobs_interview_details 面试详情]
     */
    public function jobs_interview_details()
    {
        if (IS_AJAX) {
            $id = I('get.id', 0, 'intval');
            !$id && $this->ajaxReturn(0, '请正确选择面试信息！');
            $interview = M('CompanyInterview')->where(array('did'=>$id,'company_uid'=>C('visitor.uid')))->find();
            !$interview && $this->ajaxReturn(0, '面试信息不存在！');
            $this->assign('interview', $interview);
            $is_apply = 0;
            $apply = M('PersonalJobsApply')->where(array('resume_id'=>$interview['resume_id'],'company_uid'=>C('visitor.uid')))->find();
            if ($apply) {
                $is_apply = 1;
            }
            $this->assign('is_apply', $is_apply);
            $html = $this->fetch('Company/ajax_tpl/ajax_show_interview');
            $this->ajaxReturn(1, '面试信息获取成功！', $html);
        }
    }
    /**
     * 添加面试邀请
     */
    public function jobs_interview_add()
    {
        if (IS_POST) {
            $data['jobs_id'] = I('post.jobs_id', 0, 'intval');
            $data['resume_id'] = I('post.resume_id', 0, 'intval');
            $date = I('post.date', '', 'trim');
            if (!$date) {
                $this->ajaxReturn(0, '请选择面试日期');
            }
            $ap = I('post.ap', 1, 'intval')== 1? 'AM' : 'PM';
            $time = I('post.time', 0, 'intval');
            if (!$time) {
                $this->ajaxReturn(0, '请选择面试时间');
            }
            $data['interview_time'] = strtotime($date.' '.$time.':00:00 '.$ap);
            if ($data['interview_time']<time()) {
                $this->ajaxReturn(0, '面试时间不能早于当前时间');
            }
            $data['address'] = I('post.address', '', 'trim');
            $data['contact'] = I('post.contact', '', 'trim');
            $data['telephone'] = I('post.telephone', '', 'trim');
            $data['notes'] = I('post.notes', '', 'trim');
            $data['sms_notice'] = I('post.sms_notice', 0, 'intval');
            $reg = D('CompanyInterview')->add_interview($data, C('visitor'));
            $this->ajaxReturn($reg['state'], $reg['msg']);
        } else {
            $id = I('get.id', 0, 'intval');
            !$id && $this->ajaxReturn(0, '请选择简历！');
            $is_apply = 0;
            $apply = M('PersonalJobsApply')->where(array('resume_id'=>$id,'company_uid'=>C('visitor.uid')))->find();
            if ($apply) {
                $is_apply = 1;
            }
            if (C('qscms_showresumecontact') == 2) {
                !$apply && $apply = M('CompanyDownResume')->where(array('resume_id'=>$id,'company_uid'=>C('visitor.uid')))->find();
                !$apply && $this->ajaxReturn(0, '请先下载简历！');
            } elseif (!$apply) {
                $apply = M('Resume')->field('id as resume_id,uid as resume_uid,fullname as resume_name')->find($id);
            }
            $company = M('CompanyProfile')->field('district_cn,contact,telephone,landline_tel,address')->where(array('uid'=>C('visitor.uid')))->find();
            $apply = array_merge($apply, $company);
            $apply['fullname'] = M('Resume')->where(array('id'=>$apply['resume_id']))->getfield('fullname');
            $jobs_map['uid'] = C('visitor.uid');
            if (C('qscms_jobs_display')==1) {
                $jobs_map['audit'] = 1;
            }
            $jobs = D('Jobs')->get_jobs($jobs_map, 'refreshtime desc', 'jobs', '-1');
            if ($jobs['list']) {
                $temp = current($jobs['list']);
                $default_jobs['jobs_id'] = $temp['id'];
                $default_jobs['jobs_name'] = $temp['jobs_name'];
            } else {
                $this->ajaxReturn(0, '您还没有发布职位，请先发布职位！');
            }
           
            
            $this->assign('jobs', $jobs['list']);
            $this->assign('default_jobs', $default_jobs);
            $this->assign('is_apply', $is_apply);
            $this->assign('apply', $apply);
            $html = $this->fetch('Company/ajax_tpl/ajax_interview');
            $this->ajaxReturn(1, '面试邀请弹窗获取成功！', $html);
        }
    }
    // 删除面试邀请
    public function del_jobs_interview()
    {
        if (IS_AJAX) {
            $tip='被删除后将无法恢复，您确定要删除选中的面试邀请记录吗？';
            $this->ajax_warning($tip);
        } else {
            $yid= I('request.y_id', '', 'trim');
            !$yid && $this->error("你没有选择项目！");
            $rst = D('CompanyInterview')->del_interview($yid, C('visitor'));
            if ($rst['state']) {
                $this->success("删除成功！共删除 ".$rst['num']." 行！", U('company/jobs_interview'));
            } else {
                $this->error("删除失败！");
            }
        }
    }
    /**
     * 添加下载简历（未完成）
     */
    public function resume_down_add()
    {
        $addarr["resume_id"]=I('request.resume_id', 0, 'intval');
        $save = false;
        if (IS_POST) {
            $save = true;
        }
        $rst = D('CompanyDownResume')->add_down_resume($addarr, C('visitor'), $save);
        if ($rst['state']==2) {
            $this->assign('data', $rst['data']);
            $html = $this->fetch('Company/ajax_tpl/down_resume');
            $this->ajaxReturn(1, '返回成功！', $html);
        } else {
            $this->ajaxReturn($rst['state'], $rst['msg']);
        }
    }
    /*
        收到的简历
    */
    // 列表
    public function jobs_apply()
    {
        $this->check_params();
        $where['company_uid']=C('visitor.uid');
        $stop =I('get.stop', 0, 'intval');
        $settr =I('get.settr', 0, 'intval');
        $settr && $where['apply_addtime']=array('gt',strtotime("-{$settr} day")); //筛选简历
        $jobs_id =I('get.jobs_id', 0, 'intval');
        $jobs_id && $where['jobs_id']=$jobs_id; //筛选简历
        $is_reply=I('get.is_reply', 0, 'intval');
        
        $where['is_reply']=$is_reply==0?0:array('gt',0);
        // 筛选项 -> 标签 (0->全部  1->合适  2->不合适  3->待定  4->未接通)
        $state=I('get.state', 0, 'intval');
        // 筛选项 -> 来源 (0->全部  1->委托投递  2->主动投递 )
        $is_apply=I('get.is_apply', 0, 'intval');
        $is_apply && $where['is_apply']=$is_apply;
        $personal_apply_mod = D('PersonalJobsApply');
        $condition['uid'] = C('visitor.uid');
        (!$stop && C('qscms_jobs_display') == 1) && $condition['audit'] = 1;
        $jobs_list = M('Jobs')->where($condition)->getField('id,jobs_name');
        if ($stop) {
            $jobs_list_tmp = M('JobsTmp')->where(array('uid'=>C('visitor.uid')))->getField('id,jobs_name');
            if ($jobs_list_tmp) {
                $jobs_list = $jobs_list+$jobs_list_tmp;
            }
        }

        $jobsid_arr = array();
        foreach ($jobs_list as $key => $value) {
            $jobsid_arr[] = $key;
        }
        if (!empty($jobsid_arr) && !$where['jobs_id']) {
            $where['jobs_id'] = array('in',$jobsid_arr);
        }
        $apply_list = $personal_apply_mod->get_apply_jobs($where, 1, $state);
        $date1 = time();
        $date2 = $date1-3600*24*14;
        $this->assign('date', date('Y/m/d', $date2));
        $count_where['apply_addtime'] = array('between',array($date2,$date1));
        $count_where['company_uid'] = array('eq',C('visitor.uid'));
        $count[0] = $personal_apply_mod->where($count_where)->count();
        $count_where['is_reply'] = array('gt',0);
        $count[1] = $personal_apply_mod->where($count_where)->count();
        $count[2] = round($count[1]/$count[0], 2)*100;
        $count[2] = !$count[1] ? $count[2] : 100;
        
        $this->assign('count', $count);
        $this->assign('jobs_id', $jobs_id);
        $this->assign('jobs_list', $jobs_list);
        $this->assign('apply_list', $apply_list);
        $this->assign('is_reply', $is_reply);
        $this->assign('state', $state);
        $this->assign('state_arr', $personal_apply_mod->state_arr);
        $this->_config_seo(array('title'=>'收到的申请 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    // 删除
    public function del_jobs_apply()
    {
        if (IS_AJAX) {
            $tip='被删除后将无法恢复，您确定要删除选中的简历吗？';
            $this->ajax_warning($tip);
        } else {
            $yid= I('request.y_id');
            !$yid && $this->error("你没有选择项目！");
            $reg=D('PersonalJobsApply')->del_jobs_apply($yid, C('visitor'));
            if ($reg['state']==1) {
                $this->success("删除成功！", U('jobs_apply'));
            } else {
                $this->error("删除失败！", U('jobs_apply'));
            }
        }
    }
    public function resume_doc_for_apply()
    {
        $yid= I('request.y_id');
        !$yid && $this->error("你没有选择项目！");
        $this->_resume_doc($yid, 'personal_jobs_apply');
    }
    public function resume_doc_for_download()
    {
        $yid= I('request.y_id');
        !$yid && $this->error("你没有选择项目！");
        $this->_resume_doc($yid, 'company_down_resume');
    }
    /**
     * 保存到电脑
     */
    public function _resume_doc($yid, $mod_name)
    {
        $r = D('Resume')->save_as_doc_word($yid, D($mod_name), C('visitor'), 1);
        if ($r===false) {
            $this->error('找不到简历');
        } else if ($r['zip']==1) {
            session('save_to_local_name', $r['name']);
            session('save_to_local_dir', $r['dir']);
            $this->redirect('Home/Company/save_to_local');
        }
    }
    public function save_to_local()
    {
        $name = session('save_to_local_name');
        $dir = session('save_to_local_dir');
        session('save_to_local_name', null);
        session('save_to_local_dir', null);
        header("Location:".attach($name, $dir));
    }
    
    /*
        已下载的简历
    */
    public function resume_down()
    {
        $this->check_params();
        $where['company_uid']=C('visitor.uid');
        $settr=I('get.settr', 0, 'intval');
        $settr && $where['down_addtime']=array('gt',strtotime("-".$settr." day")); //筛选 下载时间
        $state = I('get.state', ''); // 简历标记状态
        $down_list = D('CompanyDownResume')->get_down_resume($where, $state);
        $this->assign('down_list', $down_list);
        $this->assign('state', $state);
        $this->assign('state_arr', D('CompanyDownResume')->state_arr);
        $this->assign('company_nav', 'jobs_apply');
        $this->_config_seo(array('title'=>'已下载的简历 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    
    /*
        收藏的简历
    */
    public function resume_favorites()
    {
        $this->check_params();
        $where['company_uid']=C('visitor.uid');
        $settr=I('get.settr', 0, 'intval');
        $settr && $where['favorites_addtime']=array('gt',strtotime("-".$settr." day")); //筛选 收藏时间
        $favorites = D('CompanyFavorites')->get_favorites($where);
        $this->assign('favorites', $favorites);
        $this->assign('company_nav', 'jobs_apply');
        $this->_config_seo(array('title'=>'已收藏的简历 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    /**
     * 收藏简历
     */
    public function resume_favorites_add()
    {
        $rid= I('request.rid');
        !$rid && $this->error("你没有选择简历！");
        $n=D('CompanyFavorites')->add_favorites($rid, C('visitor'));
        if ($n['state']==1) {
            $this->success("收藏成功！");
        } else {
            $this->error("收藏失败！");
        }
    }
    // 删除收藏的简历
    public function del_resume_favorites()
    {
        if (IS_AJAX) {
            $tip='被删除后将无法恢复，您确定要删除选中的简历吗？';
            $this->ajax_warning($tip);
        } else {
            $yid= I('request.y_id');
            !$yid && $this->error("你没有选择项目！");
            $n=D('CompanyFavorites')->del_favorites($yid, C('visitor'));
            if ($n['state']==1) {
                $this->success("删除成功！", U('resume_favorites'));
            } else {
                $this->error("删除失败！", U('resume_favorites'));
            }
        }
    }
    /*
        浏览过的简历
    */
    public function resume_viewlog()
    {
        $this->check_params();
        $where['uid']=C('visitor.uid');
        $settr=I('get.settr', 0, 'intval');
        $settr && $where['addtime']=array('gt',strtotime("-".$settr." day")); //筛选 查看时间
        $view_resume = D('ViewResume')->get_view_resume($where);
        // dump($view_resume);
        $this->assign('view_resume', $view_resume);
        $this->assign('company_nav', 'jobs_apply');
        $this->_config_seo(array('title'=>'浏览过的简历 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    // 删除浏览过的简历
    public function del_resume_viewlog()
    {
        if (IS_AJAX) {
            $tip='被删除后将无法恢复，您确定要删除选中的简历浏览记录吗？';
            $this->ajax_warning($tip);
        } else {
            $yid= I('request.y_id');
            !$yid && $this->error("你没有选择项目！");
            $n=D('ViewResume')->del_view_resume($yid);
            if ($n['state']==1) {
                //写入会员日志
                $yid = is_array($yid)?implode(",", $yid):$yid;
                write_members_log(C('visitor'), 1020, $yid);
                $this->success("删除成功！", U('resume_viewlog'));
            } else {
                $this->error("删除失败！", U('resume_viewlog'));
            }
        }
    }
    /*
        谁看过我的职位
    */
    public function jobs_viewlog()
    {
        $this->check_params();
        $settr=I('get.settr', 0, 'intval');
        $settr && $where['addtime']=array('gt',strtotime("-".$settr." day")); //筛选 查看时间
        if ($jobs_id=I('get.jobs_id', 0, 'intval')) {
            $where['jobsid']=$jobs_id;
        } else {
            $where['jobs_uid']=C('visitor.uid');
        }
        if ($where) {
            $view_jobs = D('ViewJobs')->get_view_jobs($where);
        } else {
            $view_jobs = array();
        }
        $this->assign('view_jobs', $view_jobs);
        $this->assign('company_nav', 'jobs_apply');
        $this->_config_seo(array('title'=>'谁看过我的职位 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    // 删除  谁看过我的职位
    public function del_jobs_viewlog()
    {
        if (IS_AJAX) {
            $tip='被删除后将无法恢复，您确定要删除选中的被关注记录吗？';
            $this->ajax_warning($tip);
        } else {
            $yid= I('request.y_id');
            !$yid && $this->error("你没有选择项目！");
            $n=D('ViewJobs')->del_view_jobs($yid);
            if ($n['state']==1) {
                //写入会员日志
                $yid = is_array($yid)?implode(",", $yid):$yid;
                write_members_log(C('visitor'), 1021, $yid);
                $this->success("删除成功！", U('jobs_viewlog'));
            } else {
                $this->error("删除失败！", U('jobs_viewlog'));
            }
        }
    }
    /**
     * [jobfair 招聘会]
     */
    public function jobfair_list()
    {
        if (!isset($this->apply['Jobfair'])) {
            $this->_empty();
        }
        $where = array('显示数目' => 30,'分页显示' => 1,'列表页'=>'jobfair_list');
        $jobfair_mod = new \Common\qscmstag\jobfair_listTag($where);
        $jobfair = $jobfair_mod->run();
        $exhibitors = M('JobfairExhibitors')->where(array('uid'=>C('visitor.uid')))->getfield('jobfair_id,audit');
        foreach ($jobfair['list'] as $key => $val) {
            $jobfair['list'][$key]['audit'] = $exhibitors[$val['id']];
        }
        $this->assign('jobfair_list', $jobfair);
        $this->assign('week', array('日','一','二','三','四','五','六'));
        $this->assign('company_nav', 'jobfair_list');
        $this->_config_seo(array('title'=>'近期招聘会 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    /**
     * [my_jobfair 我预定的展会记录]
     */
    public function jobfair_enact()
    {
        if (!isset($this->apply['Jobfair'])) {
            $this->_empty();
        }
        $jobfair = D('Jobfair/JobfairExhibitors')->get_jobfair_exhibitors(C('visitor'));
        $this->assign('jobfair', $jobfair);
        $this->assign('week', array('日','一','二','三','四','五','六'));
        $this->assign('company_nav', 'jobfair_list');
        $this->_config_seo(array('title'=>'我预定的展位 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    /*
        企业风采
    */
    public function com_img()
    {
        $company_img = M('CompanyImg')->where(array('uid'=>C('visitor.uid')))->select();
        $this->assign('company_img', $company_img);
        $this->assign('count', count($company_img));
        $this->assign('company_nav', 'com_info');
        $this->_config_seo(array('title'=>'企业风采 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    // 删除企业风采
    public function del_company_img()
    {
        $del = I('request.del', 0, 'intval');
        if ($del) {
            $id = I('request.id', 0, 'intval');
            $img =  M('CompanyImg')->find($id);
            !$img && $this->ajaxReturn(0, '删除失败！');
            @unlink(C('qscms_attach_path').'company_img/'.$img['img']);
            @unlink(C('qscms_attach_path').'company_img/'.$img['img'].'_600x600.jpg');
            @unlink(C('qscms_attach_path').'company_img/'.$img['img'].'_295x165.jpg');
            if (C('qscms_qiniu_open')==1) {
                $qiniu = new \Common\ORG\qiniu;
                $qiniu->delete($img['img']);
            }
            $num = M('CompanyImg')->where(array('uid'=>C('visitor.uid'),'id'=>$id))->delete();
            $num===false && $this->ajaxReturn(0, '删除失败！');
            //写入会员日志
            write_members_log(C('visitor'), 1022, $id);
            $this->ajaxReturn(1, '删除成功！');
        } else {
            $tip='被删除后将无法恢复，您确定要删除该风采照片吗？';
            $this->ajax_warning($tip);
        }
    }
    // 设置图片备注
    public function set_company_img_title()
    {
        $id = I('request.id', 0, 'intval');
        if (IS_POST) {
            $title = I('post.title', '', 'trim,badword');
            !$title && $this->ajaxReturn(0, '备注不能为空！');
            $reg = D('CompanyImg')->edit_company_img(array('id'=>$id,'title'=>$title), C('visitor'));
            if (!$reg['state']) {
                $this->ajaxReturn(0, $reg['error']);
            }
            //写入会员日志
            write_members_log(C('visitor'), 1023, $id);
            $this->ajaxReturn(1, '备注成功！');
        }
        $info = M('CompanyImg')->where(array('uid'=>C('visitor.uid'),'id'=>$id))->find();
        $this->assign('info', $info);
        $html = $this->fetch('Company/ajax_tpl/ajax_remark_img');
        $this->ajaxReturn(1, '获取数据成功！', $html);
    }
    /*
        登录日志
    */
    public function user_loginlog()
    {
        $where = array('log_uid'=>C('visitor.uid'),'log_type'=>1001);
        $loginlog = D('MembersLog')->get_members_log($where, 15);
        $this->assign('loginlog', $loginlog);
        $this->assign('company_nav', 'com_info');
        $this->assign('left_nav', 'user_security');
        $this->_config_seo(array('title'=>'会员登录日志 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    public function _statistics($where, $mark = false)
    {
        $model = D('CompanyStatistics');
        $today = strtotime(date('Y-m-d'));
        $where['addtime'] = array('lt',$today);
        $settr = I('get.settr', 7, 'intval');
        if ($settr>0) {
            $settr_tmp = $today-$settr*3600*24;
            $where['addtime'] = array(array('egt',$settr_tmp),array('lt',$today));
        }
        $source = I('get.source', 0, 'intval');
        if ($source>0) {
            $where['source'] = array('eq',$source);
        }
        $jobid = I('get.jobid', 0, 'intval');
        if ($jobid>0) {
            $where['jobid'] = array('eq',$jobid);
        }
        $category = array();
        $set_total = $set_login = array();
        for ($i=$settr_tmp; $i < $today; $i=$i+3600*24) {
            $category[] = date('Y-m-d', $i);
            $set_total[$i] = 0;
            $set_login[$i] = 0;
        }
        $uidArr = array();
        $count_num = array('total'=>0,'login'=>0);

        $cache_name = ($mark?($mark.'_'):'').$where['comid'].'_'.$where['apply'].'_'.$settr.'_'.$source.'_'.$jobid.'_line_data.cache';

        $cache = check_cache($cache_name, $where['comid'].'/');
        if ($cache === false) {
            $list = $model->where($where)->order('viewtime desc')->select();
            write_cache($cache_name, $where['comid'].'/', json_encode($list));
        } else {
            $list = json_decode($cache, true);
        }
        
        $view_time = array();
        foreach ($list as $key => $value) {
            if ($value['uid']>0) {
                if ($value['jobid']>0 && $value['apply']==0 && !isset($view_time['time']['viewjobs'][$value['uid']])) {
                    $view_time['time']['viewjobs'][$value['uid']] = $value['viewtime'];
                    $view_time['source']['viewjobs'][$value['uid']] = $value['source'];
                }
                if ($value['jobid']>0 && $value['apply']>0 && !isset($view_time['time']['apply'][$value['uid']])) {
                    $view_time['time']['apply'][$value['uid']] = $value['viewtime'];
                    $view_time['source']['apply'][$value['uid']] = $value['source'];
                }
                if ($value['apply']==0 && !isset($view_time['time']['visitor'][$value['uid']])) {
                    $view_time['time']['visitor'][$value['uid']] = $value['viewtime'];
                    $view_time['source']['visitor'][$value['uid']] = $value['source'];
                }
                $set_login[$value['addtime']]++;
                $uidArr[] = $value['uid'];
            }
            $set_total[$value['addtime']]++;
            $count_num['total']++;
        }
        $this->assign('view_time', $view_time);
        $line_xml = '<chart palettecolors="#0075c2,#1aaf5d" bgcolor="#ffffff" showborder="0" showshadow="0" showcanvasborder="0" useplotgradientcolor="0" legendborderalpha="0" legendshadow="0" showaxislines="0" showalternatehgridcolor="0" divlinethickness="1" divlinedashed="1" divlinedashlen="1" showvalues="0">';
        $line_xml.= '<categories>';
        foreach ($category as $key => $value) {
            $line_xml.= '<category label="'.$value.'" />';
        }
        $line_xml.= '</categories>';
        if ($where['apply']==1) {
            $line_xml.= '<dataset seriesname="用户应聘次数">';
            foreach ($set_login as $key => $value) {
                $line_xml.= '<set value="'.$value.'" />';
            }
            $line_xml.= '</dataset>';
        } else {
            $line_xml.= '<dataset seriesname="总浏览次数">';
            foreach ($set_total as $key => $value) {
                $line_xml.= '<set value="'.$value.'" />';
            }
            $line_xml.= '</dataset>';
            $line_xml.= '<dataset seriesname="登录用户浏览次数">';
            foreach ($set_login as $key => $value) {
                $line_xml.= '<set value="'.$value.'" />';
            }
            $line_xml.= '</dataset>';
        }
        $line_xml.= '</chart>';
        $this->assign('line_xml', $line_xml);

        if (!empty($uidArr)) {
            $cache_name = ($mark?($mark.'_'):'').$where['comid'].'_'.$where['apply'].'_'.$settr.'_'.$source.'_'.$jobid.'_resume_data.cache';
            $cache = check_cache($cache_name, $where['comid'].'/');
            if ($cache === false) {
                $resumelist = D('Resume')->where(array('uid'=>array('in',$uidArr),'def'=>array('eq',1)))->order('field(uid,'.implode(",", $uidArr).')')->select();
                write_cache($cache_name, $where['comid'].'/', json_encode($resumelist));
            } else {
                $resumelist = json_decode($cache, true);
            }
        } else {
            $resumelist = array();
        }
        $sex_total = array();
        $education_total = array();
        $experience_total = array();
        $age_total = array();
        $table_data = array();
        $data_count = count($resumelist);
        $pagesize = 10;
        $pager = pager($data_count, $pagesize);
        $page = $pager->fshow();
        $this->assign("page", $page);
        $p = I('get.page', 1, 'intval');
        $i = 0;
        foreach ($resumelist as $key => $value) {
            if ($value['display_name']=="2") {
                $resumelist[$key]['fullname']="N".str_pad($value['id'], 7, "0", STR_PAD_LEFT);
            } elseif ($value['display_name']=="3") {
                if ($value['sex']==1) {
                    $resumelist[$key]['fullname']=cut_str($value['fullname'], 1, 0, "先生");
                } elseif ($value['sex'] == 2) {
                    $resumelist[$key]['fullname']=cut_str($value['fullname'], 1, 0, "女士");
                } else {
                    $resumelist[$key]['fullname']=cut_str($value['fullname'], 1, 0, "**");
                }
            } else {
                $resumelist[$key]['fullname']=$value['fullname'];
            }
            $resumelist[$key]['intention_jobs']=cut_str($value['intention_jobs'], 10, 0, '..');
            $resumelist[$key]['age']=date('Y')-$value['birthdate'];
            if ($i<$pagesize && $key>=($p-1)*$pagesize) {
                $i++;
                $table_data[] = $resumelist[$key];
            }
            if (!IS_AJAX) {
                $count_num['login']++;
                if ($value['sex']>0) {
                    if (isset($sex_total[$value['sex']])) {
                        $sex_total[$value['sex']]++;
                    } else {
                        $sex_total[$value['sex']] = 1;
                    }
                }
                if ($value['experience']>0) {
                    $experience_total['total'] += 1;
                    if (isset($experience_total['data'][$value['experience']])) {
                        $experience_total['data'][$value['experience']]['num']++;
                    } else {
                        $experience_total['data'][$value['experience']]['label'] = $value['experience_cn'];
                        $experience_total['data'][$value['experience']]['num'] = 1;
                    }
                }
                if ($value['education']>0) {
                    $education_total['total'] += 1;
                    if (isset($education_total['data'][$value['education']])) {
                        $education_total['data'][$value['education']]['num']++;
                    } else {
                        $education_total['data'][$value['education']]['label'] = $value['education_cn'];
                        $education_total['data'][$value['education']]['num'] = 1;
                    }
                }
                if ($value['birthdate']>0) {
                    $age_total['total'] += 1;
                    $minus_age = date('Y') - $value['birthdate'];
                    if ($minus_age<26) {
                        $age_total['data'][0]['label'] = '18-25岁';
                        $age_total['data'][0]['num'] += 1;
                    } else if ($minus_age<31) {
                        $age_total['data'][1]['label'] = '26-30岁';
                        $age_total['data'][1]['num'] += 1;
                    } else if ($minus_age<41) {
                        $age_total['data'][2]['label'] = '31-40岁';
                        $age_total['data'][2]['num'] += 1;
                    } else if ($minus_age<51) {
                        $age_total['data'][3]['label'] = '41-50岁';
                        $age_total['data'][3]['num'] += 1;
                    } else {
                        $age_total['data'][4]['label'] = '50岁';
                        $age_total['data'][4]['num'] += 1;
                    }
                }
            }
        }
        $this->assign("table_data", $table_data);
        if (IS_AJAX) {
            $this->assign('mark', $mark);
            $html = $this->fetch('Company/ajax_tpl/statistics_list');
            $this->ajaxReturn(1, '返回成功！', $html);
        }
        $sex_xml = '<chart showborder="0" enablesmartlabels="0" showlabels="0" showpercentvalues="1" showlegend="1" defaultcenterlabel="性别 （'.($sex_total[1]+$sex_total[2]).'人）" centerlabel="$label: $value人" centerlabelbold="0" showtooltip="0" decimals="0" usedataplotcolorforlabels="1" theme="fint">';
        if (!empty($sex_total)) {
            $sex_xml.= '<set label="男" value="'.$sex_total[1].'" />';
            $sex_xml.= '<set label="女" value="'.$sex_total[2].'" />';
        }
        $sex_xml.= '</chart>';
        $this->assign('sex_xml', $sex_xml);
        $experience_xml = '<chart showborder="0" enablesmartlabels="0" showlabels="0" showpercentvalues="1" showlegend="1" defaultcenterlabel="工作经验 （'.$experience_total['total'].'人）" centerlabel="$label: $value人" centerlabelbold="0" showtooltip="0" decimals="0" usedataplotcolorforlabels="1" theme="fint">';
        if (!empty($experience_total['data'])) {
            foreach ($experience_total['data'] as $key => $value) {
                $experience_xml.= '<set label="'.$value['label'].'" value="'.$value['num'].'" />';
            }
        }
        $experience_xml.= '</chart>';
        $this->assign('experience_xml', $experience_xml);

        $education_xml = '<chart showborder="0" enablesmartlabels="0" showlabels="0" showpercentvalues="1" showlegend="1" defaultcenterlabel="学历 （'.$education_total['total'].'人）" centerlabel="$label: $value人" centerlabelbold="0" showtooltip="0" decimals="0" usedataplotcolorforlabels="1" theme="fint">';
        if (!empty($education_total['data'])) {
            foreach ($education_total['data'] as $key => $value) {
                $education_xml.= '<set label="'.$value['label'].'" value="'.$value['num'].'" />';
            }
        }
        $education_xml.= '</chart>';
        $this->assign('education_xml', $education_xml);

        $age_xml = '<chart showborder="0" enablesmartlabels="0" showlabels="0" showpercentvalues="1" showlegend="1" defaultcenterlabel="年龄 （'.$age_total['total'].'人）" centerlabel="$label: $value人" centerlabelbold="0" showtooltip="0" decimals="0" usedataplotcolorforlabels="1" theme="fint">';
        if (!empty($age_total['data'])) {
            foreach ($age_total['data'] as $key => $value) {
                $age_xml.= '<set label="'.$value['label'].'" value="'.$value['num'].'" />';
            }
        }
        $age_xml.= '</chart>';
        $this->assign('age_xml', $age_xml);
        $this->assign('source', $source);
        $this->assign('settr', $settr);
        $this->assign('jobid', $jobid);
        $setmeal=D('MembersSetmeal')->get_user_setmeal(C('visitor.uid'));
        $upper_limit = 0;
        $jids = M('Jobs')->where(array('uid'=>C('visitor.uid')))->getField('id', true);
        $jids_tmp = M('JobsTmp')->where(array('uid'=>C('visitor.uid'),'display'=>1))->getField('id', true);
        if (count($jids)+count($jids_tmp)>=$setmeal['jobs_meanwhile']) {
            $upper_limit = 1;
        }
        $this->assign('upper_limit', $upper_limit);
        $this->assign('source_arr', array('1'=>'PC端','2'=>'触屏端','3'=>'移动端'));
        $this->assign('count_num', $count_num);
    }
    /**
     * 招聘效果统计 - 访客统计
     */
    public function statistics_visitor()
    {
        $where['comid'] = $this->company_profile['id'];
        $where['apply'] = 0;
        $this->_statistics($where, 'visitor');
        $this->assign('statistics_nav', 'statistics_visitor');
        $this->assign('company_nav', 'jobs_list');
        $this->_config_seo(array('title'=>'招聘效果统计 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    /**
     * 招聘效果统计 - 应聘统计
     */
    public function statistics_apply()
    {
        $where['comid'] = $this->company_profile['id'];
        $where['apply'] = 1;
        $this->_statistics($where, 'apply');
        $jobs_namearr = array();
        $jobs=D('Jobs')->get_jobs_by_uid(C('visitor.uid'));
        foreach ($jobs as $key => $value) {
            $jobs_namearr[$value['id']] = $value['jobs_name'];
        }
        $this->assign('jobs', $jobs);
        $this->assign('jobs_namearr', $jobs_namearr);
        $this->assign('jobid', I('get.jobid', 0, 'intval'));
        $this->assign('statistics_nav', 'statistics_visitor');
        $this->assign('company_nav', 'jobs_list');
        $this->_config_seo(array('title'=>'招聘效果统计 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    /**
     * 招聘效果统计 - 职位浏览统计
     */
    public function statistics_viewjobs()
    {
        $where['comid'] = $this->company_profile['id'];
        $where['apply'] = 0;
        $where['jobid'] = array('gt',0);
        $this->_statistics($where, 'viewjobs');
        $jobs_namearr = array();
        $jobs=D('Jobs')->get_jobs_by_uid(C('visitor.uid'));
        foreach ($jobs as $key => $value) {
            $jobs_namearr[$value['id']] = $value['jobs_name'];
        }
        $this->assign('jobs', $jobs);
        $this->assign('jobs_namearr', $jobs_namearr);
        $this->assign('jobid', I('get.jobid', 0, 'intval'));
        $this->assign('statistics_nav', 'statistics_visitor');
        $this->assign('company_nav', 'jobs_list');
        $this->_config_seo(array('title'=>'招聘效果统计 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    /**
     * 消息提醒
     */
    public function pms_sys()
    {
        $settr = I('get.settr', 0, 'intval');
        $new = I('get.new', 0, 'intval');
        $map = array();
        if ($settr>0) {
            $tmp_addtime = strtotime('-'.$settr.' day');
            $map['dateline'] = array('egt',$tmp_addtime);
        }
        if ($new>0) {
            $map['new'] = array('eq',$new);
        }
        $msg = D('Pms')->update_pms_read(C('visitor'), 10, $map);
        $this->assign('msg', $msg);
        $this->assign('company_nav', 'com_info');
        $this->_config_seo(array('title'=>'消息提醒 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    /**
     * [msg_check 系统消息查看]
     */
    public function msg_check()
    {
        $ids = I('request.id', '', 'trim');
        $reg = D('Pms')->msg_check($ids, C('visitor'));
        if ($reg['state']) {
            $this->assign('msg', $reg['data']);
            $html = $this->fetch('Company/ajax_tpl/ajax_show_message');
            $this->ajaxReturn(1, '系统信息获取成功！', $html);
        } else {
            $this->ajaxReturn(0, $reg['error']);
        }
    }
    /**
     * [msg_del 系统消息删除]
     */
    public function msg_del()
    {
        if (!IS_POST) {
            $tip='被删除后将无法恢复，您确定要删除选中的系统消息吗？';
            $this->ajax_warning($tip);
        } else {
            $ids = I('request.id', 0, 'intval');
            $reg = D('Pms')->msg_del($ids, C('visitor'));
            if ($reg['state']) {
                IS_AJAX && $this->ajaxReturn(1, '删除成功！');
                $this->success('删除成功！');
            } else {
                IS_AJAX && $this->ajaxReturn(0, '删除失败！');
                $this->error('删除失败！');
            }
        }
    }
    /**
     * 求职者咨询
     */
    public function pms_consult()
    {
        $msg_list = D('Msg')->msg_list(C('visitor'));
        $this->assign('msg_list', $msg_list);
        $this->assign('company_nav', 'com_info');
        $this->_config_seo(array('title'=>'求职者咨询 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    /**
     * [msg_feedback_send 发送咨询反馈]
     */
    public function msg_feedback_send()
    {
        if (IS_AJAX) {
            $data['pid'] = I('post.pid', 0, 'intval');
            $data['touid'] = I('post.touid', 0, 'intval');
            $data['message'] = I('post.message', '', 'trim');
            $reg = D('Msg')->msg_send($data, C('visitor'));
            if ($reg['state']) {
                $this->ajaxReturn(1, '消息发送成功！', $reg['data']);
            }
            $this->ajaxReturn(0, $reg['error']);
        }
    }
    /**
     * [msg_feedback_del 删除咨询反馈]
     */
    public function msg_feedback_del()
    {
        if (!IS_POST) {
            $tip='删除后将无法恢复，您确定要删除选择的咨询消息吗？';
            $this->ajax_warning($tip);
        } else {
            $ids = I('post.id', 0, 'intval');
            $reg = D('Msg')->msg_del($ids, C('visitor'));
            $this->ajaxReturn($reg['state'], $reg['tip']);
        }
    }
    /**
     * 发送简历到邮箱
     */
    public function send_to_email()
    {
        $resume_id = I('request.resume_id');
        !$resume_id && $this->ajaxReturn(0, "你没有选择项目！");
        if (IS_POST) {
            $email = I('request.email');
            !$email && $this->ajaxReturn(0, '参数错误！');
            $rst = D('Resume')->send_to_email($resume_id, $email);
            //写入会员日志
            write_members_log(C('visitor'), 1028, $resume_id, $email);
            $this->ajaxReturn(1, '发送完毕！');
        }
        $html = $this->fetch('Company/ajax_tpl/ajax_send_to_mail');
        $this->ajaxReturn(1, '返回成功！', $html);
    }
    /**
     * 已下载简历标记
     */
    public function company_label_resume_down()
    {
        $yid = I('get.y_id', 0, 'intval');
        $state = I('get.state', 0, 'intval');
        !$yid && $this->ajaxReturn(0, '你没有选择简历！');
        $rst = D('Resume')->company_label_resume($yid, 'CompanyDownResume', C('visitor.uid'), $state);
        $this->ajaxReturn($rst['state'], $rst['msg']);
    }
    /**
     * 收到的简历标记
     */
    public function company_label_resume_apply()
    {
        $yid = I('get.y_id', 0, 'intval');
        $state = I('get.state', 0, 'intval');
        !$yid && $this->ajaxReturn(0, '你没有选择简历！');
        $rst = D('Resume')->company_label_resume($yid, 'PersonalJobsApply', C('visitor.uid'), $state);
        $this->ajaxReturn($rst['state'], $rst['msg'], $rst['task']?$rst['task']['data']:0);
    }
    /**
     * 手机招聘
     */
    public function mobile_recruit()
    {
        $day = intval(strtotime(date("Y-m-d")))-86400;
        $where['company_id'] = array('eq',$this->company_profile['id']);
        $where['addtime'] = array('eq',$day);
        //统计昨日访问数
        $where['click_type'] = array('eq',1);
        $click = D('CompanyPraise')->where($where)->count();
        //统计昨日点赞数
        $where['click_type'] = array('eq',2);
        $praise = D('CompanyPraise')->where($where)->count();
        //扫描url
        $w_url = build_mobile_url(array('c'=>'Wzp','a'=>'com','params'=>'id='.$this->company_profile['id']));
        $this->assign('click', $click);
        $this->assign('praise', $praise);
        $this->assign('w_url', $w_url);
        $this->assign('company_nav', 'jobs_list');
        $setmeal=D('MembersSetmeal')->get_user_setmeal(C('visitor.uid'));
        $upper_limit = 0;
        $jids = M('Jobs')->where(array('uid'=>C('visitor.uid')))->getField('id', true);
        $jids_tmp = M('JobsTmp')->where(array('uid'=>C('visitor.uid'),'display'=>1))->getField('id', true);
        if (count($jids)+count($jids_tmp)>=$setmeal['jobs_meanwhile']) {
            $upper_limit = 1;
        }
        $this->assign('upper_limit', $upper_limit);
        $this->_config_seo(array('title'=>'手机招聘 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    /**
     * 手机招聘统计
     */
    public function mobile_recruit_statistics()
    {
        $model = D('CompanyPraise');
        $where['company_id'] = $this->company_profile['id'];
        $where['uid'] = C('visitor.uid');
        $cache_name = 'u'.C('visitor.uid').'_wzp_tabledata.cache';
        $cache = check_cache($cache_name, 'wzp/', 1);
        if ($cache === false) {
            $list = array(array());
            //昨日时间
            $yesterday = intval(strtotime(date("Y-m-d")))-86400;
            //本周时间
            $week = mktime(0, 0, 0, date("m"), date("d")-date("w")+1, date("Y"));
            $today_end = strtotime(date("Y-m-d"));
            //上周时间
            $last_week_day_begin = mktime(0, 0, 0, date("m"), date("d")-date("w")+1-7, date("Y"));
            $last_week_day_end = mktime(0, 0, 0, date("m"), date("d")-date("w"), date("Y"));
            //本月时间
            $month_day = strtotime(date("Y-m")."-1");
            //上月时间
            $month_day_begin = strtotime(date("Y-").(date('m')-1)."-1");
            $month_day_end = strtotime(date("Y-m")."-1")-86400;
            //循环数据
            $data = $model->where($where)->select();
            foreach ($data as $key => $value) {
                if ($value['addtime']==$yesterday) {
                    $list['yesterday'][$value['click_type']] += 1;
                }
                if ($value['addtime']>=$week && $value['addtime']<$today_end) {
                    $list['week'][$value['click_type']] += 1;
                }
                if ($value['addtime']>=$last_week_day_begin && $value['addtime']<=$last_week_day_end) {
                    $list['last_week'][$value['click_type']] += 1;
                }
                if ($value['addtime']>=$month_day  && $value['addtime']<$today_end) {
                    $list['month'][$value['click_type']] += 1;
                }
                if ($value['addtime']>=$month_day_begin && $value['addtime']<=$month_day_end) {
                    $list['last_month'][$value['click_type']] += 1;
                }
                if ($value['addtime']<$today_end) {
                    $list['total'][$value['click_type']] += 1;
                }
            }
            //独立ip数据单独统计
            $count_where['company_id'] = $where['company_id'];
            $count_where['addtime'] = array('eq',$yesterday);
            $list['yesterday'][4] = $model->where($count_where)->count('distinct ip');
            unset($count_where['addtime']);

            $count_where['addtime'] = array('eq',$week);
            $list['week'][4] = $model->where($count_where)->count('distinct ip');
            unset($count_where['addtime']);

            $count_where['addtime'] = array(array('egt',$last_week_day_begin),array('elt',$last_week_day_end),'and');
            $list['last_week'][4] = $model->where($count_where)->count('distinct ip');
            unset($count_where['addtime']);

            $count_where['addtime'] = array('eq',$month_day);
            $list['month'][4] = $model->where($count_where)->count('distinct ip');
            unset($count_where['addtime']);

            $count_where['addtime'] = array(array('egt',$month_day_begin),array('elt',$month_day_end),'and');
            $list['last_month'][4] = $model->where($count_where)->count('distinct ip');
            unset($count_where['addtime']);

            $list['total'][4] = $model->where($count_where)->count('distinct ip');

            write_cache($cache_name, 'wzp/', json_encode($list));
        } else {
            $list = json_decode($cache, true);
        }

        /**
        * 图表统计start
        **/
        $filter = I('get.settr', 7, 'intval');
        $cache_name = 'u'.C('visitor.uid').'_wzp_line_'.$filter.'.cache';
        $cache = check_cache($cache_name, 'wzp/', 1);
        if ($cache) {
            $line_data = json_decode($cache, 1);
        } else {
            $where1['company_id'] = $this->company_profile['id'];
            $where1['addtime'] = array('gt',strtotime(date('Y-m-d', time()-$filter*86400)));
            $line_data = $model->where($where1)->order('addtime asc')->select();
            write_cache($cache_name, 'wzp/', json_encode($line_data));
        }
        for ($i=$filter; $i >0; $i--) {
            $t = strtotime(date('Y-m-d', time()-$i*86400));
            $labelArr[] = $t;
            $line[1][$t] = 0;
            $line[2][$t] = 0;
            $line[3][$t] = 0;
        }
        foreach ($line_data as $key => $value) {
            $line[$value['click_type']][$value['addtime']] += 1;
        }
        $item = 0;
        $line_xml = '<chart palettecolors="#0075c2,#1aaf5d" bgcolor="#ffffff" showborder="0" showshadow="0" showcanvasborder="0" useplotgradientcolor="0" legendborderalpha="0" legendshadow="0" showaxislines="0" showalternatehgridcolor="0" divlinethickness="1" divlinedashed="1" divlinedashlen="1" showvalues="0">';
        $line_xml.= '<categories>';
        foreach ($labelArr as $key => $value) {
            $line_xml.= '<category label="'.date('m-d', $value).'" />';
        }
        $line_xml.= '</categories>';
        $line_xml.= '<dataset seriesname="点击数">';
        foreach ($line[1] as $key => $value) {
            $line_xml.= '<set value="'.$value.'" />';
        }
        $line_xml.= '</dataset>';

        $line_xml.= '<dataset seriesname="点赞数">';
        foreach ($line[2] as $key => $value) {
            $line_xml.= '<set value="'.$value.'" />';
        }
        $line_xml.= '</dataset>';

        $line_xml.= '<dataset seriesname="分享数">';
        foreach ($line[3] as $key => $value) {
            $line_xml.= '<set value="'.$value.'" />';
        }
        $line_xml.= '</dataset>';
        $line_xml.= '</chart>';
        $this->assign('line_xml', $line_xml);
        /**
        * 图表统计end
        **/
        $this->assign('data', $list);
        $this->assign('settr', $filter);
        $this->assign('company_nav', 'jobs_list');
        $setmeal=D('MembersSetmeal')->get_user_setmeal(C('visitor.uid'));
        $upper_limit = 0;
        $jids = M('Jobs')->where(array('uid'=>C('visitor.uid')))->getField('id', true);
        $jids_tmp = M('JobsTmp')->where(array('uid'=>C('visitor.uid'),'display'=>1))->getField('id', true);
        if (count($jids)+count($jids_tmp)>=$setmeal['jobs_meanwhile']) {
            $upper_limit = 1;
        }
        $this->assign('upper_limit', $upper_limit);
        $this->_config_seo(array('title'=>'手机招聘 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    /**
     * 模板切换
     */
    public function company_tpl()
    {
        if (IS_AJAX) {
            $tpl = I('get.tpl', 'default', 'trim');
            $r = D('CompanyProfile')->where(array('uid'=>C('visitor.uid')))->setField('tpl', $tpl);
            if ($r) {
                //写入会员日志
                write_members_log(C('visitor'), 1031, $tpl);
                $this->ajaxReturn(1, '更换模板成功！');
            } else {
                $this->ajaxReturn(0, '更换模板失败！');
            }
            exit;
        }
        //已购买模板
        $tplid_list = D('CompanyTpl')->where(array('uid'=>C('visitor.uid')))->field('tplid')->select();
        $tplid_arr = array();
        foreach ($tplid_list as $key => $value) {
            $tplid_arr[] = $value['tplid'];
        }
        if ($tplid_arr) {
            $tpl_list = D('Tpl')->where(array('tpl_id'=>array('in',$tplid_arr)))->select();
        } else {
            $tpl_list = array();
        }
        foreach ($tpl_list as $key => $value) {
            $tpl_list[$key]['thumb_dir'] = __COMPANY__.'/'.$value['tpl_dir'];
        }
        //未购买模板
        if (!empty($tplid_arr)) {
            $def_tplid_list = D('Tpl')->where(array('tpl_type'=>1,'tpl_display'=>1,'tpl_id'=>array('not in',$tplid_arr)))->select();
        } else {
            $def_tplid_list = D('Tpl')->where(array('tpl_type'=>1,'tpl_display'=>1))->select();
        }
        foreach ($def_tplid_list as $key => $value) {
            $def_tplid_list[$key]['thumb_dir'] = __COMPANY__.'/'.$value['tpl_dir'];
        }
        $current_tpl = $this->company_profile['tpl']==''?'default':$this->company_profile['tpl'];
        $this->assign('tpl_list', $tpl_list);
        $this->assign('def_tplid_list', $def_tplid_list);
        $this->assign('companyinfo', $this->company_profile);
        $this->assign('current_tpl', $current_tpl);
        $this->assign('company_nav', 'service');
        $this->assign('left_nav', 'company_tpl');
        $this->_config_seo(array('title'=>'模板切换 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    /**
     * 投诉客服
     */
    public function complaint_consultant()
    {
        $consultant = M('Consultant')->where(array('id'=>C('visitor.consultant')))->find();
        if (IS_POST) {
            $data['uid'] = C('visitor.uid');
            $data['username'] = C('visitor.username');
            $data['consultant_id'] = $consultant['id'];
            $data['consultant_name'] = $consultant['name'];
            $data['notes'] = I('post.notes', '', 'trim');
            if (!$data['notes']) {
                $this->ajaxReturn(0, '请填写投诉说明');
            }
            if ($this->apply['Subsite']) {
                $data['subsite_id'] = $consultant['subsite_id'];
            }
            $data['addtime'] = time();
            $data['audit'] = 1;
            $r = M('ConsultantComplaint')->add($data);
            if ($r) {
                $this->ajaxReturn(1, '投诉成功！管理员将尽快核实！');
            } else {
                $this->ajaxReturn(0, '投诉失败');
            }
        }
        $this->assign('consultant', $consultant);
        $html = $this->fetch('Company/ajax_tpl/ajax_complaint_consultant');
        $this->ajaxReturn(1, '获取数据成功', $html);
    }
    /**
     * 套餐快到期提醒
     */
    public function confirm_setmeal()
    {
        $my_setmeal = D('MembersSetmeal')->get_user_setmeal(C('visitor.uid'));
        $setmeal_end_days = '';
        if ($my_setmeal['endtime']>time()) {
            $setmeal_end_days = '距离到期时间还有 <span class="font_yellow">'.ceil($my_setmeal['endtime'], time()).' 天';
        } else {
            $setmeal_end_days = '服务已经到期';
        }
        $tip = '您当前【'.$my_setmeal['setmeal_name'].'】有效期 '.date('Y-m-d', $my_setmeal['starttime']).'至'.date('Y-m-d', $my_setmeal['endtime']).' ,'.$setmeal_end_days.'，为了不影响您的后续使用，建议您立即续费。';
        $this->ajax_warning($tip);
    }
    /**
     * 清除logo
     */
    public function clear_logo()
    {
        $logo = D('CompanyProfile')->where(array('uid'=>C('visitor.uid')))->getField('logo');
        @unlink(C('qscms_attach_path')."company_logo/".$logo);
        if (C('qscms_qiniu_open')==1) {
            $qiniu = new \Common\ORG\qiniu;
            $qiniu->delete($logo);
        }
        D('CompanyProfile')->where(array('uid'=>C('visitor.uid')))->setField('logo', '');
        $this->ajaxReturn(1, '成功清除logo');
    }
    /**
     * [我的打赏]
     */
    public function allowance()
    {
        if (!isset($this->apply['Allowance'])) {
            $this->_empty();
        }
        $jobs_id = I('get.jobs_id', 0, 'intval');
        if ($jobs_id>0) {
            $map_info['jobsid'] = array('eq',$jobs_id);
        }
        $type = I('get.type', '', 'trim');
        $type && $map[$type] = array('eq',1);
        $status = I('get.status', '', 'trim');
        $status!='' && $map['status'] = array('eq',$status);
        $member_turn = I('get.member_turn', 0, 'intval');
        $member_turn>0 && $map['member_turn'] = array('eq',$member_turn);
        $map_info['uid'] = array('eq',C('visitor.uid'));
        $infoid_arr = array();
        $info_list = D('Allowance/AllowanceInfo')->where($map_info)->index('id')->select();
        foreach ($info_list as $key => $value) {
            $infoid_arr[] = $key;
        }
        if (!empty($infoid_arr)) {
            $map['info_id'] = array('in',$infoid_arr);
        } else {
            $map['info_id'] = array('eq',0);
        }
        $data_count = D('Allowance/AllowanceRecord')->where($map)->count();
        $pagesize = 10;
        $pager = pager($data_count, $pagesize);
        $page = $pager->fshow();
        $list = D('Allowance/AllowanceRecord')->where($map)->order('id desc')->limit($pager->firstRow . ',' . $pager->listRows)->select();
        foreach ($list as $key => $value) {
            $list[$key]['info'] = $info_list[$value['info_id']];
            $list[$key]['info']['type_cn'] = D('Allowance/AllowanceInfo')->get_alias_cn($info_list[$value['info_id']]['type_alias']);
            $list[$key]['status_cn'] = D('Allowance/AllowanceRecord')->get_status_cn($value['status']);
            $resumeid_arr[] = $value['resumeid'];
            $recordid_arr[] = $value['id'];
        }
        if (!empty($resumeid_arr)) {
            $resumelist = D('Resume')->where(array('id'=>array('in',$resumeid_arr)))->index('id')->select();
            foreach ($resumelist as $key => $value) {
                $resumelist[$key]['age'] = date('Y') - $value['birthdate'];
            }
        } else {
            $resumelist = array();
        }
        if (!empty($recordid_arr)) {
            $log = D('Allowance/AllowanceRecordLog')->where(array('record_id'=>array('in',$recordid_arr)))->select();
            foreach ($list as $key => $value) {
                foreach ($log as $k => $v) {
                    if ($value['id']==$v['record_id']) {
                        $list[$key]['log'][$v['step']] = $v;
                    }
                }
            }
        }
        $record['list'] = $list;
        $this->assign("page", $page);
        $this->assign('jobs_id', $jobs_id);
        $this->assign('type', $type);
        $this->assign('status', $status);
        $this->assign('member_turn', $member_turn);
        $this->assign('resumelist', $resumelist);
        $this->assign('record', $record);
        $this->assign('company_nav', 'jobs_list');
        $this->assign('jobslist', D('Jobs')->where(array('uid'=>C('visitor.uid')))->getField('id,jobs_name'));
        $this->assign('status_list', D('Allowance/AllowanceRecord')->status_cn);
        $this->assign('type_list', D('Allowance/AllowanceRecord')->type_cn);
        $this->_config_seo(array('title'=>'我的打赏 - 企业会员中心 - '.C('qscms_site_name')));
        $this->display();
    }
    /*
    报名网络招聘会
    */
    public function ajax_enroll()
    {
        $subject_id = I('post.subject_id', 0, 'intval');

        !$subject_id && $this->ajaxReturn(0, '请选择招聘会！');
        $is_enroll = M('SubjectCompany')->where(array('subject_id'=>$subject_id,'company_uid'=>C('visitor.uid')))->find();
        $is_enroll && $this->ajaxReturn(0, '您已经报名此招聘会，请勿重复报名！');
        $subject = M('Subject')->where(array('id'=>$subject_id))->field('setmeal_id,holddate_end')->find();
        $setmeal = explode(',', $subject['setmeal_id']);
        $mem_set = M('MembersSetmeal')->where(array('uid'=>C('visitor.uid')))->field('setmeal_id')->find();
        if (!in_array($mem_set['setmeal_id'], $setmeal)) {
            $this->ajaxReturn(0, '您的套餐不符合报名条件，请去升级套餐！');
        }
        if (time() > $subject['holddate_end']) {
            $this->ajaxReturn(0, '该招聘会已经预定结束，非常抱歉');
        }
        $com = M('CompanyProfile')->where(array('uid'=>C('visitor.uid')))->find();
        !$com && $this->ajaxReturn(0, '您还没完善企业资料，请先去完善企业资料！');
        $jobs = M('zjydata')->where(array('company_id'=>C('visitor.uid')))->select();

        !$jobs && $this->ajaxReturn(0, '请先丰富网络招聘会下的职位');
        $post_data['company_uid'] = C('visitor.uid');
        $post_data['subject_id'] = $subject_id;
        $post_data['robot'] =0;
        $post_data['s_audit'] = 2;
        $post_data['addtime'] = time();
        $insert_id = M('SubjectCompany')->add($post_data);
        if ($insert_id) {
            $this->ajaxReturn(1, '报名成功。我们将在1个工作日内进行审核，请留意消息通知。');
        } else {
            $this->ajaxReturn(0, '报名失败！');
        }
    }
    
    
    public function jobs_zjy_list()
    {
        ;
        $map_res['subject_id'] =26;
        $map_res['audit'] = 1;
        $res = M('SubjectCompany')->where($map_res)->select();
        
        $array=array();
        foreach ($res as $v) {
            $array[]=$v['company_uid'];
        }
        $is = in_array(C('visitor.uid'), $array);
        
        if ($is) {
            $is = 1;
        } else {
            $is=0;
        }
        
        
        $this->assign('is', $is);
        
        $map['company_uid'] = C('visitor.uid');
        $shtyxydm = M('zjycode')->where($map)->find();

        if ($shtyxydm==0 ||$shtyxydm=null) {
            $error_url = U('Company/jobs_zjy_code');
            $this->error('请先绑定信用代码！', $error_url);
        }
        $res = M('zjydata')->where(array('company_id'=>C('visitor.uid')))->getField('id,company_id,zpzw');
        
        
    //           $ajax_data['shtyxydm']= $shtyxydm;
    //          $ajax_url = 'http://dx.jxjob.net/Precision/company!selCompanyJob';
    //          $output = https_request_array($http_url,$http_data);

                $this->assign('res', $res);
        $this->display();
    }

    public function jobs_zjy_add()
    {
        $map['company_uid'] = C('visitor.uid');
        $shtyxydm = M('zjycode')->where($map)->find();

        if ($shtyxydm==0 ||$shtyxydm=null) {
            $error_url = U('Company/jobs_zjy_code');
            $this->error('请先绑定信用代码！', $error_url);
        }
        if (IS_POST) {
            $data = $_POST;
            $res = M('zjydata')->add($data);
            if ($res) {
                $url = U('Company/jobs_zjy_list');
                $this->ajaxReturn(1, '发布成功', $url);
            } else {
                $url = U('Company/jobs_zjy_list');
                $this->ajaxReturn(0, '发布失败', $url);
            }
        } else {
            $shtyxydm = M('zjycode')->where(array('company_uid'=>C('visitor.uid')))->getfield('shtyxydm');
        
            $this->assign('company_id', C('visitor.uid'));
          
            $this->assign('shtyxydm', $shtyxydm);
          
          
            $zpdw = M('CompanyProfile')->where(array('uid'=>C('visitor.uid')))->getfield('companyname');
            $this->assign('zpdw', $zpdw);
          
            $this->display();
        }
    }

    public function jobs_zjy_edit()
    {
        if (IS_POST) {
            $data = $_POST;
            $res = M('zjydata')->where(array('id'=>$data['id']))->save($data);
            if ($res) {
                $url = U('Company/jobs_zjy_list');
                $this->ajaxReturn(1, '修改成功', $url);
            } else {
                $url = U('Company/jobs_zjy_list');
                $this->ajaxReturn(0, '修改失败', $url);
            }
               // http://dx.jxjob.net/Precision/job!updateJobPhp
   //          if($res){
   //              $url = U('Company/jobs_zjy_list');
   //              $this->ajaxReturn('保存成功',$url);
   //          }else{

   //          }
        } else {
            $map['id'] = $_GET['id'];
            /*
          *工作区域
          */
            $res1 = M('zjydata')->where($map)->getField('gzqydm,gzqy2dm,gzqy3dm');
    
            $array1_gzqy = array();
            foreach ($res1 as $value) {
                $array1_gzqy=$value;
            }
          
            $array1_gzqy = array_filter(array_values($array1_gzqy));

            $this->assign('array1_gzqy', $array1_gzqy);
         /*
          *专业要求
          */
            $res2 = M('zjydata')->where($map)->getField('zygjz1dm,zygjz2dm,zygjz3dm,zygjz4dm,zygjz5dm');
    
            $array1_zygjz = array();
            foreach ($res2 as $value) {
                $array1_zygjz=$value;
            }
            $array1_zygjz = array_filter(array_values($array1_zygjz));

            $this->assign('array1_zygjz', $array1_zygjz);
          
          /*
          *招聘职位类别
          */
            $res3 = M('zjydata')->where($map)->getField('zpzwlb1dm,zpzwlb2dm,zpzwlb3dm');
    
            $array1_zpzwlb = array();
            foreach ($res3 as $value) {
                $array1_zpzwlb=$value;
            }
            $array1_zpzwlb = array_filter(array_values($array1_zpzwlb));

            $this->assign('array1_zpzwlb', $array1_zpzwlb);
            
            
            
            
           // $shtyxydm = M('zjycode')->where(array('uid'=>C('visitor.uid')))->getfield('shtyxydm');
    //       $this->assign('company_id',C('visitor.uid'));
          
    //       $this->assign('shtyxydm',$shtyxydm);
          
    //       $zpdw = M('CompanyProfile')->where(array('uid'=>C('visitor.uid')))->getfield('companyname');
            $id=$_GET['id'];
            $res = M('zjydata')->where(array('id'=>$id))->find();

            $this->assign('res', $res);
            $this->display();
        }
    }
    
    public function jobs_zjy_del($id)
    {
        if ($_GET['id']) {
            $map['id'] = $_GET['id'];
            $User = M("zjydata"); // 实例化User对象
            $res = $User->where($map)->delete();
            if ($res) {
                $this->success('删除成功!');
            } else {
                $this->error('删除失败！');
            }
        }
    }

    public function jobs_zjy_code()
    {
        if (IS_POST) {
            $data = $_POST;
            $code = $data['shtyxydm'];
            $ajax_url='http://dx.jxjob.net/Precision/company!addJudgment';
//             $ajax_data['shtyxydm'] = $code;
//             $output = https_request_array($ajax_url,$ajax_data);
//             $company_mes = M('CompanyProfile')->where(array('uid'=>$data['company_uid']))->find();
//             if($output['code']==154 || $output['code']==155){
//                 $http_url = 'http://dx.jxjob.net/Precision/company!getaddtest';
//                 $http_data['shtyxydm'] = $code;
//                 $http_data['dwyhm']=$company_mes['companyname'];
//                 $http_data['password'] = '123456';
//                 $http_data['dwqc'] = $company_mes['companyname'];
//                 $http_data['dwlxr'] = $company_mes['contact'];
//                 $http_data['dwlxsj'] = $company_mes['telephone'];
//                 $http_data['dwlxjh'] = $company_mes['landline_tel'];
//                 $http_data['sfyx'] = 1;
//                 $http_data['dwjj'] = $company_mes['contents'];
//                 $http_data['u'] = 'admin';
//                 $http_data['e_mail'] = $company_mes['email'];
//                 if($company_mes['nature']>0){
//                     if($company_mes['nature']==46){
//                         $http_data['zpdwlbdm']=31;
//                         $http_data['zpdwlb'] = '国有企业';
//                     }elseif($company_mes['nature']==47 || $company_mes['nature']==54){
//                         $http_data['zpdwlbdm']=39;
//                         $http_data['zpdwlb'] = '中小企业';
//                     }elseif($company_mes['nature']==48 || $company_mes['nature']==49){
//                         $http_data['zpdwlbdm']=32;
//                         $http_data['zpdwlb'] = '三资企业';
//                     }elseif($company_mes['nature']==50 || $company_mes['nature']==51){
//                         $http_data['zpdwlbdm']=39;
//                         $http_data['zpdwlb'] = '中小企业';
//                     }elseif($company_mes['nature']== 52){
//                         $http_data['zpdwlbdm']=32;
//                         $http_data['zpdwlb'] = '三资企业';
//                     }elseif($company_mes['nature']== 53){
//                         $http_data['zpdwlbdm']=29;
//                         $http_data['zpdwlb'] = '其他事业单位';
//                     }
//                 }
//             $output = https_request_array($http_url,$http_data);

//             }

            $res = M('zjycode')->add($data);
            if ($res) {
                $url = U('Company/jobs_zjy_add');
                $this->ajaxReturn('绑定成功', $url);
            } else {
            }
        } else {
            $res = M('CompanyProfile')->where(array('uid'=>C('visitor.uid')))->find();
            $this->assign('res', $res);
            $code_res = M('zjycode')->where(array('company_uid'=>C('visitor.uid')))->count();
            $code = M('zjycode')->where(array('company_uid'=>C('visitor.uid')))->getfield('shtyxydm');
            $this->assign('code_res', $code_res);
            $this->assign('code', $code);
            $this->display();
        }
    }
    
    /*
    *最新简历推送 特定uid
    */
    public function resume_searcher()
    {
        if (C('visitor.uid')==400928) {
            $year = date('Y');
            $where['residence'] = array(array('like','%江西%'),array('like','%南昌%'),array('like','%赣州%'),array('like','%萍乡%'),array('like',''),'or') ;
            
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            
            
            $map['education']=69;

            $map['addtime'] = array('between',array('1495641600','1588262400'));
            $map['audit']=1;
            $User = M('resume'); // 实例化User对象
            $count      = $User->where($map)->count();// 查询满足要求的总记录数
            $Page       = new \Think\Page($count, 25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
            $show       = $Page->show();// 分页显示输出// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
            $list = $User->where($map)->field('id,fullname,birthdate,trade_cn,intention_jobs,experience_cn,sex_cn,education_cn,telephone')->limit($Page->firstRow.','.$Page->listRows)->order('addtime desc')->select();
        
            $this->assign('list', $list);// 赋值数据集
            $this->assign('show', $show);// 赋值分页输出
            $this->assign('year', $year);
            $this->display();
        } else {
            echo "<script>alert('你没有权限访问该模块！');window.location.href='http://www.jxujob.com/index.php?m=&c=Company';</script>";
        }
    }
}
