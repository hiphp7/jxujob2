<?php
namespace Common\Model;
use Think\Model;
class OrderModel extends Model{
	public $order_type = array(1=>'套餐升级',2=>'充值积分',3=>'简历置顶',4=>'醒目标签',5=>'简历模板',6=>'简历包',7=>'短信包',8=>'职位置顶',9=>'职位紧急',10=>'企业模板',11=>'诚聘通',12=>'预约刷新职位',13=>'职位刷新',14=>'简历下载');
	protected $_validate = array(
		array('uid,oid,utype,order_type,pay_type,payment,payment_cn,service_name,description','identicalNull','',1,'callback'),
		array('uid,utype,order_type,pay_type','identicalEnum','',0,'callback'),
	);
	protected $_auto = array ( 
		array('is_paid',1),
		array('addtime','time',1,'function'),
		array('fee',0,1)
	);
	/*
		获取单条订单信息
		@data 数组
	*/
	public function get_order_one($data)
	{
		$info = $this->where($data)->find();
		if($info['params']){
			$info['params'] = unserialize($info['params']);
		}
		return $info;
	}
	public function edit_order($id,$uid,$is_paid,$pay_amount=0,$pay_points=0,$payment='',$payment_cn='',$description='',$payment_time=0){
		if($pay_amount>0 && $pay_points>0)
		{
			$setsqlarr['pay_type']=3;
		}
		elseif($pay_amount>0)
		{
			$setsqlarr['pay_type']=2;
		}
		else
		{
			$setsqlarr['pay_type']=1;
		}
		$setsqlarr['is_paid']=$is_paid;
		$setsqlarr['pay_amount']=$pay_amount;
		$setsqlarr['pay_points']=$pay_points;
		$setsqlarr['payment']=$payment;
		$setsqlarr['payment_cn']=$payment_cn;
		$setsqlarr['description']=$description;
		$setsqlarr['payment_time']=$payment_time;
		$this->where(array('id'=>$id,'uid'=>$uid))->save($setsqlarr);
	}
	/**
	 * 添加订单数据
	 * @param $user           用户信息
	 * @param $oid            订单号
	 * @param $order_type     订单类型，详见D('Order')->order_type
	 * @param $amount         总金额
	 * @param $pay_amount     现金支付金额
	 * @param $pay_points     积分支付数
	 * @param $service_name   所购买服务名称
	 * @param $payment        支付方式英文
	 * @param $payment_cn     支付方式中文
	 * @param $description    订单详情描述
	 * @param $addtime   	  下单时间
	 * @param $is_paid        1待支付 2已支付
	 * @param $points         购买积分数
	 * @param $setmeal        购买套餐/增值服务id
	 * @param $payment_time   支付时间
	 * @param $params         需要特殊处理的参数序列化
	 * @param $notes          备注
	 */
	public function add_order($user,$oid,$order_type,$amount,$pay_amount,$pay_points,$service_name,$payment,$payment_cn,$description,$addtime,$is_paid=1,$points=0.0,$setmeal=0,$payment_time=0,$params='',$discount='',$notes='')
	{
		$uid=$user['uid'];
		$setsqlarr['oid']=$oid;
		$setsqlarr['uid']=$uid;
		$setsqlarr['utype']=$user['utype'];
		$setsqlarr['order_type']=$order_type;
		if($pay_amount>0 && $pay_points>0)
		{
			$setsqlarr['pay_type']=3;
		}
		elseif($pay_amount>0)
		{
			$setsqlarr['pay_type']=2;
		}
		else
		{
			$setsqlarr['pay_type']=1;
		}
		
		$setsqlarr['is_paid']=$is_paid;
		$setsqlarr['amount']=$amount;
		$setsqlarr['pay_amount']=$pay_amount;
		$setsqlarr['pay_points']=$pay_points;
		$setsqlarr['payment']=$payment;
		$setsqlarr['payment_cn']=$payment_cn;
		$setsqlarr['description']=$description;
		$setsqlarr['service_name']=$service_name;
		$setsqlarr['points']=$points;
		$setsqlarr['setmeal']=$setmeal;
		$setsqlarr['params']=$params;
		$setsqlarr['notes']=$notes;
		$setsqlarr['addtime']=$addtime;
		$setsqlarr['payment_time']=$payment_time;
		$setsqlarr['discount']=$discount;
		$payment_model = D('Payment')->where(array('typename'=>$payment))->find();
		if($payment_model && $pay_amount>0){
			$setsqlarr['fee']=$pay_amount*$payment_model['fee']/100;
		}else{
			$setsqlarr['fee'] = 0;
		}
		
		if(false === $this->create($setsqlarr)) return false;
		C('SUBSITE_VAL.s_id') && $setsqlarr['subsite_id'] = C('SUBSITE_VAL.s_id');
		$insert_id = $this->add($setsqlarr);
		$userinfo=M('Members')->where(array('uid'=>$uid))->find();
		if(false === $mailconfig = F('mailconfig')) $mailconfig = D('Mailconfig')->get_cache();//邮箱系统配置参数
		if(false === $sms = F('sms_config')) $sms = D('SmsConfig')->config_cache();
		if($is_paid==1){
			if($user['utype']==1){
				if(C('qscms_site_dir')=='/'){
            	$replac_mail['order_url']=rtrim(C('qscms_site_domain').C('qscms_site_dir'),'/').U('Home/CompanyService/order_detail',array('id'=>$insert_id));
				}else{
				$replac_mail['order_url']=rtrim(C('qscms_site_domain'),'/').U('Home/CompanyService/order_detail',array('id'=>$insert_id));
				}
            }else{
            	if(C('qscms_site_dir')=='/'){
            	$replac_mail['order_url']=rtrim(C('qscms_site_domain').C('qscms_site_dir'),'/').U('Home/PersonalService/order_detail',array('id'=>$insert_id));
				}else{
				$replac_mail['order_url']=rtrim(C('qscms_site_domain'),'/').U('Home/PersonalService/order_detail',array('id'=>$insert_id));
				}
            }
			$pms_message = '订单'.$oid.'已经添加成功，付款方式为：'.$payment_cn.'，应付金额'.$pay_amount.($pay_points>0?('(抵扣'.$pay_points.C('qscms_points_byname').')'):'').'。<a href="'.$replac_mail['order_url'].'" target="_blank">点击查看详情>></a>';
			D('Pms')->write_pmsnotice($user['uid'],$user['username'],$pms_message);
			if ($mailconfig['set_order']=="1" && $userinfo['email_audit']=="1" && $amount>0)
			{
				$send_mail['send_type']='set_order';
	            $send_mail['sendto_email']=$userinfo['email'];
	            $send_mail['subject']='set_order_title';
	            $send_mail['body']='set_order';
	            $replac_mail['service_name']=$service_name;
	            $replac_mail['oid']=$oid;
	            $replac_mail['amount']=$amount;
	            $replac_mail['pay_amount']=$pay_amount;
	            $replac_mail['pay_points']=$pay_points;
	            $replac_mail['points_byname']=C('qscms_points_byname');
	            D('Mailconfig')->send_mail($send_mail,$replac_mail);
			}
			if (C('qscms_sms_open')==1 && $sms['set_order']=="1"  && $userinfo['mobile_audit']=="1" && $amount>0)
			{
				$paymenttpye=D('Payment')->get_payment_info($payment_name);
				$sendSms['mobile']=$user['mobile'];
	            $sendSms['tpl']='set_order';
	            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'sitedomain'=>C('qscms_site_name'),'paymenttpye'=>$payment_cn,'oid'=>$oid,'amount'=>$amount.'');
	            D('Sms')->sendSms('notice',$sendSms);
			}
			if(C('apply.Weixin')){
				D('Weixin/TplMsg')->add_order($setsqlarr['uid'],$setsqlarr['oid'],$setsqlarr['description'],$setsqlarr['amount']);
			}
		}else if($is_paid==2){
			if($user['utype']==1){
				if(C('qscms_site_dir')=='/'){
            	$replac_mail['order_url']=rtrim(C('qscms_site_domain').C('qscms_site_dir'),'/').U('Home/CompanyService/order_detail',array('id'=>$insert_id));
				}else{
				$replac_mail['order_url']=rtrim(C('qscms_site_domain'),'/').U('Home/CompanyService/order_detail',array('id'=>$insert_id));
				}
            }else{
            	if(C('qscms_site_dir')=='/'){
            	$replac_mail['order_url']=rtrim(C('qscms_site_domain').C('qscms_site_dir'),'/').U('Home/PersonalService/order_detail',array('id'=>$insert_id));
				}else{
				$replac_mail['order_url']=rtrim(C('qscms_site_domain'),'/').U('Home/PersonalService/order_detail',array('id'=>$insert_id));
				}
            }
			$pms_message = '订单'.$oid.'已经支付成功，付款方式为：'.$payment_cn.'，支付金额'.$pay_amount.($pay_points>0?('(抵扣'.$pay_points.C('qscms_points_byname').')'):'').'。<a href="'.$replac_mail['order_url'].'" target="_blank">点击查看详情>></a>';
			D('Pms')->write_pmsnotice($user['uid'],$user['username'],$pms_message);
			if ($mailconfig['set_payment']==1 && $userinfo['email_audit']=="1" && $amount>0)
            {   
                $send_mail['send_type']='set_payment';
	            $send_mail['sendto_email']=$userinfo['email'];
	            $send_mail['subject']='set_payment_title';
	            $send_mail['body']='set_payment';
	            $replac_mail['service_name']=$service_name;
	            $replac_mail['oid']=$oid;
	            $replac_mail['pay_amount']=$pay_amount;
	            $replac_mail['pay_points']=$pay_points;
	            $replac_mail['points_byname']=C('qscms_points_byname');
	            $replac_mail['paytime']=date('Y-m-d H:i');
	            D('Mailconfig')->send_mail($send_mail,$replac_mail);          
            }
            if (C('qscms_sms_open')==1 && $sms['set_payment']=="1"  && $userinfo['mobile_audit']=="1" && $amount>0)
			{
	            $sendSms['mobile']=$user['mobile'];
                $sendSms['tpl']='set_payment';
                $sendSms['data']=array('sitename'=>C('qscms_site_name'),'sitedomain'=>C('qscms_site_domain'));
                D('Sms')->sendSms('notice',$sendSms);
			}
			if(C('apply.Weixin')){
				D('Weixin/TplMsg')->set_payment($setsqlarr['uid'],$setsqlarr['oid'],$setsqlarr['description'],$setsqlarr['amount']);
			}
		}
		return $insert_id;
	}
	/**
	 * 简历置顶回调
	 */
	protected function order_paid_resume_stick($order,$user){
		$params = unserialize($order['params']);
		$setsqlarr['resume_id'] = $params['resume_id'];
		$setsqlarr['resume_uid'] = $order['uid'];
		$setsqlarr['days'] = $params['days'];
		$setsqlarr['points'] = $params['points'];
		$setsqlarr['addtime'] = time();
		$setsqlarr['endtime'] = strtotime("+{$params['days']} day");
		$uid = $order['uid'];
		$deductible = $order['pay_points'];
		if($deductible>0){
			$p_rst = D('MembersPoints')->report_deal($uid,2,$deductible);
			if($p_rst){
                $handsel['uid'] = $uid;
                $handsel['htype'] = '';
                $handsel['htype_cn'] = $order['service_name'];
                $handsel['operate'] = 2;
                $handsel['points'] = $deductible;
                $handsel['addtime'] = time();
                D('MembersHandsel')->members_handsel_add($handsel);
            }
		}
		$rst = D('PersonalServiceStickLog')->add_stick_log($setsqlarr);
		if($rst['state']==1){
			$refreshtime = D('Resume')->where(array('id'=>array('eq',$setsqlarr['resume_id'])))->getfield('refreshtime');
			$stime = intval($refreshtime) + 100000000;
			D('Resume')->where(array('id'=>array('eq',$setsqlarr['resume_id'])))->save(array('stick'=>1,'stime'=>$stime));
	        D('ResumeSearchPrecise')->where(array('id'=>array('eq',$setsqlarr['resume_id'])))->setField('stime',$stime);
	        D('ResumeSearchFull')->where(array('id'=>array('eq',$setsqlarr['resume_id'])))->setField('stime',$stime);
	        /* 会员日志 */
	        
	        if($deductible>0){
	        	$log_payment = $order['payment_cn'].'+'.C('qscms_points_byname').'抵扣';
	        }else{
	        	$log_payment = $order['payment_cn'];
	        }
			write_members_log($user,9002,$order['service_name'],$log_payment);
			return true;
		}
		return false;
	}
	/**
	 * 醒目标签回调
	 */
	protected function order_paid_resume_tag($order,$user){
		$params = unserialize($order['params']);
		$setsqlarr['resume_id'] = $params['resume_id'];
		$setsqlarr['resume_uid'] = $order['uid'];
		$setsqlarr['days'] = $params['days'];
		$setsqlarr['points'] = $params['points'];
		$setsqlarr['tag_id'] = $params['tag_id'];
		$setsqlarr['addtime'] = time();
		$setsqlarr['endtime'] = strtotime("+{$params['days']} day");
		$uid = $order['uid'];
		$deductible = $order['pay_points'];
		if($deductible>0){
			$p_rst = D('MembersPoints')->report_deal($uid,2,$deductible);
			if($p_rst){
                $handsel['uid'] = $uid;
                $handsel['htype'] = '';
                $handsel['htype_cn'] = $order['service_name'];
                $handsel['operate'] = 2;
                $handsel['points'] = $deductible;
                $handsel['addtime'] = time();
                D('MembersHandsel')->members_handsel_add($handsel);
            }
		}
		$rst = D('PersonalServiceTagLog')->add_tag_log($setsqlarr);
		if($rst['state']==1){
			D('Resume')->where(array('id'=>array('eq',$setsqlarr['resume_id'])))->setField('strong_tag',$setsqlarr['tag_id']);
			/* 会员日志 */
	        if($deductible>0){
	        	$log_payment = $order['payment_cn'].'+'.C('qscms_points_byname').'抵扣';
	        }else{
	        	$log_payment = $order['payment_cn'];
	        }
			write_members_log($user,9002,$order['service_name'],$log_payment);
			return true;
		}
		return false;
	}
	/**
	 * 简历模板回调
	 */
	protected function order_paid_resume_tpl($order,$user){
		$params = unserialize($order['params']);
		$setsqlarr['tplid'] = $params['tplid'];
		$setsqlarr['uid'] = $order['uid'];
		$deductible = $order['pay_points'];
		if($deductible>0){
			$p_rst = D('MembersPoints')->report_deal($setsqlarr['uid'],2,$deductible);
			if($p_rst){
                $handsel['uid'] = $setsqlarr['uid'];
                $handsel['htype'] = '';
                $handsel['htype_cn'] = $order['service_name'];
                $handsel['operate'] = 2;
                $handsel['points'] = $deductible;
                $handsel['addtime'] = time();
                D('MembersHandsel')->members_handsel_add($handsel);
            }
		}
		$rst = D('ResumeTpl')->add_resume_tpl($setsqlarr);
		if($rst['state']==1){
			/* 会员日志 */
	        if($deductible>0){
	        	$log_payment = $order['payment_cn'].'+'.C('qscms_points_byname').'抵扣';
	        }else{
	        	$log_payment = $order['payment_cn'];
	        }
			write_members_log($user,9002,$order['service_name'],$log_payment);
			return true;
		}
		return false;
	}
	/**
	 * 增值包回调 - 简历包和短信包
	 */
	protected function order_paid_setmeal_increment($order,$user){
		$uid = $order['uid'];
		$project_id = $order['setmeal'];
		$deductible = $order['pay_points'];
		if($deductible>0){
			$p_rst = D('MembersPoints')->report_deal($uid,2,$deductible);
			if($p_rst){
                $handsel['uid'] = $uid;
                $handsel['htype'] = '';
                $handsel['htype_cn'] = $order['service_name'];
                $handsel['operate'] = 2;
                $handsel['points'] = $deductible;
                $handsel['addtime'] = time();
                D('MembersHandsel')->members_handsel_add($handsel);
            }
		}
		$setmeal_increment = D('SetmealIncrement')->where(array('id'=>$project_id))->find();
		if($setmeal_increment['cat']=='download_resume'){
			D('MembersSetmeal')->where(array('uid'=>$uid))->setInc('download_resume',$setmeal_increment['value']);
		}else if($setmeal_increment['cat']=='sms'){
			D('Members')->where(array('uid'=>$uid))->setInc('sms_num',$setmeal_increment['value']);
		}
		/* 会员日志 */
        if($deductible>0){
        	$log_payment = $order['payment_cn'].'+'.C('qscms_points_byname').'抵扣';
        }else{
        	$log_payment = $order['payment_cn'];
        }
		write_members_log($user,9002,$order['service_name'],$log_payment);
		return true;
	}
	/**
	 * 增值包回调 - 职位推广
	 */
	protected function order_paid_job_promotion($order,$user){
		$params = unserialize($order['params']);
		$uid = $order['uid'];
		$project_id = $order['setmeal'];
		$deductible = $order['pay_points'];
		$jobs_id = $params['jobs_id'];
		if($deductible>0){
			$p_rst = D('MembersPoints')->report_deal($uid,2,$deductible);
			if($p_rst){
                $handsel['uid'] = $uid;
                $handsel['htype'] = '';
                $handsel['htype_cn'] = $order['service_name'];
                $handsel['operate'] = 2;
                $handsel['points'] = $deductible;
                $handsel['addtime'] = time();
                D('MembersHandsel')->members_handsel_add($handsel);
            }
		}
		$setmeal_increment = D('SetmealIncrement')->where(array('id'=>$project_id))->find();

		// 推广操作
		$promotionsqlarr['cp_uid']=$uid;
		$promotionsqlarr['cp_jobid']=$jobs_id;
		$promotionsqlarr['cp_ptype']=$setmeal_increment['cat'];
		$promotionsqlarr['cp_days']=$setmeal_increment['value'];
		$promotionsqlarr['cp_starttime']=time();
		$promotionsqlarr['cp_endtime']=strtotime("{$setmeal_increment['value']} day");
		D('Promotion')->add_promotion($promotionsqlarr);
		D('Promotion')->set_job_promotion($jobs_id,$setmeal_increment['cat']);
		/* 会员日志 */
        if($deductible>0){
        	$log_payment = $order['payment_cn'].'+'.C('qscms_points_byname').'抵扣';
        }else{
        	$log_payment = $order['payment_cn'];
        }
		write_members_log($user,9002,$order['service_name'],$log_payment);
		return true;
	}
	/**
	 * 增值包回调 - 企业模板
	 */
	protected function order_paid_company_tpl($order,$user){
		$uid = $order['uid'];
		$project_id = $order['setmeal'];
		$deductible = $order['pay_points'];
		if($deductible>0){
			$p_rst = D('MembersPoints')->report_deal($uid,2,$deductible);
			if($p_rst){
                $handsel['uid'] = $uid;
                $handsel['htype'] = '';
                $handsel['htype_cn'] = $order['service_name'];
                $handsel['operate'] = 2;
                $handsel['points'] = $deductible;
                $handsel['addtime'] = time();
                D('MembersHandsel')->members_handsel_add($handsel);
            }
		}
		D('CompanyTpl')->add_company_tpl(array('uid'=>$uid,'tplid'=>$project_id));
		/* 会员日志 */
        if($deductible>0){
        	$log_payment = $order['payment_cn'].'+'.C('qscms_points_byname').'抵扣';
        }else{
        	$log_payment = $order['payment_cn'];
        }
		write_members_log($user,9002,$order['service_name'],$log_payment);
		return true;
	}
	/**
	 * 增值包回调 - 诚聘通
	 */
	protected function order_paid_famous_company($order,$user){
		$uid = $order['uid'];
		D('CompanyProfile')->where(array('uid'=>$uid))->setField('famous',1);
		D('Jobs')->where(array('uid'=>$uid))->setField('famous',1);
		D('JobsSearch')->where(array('uid'=>$uid))->setField('famous',1);
		D('JobsSearchKey')->where(array('uid'=>$uid))->setField('famous',1);
		D('JobsTmp')->where(array('uid'=>$uid))->setField('famous',1);
		/* 会员日志 */
        if($deductible>0){
        	$log_payment = $order['payment_cn'].'+'.C('qscms_points_byname').'抵扣';
        }else{
        	$log_payment = $order['payment_cn'];
        }
        $companyinfo = D('CompanyProfile')->where(array('uid'=>$uid))->find();
        $auditsqlarr['company_id']=$companyinfo['id'];
		$auditsqlarr['reason']='成为诚聘通企业';
		$auditsqlarr['status']='是';
		$auditsqlarr['addtime']=time();
		$auditsqlarr['audit_man']='系统';
		$auditsqlarr['famous']=1;
		M('AuditReason')->data($auditsqlarr)->add();
		write_members_log($user,9002,$order['service_name'],$log_payment);
		return true;
	}
	/**
	 * 增值包回调 - 预约刷新职位
	 */
	protected function order_paid_auto_refresh_jobs($order,$user){
		$params = unserialize($order['params']);
		$uid = $order['uid'];
		$project_id = $order['setmeal'];
		$deductible = $order['pay_points'];
		$jobs_id = $params['jobs_id'];
		if($deductible>0){
			$p_rst = D('MembersPoints')->report_deal($uid,2,$deductible);
			if($p_rst){
                $handsel['uid'] = $uid;
                $handsel['htype'] = '';
                $handsel['htype_cn'] = $order['service_name'];
                $handsel['operate'] = 2;
                $handsel['points'] = $deductible;
                $handsel['addtime'] = time();
                D('MembersHandsel')->members_handsel_add($handsel);
            }
		}
		$setmeal_increment = D('SetmealIncrement')->where(array('id'=>$project_id))->find();
		$days = $setmeal_increment['value'];
		$nowtime = time();
		for ($i=0; $i < $days*4; $i++) { 
			$timespace = 3600*6*$i;
			M('QueueAutoRefresh')->add(array('uid'=>$uid,'pid'=>$jobs_id,'type'=>1,'refreshtime'=>$nowtime+$timespace));
		}
		/* 会员日志 */
        if($deductible>0){
        	$log_payment = $order['payment_cn'].'+'.C('qscms_points_byname').'抵扣';
        }else{
        	$log_payment = $order['payment_cn'];
        }
		write_members_log($user,9002,$order['service_name'],$log_payment);
		return true;
	}
	/**
	 * 单独购买职位刷新
	 */
	protected function order_paid_single_refresh_jobs($order,$user){
		$params = unserialize($order['params']);
		$uid = $order['uid'];
		$deductible = $order['pay_points'];
		$jobs_id = $params['jobs_id'];
		if($deductible>0){
			$p_rst = D('MembersPoints')->report_deal($uid,2,$deductible);
			if($p_rst){
                $handsel['uid'] = $uid;
                $handsel['htype'] = '';
                $handsel['htype_cn'] = $order['service_name'];
                $handsel['operate'] = 2;
                $handsel['points'] = $deductible;
                $handsel['addtime'] = time();
                D('MembersHandsel')->members_handsel_add($handsel);
            }
		}
		// 刷新操作
		$time=time();
		M('CompanyProfile')->where(array('uid'=>$uid))->setField('refreshtime',$time);
		M('Jobs')->where(array('uid'=>$uid,'id'=>array('in',$jobs_id)))->setField('refreshtime',$time);
		M('JobsTmp')->where(array('uid'=>$uid,'id'=>array('in',$jobs_id)))->setField('refreshtime',$time);
		M('JobsSearch')->where(array('uid'=>$uid,'id'=>array('in',$jobs_id)))->setField('refreshtime',$time);
		M('JobsSearchKey')->where(array('uid'=>$uid,'id'=>array('in',$jobs_id)))->setField('refreshtime',$time);
		/* 会员日志 */
        if($deductible>0){
        	$log_payment = $order['payment_cn'].'+'.C('qscms_points_byname').'抵扣';
        }else{
        	$log_payment = $order['payment_cn'];
        }
		write_members_log($user,9002,$order['service_name'],$log_payment);
		return true;
	}
	/**
	 * 单独购买简历下载
	 */
	protected function order_paid_single_resume_download($order,$user){
		$params = unserialize($order['params']);
		$uid = $order['uid'];
		$deductible = $order['pay_points'];
		$resume_id = $params['resume_id'];
		if($deductible>0){
			$p_rst = D('MembersPoints')->report_deal($uid,2,$deductible);
			if($p_rst){
                $handsel['uid'] = $uid;
                $handsel['htype'] = '';
                $handsel['htype_cn'] = $order['service_name'];
                $handsel['operate'] = 2;
                $handsel['points'] = $deductible;
                $handsel['addtime'] = time();
                D('MembersHandsel')->members_handsel_add($handsel);
            }
		}
		$resume_arr = D('Resume')->where(array('id'=>array('in',$resume_id)))->select();
		foreach ($resume_arr as $key => $value) {
			$addarr['resume_id'] = $value['id'];
			if ($value['display_name']=="2")
			{
				$addarr['resume_name']="N".str_pad($value['id'],7,"0",STR_PAD_LEFT);
			}
			elseif ($value['display_name']=="3")
			{
				if($value['sex']==1)
				{
						$addarr['resume_name']=cut_str($value['fullname'],1,0,"先生");
				}
				elseif($value['sex']==2)
				{
					$addarr['resume_name']=cut_str($value['fullname'],1,0,"女士");
				}
			}
			else
			{
			$addarr['resume_name']=$value['fullname'];
			}
			$company = M('CompanyProfile')->where(array('uid'=>$uid))->find();
			$addarr["company_uid"]=$uid;
			$addarr["resume_uid"]=$value['uid'];
			$addarr['company_name']=$company['companyname'];
			$addarr['down_addtime'] = time();
			D('CompanyDownResume')->save_down_resume($addarr);
		}
		/* 会员日志 */
        if($deductible>0){
        	$log_payment = $order['payment_cn'].'+'.C('qscms_points_byname').'抵扣';
        }else{
        	$log_payment = $order['payment_cn'];
        }
		write_members_log($user,9002,$order['service_name'],$log_payment);
		return true;
	}
	/*
		付款后开通
	*/
	public function order_paid($out_trade_no,$time)
	{
		$order = $this->where(array('oid'=>$out_trade_no,'is_paid'=>'1'))->find();
		//判断是否支付完成（防止支付完立即关闭页面  从而导致未开通服务）
		if(intval($order['is_paid']) == 1)
		{
			$data['is_paid']=2;
			$data['payment_time']=$time;
			$this->where(array('oid'=>$out_trade_no))->save($data);
			$user= D('Members')->get_user_one(array('uid'=>$order['uid']));
			//套餐支付
			if($order['order_type']=='1')			
			{		 
				$deductible = $order['pay_points'];
				if($deductible>0){
					$p_rst = D('MembersPoints')->report_deal($order['uid'],2,$deductible);
					if($p_rst){
		                $handsel['uid'] = $order['uid'];
		                $handsel['htype'] = '';
		                $handsel['htype_cn'] = $order['service_name'];
		                $handsel['operate'] = 2;
		                $handsel['points'] = $deductible;
		                $handsel['addtime'] = time();
		                D('MembersHandsel')->members_handsel_add($handsel);
		            }
				}
				$order_name = "套餐订单";
				D('MembersSetmeal')->set_members_setmeal($order['uid'],$order['setmeal']);
				$setmeal=M('Setmeal')->where(array('id'=>$order['setmeal']))->find();
				/* 会员日志 */
		        if($deductible>0){
		        	$log_payment = $order['payment_cn'].'+'.C('qscms_points_byname').'抵扣';
		        }else{
		        	$log_payment = $order['payment_cn'];
		        }
				write_members_log($user,9002,$order['service_name'],$log_payment);
				//会员套餐变更记录。会员购买成功。log_type 2表示：会员自己购买
				$notes=date('Y-m-d H:i',time())."通过：".D('Payment')->get_payment_info($order['payment_name'],true)." 成功充值 ".$order['amount']."元并开通{$setmeal['setmeal_name']}";
				$members_charge_log['_t']='MembersChargeLog';
				$members_charge_log["log_uid"]=$user['uid'];
			 	$members_charge_log["log_username"]=$user['username'];
			 	$members_charge_log["log_type"]=2;
			 	$members_charge_log["log_value"]=$notes;
			 	$members_charge_log["log_amount"]=$order['amount'];
			 	$members_charge_log["log_ismoney"]= $order['amount']>0?2:1;
			 	$members_charge_log["log_mode"]=1;
			 	$members_charge_log["log_utype"]=$user['utype'];
			 	setLog($members_charge_log);
			 	unset($members_log,$members_charge_log);
			}
			//积分支付
			else if($order['order_type'] == '2')			
			{		 
				$order_name = C('qscms_points_byname')."订单";
				$p_rst = D('MembersPoints')->report_deal($user['uid'],1,$order['points']);
				if($p_rst){
	                $handsel['uid'] = $user['uid'];
	                $handsel['htype'] = 'buy_points';
	                $handsel['htype_cn'] = '充值'.C('qscms_points_byname');
	                $handsel['operate'] = 1;
	                $handsel['points'] = $order['points'];
	                $handsel['addtime'] = time();
	                D('MembersHandsel')->members_handsel_add($handsel);
	            }
				$user_points=D('MembersPoints')->get_user_points($user['uid']);
				/* 会员日志 */
				write_members_log($user,9002,$order['service_name'],$order['payment_cn']);
				//会员套餐变更记录。会员购买成功。2表示：会员自己购买
				$members_charge_log['_t']='MembersChargeLog';
				$members_charge_log["log_uid"]=$user['uid'];
			 	$members_charge_log["log_username"]=$user['username'];
			 	$members_charge_log["log_type"]=2;
			 	$members_charge_log["log_value"]=$notes;
			 	$members_charge_log["log_amount"]=$order['amount'];
			 	$members_charge_log["log_ismoney"]= $order['amount']>0?2:1;
			 	$members_charge_log["log_mode"]=1;
			 	$members_charge_log["log_utype"]=$user['utype'];
			 	setLog($members_charge_log);
			 	unset($members_log,$members_charge_log);

			}
			//简历置顶
			if($order['order_type']=='3'){
				$this->order_paid_resume_stick($order,$user);
			}
			//醒目标签
			else if($order['order_type']=='4'){
				$this->order_paid_resume_tag($order,$user);
			}
			//简历模板
			else if($order['order_type']=='5'){
				$this->order_paid_resume_tpl($order,$user);
			}
			//增值包-简历包,短信包
			else if($order['order_type']=='6' || $order['order_type']=='7'){
				$this->order_paid_setmeal_increment($order,$user);
			}
			//增值包-职位推广
			else if($order['order_type']=='8' || $order['order_type']=='9'){
				$this->order_paid_job_promotion($order,$user);
			}
			//增值包-企业模板
			else if($order['order_type']=='10'){
				$this->order_paid_company_tpl($order,$user);
			}
			//增值包-诚聘通
			else if($order['order_type']=='11'){
				$this->order_paid_famous_company($order,$user);
			}
			//增值包-预约职位刷新
			else if($order['order_type']=='12'){
				$this->order_paid_auto_refresh_jobs($order,$user);
			}
			//单独购买职位刷新
			else if($order['order_type']=='13'){
				$this->order_paid_single_refresh_jobs($order,$user);
			}
			//单独购买简历下载
			else if($order['order_type']=='14'){
				$this->order_paid_single_resume_download($order,$user);
			}
			//站内信
			if($user['utype']==1){
            	$replac_mail['order_url']=rtrim(C('qscms_site_domain').C('qscms_site_dir'),'/').U('Home/CompanyService/order_detail',array('id'=>$order['id']));
            }else{
            	$replac_mail['order_url']=rtrim(C('qscms_site_domain').C('qscms_site_dir'),'/').U('Home/PersonalService/order_detail',array('id'=>$order['id']));
            }
			$pms_message = '订单'.$order['oid'].'已经支付成功，付款方式为：'.$order['payment_cn'].'，支付金额'.$order['pay_amount'].($order['pay_points']>0?('(抵扣'.$order['pay_points'].C('qscms_points_byname').')'):'').'。<a href="'.$replac_mail['order_url'].'" target="_blank">点击查看详情>></a>';
			D('Pms')->write_pmsnotice($user['uid'],$user['username'],$pms_message);
			// 发送邮件
			$mailMod = D('Mailconfig');
			$mailconfig = $mailMod->get_cache();
            if ($mailconfig['set_payment']==1 && $user['email_audit']==1 && $order['amount']>0)
            {   
                $send_mail['send_type']='set_payment';
	            $send_mail['sendto_email']=$user['email'];
	            $send_mail['subject']='set_payment_title';
	            $send_mail['body']='set_payment';
	            $replac_mail['service_name']=$order['service_name'];
	            $replac_mail['oid']=$order['oid'];
	            $replac_mail['pay_amount']=$order['pay_amount'];
	            $replac_mail['pay_points']=$order['pay_points'];
	            $replac_mail['points_byname']=C('qscms_points_byname');
	            $replac_mail['paytime']=date('Y-m-d H:i');
	            D('Mailconfig')->send_mail($send_mail,$replac_mail);         
            }
			// 发送短信
			if(false === $sms = F('sms_config')) $sms = D('SmsConfig')->config_cache();
			if (C('qscms_sms_open')==1 && $sms['set_payment']==1  && $user['mobile_audit']==1 && $order['amount']>0)
			{  
				$sendSms['mobile']=$user['mobile'];
                $sendSms['tpl']='set_payment';
                $sendSms['data']=array('sitename'=>C('qscms_site_name'),'sitedomain'=>C('qscms_site_domain'));
                D('Sms')->sendSms('notice',$sendSms);
			}
			//微信
			if(C('apply.Weixin')){
				D('Weixin/TplMsg')->set_payment($order['uid'],$order['oid'],$order['description'],$order['amount']);
			}
			return true;
	 	}
	 	else
	 	{
	 		return true;
	 	}
	}
	/*
		订单列表
		@$data 订单查询条件
	*/
	public function get_order_list($data,$pagesize=10)
	{
		$count = $this->where($data)->count();
		$pager =  pager($count, $pagesize);
		$rst['list'] = $this->where($data)->order('id desc')->limit($pager->firstRow . ',' . $pager->listRows)->select();
		foreach ($rst['list'] as $key => $value) {
			$rst['list'][$key]['invoice'] = D('OrderInvoice')->getone($value['id']);
		}
		$rst['page']=$pager->fshow();
		return $rst;
	}
	/**
	 * 付款后开通
	 */
	function admin_order_paid($id)
	{
		$time = time();
		$order = $this->where(array('id'=>$id))->find();
		return $this->order_paid($order['oid'],$time);
	}
}
?>