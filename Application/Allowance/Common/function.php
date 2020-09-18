<?php
//个人7天内未确认，自动生成企业退款记录
function check_personal_no_operate(){
    $map['member_turn'] = 2;
    $map['updatetime'] = array('lt',strtotime('-7 days'));
    $list = D('AllowanceRecord')->where($map)->select();
    foreach ($list as $key => $value) {
        $setsqlarr['uid'] = $value['company_uid'];
        $setsqlarr['type'] = 2;
        $setsqlarr['amount'] = $value['amount'];
        $setsqlarr['note'] = '个人7天内未确认，自动生成企业退款记录';
        D('AllowanceRefundmentRecord')->record_add($setsqlarr);
        unset($setsqlarr);
        D('AllowanceRecord')->finish($value['id']);
        //短信提醒
        if(C('qscms_sms_open')==1){
            if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
            //通知个人
            $personal_info=M('Members')->where(array('uid'=>$value['personal_uid']))->find();
            if ($sms['set_allowance_7days_noconfirm_auto_refund_personal']=="1"  && $personal_info['mobile_audit']=="1")
            {
                $sendSms['mobile']=$personal_info['mobile'];
                $sendSms['tpl']='set_allowance_7days_noconfirm_auto_refund_personal';
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'));
                D('Common/Sms')->sendSms('notice',$sendSms);
            }
            //通知企业
            $company_user_info=M('Members')->where(array('uid'=>$value['company_uid']))->find();
            if ($sms['set_allowance_7days_noconfirm_auto_refund_company']=="1"  && $company_user_info['mobile_audit']=="1")
            {
                $sendSms['mobile']=$company_user_info['mobile'];
                $sendSms['tpl']='set_allowance_7days_noconfirm_auto_refund_company';
                $resumeinfo = D('Common/Resume')->find($value['resumeid']);
                $sendSms['data']=array('sitename'=>C('qscms_site_name'),'fullname'=>$resumeinfo['fullname']);
                D('Common/Sms')->sendSms('notice',$sendSms);
            }
        }
        //微信通知
        if(C('apply.Weixin')){
            $baseinfo = D('Allowance/AllowanceInfo')->find($value['info_id']);
            //通知个人
            D('Allowance/AllowanceTplMsg')->set_allowance_7days_noconfirm_auto_refund_personal($value['personal_uid'],$baseinfo['jobsname'].'【'.D('Allowance/AllowanceRecord')->get_type_cn($baseinfo['type_alias']).'】（'.$value['amount'].'元）');
            //通知企业
        	if(!$resumeinfo){
            	$resumeinfo = D('Common/Resume')->find($value['resumeid']);
            }
        	D('Allowance/AllowanceTplMsg')->set_allowance_7days_noconfirm_auto_refund_company($value['company_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$value['amount'].'元）',$resumeinfo['fullname'],$baseinfo['surplus_num']);
        }
    }
}
//企业24小时内未确认面试，自动打款给个人
function check_company_no_operate(){
    $map['member_turn'] = 1;
    $map['interview'] = 1;
    $map['step'] = 40;
    $map['updatetime'] = array('lt',strtotime('-24 hours'));
    $list = D('AllowanceRecord')->where($map)->select();
    foreach ($list as $key => $value) {
        $pay_status = D('AllowanceRecord')->pay($value['id']);
        //短信提醒
        if(C('qscms_sms_open')==1){
            if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
            //通知个人
            $personal_info=M('Members')->where(array('uid'=>$value['personal_uid']))->find();
            if ($sms['set_allowance_24hours_noconfirm_auto_putout_personal']=="1"  && $personal_info['mobile_audit']=="1")
            {
                $sendSms['mobile']=$personal_info['mobile'];
                $sendSms['tpl']='set_allowance_24hours_noconfirm_auto_putout_personal';
	            $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$value['company_uid']))->find();
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'companyname'=>$companyinfo['companyname'],'amount'=>$value['amount']);
                D('Common/Sms')->sendSms('notice',$sendSms);
            }
            //通知企业
            $company_user_info=M('Members')->where(array('uid'=>$value['company_uid']))->find();
            if ($sms['set_allowance_24hours_noconfirm_auto_putout_company']=="1"  && $company_user_info['mobile_audit']=="1")
            {
                $sendSms['mobile']=$company_user_info['mobile'];
                $sendSms['tpl']='set_allowance_24hours_noconfirm_auto_putout_company';
                $resumeinfo = D('Common/Resume')->find($value['resumeid']);
                $sendSms['data']=array('sitename'=>C('qscms_site_name'),'fullname'=>$resumeinfo['fullname'],'amount'=>$value['amount']);
                D('Common/Sms')->sendSms('notice',$sendSms);
            }
        }
        //微信通知
        if(C('apply.Weixin')){
            $baseinfo = D('Allowance/AllowanceInfo')->find($value['info_id']);
            //通知个人
            if(false===$config=F('allowance_config')){
				$config = D('Allowance/AllowanceConfig')->config_cache();
			}
            if($config['service_charge']>0){
				$amount = $value['amount']*(1-$config['service_charge']/100);
			}else{
				$amount = $value['amount'];
			}
	        $amount = $amount<1?1:$amount;
            D('Allowance/AllowanceTplMsg')->set_allowance_24hours_noconfirm_auto_putout_personal($value['personal_uid'],$baseinfo['jobsname'].'【'.D('Allowance/AllowanceRecord')->get_type_cn($baseinfo['type_alias']).'】（'.$value['amount'].'元）',$value['amount']-$amount,$amount);
            //通知企业
        	if(!$resumeinfo){
            	$resumeinfo = D('Common/Resume')->find($value['resumeid']);
            }
        	D('Allowance/AllowanceTplMsg')->set_allowance_7days_noconfirm_auto_refund_company($value['company_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$value['amount'].'元）',$resumeinfo['fullname'],$baseinfo['surplus_num']);
        }
    }
}
//企业72小时内未同意/拒绝面试，自动生成退款
function check_company_no_agree_refuse(){
    $map['member_turn'] = 1;
    $map['interview'] = 1;
    $map['step'] = 10;
    $map['updatetime'] = array('lt',strtotime('-72 hours'));
    $list = D('AllowanceRecord')->where($map)->select();
    foreach ($list as $key => $value) {
        $setsqlarr['step'] = 30;
        $setsqlarr['step_finish'] = 1;
        $setsqlarr['updatetime'] = time();
        $setsqlarr['status'] = 2;
        $setsqlarr['member_turn'] = 0;
        D('AllowanceRecord')->where(array('id'=>$value['id']))->save($setsqlarr);
        D('AllowanceRecordLog')->add(array('step'=>30,'record_id'=>$value['id'],'note'=>'企业3天内未处理面试申请，系统判定拒绝面试，自动发起退款'));
        $refund['uid'] = $value['company_uid'];
        $refund['type'] = 2;
        $refund['amount'] = $value['amount'];
        $refund['note'] = '企业3天内未处理面试申请，系统判定拒绝面试，自动发起退款';
        $insert_id = D('AllowanceRefundmentRecord')->record_add($refund);
        //短信提醒
        if($insert_id && C('qscms_sms_open')==1){
            if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
            //通知个人
            $personal_info=M('Members')->where(array('uid'=>$value['personal_uid']))->find();
            if ($sms['set_allowance_3days_noagree_auto_refund_personal']=="1"  && $personal_info['mobile_audit']=="1")
            {
                $sendSms['mobile']=$personal_info['mobile'];
                $sendSms['tpl']='set_allowance_3days_noagree_auto_refund_personal';
	            $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$value['company_uid']))->find();
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'companyname'=>$companyinfo['companyname']);
                D('Common/Sms')->sendSms('notice',$sendSms);
            }
            //通知企业
            $company_user_info=M('Members')->where(array('uid'=>$value['company_uid']))->find();
            if ($sms['set_allowance_3days_noagree_auto_refund_company']=="1"  && $company_user_info['mobile_audit']=="1")
            {
                $sendSms['mobile']=$company_user_info['mobile'];
                $sendSms['tpl']='set_allowance_3days_noagree_auto_refund_company';
                $sendSms['data']=array('sitename'=>C('qscms_site_name'));
                D('Common/Sms')->sendSms('notice',$sendSms);
            }
        }
        //微信通知
        if($insert_id && C('apply.Weixin')){
            $baseinfo = D('Allowance/AllowanceInfo')->find($value['info_id']);
            //通知个人
	        $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$value['company_uid']))->find();
            D('Allowance/AllowanceTplMsg')->set_allowance_3days_noagree_auto_refund_personal($value['personal_uid'],$baseinfo['jobsname'].'【'.D('Allowance/AllowanceRecord')->get_type_cn($baseinfo['type_alias']).'】（'.$value['amount'].'元）',$companyinfo['companyname']);
            //通知企业
        	if(!$resumeinfo){
            	$resumeinfo = D('Common/Resume')->find($value['resumeid']);
            }
        	D('Allowance/AllowanceTplMsg')->set_allowance_3days_noagree_auto_refund_company($value['company_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$value['amount'].'元）',$resumeinfo['fullname']);
        }
    }
}