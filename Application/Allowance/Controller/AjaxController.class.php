<?php
namespace Allowance\Controller;
use Common\Controller\FrontendController;
use Allowance\Model\AllowanceInfoModel;
class AjaxController extends FrontendController{
	public function _initialize() {
        parent::_initialize();
    }
    /**
     * 职位红包
     */
    public function set_allowance_job(){
        $jobsid = I('get.jobsid',0,'intval');
        if(!$jobsid){
            $this->ajaxReturn(0,'参数错误！');
        }
        //检测是否被屏蔽领取红包功能
        $in_blacklist = D('AllowanceBlacklist')->where(array('uid'=>C('visitor.uid')))->find();
        if($in_blacklist){
            if($in_blacklist['deadline']==0 || $in_blacklist['deadline']>time()){
                $this->ajaxReturn(0,'你的发布红包职位权限被冻结，暂时无法发布红包职位，如有疑问请联系网站管理员');
            }else{
                D('AllowanceBlacklist')->where(array('uid'=>$user['uid']))->delete();
            }
        }
        $category = D('Category')->get_category_cache();
        $this->assign('category',$category);
        $this->assign('jobsid',$jobsid);
        if(false === $config = F('allowance_config')){
            $config = D('AllowanceConfig')->config_cache();
        }
        $this->assign('range',$config['amount_range']);
        $html = $this->fetch('set_allowance_job');
        $this->ajaxReturn(1,'获取数据成功！',$html);
    }
    /**
     * 职位红包保存
     */
    public function set_allowance_job_save(){
        $setsqlarr['payment'] = I('request.payment','wxpay','trim');
        if(!in_array($setsqlarr['payment'], array('wxpay','alipay'))){
            $this->ajaxReturn(0,'支付方式错误！');
        }
        $setsqlarr['jobsid'] = I('request.jobsid',0,'intval');
        !$setsqlarr['jobsid'] && $this->ajaxReturn(0,'职位id错误！');
        $model = D('AllowanceInfo');
        $setsqlarr['type_alias'] = I('request.type_alias');
        if(!array_key_exists($setsqlarr['type_alias'], $model->type_alias_cn)){
            $this->ajaxReturn(0,'红包类型错误！');
        }
        if(false === $config = F('allowance_config')){
            $config = D('AllowanceConfig')->config_cache();
        }
        $setsqlarr['per_amount'] = I('request.per_amount',0,'intval');
        !$setsqlarr['per_amount'] && $this->ajaxReturn(0,'请填写红包金额！');
        if($setsqlarr['per_amount']<$config['amount_range'][0]) $this->ajaxReturn(0,'红包金额不能小于'.$config['amount_range'][0].'元');
        if($setsqlarr['per_amount']>$config['amount_range'][1]) $this->ajaxReturn(0,'红包金额不能大于'.$config['amount_range'][1].'元');
        $setsqlarr['total_num'] = I('request.total_num',0,'intval');
        !$setsqlarr['total_num'] && $this->ajaxReturn(0,'请填写红包总数量！');
        $setsqlarr['complete_percent'] = I('request.complete_percent',0,'intval');
        $setsqlarr['education'] = I('request.education','','trim');
        $setsqlarr['experience'] = I('request.experience','','trim');
        $setsqlarr['intention_jobs'] = I('request.intention_jobs','','trim');
        $setsqlarr['uid'] = C('visitor.uid');
        $insertid = $model->add_allowance_job($setsqlarr);
        if($insertid){
            $info = $model->find($insertid);
            $paysetarr['pay_resource'] = 'allowance';
            $paysetarr['ordtotal_fee']=$info['pay_amount'];
            $paysetarr['oid']=$info['oid'];
            $paysetarr['payFrom'] = 'pc';
            $paysetarr['type'] = $setsqlarr['payment'];
            $paysetarr['ordsubject'] = $model->type_alias_cn[$info['type_alias']];
            $paysetarr['ordbody'] = $model->type_alias_cn[$info['type_alias']];
            $r = D('Common/Payment')->pay($paysetarr);
            if($setsqlarr['payment']=='wxpay')
            {
                fopen(QSCMS_DATA_PATH.'wxpay/'.$info['oid'].'.tmp', "w") or die("无法打开缓存文件!");
                $_SESSION['wxpay_no'] = $info['oid'];
                $this->ajaxReturn(1,'回调成功',C('qscms_site_dir').'index.php?m=Home&c=Qrcode&a=index&url='.$r);
            }
        }else{
            $this->ajaxReturn(0,'保存失败！');
        }
    }
    /**
     * 检查微信支付回调
     */
    public function check_weixinpay_notify(){
        if(file_exists(QSCMS_DATA_PATH.'wxpay/'.$_SESSION['wxpay_no'].'.tmp')){
            $this->ajaxReturn(0,'回调成功');
        }else{
            $order = D('AllowanceInfo')->where(array('oid'=>$_SESSION['wxpay_no']))->find();
            unset($_SESSION['wxpay_no']);
            $this->ajaxReturn(1,'回调成功',U('Home/Company/jobs_list'));
        }
    }
    /**
     * 面试邀请
     */
    public function interview_add(){
        if(IS_POST){
            $data['jobs_name'] = I('post.jobs_name','','trim');
            $data['fullname'] = I('post.fullname','','trim');
            $data['company_name'] = I('post.company_name','','trim');
            $data['resume_id'] = I('post.resume_id',0,'intval');
            $date = I('post.date','','trim');
            if(!$date){
                $this->ajaxReturn(0,'请选择面试日期');
            }
            $ap = I('post.ap',1,'intval')== 1? 'AM' : 'PM';
            $time = I('post.time',0,'intval');
            if(!$time){
                $this->ajaxReturn(0,'请选择面试时间');
            }
            $data['interview_time'] = strtotime($date.' '.$time.':00:00 '.$ap);
            if($data['interview_time']<time()){
                $this->ajaxReturn(0,'面试时间不能早于当前时间');
            }
            $data['address'] = I('post.address','','trim');
            if(!$data['address']){
                $this->ajaxReturn(0,'请填写面试地点');
            }
            $data['contact'] = I('post.contact','','trim');
            if(!$data['contact']){
                $this->ajaxReturn(0,'请填写联系人');
            }
            $data['telephone'] = I('post.telephone','','trim');
            if(!$data['telephone']){
                $this->ajaxReturn(0,'请填写联系电话');
            }
            $data['notes'] = I('post.notes','','trim');
            $data['sms_notice'] = I('post.sms_notice',0,'intval');
            $data['record_id'] = I('post.record_id',0,'intval');
            $reg = D('AllowanceInterview')->add($data);
            $this->ajaxReturn(1,'操作成功！');
        }else{
            $record_id = I('get.record_id',0,'intval');
            $resume_id = I('get.resume_id',0,'intval');
            !$resume_id && $this->ajaxReturn(0,'请选择简历！');
            $jobsid = I('get.jobsid',0,'intval'); 
            $company = M('CompanyProfile')->field('companyname,district_cn,contact,telephone,landline_tel,address')->where(array('uid'=>C('visitor.uid')))->find();
            $resumeinfo = M('Resume')->find($resume_id);
            $jobs = D('Jobs')->find($jobsid);
            !$jobs && $jobs = D('JobsTmp')->find($jobsid);

            //统计
            $count_map['personal_uid'] = $resumeinfo['uid'];
            $count_map['interview'] = 1;
            $count_map['addtime'] = array('egt',strtotime('-7day'));
            $count_record = D('AllowanceRecord')->where($count_map)->count();
            //评价
            $score = D('AllowanceEvaluateByCompany')->where(array('personal_uid'=>$resumeinfo['uid']))->select();
            $show_score = false;
            if($score){
                $show_score = true;
            }
            if($show_score){
                $avg['education'] = D('AllowanceEvaluateByCompany')->where(array('personal_uid'=>$resumeinfo['uid']))->avg('score_education');
                $avg['experience'] = D('AllowanceEvaluateByCompany')->where(array('personal_uid'=>$resumeinfo['uid']))->avg('score_experience');
            }else{
                $avg = array();
            }

            $this->assign('count_record',$count_record);
            $this->assign('avg',$avg);
            $this->assign('record_id',$record_id);
            $this->assign('resume_id',$resume_id);
            $this->assign('jobs',$jobs);
            $this->assign('company',$company);
            $this->assign('fullname',$resumeinfo['fullname']);
            $html = $this->fetch('ajax_interview');
            $this->ajaxReturn(1,'面试邀请弹窗获取成功！',$html);
        }
    }
    /**
     * 同意面试
     */
    public function agree_interview(){
        $record_id = I('get.record_id',0,'intval');
        if(!$record_id){
            $this->ajaxReturn(0,'参数错误！');
        }
        D('AllowanceRecord')->agree_interview($record_id);
        $this->ajaxReturn(1,'操作成功！');
    }
    /**
     * 拒绝面试
     */
    public function refuse_interview(){
        $record_id = I('get.record_id',0,'intval');
        if(!$record_id){
            $this->ajaxReturn(0,'参数错误！');
        }
        D('AllowanceRecord')->refuse_interview($record_id);
        $this->ajaxReturn(1,'操作成功！');
    }
    /**
     * 确认面试
     */
    public function confirm_interview(){
        $record_id = I('request.record_id',0,'intval');
        if(!$record_id){
            $this->ajaxReturn(0,'参数错误！');
        }
        if(IS_POST){
            $be_score = I('post.score',0,'intval');
            if($be_score){
                $score['uid'] = C('visitor.uid');
                $score['score_experience'] = I('post.score_experience',0,'intval');
                $score['score_education'] = I('post.score_education',0,'intval');
            }else{
                $score = array();
            }
            $r = D('AllowanceRecord')->confirm_interview($record_id,$score);
            if($r){
                $this->ajaxReturn(1,'操作成功！');
            }else{
                $this->ajaxReturn(0,D('AllowanceRecord')->getError());
            }
        }else{
            $html = $this->fetch('confirm_interview');
            $this->ajaxReturn(1,'返回成功',$html);
        }
    }
    /**
     * 确认个人缺席面试
     */
    public function absent_interview(){
        $record_id = I('request.record_id',0,'intval');
        if(!$record_id){
            $this->ajaxReturn(0,'参数错误！');
        }
        if(IS_POST){
            D('AllowanceRecord')->absent_interview($record_id);
            $this->ajaxReturn(1,'操作成功！');
        }else{
            $html = $this->fetch('absent_interview');
            $this->ajaxReturn(1,'回调成功！',$html);
        }
    }
    /**
     * 确认入职
     */
    public function confirm_entry(){
        $record_id = I('get.record_id',0,'intval');
        if(!$record_id){
            $this->ajaxReturn(0,'参数错误！');
        }
        $r = D('AllowanceRecord')->confirm_entry($record_id);
        if($r){
            $this->ajaxReturn(1,'操作成功！');
        }else{
            $this->ajaxReturn(0,D('AllowanceRecord')->getError());
        }
    }
    /**
     * 确认个人缺席入职
     */
    public function absent_entry(){
        $record_id = I('request.record_id',0,'intval');
        if(!$record_id){
            $this->ajaxReturn(0,'参数错误！');
        }
        if(IS_POST){
            D('AllowanceRecord')->absent_entry($record_id);
            $this->ajaxReturn(1,'操作成功！');
        }else{
            $html = $this->fetch('absent_entry');
            $this->ajaxReturn(1,'回调成功！',$html);
        }
    }
    /**
     * 我已面试
     */
    public function ever_interview(){
        $record_id = I('request.record_id',0,'intval');
        if(!$record_id){
            $this->ajaxReturn(0,'参数错误！');
        }
        $company_uid = I('request.company_uid',0,'intval');
        if(IS_POST){
            $setsqlarr['uid'] = C('visitor.uid');
            $setsqlarr['company_uid'] = $company_uid;
            $setsqlarr['record_id'] = $record_id;
            $setsqlarr['interviewer_name'] = I('request.interviewer_name','','trim');
            !$setsqlarr['interviewer_name'] && $this->ajaxReturn(0,'请填写面试官称呼！');
            $setsqlarr['interviewer_age'] = I('request.interviewer_age','','trim');
            !$setsqlarr['interviewer_age'] && $this->ajaxReturn(0,'请填写面试官年龄！');
            $setsqlarr['interviewer_glasses'] = I('request.interviewer_glasses',0,'intval');
            $setsqlarr['interview_time'] = I('request.interview_time','','trim');
            !$setsqlarr['interview_time'] && $this->ajaxReturn(0,'请填写面试时间！');
            $setsqlarr['other'] = I('request.other','','trim');
            $r = D('AllowanceRecord')->ever_interview($setsqlarr);
            if($r){
                $this->ajaxReturn(1,'操作成功！');
            }else{
                $this->ajaxReturn(0,D('AllowanceRecord')->getError());
            }
        }else{
            $this->assign('record_id',$record_id);
            $this->assign('company_uid',$company_uid);
            $html = $this->fetch('ever_interview');
            $this->ajaxReturn(1,'返回成功',$html);
        }
    }
    /**
     * 我已入职
     */
    public function ever_entry(){
        $record_id = I('request.record_id',0,'intval');
        if(!$record_id){
            $this->ajaxReturn(0,'参数错误！');
        }
        if(IS_POST){
            $setsqlarr['record_id'] = $record_id;
            $setsqlarr['department'] = I('request.department','','trim');
            !$setsqlarr['department'] && $this->ajaxReturn(0,'请填写入职部门！');
            $setsqlarr['position'] = I('request.position','','trim');
            !$setsqlarr['position'] && $this->ajaxReturn(0,'请填写职务！');
            $setsqlarr['entry_time'] = I('request.entry_time','','trim');
            !$setsqlarr['entry_time'] && $this->ajaxReturn(0,'请填写入职时间！');
            $r = D('AllowanceRecord')->ever_entry($setsqlarr);
            if($r){
                $this->ajaxReturn(1,'操作成功！');
            }else{
                $this->ajaxReturn(0,D('AllowanceRecord')->getError());
            }
        }else{
            $this->assign('record_id',$record_id);
            $html = $this->fetch('ever_entry');
            $this->ajaxReturn(1,'返回成功',$html);
        }
    }
    
