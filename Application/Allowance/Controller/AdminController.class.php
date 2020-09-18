<?php
namespace Allowance\Controller;
use Common\Controller\BackendController;
class AdminController extends BackendController{
	public function _initialize() {
        parent::_initialize();
        check_personal_no_operate();
        check_company_no_operate();
        check_company_no_agree_refuse();
    }
    /**
     * 红包职位列表
     */
	public function index(){
        $this->_name = 'AllowanceInfo';
        $key_type = I('request.key_type',0,'intval');
        $key = I('request.key','','trim');
        $where['status'] = 1;
        if ($key && $key_type>0){
            switch($key_type){
                case 1:
                    $where['jobsname']=array('like','%'.$key.'%');break;
                case 2:
                    $where['jobsid']=array('eq',$key);break;
            }
        }else{
            if($settr=I('get.settr',0,'intval')){
                $where['addtime']=array('gt',strtotime("-".$settr." day"));
            }
            if($type=I('get.type','','trim')){
                $where['type_alias']=array('eq',$type);
            }
        }
        $this->where = $where;
        $this->order = 'id desc';
        $this->assign('typelist',D('AllowanceInfo')->type_alias_cn);
        parent::index();
    }
    /**
     * 申请退款
     */
    public function set_refund(){
        $id = I('get.id',0,'intval');
        !$id && $this->error('请选择项目！');
        $this->assign('id',$id);
        $html = $this->fetch('set_refund');
        $this->ajaxReturn(1,'获取数据成功！',$html);
    }
    /**
     * 生成退款记录
     */
    public function set_refund_save(){
        $id = I('request.id',0,'intval');
        !$id && $this->error('请选择项目！');
        $info = D('AllowanceInfo')->find($id);
        $note = I('request.note','管理员发起退款','trim');
        $setsqlarr['uid'] = $info['uid'];
        $setsqlarr['type'] = 1;
        $setsqlarr['amount'] = $info['per_amount'] * $info['surplus_num'];
        $setsqlarr['note'] = $note;
        D('AllowanceRefundmentRecord')->record_add($setsqlarr);
        D('AllowanceInfo')->surplusnum_minus($id,$info['surplus_num']);
        $this->success('发起退款成功，请到待处理退款中确认退款。');
    }
    /**
     * 待处理退款
     */
    public function refund_list(){
        $this->_name = 'AllowanceRefundment';
        $db_pre = C('DB_PREFIX');
        $this_t = $db_pre.'allowance_refundment';
        $key_type = I('request.key_type',0,'intval');
        $key = I('request.key','','trim');
        $join = array();
        $join[] = 'left join '.$db_pre."members as m on ".$this_t.".uid=m.uid";
        $join[] = 'left join '.$db_pre."company_profile as c on ".$this_t.".uid=c.uid";
        if ($key && $key_type>0){
            switch($key_type){
                case 1:
                    $where['m.username']=array('like','%'.$key.'%');break;
                case 2:
                    $where[$this_t.'.uid']=array('eq',$key);break;
            }
        }else{
            if($settr=I('get.settr',0,'intval')){
                $where['lasttime']=array('gt',strtotime("-".$settr." day"));
            }
        }
        $this->where = $where;
        $this->field = $this_t.'.*,m.username,c.companyname';
        $this->order = $this_t.'.status desc,lasttime desc ,'.$this_t.'.id desc';
        $this->join = $join;
        parent::index();
    }
    /**
     * 退款记录详情
     */
    public function refund_detail(){
        $id = I('get.id',0,'intval');
        !$id && $this->error('请选择项目！');
        $uid = I('get.uid',0,'intval');
        !$uid && $this->error('参数错误！');
        $list = D('AllowanceRefundmentRecord')->where(array('pid'=>$id))->order('refundtime asc,addtime desc')->select();
        $this->assign('list',$list);
        $this->assign('uid',$uid);
        $this->assign('typelist',D('AllowanceRefundmentRecord')->typelist);
        $html = $this->fetch('refund_detail');
        $this->ajaxReturn(1,'获取数据成功！',$html);
    }
    /**
     * 确认退款
     */
    public function confirm_refund(){
        $id = I('request.id');
        !$id && $this->error('请选择项目！');
        $uid = I('request.uid',0,'intval');
        !$uid && $this->error('参数错误！');
        D('AllowanceRefundmentRecord')->record_set($id,$uid);
        $this->success('退款成功！');
    }
    /**
     * 交易记录
     */
    public function deal_list(){
        $list_type = I('get.list_type',1,'intval');
        if($list_type==1){
            $this->_deal_list_company();
        }else{
            $this->_deal_list_personal();
        }
    }
    protected function _deal_list_company(){
        $this->_name = 'AllowanceDealLogCompany';
        $db_pre = C('DB_PREFIX');
        $this_t = $db_pre.'allowance_deal_log_company';
        $key_type = I('request.key_type',0,'intval');
        $key = I('request.key','','trim');
        $join = array();
        $join[] = 'left join '.$db_pre."allowance_info as i on ".$this_t.".info_id=i.id";
        if ($key && $key_type>0){
            switch($key_type){
                case 1:
                    $where[$this_t.'.companyname']=array('like','%'.$key.'%');break;
                case 2:
                    $where['i.jobsname']=array('like','%'.$key.'%');break;
                case 3:
                    $where[$this_t.'.uid']=array('eq',$key);break;
            }
        }else{
            if($settr=I('get.settr',0,'intval')){
                $where[$this_t.'.addtime']=array('gt',strtotime("-".$settr." day"));
            }
        }
        $this->where = $where;
        $this->field = $this_t.'.*,i.type_alias,i.jobsname';
        $this->order = 'addtime desc ,id desc';
        $this->join = $join;
        $this->_tpl = 'deal_list_company';
        $this->assign('typelist',D('AllowanceInfo')->type_alias_cn);
        parent::index();
    }
    protected function _deal_list_personal(){
        $this->_name = 'AllowanceDealLogPersonal';
        $db_pre = C('DB_PREFIX');
        $this_t = $db_pre.'allowance_deal_log_personal';
        $key_type = I('request.key_type',0,'intval');
        $key = I('request.key','','trim');
        $join = array();
        $join[] = 'left join '.$db_pre."members as m on ".$this_t.".uid=m.uid";
        if ($key && $key_type>0){
            switch($key_type){
                case 1:
                    $where['m.username']=array('like','%'.$key.'%');break;
                case 2:
                    $where[$this_t.'.uid']=array('eq',$key);break;
            }
        }else{
            if($settr=I('get.settr',0,'intval')){
                $where['addtime']=array('gt',strtotime("-".$settr." day"));
            }
            if($type=I('get.type','','trim')){
                $where['type']=array('eq',$type);
            }
        }
        
        $this->where = $where;
        $this->field = $this_t.'.*,m.username';
        $this->order = 'addtime desc ,id desc';
        $this->join = $join;
        $this->_tpl = 'deal_list_personal';
        $this->assign('typelist',D('AllowanceInfo')->type_alias_cn);
        parent::index();
    }
    /**
     * 黑名单
     */
    public function blacklist(){
        $this->_name = 'AllowanceBlacklist';
        $db_pre = C('DB_PREFIX');
        $this_t = $db_pre.'allowance_blacklist';
        $key_type = I('request.key_type',0,'intval');
        $key = I('request.key','','trim');
        $join = array();
        $join[] = 'left join '.$db_pre."members as m on ".$this_t.".uid=m.uid";
        if ($key && $key_type>0){
            switch($key_type){
                case 1:
                    $where['m.username']=array('like','%'.$key.'%');break;
                case 2:
                    $where[$this_t.'.uid']=array('eq',$key);break;
            }
        }else{
            if($settr=I('get.settr',0,'intval')){
                $where[$this_t.'.addtime']=array('gt',strtotime("-".$settr." day"));
            }
            if($robot=I('get.robot','','trim')){
                $where[$this_t.'.robot']=array('eq',$robot);
            }
        }
        $where['deadline'] = array(array('eq',0),array('gt',time()),'or');
        $this->where = $where;
        $this->field = $this_t.'.*,m.username';
        $this->order = 'addtime desc ,id desc';
        $this->join = $join;
        parent::index();
    }
    /**
     * 从黑名单中移除
     */
    public function release(){
        $uid = I('request.uid');
        !$uid && $this->error('请选择用户！');
        D('AllowanceBlacklist')->release($uid);
        $this->success('移除成功！');
    }
    /**
     * 参数配置
     */
    public function config(){
        if(IS_POST){
            foreach (I('post.') as $key => $val) {
                $val = is_array($val) ? serialize($val) : $val;
                D('AllowanceConfig')->where(array('name' => $key))->save(array('value' => $val));
            }
            $this->success(L('operation_success'));
            exit;
        }
        $config = D('AllowanceConfig')->getField('name,value');
        $config['amount_range'] = unserialize($config['amount_range']);
        $this->assign('config',$config);
        if(false === $smsConfig = F('sms_config')) $smsConfig = D('Common/SmsConfig')->config_cache();
        $this->assign('smsConfig',$smsConfig);
        $weixin_config_list = D('Allowance/AllowanceWeixinTplmsg')->select();
        $this->assign('weixin_config_list',$weixin_config_list);
        $this->display();
    }
    /**
     * 审核
     */
    public function set_audit(){
        $id = I('request.id');
        !$id && $this->error('请选择项目！');
        $this->assign('id',$id);
        $html = $this->fetch('set_audit');
        $this->ajaxReturn(1,'调用成功！',$html);
    }
    /**
     * 审核
     */
    public function set_audit_save(){
        $id = I('request.id');
        !$id && $this->error('请选择项目！');
        $id_arr = is_array($id)?$id:array($id);
        $pass = I('request.pass',0,'intval');
        $note = I('request.note','','trim');
        if($pass==1){
            foreach ($id_arr as $key => $value) {
                $pay_status = D('AllowanceRecord')->pay($value);
                D('AllowanceRecordLog')->add(array('step'=>0,'record_id'=>$value,'note'=>'管理员审核通过，红包发放给个人'.($note?'；备注：'.$note:'')));
                //短信提醒
                if(C('qscms_sms_open')==1){
                    if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
                    $recordinfo = D('Allowance/AllowanceRecord')->find($value);
                    $baseinfo = D('Allowance/AllowanceInfo')->find($recordinfo['info_id']);
                    //通知个人
                    $personal_info=M('Members')->where(array('uid'=>$recordinfo['personal_uid']))->find();
                    if ($sms['set_allowance_audit_pass_personal']=="1"  && $personal_info['mobile_audit']=="1")
                    {
                        $sendSms['mobile']=$personal_info['mobile'];
                        $sendSms['tpl']='set_allowance_audit_pass_personal';
                        $sendSms['data']=array('sitename'=>C('qscms_site_name'),'jobsname'=>$baseinfo['jobsname']);
                        D('Common/Sms')->sendSms('notice',$sendSms);
                    }
                    //通知企业
                    $company_user_info=M('Members')->where(array('uid'=>$recordinfo['company_uid']))->find();
                    if ($sms['set_allowance_audit_pass_company']=="1"  && $company_user_info['mobile_audit']=="1")
                    {
                        $sendSms['mobile']=$company_user_info['mobile'];
                        $sendSms['tpl']='set_allowance_audit_pass_company';
                        $resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
                        $sendSms['data']=array('sitename'=>C('qscms_site_name'),'fullname'=>$resumeinfo['fullname']);
                        D('Common/Sms')->sendSms('notice',$sendSms);
                    }
                }
                //微信通知
                if(C('apply.Weixin')){
                    $recordinfo = D('Allowance/AllowanceRecord')->find($value);
                    $baseinfo = D('Allowance/AllowanceInfo')->find($recordinfo['info_id']);
                    //通知个人
                    if(false===$config=F('allowance_config')){
                        $config = D('Allowance/AllowanceConfig')->config_cache();
                    }
                    if($config['service_charge']>0){
                        $amount = $recordinfo['amount']*(1-$config['service_charge']/100);
                    }else{
                        $amount = $recordinfo['amount'];
                    }
                    $amount = $amount<1?1:$amount;
                    D('Allowance/AllowanceTplMsg')->set_allowance_audit_pass_personal($recordinfo['personal_uid'],$baseinfo['jobsname'].'【'.D('Allowance/AllowanceRecord')->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$baseinfo['jobsname'],$recordinfo['amount']-$amount,$amount);
                    //通知企业
                    if(!$resumeinfo){
                        $resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
                    }
                    D('Allowance/AllowanceTplMsg')->set_allowance_audit_pass_company($recordinfo['company_uid'],$baseinfo['jobsname'].'【'.D('Allowance/AllowanceRecord')->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$resumeinfo['fullname'],$baseinfo['surplus_num']);
                }
            }
            $note = '审核结果：审核通过，发放红包给个人。备注：'.($note?$note:'无');
        }else{
            $list = D('AllowanceRecord')->where(array('id'=>array('in',$id_arr)))->select();
            foreach ($list as $key => $value) {
                $setsqlarr['uid'] = $value['company_uid'];
                $setsqlarr['type'] = 3;
                $setsqlarr['amount'] = $value['amount'];
                $setsqlarr['note'] = '审核未通过，管理员发起企业退款';
                D('AllowanceRefundmentRecord')->record_add($setsqlarr);
                unset($setsqlarr);
                D('AllowanceRecord')->admin_finish($value['id']);
                D('AllowanceRecordLog')->add(array('step'=>0,'record_id'=>$value['id'],'note'=>'管理员审核未通过，退款给企业'.($note?'；备注：'.$note:'')));
                //短信提醒
                if(C('qscms_sms_open')==1){
                    if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
                    $baseinfo = D('Allowance/AllowanceInfo')->find($value['info_id']);
                    //通知个人
                    $personal_info=M('Members')->where(array('uid'=>$value['personal_uid']))->find();
                    if ($sms['set_allowance_audit_nopass_personal']=="1"  && $personal_info['mobile_audit']=="1")
                    {
                        $sendSms['mobile']=$personal_info['mobile'];
                        $sendSms['tpl']='set_allowance_audit_nopass_personal';
                        $sendSms['data']=array('sitename'=>C('qscms_site_name'),'jobsname'=>$baseinfo['jobsname']);
                        D('Common/Sms')->sendSms('notice',$sendSms);
                    }
                    //通知企业
                    $company_user_info=M('Members')->where(array('uid'=>$value['company_uid']))->find();
                    if ($sms['set_allowance_audit_nopass_company']=="1"  && $company_user_info['mobile_audit']=="1")
                    {
                        $sendSms['mobile']=$company_user_info['mobile'];
                        $sendSms['tpl']='set_allowance_audit_nopass_company';
                        $resumeinfo = D('Common/Resume')->find($value['resumeid']);
                        $sendSms['data']=array('sitename'=>C('qscms_site_name'),'fullname'=>$resumeinfo['fullname'],'jobsname'=>$baseinfo['jobsname']);
                        D('Common/Sms')->sendSms('notice',$sendSms);
                    }
                }
                //微信通知
                if(C('apply.Weixin')){
                    $baseinfo = D('Allowance/AllowanceInfo')->find($value['info_id']);
                    //通知个人
                    D('Allowance/AllowanceTplMsg')->set_allowance_audit_nopass_personal($value['personal_uid'],$baseinfo['jobsname'].'【'.D('Allowance/AllowanceRecord')->get_type_cn($baseinfo['type_alias']).'】（'.$value['amount'].'元）');
                    //通知企业
                    if(!$resumeinfo){
                        $resumeinfo = D('Common/Resume')->find($value['resumeid']);
                    }
                    D('Allowance/AllowanceTplMsg')->set_allowance_audit_nopass_company($value['company_uid'],$baseinfo['jobsname'].'【'.D('Allowance/AllowanceRecord')->get_type_cn($baseinfo['type_alias']).'】（'.$value['amount'].'元）',$resumeinfo['fullname'],$baseinfo['surplus_num']);
                }
            }
            $note = '审核结果：审核未通过，退款给企业。备注：'.($note?$note:'无');
        }
        D('AllowanceRecord')->where(array('id'=>array('in',$id_arr)))->save(array('audit_note'=>$note,'audit_man'=>C('visitor.username')));
        $this->success('审核成功！');
    }
    /**
     * 审核详情
     */
    public function audit_detail(){
        $id = I('request.id');
        !$id && $this->error('请选择项目！');
        $info = D('AllowanceRecord')->find($id);
        $this->assign('info',$info);
        $html = $this->fetch('audit_detail');
        $this->ajaxReturn(1,'调用成功！',$html);
    }
    /**
     * 仲裁详情
     */
    public function arbitrate_detail(){
        $id = I('request.id');
        !$id && $this->error('请选择项目！');
        $info = D('AllowanceRecord')->find($id);
        $this->assign('info',$info);
        $html = $this->fetch('arbitrate_detail');
        $this->ajaxReturn(1,'调用成功！',$html);
    }
    /**
     * 发放失败列表
     */
    public function fail_list(){
        $this->_name = 'AllowanceRecord';
        $db_pre = C('DB_PREFIX');
        $this_t = $db_pre.'allowance_record';
        $join = array();
        $join[] = 'left join '.$db_pre."allowance_info as i on ".$this_t.".info_id=i.id";
        $join[] = 'left join '.$db_pre."company_profile as c on ".$this_t.".company_uid=c.uid";
        $join[] = 'left join '.$db_pre."resume as r on ".$this_t.".resumeid=r.id";
        if($settr=I('get.settr',0,'intval')){
            $where['updatetime']=array('gt',strtotime("-".$settr." day"));
        }
        $where[$this_t.'.pay_status'] = 2;
        $this->where = $where;
        $this->field = $this_t.'.*,i.jobsname,c.companyname,r.fullname';
        $this->order = $this_t.'.status desc,updatetime desc ,'.$this_t.'.id desc';
        $this->join = $join;
        parent::index();
    }
    /**
     * 重新发放
     */
    public function fail_repay(){
        $id = I('request.id');
        !$id && $this->error('请选择项目！');
        $id_arr = is_array($id)?$id:array($id);
        foreach ($id_arr as $key => $value) {
            D('AllowanceRecord')->pay($value,array(),true);
        }
        $this->success('操作成功！');
    }
    /**
     * 删除失败记录（实际上是把record记录的pay_status设为0）
     */
    public function fail_delete(){
        $id = I('request.id');
        !$id && $this->error('请选择项目！');
        $id_arr = is_array($id)?$id:array($id);
        D('AllowanceRecord')->where(array('id'=>array('in',$id_arr)))->save(array('pay_status'=>0,'pay_fail_reason'=>''));
        $this->success('操作成功！');
    }
    /**
     * 仲裁
     */
    public function set_arbitrate(){
        $id = I('request.id');
        !$id && $this->error('请选择项目！');
        $this->assign('id',$id);
        $html = $this->fetch('set_arbitrate');
        $this->ajaxReturn(1,'调用成功！',$html);
    }
    /**
     * 仲裁
     */
    public function set_arbitrate_save(){
        $id = I('request.id');
        !$id && $this->error('请选择项目！');
        $id_arr = is_array($id)?$id:array($id);
        $operate = I('request.operate','refund','trim');
        if(!in_array($operate, array('refund','putout'))){
            $this->error('参数错误！');
        }
        $note = I('request.note','','trim');
        if($operate=='putout'){
            foreach ($id_arr as $key => $value) {
                $pay_status = D('AllowanceRecord')->pay($value);
                D('AllowanceRecordLog')->add(array('step'=>0,'record_id'=>$value,'note'=>'管理员仲裁，发放红包给个人'.($note?'；备注：'.$note:'')));
                //短信提醒
                if(C('qscms_sms_open')==1){
                    if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
                    $recordinfo = D('Allowance/AllowanceRecord')->find($value);
                    $sms_alias = $recordinfo['interview']==1?'set_allowance_arbitrate_putout_interview_personal':'set_allowance_arbitrate_putout_entry_personal';
                    //通知个人
                    $personal_info=M('Members')->where(array('uid'=>$recordinfo['personal_uid']))->find();
                    if ($sms[$sms_alias]=="1"  && $personal_info['mobile_audit']=="1")
                    {
                        $sendSms['mobile']=$personal_info['mobile'];
                        $sendSms['tpl']=$sms_alias;
                        $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
                        $sendSms['data']=array('sitename'=>C('qscms_site_name'),'companyname'=>$companyinfo['companyname']);
                        D('Common/Sms')->sendSms('notice',$sendSms);
                    }
                    //通知企业
                    $sms_alias = $recordinfo['interview']==1?'set_allowance_arbitrate_putout_interview_company':'set_allowance_arbitrate_putout_entry_company';
                    $company_user_info=M('Members')->where(array('uid'=>$recordinfo['company_uid']))->find();
                    if ($sms[$sms_alias]=="1"  && $company_user_info['mobile_audit']=="1")
                    {
                        $sendSms['mobile']=$company_user_info['mobile'];
                        $sendSms['tpl']=$sms_alias;
                        $resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
                        $sendSms['data']=array('sitename'=>C('qscms_site_name'),'fullname'=>$resumeinfo['fullname']);
                        D('Common/Sms')->sendSms('notice',$sendSms);
                    }
                }
                //微信通知
                if(C('apply.Weixin')){
                    $baseinfo = D('Allowance/AllowanceInfo')->find($value['info_id']);
                    //通知个人
                    $func = $recordinfo['interview']==1?'set_allowance_arbitrate_putout_interview_personal':'set_allowance_arbitrate_putout_entry_personal';
                    D('Allowance/AllowanceTplMsg')->$func($value['personal_uid'],$baseinfo['jobsname'].'【'.D('Allowance/AllowanceRecord')->get_type_cn($baseinfo['type_alias']).'】（'.$value['amount'].'元）');
                    //通知企业
                    if(!$resumeinfo){
                        $resumeinfo = D('Common/Resume')->find($value['resumeid']);
                    }
                    $func = $recordinfo['interview']==1?'set_allowance_arbitrate_putout_interview_company':'set_allowance_arbitrate_putout_entry_company';
                    D('Allowance/AllowanceTplMsg')->$func($value['company_uid'],$baseinfo['jobsname'].'【'.D('Allowance/AllowanceRecord')->get_type_cn($baseinfo['type_alias']).'】（'.$value['amount'].'元）',$resumeinfo['fullname'],$baseinfo['surplus_num']);
                }
            }
            $note = '仲裁结果：发放红包给个人。备注：'.($note?$note:'无');
        }else{
            $list = D('AllowanceRecord')->where(array('id'=>array('in',$id_arr)))->select();
            foreach ($list as $key => $value) {
                $setsqlarr['uid'] = $value['company_uid'];
                $setsqlarr['type'] = 3;
                $setsqlarr['amount'] = $value['amount'];
                $setsqlarr['note'] = '管理员仲裁，向企业发起退款';
                D('AllowanceRefundmentRecord')->record_add($setsqlarr);
                unset($setsqlarr);
                D('AllowanceRecord')->admin_finish($value['id']);
                D('AllowanceRecordLog')->add(array('step'=>0,'record_id'=>$value['id'],'note'=>'管理员仲裁，向企业发起退款'.($note?'；备注：'.$note:'')));
                //短信提醒
                if(C('qscms_sms_open')==1){
                    if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
                    $sms_alias = $value['interview']==1?'set_allowance_arbitrate_refund_interview_personal':'set_allowance_arbitrate_refund_entry_personal';
                    //通知个人
                    $personal_info=M('Members')->where(array('uid'=>$value['personal_uid']))->find();
                    if ($sms[$sms_alias]=="1"  && $personal_info['mobile_audit']=="1")
                    {
                        $sendSms['mobile']=$personal_info['mobile'];
                        $sendSms['tpl']=$sms_alias;
                        $sendSms['data']=array('sitename'=>C('qscms_site_name'));
                        D('Common/Sms')->sendSms('notice',$sendSms);
                    }
                    //通知企业
                    $sms_alias = $value['interview']==1?'set_allowance_arbitrate_refund_interview_company':'set_allowance_arbitrate_refund_entry_company';
                    $company_user_info=M('Members')->where(array('uid'=>$value['company_uid']))->find();
                    if ($sms[$sms_alias]=="1"  && $company_user_info['mobile_audit']=="1")
                    {
                        $sendSms['mobile']=$company_user_info['mobile'];
                        $sendSms['tpl']=$sms_alias;
                        $resumeinfo = D('Common/Resume')->find($value['resumeid']);
                        $sendSms['data']=array('sitename'=>C('qscms_site_name'),'fullname'=>$resumeinfo['fullname'],'jobsname'=>$baseinfo['jobsname']);
                        D('Common/Sms')->sendSms('notice',$sendSms);
                    }
                }
                //微信通知
                if(C('apply.Weixin')){
                    $baseinfo = D('Allowance/AllowanceInfo')->find($value['info_id']);
                    //通知个人
                    $func = $recordinfo['interview']==1?'set_allowance_arbitrate_refund_interview_personal':'set_allowance_arbitrate_refund_entry_personal';
                    D('Allowance/AllowanceTplMsg')->$func($value['personal_uid'],$baseinfo['jobsname'].'【'.D('Allowance/AllowanceRecord')->get_type_cn($baseinfo['type_alias']).'】（'.$value['amount'].'元）');
                    //通知企业
                    if(!$resumeinfo){
                        $resumeinfo = D('Common/Resume')->find($value['resumeid']);
                    }
                    $func = $recordinfo['interview']==1?'set_allowance_arbitrate_refund_interview_company':'set_allowance_arbitrate_refund_entry_company';
                    D('Allowance/AllowanceTplMsg')->$func($value['company_uid'],$baseinfo['jobsname'].'【'.D('Allowance/AllowanceRecord')->get_type_cn($baseinfo['type_alias']).'】（'.$value['amount'].'元）',$resumeinfo['fullname'],$baseinfo['surplus_num']);
                }
            }
            $note = '仲裁结果：退款给企业。备注：'.($note?$note:'无');
        }
        D('AllowanceRecord')->where(array('id'=>array('in',$id_arr)))->save(array('arbitrate_note'=>$note,'arbitrate_man'=>C('visitor.username')));
        $this->success('操作成功！');
    }
    /**
     * 领取记录
     */
    public function signon_log(){
        $id = I('get.id',0,'intval');
        $join = 'left join '.C('DB_PREFIX').'members_info as m on m.uid='.C('DB_PREFIX').'allowance_record.personal_uid';
        $field = C('DB_PREFIX').'allowance_record.*,m.realname';
        $list = D('AllowanceRecord')->join($join)->field($field)->where(array('info_id'=>$id))->order('id desc')->select();
        $this->assign('list',$list);
        $this->assign('status_arr',D('AllowanceRecord')->status_cn);
        $html = $this->fetch('signon_log');
        $this->ajaxReturn(1,'获取数据成功！',$html);
    }
    /**
     * 打赏记录
     */
    public function record_list(){
        $this->_name = 'AllowanceRecord';
        $db_pre = C('DB_PREFIX');
        $this_t = $db_pre.'allowance_record';
        $join = array();
        $join[] = 'left join '.$db_pre."allowance_info as i on ".$this_t.".info_id=i.id";
        $join[] = 'left join '.$db_pre."resume as r on ".$this_t.".resumeid=r.id";
        $field = $this_t.'.*,i.jobsname,r.fullname';
        $order = $this_t.'.id desc';
        $key_type = I('request.key_type',0,'intval');
        $key = I('request.key','','trim');
        $get_status = I('get.status','-1','trim');
        if($get_status!='-1'){
            $where[$this_t.'.status'] = array('eq',$get_status);
        }
        if ($key && $key_type>0){
            switch($key_type){
                case 1:
                    $where['i.jobsname']=array('like','%'.$key.'%');break;
                case 2:
                    $where['i.jobsid']=array('eq',$key);break;
            }
        }else{
            $type = I('get.type','','trim');
            if($type!=''){
                $where['type_alias'] = array('eq',$type);
            }
        }
        parent::_list(D('AllowanceRecord'),$where,$order,$field,'',$join,10,'_format_list');
        $this->assign('get_status',$get_status);
        $this->assign('type_list',D('AllowanceRecord')->type_cn);
        $this->assign('status_list',D('AllowanceRecord')->status_cn);
        $this->display();
    }
    protected function _format_list($list){
        foreach ($list as $key => $value) {
            $type = '';
            if($value['apply']==1){
                $type = 'apply';
            }else if($value['interview']==1){
                $type = 'interview';
            }else if($value['entry']==1){
                $type = 'entry';
            }
            $list[$key]['type_cn'] = D('AllowanceInfo')->get_alias_cn($type);
            $list[$key]['status_cn'] = D('AllowanceRecord')->get_status_cn($value['status']);
        }
        return $list;
    }
    /**
     * 加入黑名单
     */
    public function set_blacklist(){
        $uid = I('request.uid');
        !$uid && $this->error('请选择！');
        $this->assign('uid',$uid);
        $this->assign('timelimit_options',D('AllowanceBlacklist')->timelimit_options);
        $html = $this->fetch('set_blacklist');
        $this->ajaxReturn(1,'调用成功！',$html);
    }
    /**
     * 加入黑名单
     */
    public function set_blacklist_save(){
        $uid = I('request.uid');
        !$uid && $this->error('请选择用户！');
        $note = I('request.note','','trim');
        $timelimit = I('request.timelimit',0,'intval');
        $deadline = $timelimit==0?0:strtotime('+'.$timelimit.' days');
        $uid_arr = is_array($uid)?$uid:array($uid);
        $members = D('Common/Members')->where(array('uid'=>array('in',$uid_arr)))->select();
        foreach ($members as $key => $value) {
            $insert_id = D('AllowanceBlacklist')->add(array('deadline'=>$deadline,'uid'=>$value['uid'],'utype'=>$value['utype'],'robot'=>0,'note'=>$note,'admin_name'=>C('visitor.username')));
            //短信提醒
            if($insert_id && C('qscms_sms_open')==1){
                if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
                if ($sms['set_allowance_add_blacklist']=="1"  && $value['mobile_audit']=="1")
                {
                    $sendSms['mobile']=$value['mobile'];
                    $sendSms['tpl']='set_allowance_add_blacklist';
                    $timelimit = $timelimit==0?'永久':$timelimit.'天';
                    $sendSms['data']=array('sitename'=>C('qscms_site_name'),'time_limit'=>$timelimit);
                    D('Common/Sms')->sendSms('notice',$sendSms);
                }
            }
            //微信提醒
            if(C('apply.Weixin')){
                D('Allowance/AllowanceTplMsg')->set_allowance_add_blacklist($value['uid']);
            }
        }
        $this->success('操作成功！');
    }
    /**
     * 新增黑名单
     */
    public function add_blacklist(){
        $this->assign('timelimit_options',D('AllowanceBlacklist')->timelimit_options);
        $html = $this->fetch('add_blacklist');
        $this->ajaxReturn(1,'调用成功！',$html);
    }
    /**
     * 新增黑名单
     */
    public function add_blacklist_save(){
        $uid = I('request.uid',0,'intval');
        !$uid && $this->error('请填写会员uid！');
        $member = D('Common/Members')->find($uid);
        !$member && $this->error('用户不存在！');
        $note = I('request.note','','trim');
        $timelimit = I('request.timelimit',0,'intval');
        $deadline = $timelimit==0?0:strtotime('+'.$timelimit.' days');
        D('AllowanceBlacklist')->add(array('deadline'=>$deadline,'uid'=>$uid,'utype'=>$member['utype'],'robot'=>0,'note'=>$note,'admin_name'=>C('visitor.username')));
        $this->success('操作成功！');
    }
    /**
     * record日志
     */
    public function record_log(){
        $id = I('request.id');
        !$id && $this->error('请选择记录！');
        $list = D('AllowanceRecordLog')->where(array('record_id'=>$id))->select();
        $this->assign('list',$list);
        $html = $this->fetch('record_log');
        $this->ajaxReturn(1,'调用成功！',$html);
    }
    /**
     * 个人领取红包记录
     */
    public function record_personal_log(){
        $uid = I('request.uid',0,'intval');
        !$uid && $this->error('请选择会员！');
        $db_pre = C('DB_PREFIX');
        $this_t = $db_pre.'allowance_record';
        $join = array();
        $join[] = 'left join '.$db_pre."allowance_info as i on ".$this_t.".info_id=i.id";
        $join[] = 'left join '.$db_pre."resume as r on ".$this_t.".resumeid=r.id";
        $field = $this_t.'.*,i.jobsname,r.fullname';
        $order = $this_t.'.id desc';
        $where['personal_uid'] = $uid;
        // $where[$this_t.'.status'] = 3;
        $list = D('AllowanceRecord')->where($where)->field($field)->join($join)->order($order)->select();
        $list = $this->_format_list($list);
        $this->assign('list',$list);
        $html = $this->fetch('record_personal_log');
        $this->ajaxReturn(1,'调用成功！',$html);
    }
    /**
     * 企业发放红包记录
     */
    public function record_company_log(){
        $uid = I('request.uid',0,'intval');
        !$uid && $this->error('请选择会员！');
        $db_pre = C('DB_PREFIX');
        $this_t = $db_pre.'allowance_record';
        $join = array();
        $join[] = 'left join '.$db_pre."allowance_info as i on ".$this_t.".info_id=i.id";
        $join[] = 'left join '.$db_pre."resume as r on ".$this_t.".resumeid=r.id";
        $field = $this_t.'.*,i.jobsname,r.fullname';
        $order = $this_t.'.id desc';
        $where['company_uid'] = $uid;
        // $where[$this_t.'.status'] = 3;
        $list = D('AllowanceRecord')->where($where)->field($field)->join($join)->order($order)->select();
        $list = $this->_format_list($list);
        $this->assign('list',$list);
        $html = $this->fetch('record_company_log');
        $this->ajaxReturn(1,'调用成功！',$html);
    }
    protected function setPercent($num,$total){
        return round($num/$total*100,2);
    }
    protected function _income_chart($starttime,$endtime){
        //求职新增曲线图
        $cachename = $starttime.$endtime.'_service_charge_line.cache';
        $check_cache = check_cache($cachename,'allowance/');
        if($check_cache){
            $data = $check_cache;
        }else{
            for($i=$starttime;$i<$endtime;$i=$i+3600*24){
                $day = date("m/d",$i);
                $datelist[$day]=0;
            }
            $result = D('AllowanceDealLogPersonal')->where(array('addtime'=>array(array('egt',$starttime),array('lt',$endtime+3600*24),'and')))->select();
            foreach ($result as $key => $value) {
                $date=date("m/d",$value['addtime']);
                if(isset($datelist[$date])){
                    $datelist[$date] += $value['service_charge'];
                }
                
            }
            $i = 0;
            foreach ($datelist as $key => $value) {
                $moneyArr[$i]['label'] = $key;
                $moneyArr[$i]['value'] = $value;
                $i++;
            }
            $dataArr = array(
                    $moneyArr
                );
            $data = json_encode($dataArr);
            write_cache($cachename,'allowance/',$data);
        }
        $this->assign('service_charge_line',$data);
        unset($datelist,$moneyArr,$dataArr,$data);
        //个人信息-工作经验饼状图
        $cachename = $starttime.$endtime.'_service_charge_pie.cache';
        $check_cache = check_cache($cachename,'allowance/');
        if($check_cache){
            $data = $check_cache;
        }else{
            $count_money = D('AllowanceDealLogPersonal')->count();
            $dataArr = array();
            $dArr = array();
            $type_options = D('AllowanceRecord')->type_cn;
            foreach ($type_options as $key => $value) {
                $typeArr[$key]['label'] = $value;
                $typeArr[$key]['value'] = 0;
            }

            $result = D('AllowanceDealLogPersonal')->where(array('addtime'=>array(array('egt',$starttime),array('lt',$endtime+3600*24),'and')))->field('sum(service_charge) as total,type')->group('type')->select();
           
            foreach ($result as $key => $value) {
                $typeArr[$value['type']]['value'] = $value['total'];
            }
            $i = 0;
            foreach ($typeArr as $key => $value) {
                $dArr[$i]['label'] = $value['label'];
                $dArr[$i]['value'] = $value['value'];
                $i++;
            }
            usort($dArr, function($a, $b) {
                $al = $a['value'];
                $bl = $b['value'];
                if ($al == $bl)
                    return 0;
                return ($al > $bl) ? -1 : 1;
            });     
            
            $dataArr = array(
                    $dArr
                );
        
            $data = json_encode($dataArr);
            write_cache($cachename,'allowance/',$data);
        }
        $this->assign('service_charge_pie',$data);
        $this->assign('starttime',date('Y-m-d',$starttime));
        $this->assign('endtime',date('Y-m-d',$endtime-3600*24));
        $this->display('income_chart');
    }
    protected function _income_list($starttime,$endtime){
        $this->_name = 'AllowanceDealLogPersonal';
        $db_pre = C('DB_PREFIX');
        $this_t = $db_pre.'allowance_deal_log_personal';
        $key_type = I('request.key_type',0,'intval');
        $key = I('request.key','','trim');
        $join = array();
        $join[] = 'left join '.$db_pre."members as m on ".$this_t.".uid=m.uid";
        $where['addtime']=array(array('egt',$starttime),array('lt',$endtime),'and');
        if($type=I('get.type','','trim')){
            $where['type']=array('eq',$type);
        }
        $this->where = $where;
        $this->field = $this_t.'.*,m.username';
        $this->order = 'addtime desc ,id desc';
        $this->join = $join;
        $this->_tpl = 'income_list';
        $this->assign('typelist',D('AllowanceInfo')->type_alias_cn);
        $this->assign('type',$type);
        $this->assign('starttime',date('Y-m-d',$starttime));
        $this->assign('endtime',date('Y-m-d',$endtime-3600*24));
        parent::index();
    }
    public function income(){
        $endtime = I('get.endtime',date('Y-m-d',strtotime('today')-3600*24),'trim');
        $starttime = I('get.starttime',date('Y-m-d',strtotime($endtime)-3600*24*7),'trim');
        $starttime = strtotime($starttime);
        $endtime = strtotime($endtime)+3600*24;
        if($endtime>strtotime('today')){
            $endtime = strtotime('today');
            if($starttime>strtotime('today')){
                $starttime = date('Y-m-d',strtotime($endtime)-3600*24*7);
            }
        }
        $show_type = I('get.show_type','chart','trim');
        $this->assign('show_type',$show_type);
        if($show_type=='chart'){
            $this->_income_chart($starttime,$endtime);
        }else{
            $this->_income_list($starttime,$endtime);
        }
    }
    public function sms_rule(){
        if(IS_POST){
            foreach (I('post.') as $key => $val) {
                $reg = D('Common/SmsConfig')->where(array('name' => $key))->save(array('value' => intval($val)));
                if(false === $reg){
                    IS_AJAX && $this->ajaxReturn(0, L('operation_failure'));
                    $this->error(L('operation_failure'));
                }
            }
            $this->success(L('operation_success'));
        }
    }
    public function weixin_rule(){
        if(IS_POST){
            $post = I('post.');
            foreach ($post['alias'] as $key => $val) {
                unset($data);
                $data['value'] = $post[$val.'_value'];
                $data['template_id'] = $post['template_id'][$key];
                D('Allowance/AllowanceWeixinTplmsg')->where(array('alias' => $val))->save($data);
            }
            $this->success(L('operation_success'));
        }
    }
    /**
     * 使用说明
     */
    public function instructions(){
        $this->display();
    }
}
?>