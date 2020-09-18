<?php
namespace Allowance\Model;
use Think\Model;
class AllowanceRecordModel extends Model{
	//是否需要审核
	private $need_audit;
	//配置信息
	private $config;
	//流程步骤
	const APPLY_JOB = 1;//申请面试
	const APPLY_INTERVIEW = 10;//申请面试
	const AGREE_INTERVIEW = 20;//同意面试
	const REFUSE_INTERVIEW = 30;//拒绝面试
	const EVER_INTERVIEW = 40;//我已面试
	const CONFIRM_INTERVIEW = 50;//确认个人已面试
	const ABSENT_INTERVIEW = 60;//确认个人缺席
	const APPLY_ENTRY = 70;//申请入职
	const EVER_ENTRY = 71;//我已入职
	const CONFIRM_ENTRY = 80;//确定入职
	const ABSENT_ENTRY = 90;//缺席（入职）
	//打赏状态
	const STATUS_DOING = 0;//进行中
	const STATUS_AUDIT = 1;//待审核
	const STATUS_FINISH = 2;//已终止
	const STATUS_GRAND = 3;//已发放
	const STATUS_ARBITRATE = 4;//待仲裁
	//待处理
	const COMPANY_TURN = 1;
	const PERSONAL_TURN = 2;
	const NOBODY_TURN = 0;
	public $status_cn;
	private $_error='未知错误';
	public $type_cn = array('apply'=>'投递红包','interview'=>'面试红包','entry'=>'入职红包');
	public function __construct(){
		parent::__construct();
		$this->status_cn = array(self::STATUS_DOING=>'进行中',self::STATUS_AUDIT=>'待审核',self::STATUS_ARBITRATE=>'待仲裁',self::STATUS_FINISH=>'已终止',self::STATUS_GRAND=>'已发放');
		if(false===$this->config=F('allowance_config')){
			$this->config = D('Allowance/AllowanceConfig')->config_cache();
		}
		$this->need_audit = $this->config['apply_audit_open'];
	}
	protected function _before_insert(&$data,$options){
		$timestamp = time();
		if(!$data['apply']){
			$data['apply'] = 0;
		}
		if(!$data['interview']){
			$data['interview'] = 0;
		}
		if(!$data['entry']){
			$data['entry'] = 0;
		}
		$data['status'] = self::STATUS_DOING;
		if($data['apply']==1){
			$data['step'] = self::APPLY_JOB;
			$data['member_turn'] = self::COMPANY_TURN;
			$this->need_audit==1 && $data['status'] = self::STATUS_AUDIT;
		}else if($data['interview']==1){
			$data['step'] = self::APPLY_INTERVIEW;
			$data['member_turn'] = self::COMPANY_TURN;
		}else if($data['entry']==1){
			$data['step'] = self::APPLY_ENTRY;
			$data['member_turn'] = self::PERSONAL_TURN;
		}
		
		$data['step_finish'] = 0;
		$data['addtime'] = $timestamp;
		$data['updatetime'] = $timestamp;
		$data['pay_status'] = 0;
		$data['pay_fail_reason'] = '';
		$data['auto_finish'] = 0;
		$data['audit_note'] = '';
		$data['audit_man'] = '';
		$data['arbitrate_note'] = '';
		$data['arbitrate_man'] = '';
		$data['oid'] = "Com".date('ymd',$timestamp).date('His',$timestamp);
	}
	protected function _after_insert($data, $option){
		//每增加一个红包申请，对应红包数量减一
        D('Allowance/AllowanceInfo')->surplusnum_minus($data['info_id']);
        //增加操作日志记录
        D('Allowance/AllowanceRecordLog')->add(array('step'=>$data['step'],'record_id'=>$data['id'],'note'=>'申请领取红包'));
    }
    public function get_status_cn($status){
    	return $this->status_cn[$status];
    }
    public function get_type_cn($type){
    	return $this->type_cn[$type];
    }
    /**
     * 7天内未确认自动结束流程
     */
    public function finish($record_id){
    	$this->where(array('id'=>$record_id))->save(array('auto_finish'=>1,'status'=>self::STATUS_FINISH,'member_turn'=>self::NOBODY_TURN,'step_finish'=>1,'updatetime'=>time()));
    	return true;
    }
    /**
     * 管理员结束流程
     */
    public function admin_finish($record_id){
    	$this->where(array('id'=>$record_id))->save(array('status'=>self::STATUS_FINISH,'member_turn'=>self::NOBODY_TURN,'step_finish'=>1,'updatetime'=>time()));
    	return true;
    }
	/**
	 * 申请红包
	 */
	public function add_apply($data,$resumeinfo=false){
		if($resumeinfo){
			$check_intention = D('Allowance/AllowanceInfo')->check_intention($data['info_id'],$resumeinfo);
			if(!$check_intention['status']){
				$this->_error = '该简历不符合设置条件';
				return false;
			}
		}
		$check_bind = D('AllowanceInfo')->check_binding_weixin($data['personal_uid']);
		if(!$check_bind){
			$this->_error = '请先绑定微信账号，用微信扫描左侧二维码进行绑定。';
			return false;
		}
		$surplusnum = D('Allowance/AllowanceInfo')->check_surplusnum($data['info_id']);
		if($surplusnum<=0){
			$this->_error = '红包数量不足';
			return false;
		}
		$check_latest_signon = $this->where(array('uid'=>$data['personal_uid']))->order('id desc')->find();
		if(time()-$check_latest_signon['addtime']<=$this->config['signon_timespace']){
			$this->_error = '同一账号领取红包时间间隔不得小于'.$this->config['signon_timespace'].'秒';
			return false;
		}
		if($this->config['apply_need_auth_mobile']==1){
			$check_mobile_auth = M('Members')->where(array('uid'=>$data['personal_uid']))->find();
			if($check_mobile_auth['mobile_audit']=="0"){
				$this->_error = '无法领取红包，请先验证手机号！';
				return false;
			}
		}
		$insert_id = $this->add($data);
		if($insert_id){
			if($this->need_audit==0 && $data['apply']==1){
				$pay_status = $this->pay($insert_id,$check_bind);
				//短信通知
				if(C('qscms_sms_open')==1){
					if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
                    //通知个人
					$baseinfo = D('AllowanceInfo')->find($data['info_id']);
                    $personal_info=M('Members')->where(array('uid'=>$data['personal_uid']))->find();
                    if ($sms['set_allowance_apply_signon_personal']=="1"  && $personal_info['mobile_audit']=="1")
                    {
                        $sendSms['mobile']=$personal_info['mobile'];
                        $sendSms['tpl']='set_allowance_apply_signon_personal';
			            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'jobsname'=>$baseinfo['jobsname']);
                        D('Common/Sms')->sendSms('notice',$sendSms);
                    }
                    //通知企业
                    $company_user_info=M('Members')->where(array('uid'=>$data['company_uid']))->find();
                    if ($sms['set_allowance_apply_signon_company']=="1"  && $company_user_info['mobile_audit']=="1")
                    {
                        $sendSms['mobile']=$company_user_info['mobile'];
                        $sendSms['tpl']='set_allowance_apply_signon_company';
                        if(!$resumeinfo){
			            	$resumeinfo = D('Common/Resume')->find($data['resumeid']);
			            }
                        $sendSms['data']=array('sitename'=>C('qscms_site_name'),'fullname'=>$resumeinfo['fullname'],'jobsname'=>$baseinfo['jobsname'],'num'=>$baseinfo['surplus_num']);
                        D('Common/Sms')->sendSms('notice',$sendSms);
                    }
				}
				//微信通知
				if(C('apply.Weixin')){
					$baseinfo = D('AllowanceInfo')->find($data['info_id']);
	        		//通知个人
	        		if($this->config['service_charge']>0){
						$amount = $data['amount']*(1-$this->config['service_charge']/100);
					}else{
						$amount = $data['amount'];
					}
			        $amount = $amount<1?1:$amount;
	            	D('Allowance/AllowanceTplMsg')->set_allowance_apply_signon_personal($data['personal_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】',$data['amount']-$amount,$amount);
	            	//通知企业
	            	if(!$resumeinfo){
		            	$resumeinfo = D('Common/Resume')->find($data['resumeid']);
		            }
	            	D('Allowance/AllowanceTplMsg')->set_allowance_apply_signon_company($data['company_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$data['amount'].'元）',$resumeinfo['fullname'],$baseinfo['surplus_num']);
				}
			}
			//短信通知
			if(C('qscms_sms_open')==1){
				if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
				//投递红包 - 需要审核的
				if($data['apply']==1 && $this->need_audit==1){
					$baseinfo = D('AllowanceInfo')->find($data['info_id']);
					//通知个人
					$personal_info=M('Members')->where(array('uid'=>$data['personal_uid']))->find();
			    	if ($sms['set_allowance_apply_audit_personal']=="1"  && $personal_info['mobile_audit']=="1")
					{
			            $sendSms['mobile']=$personal_info['mobile'];
			            $sendSms['tpl']='set_allowance_apply_audit_personal';
			            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'jobsname'=>$baseinfo['jobsname']);
			            D('Common/Sms')->sendSms('notice',$sendSms);
					}
					//通知企业
					$company_user_info=M('Members')->where(array('uid'=>$data['company_uid']))->find();
			    	if ($sms['set_allowance_apply_audit_company']=="1"  && $company_user_info['mobile_audit']=="1")
					{
			            $sendSms['mobile']=$company_user_info['mobile'];
			            $sendSms['tpl']='set_allowance_apply_audit_company';
			            if(!$resumeinfo){
			            	$resumeinfo = D('Common/Resume')->find($data['resumeid']);
			            }
			            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'jobsname'=>$baseinfo['jobsname'],'fullname'=>$resumeinfo['fullname']);
			            D('Common/Sms')->sendSms('notice',$sendSms);
					}
				}
				//面试红包
				if($data['interview']==1){
					$baseinfo = D('AllowanceInfo')->find($data['info_id']);
					//通知个人
					$personal_info=M('Members')->where(array('uid'=>$data['personal_uid']))->find();
			    	if ($sms['set_allowance_apply_interview_personal']=="1"  && $personal_info['mobile_audit']=="1")
					{
			            $sendSms['mobile']=$personal_info['mobile'];
			            $sendSms['tpl']='set_allowance_apply_interview_personal';
			            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'jobsname'=>$baseinfo['jobsname']);
			            D('Common/Sms')->sendSms('notice',$sendSms);
					}
					//通知企业
					$company_user_info=M('Members')->where(array('uid'=>$data['company_uid']))->find();
			    	if ($sms['set_allowance_apply_interview_company']=="1"  && $company_user_info['mobile_audit']=="1")
					{
			            $sendSms['mobile']=$company_user_info['mobile'];
			            $sendSms['tpl']='set_allowance_apply_interview_company';
			            if(!$resumeinfo){
			            	$resumeinfo = D('Common/Resume')->find($data['resumeid']);
			            }
			            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'jobsname'=>$baseinfo['jobsname'],'fullname'=>$resumeinfo['fullname']);
			            D('Common/Sms')->sendSms('notice',$sendSms);
					}
				}
				//入职红包
				if($data['entry']==1){
					$baseinfo = D('AllowanceInfo')->find($data['info_id']);
					//通知个人
					$personal_info=M('Members')->where(array('uid'=>$data['personal_uid']))->find();
			    	if ($sms['set_allowance_apply_entry_jobs_personal']=="1"  && $personal_info['mobile_audit']=="1")
					{
			            $sendSms['mobile']=$personal_info['mobile'];
			            $sendSms['tpl']='set_allowance_apply_entry_jobs_personal';
			            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'jobsname'=>$baseinfo['jobsname']);
			            D('Common/Sms')->sendSms('notice',$sendSms);
					}
					//通知企业
					$company_user_info=M('Members')->where(array('uid'=>$data['company_uid']))->find();
			    	if ($sms['set_allowance_apply_entry_jobs_company']=="1"  && $company_user_info['mobile_audit']=="1")
					{
			            $sendSms['mobile']=$company_user_info['mobile'];
			            $sendSms['tpl']='set_allowance_apply_entry_jobs_company';
			            if(!$resumeinfo){
			            	$resumeinfo = D('Common/Resume')->find($data['resumeid']);
			            }
			            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'jobsname'=>$baseinfo['jobsname'],'fullname'=>$resumeinfo['fullname']);
			            D('Common/Sms')->sendSms('notice',$sendSms);
					}
				}
			}
	        //微信提醒
	        if(C('apply.Weixin')){
	        	if($data['apply']==1 && $this->need_audit==1){
					$baseinfo = D('AllowanceInfo')->find($data['info_id']);
	        		//通知个人
	            	D('Allowance/AllowanceTplMsg')->set_allowance_apply_audit_personal($data['personal_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$data['amount'].'元）');
	            	//通知企业
	            	if(!$resumeinfo){
		            	$resumeinfo = D('Common/Resume')->find($data['resumeid']);
		            }
	            	D('Allowance/AllowanceTplMsg')->set_allowance_apply_audit_company($data['company_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$data['amount'].'元）',$resumeinfo['fullname']);
	            }
	            //面试红包
				if($data['interview']==1){
					$baseinfo = D('AllowanceInfo')->find($data['info_id']);
	        		//通知个人
	            	D('Allowance/AllowanceTplMsg')->set_allowance_apply_interview_personal($data['personal_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$data['amount'].'元）');
	            	//通知企业
	            	if(!$resumeinfo){
		            	$resumeinfo = D('Common/Resume')->find($data['resumeid']);
		            }
	            	D('Allowance/AllowanceTplMsg')->set_allowance_apply_interview_company($data['company_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$data['amount'].'元）',$resumeinfo['fullname']);
				}
	            //入职红包
				if($data['entry']==1){
					$baseinfo = D('AllowanceInfo')->find($data['info_id']);
	        		//通知个人
	            	D('Allowance/AllowanceTplMsg')->set_allowance_apply_entry_personal($data['personal_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$data['amount'].'元）');
	            	//通知企业
	            	if(!$resumeinfo){
		            	$resumeinfo = D('Common/Resume')->find($data['resumeid']);
		            }
	            	D('Allowance/AllowanceTplMsg')->set_allowance_apply_entry_company($data['company_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$data['amount'].'元）',$resumeinfo['fullname']);
				}
	        }
			return true;
		}else{
			return false;
		}
	}
	/**
	 * 同意面试
	 */
	public function agree_interview($id){
		$setsqlarr['step'] = self::AGREE_INTERVIEW;
		$setsqlarr['updatetime'] = time();
		$setsqlarr['member_turn'] = self::PERSONAL_TURN;
		$this->where(array('id'=>$id))->save($setsqlarr);
		D('Allowance/AllowanceRecordLog')->add(array('step'=>self::AGREE_INTERVIEW,'record_id'=>$id,'note'=>'企业同意面试'));
		//短信通知
		if(C('qscms_sms_open')==1){
			if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
			$recordinfo = $this->find($id);
			//通知个人
			$personal_info=M('Members')->where(array('uid'=>$recordinfo['personal_uid']))->find();
	    	if ($sms['set_allowance_agree_interview_personal']=="1"  && $personal_info['mobile_audit']=="1")
			{
	            $sendSms['mobile']=$personal_info['mobile'];
	            $sendSms['tpl']='set_allowance_agree_interview_personal';
	            $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'companyname'=>$companyinfo['companyname']);
	            D('Common/Sms')->sendSms('notice',$sendSms);
			}
			//通知企业
			$company_user_info=M('Members')->where(array('uid'=>$recordinfo['company_uid']))->find();
	    	if ($sms['set_allowance_agree_interview_company']=="1"  && $company_user_info['mobile_audit']=="1")
			{
	            $sendSms['mobile']=$company_user_info['mobile'];
	            $sendSms['tpl']='set_allowance_agree_interview_company';
	            $resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'fullname'=>$resumeinfo['fullname']);
	            D('Common/Sms')->sendSms('notice',$sendSms);
			}
		}
		//微信通知
		if(C('apply.Weixin')){
			$recordinfo = $this->find($id);
			$baseinfo = D('Allowance/AllowanceInfo')->find($recordinfo['info_id']);
			//通知个人
	        $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
	    	D('Allowance/AllowanceTplMsg')->set_allowance_agree_interview_personal($recordinfo['personal_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$companyinfo['companyname']);
	    	//通知企业
	    	if(!$resumeinfo){
	        	$resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
	        }
	    	D('Allowance/AllowanceTplMsg')->set_allowance_agree_interview_company($recordinfo['company_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$resumeinfo['fullname']);
		}
		return true;
	}
	/**
	 * 拒绝面试
	 */
	public function refuse_interview($id){
		$setsqlarr['step'] = self::REFUSE_INTERVIEW;
		$setsqlarr['step_finish'] = 1;
		$setsqlarr['updatetime'] = time();
		$setsqlarr['status'] = self::STATUS_FINISH;
		$setsqlarr['member_turn'] = self::NOBODY_TURN;
		$this->where(array('id'=>$id))->save($setsqlarr);
		D('Allowance/AllowanceRecordLog')->add(array('step'=>self::REFUSE_INTERVIEW,'record_id'=>$id,'note'=>'企业拒绝面试，自动发起退款'));
		$info = $this->find($id);
		$refund['uid'] = $info['company_uid'];
        $refund['type'] = 2;
        $refund['amount'] = $info['amount'];
        $refund['note'] = '企业拒绝面试，自动发起退款';
        D('Allowance/AllowanceRefundmentRecord')->record_add($refund);
        //短信通知
		if(C('qscms_sms_open')==1){
			if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
			$recordinfo = $this->find($id);
			//通知个人
			$personal_info=M('Members')->where(array('uid'=>$recordinfo['personal_uid']))->find();
	    	if ($sms['set_allowance_refuse_interview_personal']=="1"  && $personal_info['mobile_audit']=="1")
			{
	            $sendSms['mobile']=$personal_info['mobile'];
	            $sendSms['tpl']='set_allowance_refuse_interview_personal';
	            $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'companyname'=>$companyinfo['companyname']);
	            D('Common/Sms')->sendSms('notice',$sendSms);
			}
			//通知企业
			$company_user_info=M('Members')->where(array('uid'=>$recordinfo['company_uid']))->find();
	    	if ($sms['set_allowance_refuse_interview_company']=="1"  && $company_user_info['mobile_audit']=="1")
			{
	            $sendSms['mobile']=$company_user_info['mobile'];
	            $sendSms['tpl']='set_allowance_refuse_interview_company';
	            $resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'fullname'=>$resumeinfo['fullname']);
	            D('Common/Sms')->sendSms('notice',$sendSms);
			}
		}
		//微信通知
		if(C('apply.Weixin')){
			$recordinfo = $this->find($id);
			$baseinfo = D('Allowance/AllowanceInfo')->find($recordinfo['info_id']);
			//通知个人
	        $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
	    	D('Allowance/AllowanceTplMsg')->set_allowance_refuse_interview_personal($recordinfo['personal_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$companyinfo['companyname']);
	    	//通知企业
	    	if(!$resumeinfo){
	        	$resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
	        }
	    	D('Allowance/AllowanceTplMsg')->set_allowance_refuse_interview_company($recordinfo['company_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$resumeinfo['fullname']);
		}
		return true;
	}
	/**
	 * 我已面试
	 */
	public function ever_interview($interview_info){
		$setsqlarr['step'] = self::EVER_INTERVIEW;
		$setsqlarr['updatetime'] = time();
		$setsqlarr['member_turn'] = self::COMPANY_TURN;
		$this->where(array('id'=>$interview_info['record_id']))->save($setsqlarr);
		D('Allowance/AllowanceRecordLog')->add(array('step'=>self::EVER_INTERVIEW,'record_id'=>$interview_info['record_id'],'note'=>'个人确认已面试'));
		if(!empty($interview_info)){
			D('Allowance/AllowanceEvaluateByPersonal')->add($interview_info);
		}
		//短信通知
		if(C('qscms_sms_open')==1){
			if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
			$recordinfo = $this->find($interview_info['record_id']);
			//通知个人
			$personal_info=M('Members')->where(array('uid'=>$recordinfo['personal_uid']))->find();
	    	if ($sms['set_allowance_ever_interview_personal']=="1"  && $personal_info['mobile_audit']=="1")
			{
	            $sendSms['mobile']=$personal_info['mobile'];
	            $sendSms['tpl']='set_allowance_ever_interview_personal';
	            $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'companyname'=>$companyinfo['companyname']);
	            D('Common/Sms')->sendSms('notice',$sendSms);
			}
			//通知企业
			$company_user_info=M('Members')->where(array('uid'=>$recordinfo['company_uid']))->find();
	    	if ($sms['set_allowance_ever_interview_company']=="1"  && $company_user_info['mobile_audit']=="1")
			{
	            $sendSms['mobile']=$company_user_info['mobile'];
	            $sendSms['tpl']='set_allowance_ever_interview_company';
	            $resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'fullname'=>$resumeinfo['fullname']);
	            D('Common/Sms')->sendSms('notice',$sendSms);
			}
		}
		//微信通知
		if(C('apply.Weixin')){
			$recordinfo = $this->find($id);
			$baseinfo = D('Allowance/AllowanceInfo')->find($recordinfo['info_id']);
			//通知个人
	        $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
	    	D('Allowance/AllowanceTplMsg')->set_allowance_ever_interview_personal($recordinfo['personal_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$companyinfo['companyname']);
	    	//通知企业
	    	if(!$resumeinfo){
	        	$resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
	        }
	    	D('Allowance/AllowanceTplMsg')->set_allowance_ever_interview_company($recordinfo['company_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$resumeinfo['fullname']);
		}
		return true;
	}
	/**
	 * 确认个人已面试
	 */
	public function confirm_interview($id,$scoreinfo = array()){
		$recordinfo = $this->find($id);
		$data['step'] = self::CONFIRM_INTERVIEW;
		$data['updatetime'] = time();
		$data['member_turn'] = self::NOBODY_TURN;
		if($recordinfo['interview']==1){
			$data['status'] = self::STATUS_GRAND;
			$data['step_finish'] = 1;
		}
		$this->where(array('id'=>$id))->save($data);
		D('Allowance/AllowanceRecordLog')->add(array('step'=>self::CONFIRM_INTERVIEW,'record_id'=>$id,'note'=>'企业确认已面试'));
		if(!empty($scoreinfo)){
			$scoreinfo['personal_uid'] = $recordinfo['personal_uid'];
			$scoreinfo['record_id'] = $id;
			D('Allowance/AllowanceEvaluateByCompany')->add($scoreinfo);
		}
		$pay_status = $this->pay($id);
		//短信通知
		if(C('qscms_sms_open')==1){
			if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
			//通知个人
			$personal_info=M('Members')->where(array('uid'=>$recordinfo['personal_uid']))->find();
	    	if ($sms['set_allowance_confirm_interview_personal']=="1"  && $personal_info['mobile_audit']=="1")
			{
	            $sendSms['mobile']=$personal_info['mobile'];
	            $sendSms['tpl']='set_allowance_confirm_interview_personal';
	            $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'companyname'=>$companyinfo['companyname']);
	            D('Common/Sms')->sendSms('notice',$sendSms);
			}
			//通知企业
			$company_user_info=M('Members')->where(array('uid'=>$recordinfo['company_uid']))->find();
	    	if ($sms['set_allowance_confirm_interview_company']=="1"  && $company_user_info['mobile_audit']=="1")
			{
	            $sendSms['mobile']=$company_user_info['mobile'];
	            $sendSms['tpl']='set_allowance_confirm_interview_company';
	            $resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'fullname'=>$resumeinfo['fullname']);
	            D('Common/Sms')->sendSms('notice',$sendSms);
			}
		}
		//微信通知
		if(C('apply.Weixin')){
			$recordinfo = $this->find($id);
			$baseinfo = D('Allowance/AllowanceInfo')->find($recordinfo['info_id']);
			//通知个人
	        $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
	        if($this->config['service_charge']>0){
				$amount = $recordinfo['amount']*(1-$this->config['service_charge']/100);
			}else{
				$amount = $recordinfo['amount'];
			}
	        $amount = $amount<1?1:$amount;
	    	D('Allowance/AllowanceTplMsg')->set_allowance_confirm_interview_personal($recordinfo['personal_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$companyinfo['companyname'],$recordinfo['amount']-$amount,$amount);
	    	//通知企业
	    	if(!$resumeinfo){
	        	$resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
	        }
	    	D('Allowance/AllowanceTplMsg')->set_allowance_confirm_interview_company($recordinfo['company_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$resumeinfo['fullname'],$baseinfo['surplus_num']);
		}
		return true;
	}
	/**
	 * 确认个人缺席
	 */
	public function absent_interview($id){
		$this->where(array('id'=>$id))->save(array('step'=>self::ABSENT_INTERVIEW,'step_finish'=>1,'updatetime'=>time(),'member_turn'=>self::NOBODY_TURN,'status'=>self::STATUS_ARBITRATE));
		D('Allowance/AllowanceRecordLog')->add(array('step'=>self::ABSENT_INTERVIEW,'record_id'=>$id,'note'=>'企业确认个人缺席'));
		//短信通知
		if(C('qscms_sms_open')==1){
			if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
			$recordinfo = $this->find($id);
			//通知个人
			$personal_info=M('Members')->where(array('uid'=>$recordinfo['personal_uid']))->find();
	    	if ($sms['set_allowance_absent_interview_personal']=="1"  && $personal_info['mobile_audit']=="1")
			{
	            $sendSms['mobile']=$personal_info['mobile'];
	            $sendSms['tpl']='set_allowance_absent_interview_personal';
	            $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'companyname'=>$companyinfo['companyname']);
	            D('Common/Sms')->sendSms('notice',$sendSms);
			}
			//通知企业
			$company_user_info=M('Members')->where(array('uid'=>$recordinfo['company_uid']))->find();
	    	if ($sms['set_allowance_absent_interview_company']=="1"  && $company_user_info['mobile_audit']=="1")
			{
	            $sendSms['mobile']=$company_user_info['mobile'];
	            $sendSms['tpl']='set_allowance_absent_interview_company';
	            $resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'fullname'=>$resumeinfo['fullname']);
	            D('Common/Sms')->sendSms('notice',$sendSms);
			}
		}
		//微信通知
		if(C('apply.Weixin')){
			$recordinfo = $this->find($id);
			$baseinfo = D('Allowance/AllowanceInfo')->find($recordinfo['info_id']);
			//通知个人
	        $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
	    	D('Allowance/AllowanceTplMsg')->set_allowance_absent_interview_personal($recordinfo['personal_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$companyinfo['companyname']);
	    	//通知企业
	    	if(!$resumeinfo){
	        	$resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
	        }
	    	D('Allowance/AllowanceTplMsg')->set_allowance_absent_interview_company($recordinfo['company_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$resumeinfo['fullname'],$baseinfo['surplus_num']);
		}
		return true;
	}
	/**
	 * 我已入职
	 */
	public function ever_entry($info){
		$this->where(array('id'=>$info['record_id']))->save(array('step'=>self::EVER_ENTRY,'updatetime'=>time(),'member_turn'=>self::COMPANY_TURN));
		D('Allowance/AllowanceRecordLog')->add(array('step'=>self::EVER_ENTRY,'record_id'=>$info['record_id'],'note'=>'个人确认已入职'));
		if(!empty($info)){
			D('Allowance/AllowanceEntryInfo')->add($info);
		}
		//短信通知
		if(C('qscms_sms_open')==1){
			if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
			$recordinfo = $this->find($info['record_id']);
			//通知个人
			$personal_info=M('Members')->where(array('uid'=>$recordinfo['personal_uid']))->find();
	    	if ($sms['set_allowance_ever_entry_personal']=="1"  && $personal_info['mobile_audit']=="1")
			{
	            $sendSms['mobile']=$personal_info['mobile'];
	            $sendSms['tpl']='set_allowance_ever_entry_personal';
	            $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'companyname'=>$companyinfo['companyname']);
	            D('Common/Sms')->sendSms('notice',$sendSms);
			}
			//通知企业
			$company_user_info=M('Members')->where(array('uid'=>$recordinfo['company_uid']))->find();
	    	if ($sms['set_allowance_ever_entry_company']=="1"  && $company_user_info['mobile_audit']=="1")
			{
	            $sendSms['mobile']=$company_user_info['mobile'];
	            $sendSms['tpl']='set_allowance_ever_entry_company';
	            $resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'fullname'=>$resumeinfo['fullname']);
	            D('Common/Sms')->sendSms('notice',$sendSms);
			}
		}
		//微信通知
		if(C('apply.Weixin')){
			$recordinfo = $this->find($id);
			$baseinfo = D('Allowance/AllowanceInfo')->find($recordinfo['info_id']);
			//通知个人
	        $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
	    	D('Allowance/AllowanceTplMsg')->set_allowance_ever_entry_personal($recordinfo['personal_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$companyinfo['companyname']);
	    	//通知企业
	    	if(!$resumeinfo){
	        	$resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
	        }
	    	D('Allowance/AllowanceTplMsg')->set_allowance_ever_entry_company($recordinfo['company_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$resumeinfo['fullname']);
		}
		return true;
	}
	/**
	 * 确定入职
	 */
	public function confirm_entry($id){
		$data['updatetime'] = time();
		$data['step'] = self::CONFIRM_ENTRY;
		$data['member_turn'] = self::NOBODY_TURN;
		// if($this->need_audit==1){
		// 	$data['status'] = self::STATUS_AUDIT;
		// }
		$this->where(array('id'=>$id))->save($data);
		D('Allowance/AllowanceRecordLog')->add(array('step'=>self::CONFIRM_ENTRY,'record_id'=>$id,'note'=>'企业确认个人已入职'));
		// if($this->need_audit!=1){
		$pay_status = $this->pay($id);
		//短信提醒
        if(C('qscms_sms_open')==1){
            if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
            $recordinfo = $this->find($id);
            //通知个人
            $personal_info=M('Members')->where(array('uid'=>$recordinfo['personal_uid']))->find();
            if ($sms['set_allowance_confirm_entry_personal']=="1"  && $personal_info['mobile_audit']=="1")
            {
                $sendSms['mobile']=$personal_info['mobile'];
                $sendSms['tpl']='set_allowance_confirm_entry_personal';
	            $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'companyname'=>$companyinfo['companyname']);
                D('Common/Sms')->sendSms('notice',$sendSms);
            }
            //通知企业
            $company_user_info=M('Members')->where(array('uid'=>$recordinfo['company_uid']))->find();
            if ($sms['set_allowance_confirm_entry_company']=="1"  && $company_user_info['mobile_audit']=="1")
            {
                $sendSms['mobile']=$company_user_info['mobile'];
                $sendSms['tpl']='set_allowance_confirm_entry_company';
                $resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
                $sendSms['data']=array('sitename'=>C('qscms_site_name'),'fullname'=>$resumeinfo['fullname']);
                D('Common/Sms')->sendSms('notice',$sendSms);
            }
        }
		//微信通知
		if(C('apply.Weixin')){
			$recordinfo = $this->find($id);
			$baseinfo = D('Allowance/AllowanceInfo')->find($recordinfo['info_id']);
			//通知个人
			if($this->config['service_charge']>0){
				$amount = $recordinfo['amount']*(1-$this->config['service_charge']/100);
			}else{
				$amount = $recordinfo['amount'];
			}
	        $amount = $amount<1?1:$amount;
	        $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
	    	D('Allowance/AllowanceTplMsg')->set_allowance_confirm_entry_personal($recordinfo['personal_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$companyinfo['companyname'],$recordinfo['amount']-$amount,$amount);
	    	//通知企业
	    	if(!$resumeinfo){
	        	$resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
	        }
	    	D('Allowance/AllowanceTplMsg')->set_allowance_confirm_entry_company($recordinfo['company_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$resumeinfo['fullname'],$baseinfo['surplus_num']);
		}
		// }
		return true;
	}
	/**
	 * 确认个人缺席（入职）
	 */
	public function absent_entry($id){
		$this->where(array('id'=>$id))->save(array('step'=>self::ABSENT_ENTRY,'step_finish'=>1,'updatetime'=>time(),'member_turn'=>self::NOBODY_TURN,'status'=>self::STATUS_ARBITRATE));
		D('Allowance/AllowanceRecordLog')->add(array('step'=>self::ABSENT_ENTRY,'record_id'=>$id,'note'=>'企业确认个人缺席'));
		//短信通知
		if(C('qscms_sms_open')==1){
			if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
			$recordinfo = $this->find($id);
			//通知个人
			$personal_info=M('Members')->where(array('uid'=>$recordinfo['personal_uid']))->find();
	    	if ($sms['set_allowance_absent_entry_personal']=="1"  && $personal_info['mobile_audit']=="1")
			{
	            $sendSms['mobile']=$personal_info['mobile'];
	            $sendSms['tpl']='set_allowance_absent_entry_personal';
	            $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'companyname'=>$companyinfo['companyname']);
	            D('Common/Sms')->sendSms('notice',$sendSms);
			}
			//通知企业
			$company_user_info=M('Members')->where(array('uid'=>$recordinfo['company_uid']))->find();
	    	if ($sms['set_allowance_absent_entry_company']=="1"  && $company_user_info['mobile_audit']=="1")
			{
	            $sendSms['mobile']=$company_user_info['mobile'];
	            $sendSms['tpl']='set_allowance_absent_entry_company';
	            $resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'fullname'=>$resumeinfo['fullname']);
	            D('Common/Sms')->sendSms('notice',$sendSms);
			}
		}
		//微信通知
		if(C('apply.Weixin')){
			$recordinfo = $this->find($id);
			$baseinfo = D('Allowance/AllowanceInfo')->find($recordinfo['info_id']);
			//通知个人
	        $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$recordinfo['company_uid']))->find();
	    	D('Allowance/AllowanceTplMsg')->set_allowance_absent_entry_personal($recordinfo['personal_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$companyinfo['companyname']);
	    	//通知企业
	    	if(!$resumeinfo){
	        	$resumeinfo = D('Common/Resume')->find($recordinfo['resumeid']);
	        }
	    	D('Allowance/AllowanceTplMsg')->set_allowance_absent_entry_company($recordinfo['company_uid'],$baseinfo['jobsname'].'【'.$this->get_type_cn($baseinfo['type_alias']).'】（'.$recordinfo['amount'].'元）',$resumeinfo['fullname'],$baseinfo['surplus_num']);
		}
		return true;
	}
	/**
	 * 发起支付
	 */
	public function pay($id,$userbind = array(),$repay=false){
		$recordinfo = $this->find($id);
		if(empty($userbind)){
			$userbind = D('Allowance/AllowanceInfo')->check_binding_weixin($recordinfo['personal_uid']);
		}
		$pay_status = 1;
		$pay_fail_reason = '';
		if($userbind){
			include QSCMSLIB_PATH . "pay/wxpay/wxpay.class.php";
			$pay_type = D('Common/Payment')->get_cache();
        	$setting = $pay_type['wxpay'];
	        $payObj = new \wxpay_pay($setting);
	        $data['openid'] = $userbind['keyid'];
	        $data['partner_trade_no'] = $recordinfo['oid'];
	        
			if($this->config['service_charge']>0){
				$amount = $recordinfo['amount']*(1-$this->config['service_charge']/100);
			}else{
				$amount = $recordinfo['amount'];
			}
	        $data['amount'] = $amount<1?1:$amount;
	        $result = $payObj->payment($data);
	        if($result){
	        	//写入交易记录
	        	$deallog['uid'] = $recordinfo['personal_uid'];
	        	$deallog['amount'] = $data['amount'];
	        	$deallog['service_charge'] = $recordinfo['amount'] - $data['amount'];
	        	$deallog['info_id'] = $recordinfo['info_id'];
	        	$deallog['record_id'] = $id;
	        	if($recordinfo['apply']==1){
	        		$deallog['type'] = 'apply';
	        	}else if($recordinfo['interview']==1){
	        		$deallog['type'] = 'interview';
	        	}else{
	        		$deallog['type'] = 'entry';
	        	}
	        	$deallog['note'] = '领取【'.$this->get_type_cn($deallog['type']).'】';
	        	D('Allowance/AllowanceDealLogPersonal')->add($deallog);
	        }else{
	        	$pay_status = 2;
				$pay_fail_reason = $payObj->getError();
	        }
		}else{
			//支付失败，写明原因，方便后台管理员操作
			$pay_status = 2;
			$pay_fail_reason = '绑定信息错误';
		}
		if($pay_status==2){
			D('Allowance/AllowanceRecordLog')->add(array('step'=>0,'record_id'=>$id,'note'=>'支付失败，原因：'.$pay_fail_reason));
	    }else{
			D('Allowance/AllowanceRecordLog')->add(array('step'=>0,'record_id'=>$id,'note'=>'系统发放红包给个人'));
	    }
	    if($repay){
	    	$this->where(array('id'=>$id))->save(array('pay_status'=>$pay_status,'pay_fail_reason'=>$pay_fail_reason));
	    }else{
	    	//不管支付成功不成功，先结束流程
	    	$this->where(array('id'=>$id))->save(array('status'=>self::STATUS_GRAND,'step_finish'=>1,'updatetime'=>time(),'member_turn'=>self::NOBODY_TURN,'pay_status'=>$pay_status,'pay_fail_reason'=>$pay_fail_reason));
	    }
	    if($pay_status==2){
	    	return false;
	    }else{
	    	return true;
	    }
	}
	
	public function getError(){
		return $this->_error;
	}
	public function check_apply($jobsid,$user,$resumeid){
		$jobsinfo = D('Common/Jobs')->find($jobsid);
		if(!$jobsinfo){
			$jobsinfo = D('Common/JobsTmp')->find($jobsid);
			if($jobsinfo){
				if($jobsinfo['display']!=1){
					$this->_error = '该职位已关闭或未通过审核';
					return false;
				}
			}else{
				$this->_error = '职位不存在！';
				return false;
			}
		}
        if($resumeid){
			$resume = M('Resume')->where(array('id'=>$resumeid,'uid'=>$user['uid']))->find();
            if(!$resume){
            	$this->_error = "选择的简历不存在！";
				return false;
            }
		}else{
            $resume = M('Resume')->where(array('uid'=>$user['uid']))->order('def desc')->find();
            if(!$resume){
            	$this->_error = "请您先填写一份简历！";
				return '-1';
            }
            $resume_def = D('Common/Resume')->get_resume_list(array('where'=>array('uid'=>$user['uid']),'field'=>$field,'limit'=>1,'order'=>'def desc,id desc','_audit'=>1));
            if(!$resume_def){
            	$this->_error = "您的默认简历尚未审核通过！";
				return false;
            }
		}
		
		return array(
				'jobsinfo'=>$jobsinfo,
				'resume'=>$resume
			);
	}
	public function jobs_apply_add($jobsid,$user,$resumeid){
		$check_result = $this->check_apply($jobsid,$user,$resumeid);
		if($check_result===false || $check_result=='-1'){
			$this->_error = $this->getError();
			return false;
		}
		$count = D('Common/PersonalJobsApply')->where(array('personal_uid'=>$user['uid'],'apply_addtime'=>array('egt',strtotime('today'))))->count('did');
		if(C('qscms_apply_jobs_max') < $count+1){
			$this->_error = "您每天可以投递".C('qscms_apply_jobs_max')."个职位，今天已投递了{$count}个";
			return false;
		}
		$check_jobs = D('Common/PersonalJobsApply')->where(array('company_uid'=>array('eq',$check_result['jobsinfo']['uid']),'personal_uid'=>$user['uid']))->find();
		if($check_jobs){
			$this->_error = '你已经申请过该公司的职位了！';
			return false;
		}
		$jobsinfo = $check_result['jobsinfo'];
		$resume = $check_result['resume'];
		//判断新创建简历是否超过领取红包限定时间
		
		if($this->config['new_resume_apply_timespace']>0 && $resume['addtime']>=(time()-$this->config['new_resume_apply_timespace']*60))
		{
			$this->_error = "新创建简历".$this->config['new_resume_apply_timespace']."分钟内不能领取红包，你可以选择“直接投递”或过一会儿再来领取。";
			return false;
		}
		//判断是否超过红包职位每天最多可申请次数
		if($this->config['apply_maxtime_perday']>0){
			$count_map['personal_uid'] = $user['uid'];
			$count_map['addtime'] = array('egt',strtotime('today'));
			$count_today_apply_times = $this->where($count_map)->count();
			if($count_today_apply_times>=$this->config['apply_maxtime_perday']){
				$this->_error = "每天最多可领取红包".$this->config['apply_maxtime_perday']."次，你今天领取数已达上限，你可以选择“直接投递”或明天再来领取。";
				return false;
			}
		}
		if($this->config['apply_maxtime_per_one_month']>0){
			$count_map['personal_uid'] = $user['uid'];
			$count_map['addtime'] = array('egt',strtotime('-30 days'));
			$count_one_month_apply_times = $this->where($count_map)->count();
			if($count_one_month_apply_times>=$this->config['apply_maxtime_per_one_month']){
				$this->_error = "30天内最多可领取红包".$this->config['apply_maxtime_per_one_month']."次，你的领取数已达上限，你可以选择“直接投递”或明天再来领取。";
				return false;
			}
		}
		if($this->config['apply_maxtime_per_three_month']>0){
			$count_map['personal_uid'] = $user['uid'];
			$count_map['addtime'] = array('egt',strtotime('-90 days'));
			$count_three_month_apply_times = $this->where($count_map)->count();
			if($count_three_month_apply_times>=$this->config['apply_maxtime_per_three_month']){
				$this->_error = "90天内最多可领取红包".$this->config['apply_maxtime_per_three_month']."次，你的领取数已达上限，你可以选择“直接投递”或明天再来领取。";
				return false;
			}
		}
		//检测是否被屏蔽领取红包功能
		$in_blacklist = D('Allowance/AllowanceBlacklist')->where(array('uid'=>$user['uid']))->find();
		if($in_blacklist){
			if($in_blacklist['deadline']==0 || $in_blacklist['deadline']>time()){
				$this->_error = "你的领取红包权限被系统冻结，请联系网站管理员，你也可以选择“直接投递”。";
				return false;
			}else{
				D('Allowance/AllowanceBlacklist')->where(array('uid'=>$user['uid']))->delete();
			}
		}
		if(!D('Allowance/AllowanceEditIntentionLog')->check($user['uid']) && !D('Allowance/AllowanceDealLogPersonal')->check($user['uid'])){
			//触发流程控制，加入黑名单
			$deadline = $this->config['blacklist_time_limit']==0?0:strtotime('+'.$this->config['blacklist_time_limit'].' days');
			D('Allowance/AllowanceBlacklist')->add(array('uid'=>$user['uid'],'robot'=>1,'deadline'=>$deadline,'utype'=>2));
			$this->_error = "你的领取红包权限被系统冻结，请联系网站管理员，你也可以选择“直接投递”。";
			return false;
		}
		$record['company_uid'] = $jobsinfo['uid'];
		$record['personal_uid'] = $user['uid'];
		$record['resumeid'] = $resume['id'];
		$record['jobsid'] = $jobsinfo['id'];
		$record['info_id'] = $jobsinfo['allowance_id'];
		$allowance_info = D('Allowance/AllowanceInfo')->find($record['info_id']);
		$record[$allowance_info['type_alias']] = 1;
		$record['amount'] = $allowance_info['per_amount'];
		$record_result = $this->add_apply($record);
		if(!$record_result){
			$this->_error = $this->getError();
			return false;
		}else{
			$setsqlarr['resume_id'] = $resume['id'];
			$setsqlarr['resume_name'] = $resume['title'];
			$setsqlarr['personal_uid'] = $user['uid'];
			$setsqlarr['jobs_id'] = $jobsid;
			$setsqlarr['jobs_name'] = $jobsinfo['jobs_name'];
			$setsqlarr['company_id'] = $jobsinfo['company_id'];
			$setsqlarr['company_name'] = $jobsinfo['companyname'];
			$setsqlarr['company_uid'] = $jobsinfo['uid'];
			if(false !== D('Common/PersonalJobsApply')->create($setsqlarr)){
				if(false !== $insertid = D('Common/PersonalJobsApply')->add()){
					$reg = D('Common/TaskLog')->do_task($user,4);//做任务记录积分操作,4:投递简历
					$jobs_contact = D('Common/JobsContact')->where(array('pid'=>$jobsid))->find();
					$statistics_data['comid'] = $jobsinfo['company_id'];
			        $statistics_data['jobid'] = $jobsid;
			        $statistics_data['uid'] = $user['uid'];
			        $statistics_data['apply'] = 1;
			        $statistics_data['source'] = C('LOG_SOURCE')==2?2:1;
			        $statistics_model = D('Common/CompanyStatistics');
			        $statistics_model->create($statistics_data);
			        $statistics_model->add();
			        $resume_basicinfo = D('Common/Resume')->get_resume_basic($user['uid'],$resume['id']);
			        $setmeal=D('Common/MembersSetmeal')->get_user_setmeal($jobsinfo['uid']);
	        		return $allowance_info;
				}
			}else{
				$this->_error = D('Common/PersonalJobsApply')->getError();
				return false;
			}
		}
	}
	/**
     * 是否显示联系方式
     */
    protected function _get_show_contact($resume,$company_uid,$setmeal){
    	$show_contact = false;
        if(C('qscms_showresumecontact')!=2 || $setmeal['show_apply_contact']=='1'){
            $show_contact = true;
        }
        if(!$show_contact){
        	//下载过该简历
        	$down_resume = D('Common/CompanyDownResume')->check_down_resume($resume['id'],$company_uid);
        	if($down){
	            $show_contact = true;
	        }
        }
        return $show_contact;
    }
}
?>