    /**
     * 领取红包检测提示
     */
    public function apply_allowance(){
        $jid = I('request.jid',0,'intval');
        !$jid && $this->ajaxReturn(0,'请选择要投递的职位！');
        $check = D('AllowanceRecord')->check_apply($jid,$this->visitor->info);
        if($check===false){
            $this->ajaxReturn(0,D('AllowanceRecord')->getError());
        }else if($check=='-1'){
            $this->ajaxReturn(2,D('AllowanceRecord')->getError());
        }
        $jobsinfo = $check['jobsinfo'];
        $resume = $check['resume'];
        $tip = D('AllowanceInfo')->check_intention($jobsinfo['allowance_id'],$resume);
        $this->assign('tip',$tip);
        $allowance_info = D('AllowanceInfo')->find($jobsinfo['allowance_id']);
        $this->assign('allowance_info',$allowance_info);
        $this->assign('resumeid',$resume['id']);
        $data['html'] = $this->fetch('apply_allowance');
        $data['tip_status'] = $tip['status'];
        $this->ajaxReturn(1,'回调成功！',$data);
    }
    /**
     * 领取红包
     */
    public function apply_allowance_save(){
        $jid = I('request.jid',0,'intval');
        !$jid && $this->ajaxReturn(0,'请选择要投递的职位！');
        if(false===$config=F('allowance_config')){
            $config = D('Allowance/AllowanceConfig')->config_cache();
        }
        if($config['apply_need_auth_mobile']==1){
            $check_mobile_auth = M('Members')->where(array('uid'=>C('visitor.uid')))->find();
            if($check_mobile_auth['mobile_audit']=="0"){
                $html=$this->fetch('ajax_auth_mobile');
                $this->ajaxReturn(3,'手机验证弹窗获取成功！',$html);
            }
        }
        $need_check_bind = I('get.need_check_bind',0,'intval');
        if($need_check_bind){
            $check_bind = D('Allowance/AllowanceInfo')->check_binding_weixin(C('visitor.uid'));
            if(!$check_bind){
                if(!C('qscms_weixin_apiopen')) $this->ajaxReturn(0,'未配置微信参数！');
                $qrimg = \Common\qscmslib\weixin::qrcode_img('bind',115,115);
                $this->assign('qrimg',$qrimg);
                $html = $this->fetch('allowance_bind_weixin');
                $this->ajaxReturn(2,'请先绑定微信',$html);
            }
        }
        $reg = D('AllowanceRecord')->jobs_apply_add($jid,$this->visitor->info);
        !$reg && $this->ajaxReturn(0,D('AllowanceRecord')->getError());
        $this->ajaxReturn(1,'投递成功！',$reg);
    }
    /**
     * 领取面试红包、入职红包、在职红包后的弹框提示
     */
    public function apply_allowance_okremind(){
        $type = I('get.type','','trim');
        switch($type){
            case 'interview':
                $tpl = 'remind_interview';
                break;
            case 'entry':
                $tpl = 'remind_entry';
                break;
            default:
                $tpl = 'remind_interview';
                break;
        }
        $html = $this->fetch($tpl);
        $this->ajaxReturn(1,'回调成功！',$html);
    }
    /**
     * 面试详情
     */
    public function interview_detail(){
        $record_id = I('request.record_id',0,'intval');
        !$record_id && $this->ajaxReturn(0,'参数错误！');
        $info = D('AllowanceInterview')->where(array('record_id'=>$record_id))->find();
        $this->assign('info',$info);
        $html = $this->fetch('interview_detail');
        $this->ajaxReturn(1,'回调成功！',$html);
    }
    /**
     * 统计
     */
    public function counter(){
        $db_pre = C('DB_PREFIX');
        $this_t = $db_pre.'allowance_info';
        $join = 'right join '.$db_pre.'jobs as j on j.id='.$this_t.'.jobsid';
        $list = D('AllowanceInfo')->join($join)->where(array($this_t.'.status'=>1))->select();
        $data = array('money_count_jobs'=>0,'money_count_amount'=>0);
        foreach ($list as $key => $value) {
            $data['money_count_jobs']++;
            $data['money_count_amount'] += $value['per_amount']*$value['total_num'];
        }
        $this->ajaxReturn(1,'获取数据成功！',$data);
    }
    /**
     * 统计
     */
    public function counter_per_company($uid){
        $db_pre = C('DB_PREFIX');
        $this_t = $db_pre.'allowance_info';
        $join = 'right join '.$db_pre.'jobs as j on j.id='.$this_t.'.jobsid';
        $list = D('AllowanceInfo')->join($join)->where(array($this_t.'.status'=>1,$this_t.'.uid'=>$uid))->select();
        $data = array('money_count_jobs'=>0,'money_count_amount'=>0);
        foreach ($list as $key => $value) {
            $data['money_count_jobs']++;
            $data['money_count_amount'] += $value['per_amount']*$value['total_num'];
        }
        if($data['money_count_jobs']>0 && $data['money_count_amount']>0){
            $this->ajaxReturn(1,'获取数据成功！',$data);
        }else{
            $this->ajaxReturn(0,'获取数据成功！',$data);
        }
    }
    public function explain(){
        $html = $this->fetch('explain');
        $this->ajaxReturn(1,'获取数据成功！',$html);
    }
}
?>