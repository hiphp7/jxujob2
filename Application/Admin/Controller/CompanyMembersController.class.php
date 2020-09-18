<?php
namespace Admin\Controller;

use Common\Controller\BackendController;

class CompanyMembersController extends BackendController
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_mod = D('Members');
    }
    /**
     * 企业会员列表
     */
    public function index()
    {
        $this->_name = 'Members';
        $db_pre = C('DB_PREFIX');
        $table_name = $db_pre.'members';
        $verification=I('get.verification', 0, 'intval');
        $settr=I('get.settr', 0, 'intval');
        $key_type = I('request.key_type', 0, 'intval');
        $key = I('request.key', '', 'trim');
        if ($key && $key_type>0) {
            switch ($key_type) {
                case 1:
                    $where[$table_name.'.username']=array('like',$key.'%');
                    break;
                case 2:
                    $where[$table_name.'.uid']=array('eq',$key);
                    break;
                case 3:
                    $where[$table_name.'.email']=array('like',$key.'%');
                    break;
                case 4:
                    $where[$table_name.'.mobile']=array('like',$key.'%');
                    break;
            }
        } else {
            if ($settr>0) {
                $tmpsettr=strtotime("-".$settr." day");
                $where['reg_time']=array('gt',$tmpsettr);
            }
            if ($verification>0) {
                switch ($verification) {
                    case 1:
                        $where['email_audit']=array('eq',1);
                        break;
                    case 2:
                        $where['email_audit']=array('eq',0);
                        break;
                    case 3:
                        $where['mobile_audit']=array('eq',1);
                        break;
                    case 4:
                        $where['mobile_audit']=array('eq',0);
                        break;
                }
            }
        }
        $where['utype'] = 1;
        $this->where = $where;
        $this->field = $table_name.".*,s.name as con_name,c.companyname,c.contents as c_contents,c.id as company_id";
        $this->order = $table_name.'.uid '.'desc';
        $joinsql[0] = 'left join '.$db_pre."company_profile as c on ".$table_name.".uid=c.uid";
        $joinsql[1] = 'left join '.$db_pre."consultant as s on ".$table_name.".consultant=s.id";
        $this->join = $joinsql;
        parent::index();
    }
    public function _before_add()
    {
        if (IS_POST) {
            $com_setarr['audit'] = 0;
            $com_setarr['companyname']=I('post.companyname', '', 'trim,badword');
            $com_setarr['contact']=I('post.contact', '', 'trim,badword');
            $com_setarr['telephone']=I('post.telephone', '', 'trim,badword');
            $company_mod = D('CompanyProfile');
            if (false === $datas = $company_mod->create($com_setarr)) {
                IS_AJAX && $this->ajaxReturn(0, $company_mod->getError());
                $this->error($company_mod->getError());
            }
        }
    }
    public function add()
    {
        $this->_name = 'Members';
        $this->assign('givesetmeal', D('Setmeal')->get_setmeal_cache());
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
        if (I('post.is_money', 0, 'intval') && I('post.log_amount')) {
            $amount=round(I('post.log_amount'), 2);
            $ismoney=2;
        } else {
            $amount='0.00';
            $ismoney=1;
        }
        $data['uid'] = $id;
        D('Members')->user_register($data);
        if (I('post.regpoints')=='y') {
            $regpoints_num = I('post.regpoints_num', 0, 'intval');
            D('MembersPoints')->report_deal($id, 1, $regpoints_num);
        }
        $reg_service=I('post.reg_service', 0, 'intval');
        if ($reg_service>0) {
            D('MembersSetmeal')->set_members_setmeal($id, $reg_service);
        }
        $company_mod=D('CompanyProfile');
        $company_mod->uid=$id;
        $Setmeal=D('MembersSetmeal')->where(array('uid'=>$id))->find();
        $company_mod->setmeal_name=$Setmeal['setmeal_name'];
        $company_mod->setmeal_id=$Setmeal['setmeal_id'];
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
        $com_setarrs['mobile']  =I('post.telephone', '', 'trim,badword');
        D('Members')->where(array('uid'=>$id))->save($com_setarrs);
    }
    /**
     * 编辑会员信息
     */
    public function edit()
    {
        $this->_name = 'Members';
        if (!IS_POST) {
            $uid = I('request.uid', 0, 'intval');
            $company_user=D('Members')->get_user_one(array('uid'=>$uid));
            $company_profile=D('CompanyProfile')->where(array('uid'=>array('eq',$company_user['uid'])))->find();
            $company_user['tpl']=$company_profile['tpl'];
            $this->assign('company_user', $company_user);
            $this->assign('userpoints', D('MembersPoints')->get_user_points($company_user['uid']));
            $this->assign('setmeal', D('MembersSetmeal')->get_user_setmeal($company_user['uid']));
            $this->assign('givesetmeal', D('Setmeal')->where(array('display'=>1))->order('show_order desc,id')->getField('id,setmeal_name'));
        }
        parent::edit();
    }
    public function _after_update($id, $data)
    {
        if ($this->_name == 'Members') {
            if (2 == I('request.status', 0, 'intval')) {
                $jobs = M('Jobs')->where(array('uid'=>$data['uid']))->select();
                if ($r = M('JobsTmp')->addAll($jobs)) {
                    M('Jobs')->where(array('uid'=>$data['uid']))->delete();
                }
            } elseif (1 == I('request.status', 0, 'intval')) {
                $jobs = M('JobsTmp')->where(array('uid'=>$data['uid']))->select();
                $setmeal = D('MembersSetmeal')->get_user_setmeal($data['uid']);
                foreach ($jobs as $key => $val) {
                    $jids[] = $val['id'];
                    $job[] = $val;
                    if (count($jids) >= $setmeal['jobs_meanwhile']) {
                        break;
                    }
                }
                if ($r = M('Jobs')->addAll($job)) {
                    M('JobsTmp')->where(array('id'=>array('in',$jids),'uid'=>$data['uid']))->delete();
                }
            }
        }
    }
    public function _before_update($data)
    {
        if (isset($_POST['password'])) {
            $model = D('Members');
            $member = $model->find(I('post.uid', 0, 'intval'));
            $data['password'] = $model->make_md5_pwd(I('post.password', '', 'trim'), $member['pwd_hash']);
        }
        return $data;
    }
    /**
     * 修改用户积分设置
     */
    public function user_points_edit()
    {
        if (IS_POST) {
            $points_type = I('post.points_type', 1, 'intval');
            $t=$points_type==1?"+":"-";
            $points = I('post.points', 1, 'intval');
            $uid = I('post.uid', 1, 'intval');
            D('MembersPoints')->report_deal($uid, $points_type, $points);
            $userinfo=D('Members')->get_user_one(array('uid'=>$uid));
            $user_points=D('MembersPoints')->get_user_points($userinfo['uid']);

        
        //会员积分变更记录。管理员后台修改会员的积分。3表示：管理员后台修改
            if (I('post.is_money', 0, 'intval') && I('post.log_amount')) {
                $amount=round(I('post.log_amount'), 2);
                $ismoney=2;
            } else {
                $amount='0.00';
                $ismoney=1;
            }
            $notes="操作人：".C('visitor.username').",说明：修改会员 {$userinfo['username']} ".C('qscms_points_byname')." ({$t}{$points})。收取".C('qscms_points_byname')."金额：{$amount} 元，备注：".I('post.points_notes', '', 'trim');
        // 套餐变更记录
            $members_charge_log['_t']='MembersChargeLog';
            $members_charge_log['log_uid']=$userinfo['uid'];
            $members_charge_log['log_username']=$userinfo['username'];
            $members_charge_log['log_type']=4;
            $members_charge_log['log_value']=$notes;
            $members_charge_log['log_amount']=$amount;
            $members_charge_log['log_ismoney']=$ismoney;
            $members_charge_log['log_mode']=2;
            $members_charge_log['log_utype']=$userinfo['utype'];
            setLog($members_charge_log);
            admin_write_log("修改会员uid为".$userinfo['uid'].C('qscms_points_byname'), C('visitor.username'), 3);
            $this->success('保存成功！');
        } else {
            $where['uid']=I('get.uid', 0, 'intval');
            $list = D('MembersHandsel')->get_handsel_list($where);
            $this->assign('list', $list);
            $this->edit();
        }
    }
    /**
     * 修改用户套餐设置
     */
    public function user_setmeal_edit()
    {
        if (IS_POST) {
            $setsqlarr = I('post.');
            $uid = I('post.uid', 1, 'intval');
            if (I('post.setendtime', '', 'trim')<>"") {
                $setendtime=strtotime(I('post.setendtime', '', 'trim'));
                if ($setendtime=='') {
                    $this->error('日期格式错误！');
                } else {
                    $setsqlarr['endtime']=$setendtime;
                }
            } else {
                $setsqlarr['endtime']=0;
            }
            if (I('post.days', '', 'trim')<>"") {
                $days = I('post.days', 0, 'intval');
                if ($days<>0) {
                    $oldendtime=I('post.oldendtime', 0, 'intval');
                    $setsqlarr['endtime']=strtotime("".$days." days", $oldendtime==0?time():$oldendtime);
                }
                if ($days==0) {
                    $setsqlarr['endtime']=0;
                }
            }
            $setmealtime=$setsqlarr['endtime'];
            if ($uid) {
                if (false === D('MembersSetmeal')->create($setsqlarr)) {
                    $this->error(D('MembersSetmeal')->getError());
                } else {
                    if (false === D('MembersSetmeal')->where(array('uid'=>array('eq',$uid)))->save()) {
                        $this->error('设置套餐失败！');
                    }
                }
                $setmeal=D('MembersSetmeal')->get_user_setmeal($uid);
                //会员套餐变更记录。管理员后台修改会员套餐：修改会员。3表示：管理员后台修改
                $setmeal['endtime']=date('Y-m-d', $setmeal['endtime']);
                $setsqlarr['endtime']=date('Y-m-d', $setsqlarr['endtime']);
                $setsqlarr['log_amount']=round($_POST['log_amount']);
                $notes=D('MembersSetmeal')->edit_setmeal_notes($setsqlarr, $setmeal);
                if ($notes) {
                    $user=D('Members')->get_user_one(array('uid'=>$uid));
                    $ismoney=round(I('post.log_amount'))?2:1;
                    $members_charge_log['_t']='MembersChargeLog';
                    $members_charge_log['log_uid']=$user['uid'];
                    $members_charge_log['log_username']=$user['username'];
                    $members_charge_log['log_type']=3;
                    $members_charge_log['log_value']=$notes;
                    $members_charge_log['log_amount']=$setsqlarr['log_amount'];
                    $members_charge_log['log_ismoney']=$ismoney;
                    $members_charge_log['log_mode']=2;
                    $members_charge_log['log_utype']=$user['utype'];
                    setLog($members_charge_log);
                }
                if ($setsqlarr['endtime']<>"") {
                    $jobs_deadline['setmeal_deadline']=$setmealtime;
                    $jobs_deadline['deadline']=$setmealtime;
                    M('Jobs')->where(array('uid'=>$uid))->save($jobs_deadline);
                    M('JobsTmp')->where(array('uid'=>$uid))->save($jobs_deadline);
                    D('Jobs')->distribution_jobs_uid($uid);
                }
                if (I('post.sms_num', '', 'trim')<>"") {
                    $sms_num = I('post.sms_num', 0, 'intval');
                    D('Members')->where(array('uid'=>$uid))->setField('sms_num', $sms_num);
                }
            }
            admin_write_log("编辑会员uid为".$uid."套餐信息", C('visitor.username'), 3);
            $this->success('操作成功！');
        } else {
            $where['log_utype'] = 1;
            $where['log_uid'] = I('get.uid', 0, 'intval');
            $log = D('MembersSetmealLog')->get_members_setmeal_log($where);
            $this->assign('log', $log);
            $this->edit();
        }
    }
    /**
     * 重新开通用户套餐
     */
    public function user_setmeal_set()
    {
        $reg_service = I('post.reg_service', 0, 'intval');
        if ($reg_service==0) {
            $this->error('请选择套餐');
        }
        $uid = I('post.uid', 1, 'intval');
        $rst = D('MembersSetmeal')->set_members_setmeal($uid, $reg_service);
        if ($rst['state']==1) {
            //会员套餐变更记录。管理员后台修改会员套餐：重新开通套餐。3表示：管理员后台修改
            $userinfo=D('Members')->get_user_one(array('uid'=>$uid));
            if (I('post.is_money', 0, 'intval') && I('post.log_amount')) {
                $amount=round(I('post.log_amount'), 2);
                $ismoney=2;
            } else {
                $amount='0.00';
                $ismoney=1;
            }
            $setmeal=M('Setmeal')->where(array('id'=>$reg_service))->find();
            $notes="操作人：".C('visitor.username').",说明：为会员 {$userinfo['username']} 重新开通【{$setmeal['setmeal_name']}】服务，收取服务金额：{$amount}元，服务ID：{$reg_service}。";
            $members_charge_log['_t']='MembersChargeLog';
            $members_charge_log['log_uid']=$userinfo['uid'];
            $members_charge_log['log_username']=$userinfo['username'];
            $members_charge_log['log_type']=4;
            $members_charge_log['log_value']=$notes;
            $members_charge_log['log_amount']=$amount;
            $members_charge_log['log_ismoney']=$ismoney;
            $members_charge_log['log_mode']=2;
            $members_charge_log['log_utype']=$userinfo['utype'];
            setLog($members_charge_log);
            admin_write_log("修改会员uid为".$uid."套餐信息", C('visitor.username'), 3);
            $this->success('修改成功');
        } else {
            $this->error($rst['error']);
        }
    }
    /**
     * 删除会员
     */
    public function delete()
    {
        $tuid = I('post.tuid', 'trim', 'trim')!=''?I('post.tuid'):$this->error('你没有选择会员！');
        if (I('post.delete_user')=='yes' && false===D('Members')->delete_member($tuid)) {
            $this->error('删除会员失败！');
        }
        if (I('post.delete_company')=='yes') {
            D('CompanyProfile')->admin_delete_company($tuid);
        }
        if (I('post.delete_jobs')=='yes') {
            D('Jobs')->admin_delete_jobs_for_uid($tuid);
        }
        admin_write_log('删除会员'.$tuid, C('visitor'), 3);
        $this->success('删除成功！');
    }
    public function base()
    {
        $this->display();
    }
    public function user_log()
    {
        $this->_name = 'MembersLog';
        $this->assign('type_arr', D('MembersLog')->type_arr);
        $map['log_uid'] = $_GET['uid'];
        $log_type = I('get.log_type', '', 'trim');
        $log_type && $map['log_type'] = $log_type;
        if ($settr = I('request.settr', 0, 'intval')) {
            $map['log_addtime'] = array('gt',strtotime("-".$settr." day"));
        }
        parent::_list(D('MembersLog'), $map);
        $this->display();
    }
    public function user_order()
    {
        $this->_name = 'Order';
        $map = array('utype'=>array('eq',1));
        $order_by = 'addtime desc';
        $is_paid = I('get.is_paid', 0, 'intval');
        $settr = I('get.settr', 0, 'intval');
        $uid = I('get.uid', 0, 'intval');
        $typename = I('get.typename', '', 'trim');
        $uid>0 && $map['uid']=array('eq',$uid);
        $is_paid>0 && $map['is_paid']=array('eq',$is_paid);
        $typename<>'' && $map['payment']=array('eq',$typename);
        if ($settr>0) {
            $tmpsettr=strtotime("-".$settr." day");
            $map['addtime'] = array('gt',$tmpsettr);
        }

        parent::_list(D('Order'), $map, $order_by, '*', '', array(), 10);
        $payment_list=M('Payment')->where(array('p_install'=>2))->order('listorder desc')->select();
        $this->assign('payment_list', $payment_list);
        $this->display();
    }
    public function user_increment()
    {
        $table_name = C('DB_PREFIX').'order';
        $map = array($table_name.'.utype'=>array('eq',1));
        $map = array($table_name.'.is_paid'=>array('eq',2));
        $order_by = $table_name.'.addtime '.'desc';
        $order_type = I('get.order_type', 0, 'intval');
        $settr = I('get.settr', 0, 'intval');
        $uid = I('get.uid', 0, 'intval');
        $uid>0 && $map[$table_name.'.uid']=array('eq',$uid);
        $order_type>0 && $map[$table_name.'.order_type']=array('eq',$order_type);
        if ($settr>0) {
            $tmpsettr=strtotime("-".$settr." day");
            $map[$table_name.'.addtime'] = array('gt',$tmpsettr);
        }

        $joinsql[0] = C('DB_PREFIX').'members as m on '.$table_name.'.uid=m.uid';
        $joinsql[1] = C('DB_PREFIX').'company_profile as c on '.$table_name.'.uid=c.uid';
        $field_list = $table_name.'.*,m.username,c.companyname';
        parent::_list(D('Order'), $map, $order_by, $field_list, '', $joinsql, 10);
        $increment_type = D('Order')->order_type;
        unset($increment_type[3], $increment_type[4], $increment_type[5]);
        $this->assign('increment_type', $increment_type);
        $this->display();
    }
    /**
     * 格式化列表
     */
    public function _format_promotion_list($list)
    {
        return D('Promotion')->format_list($list);
    }
    public function user_jobs_apply()
    {
        $uid = I('get.uid', 0, 'intval');
        $where['company_uid']=$uid;
        $settr =I('get.settr', 0, 'intval');
        $settr && $where['apply_addtime']=array('gt',strtotime("-{$settr} day")); //筛选简历
        $personal_apply_mod = D('PersonalJobsApply');
        $apply_list = $personal_apply_mod->get_apply_jobs($where, 1);
        $this->assign('apply_list', $apply_list);
        $this->display();
    }
    public function user_interview()
    {
        $uid = I('get.uid', 0, 'intval');
        $where['company_uid']=$uid;
        $settr =I('get.settr', 0, 'intval');
        $settr && $where['interview_addtime']=array('gt',strtotime("-{$settr} day")); //筛选简历
        $company_interview_mod = D('CompanyInterview');
        $interview = $company_interview_mod->get_invitation_pre($where, 1);
        $this->assign('interview', $interview);
        $this->display();
    }
    public function user_down()
    {
        $uid = I('get.uid', 0, 'intval');
        $where['company_uid']=$uid;
        $settr=I('get.settr', 0, 'intval');
        $settr && $where['down_addtime']=array('gt',strtotime("-".$settr." day")); //筛选 下载时间
        $down_list = D('CompanyDownResume')->get_down_resume($where, $state);
        $this->assign('down_list', $down_list);
        $this->display();
    }
    /**
     * 设置顾问
     */
    public function consultant_install()
    {
        //得到要设置顾问的企业会员uid
        $tuid = I('request.tuid');
        !$tuid && $this->error("你没有选择会员！");
        if (is_array($tuid)) {
            $tuid=implode(",", $tuid);
        }
        $this->assign('tuid', $tuid);
        $this->_name = 'Consultant';
        parent::index();
    }
    /**
     * 设置顾问
     */
    public function consultant_install_save()
    {
        //得到 顾问的id
        $id = I('get.id', 0, 'intval');
        !$id && $this->error("选择顾问发生错误！");
        //得到要设置顾问的企业会员uid
        $tuid = I('request.tuid');
        !$tuid && $this->error("你没有选择会员！");
        $tuid=explode(",", $tuid);
        foreach ($tuid as $uid) {
            D('Members')->where(array('uid'=>$uid))->setField('consultant', $id);
        }
        $this->success('设置成功！', U('CompanyMembers/index'));
    }
}
