<?php
namespace Common\Model;
use Think\Model;
class CompanyDownResumeModel extends Model
{
	public $state_arr = array('1'=>'可面试','2'=>'未接通','3'=>'不合适');
	protected $_validate = array(
		array('resume_id,resume_name,resume_uid,company_uid,company_name','identicalNull','',1,'callback'),
		array('resume_id,resume_uid,company_uid','identicalEnum','',1,'callback'),
	);
	protected $_auto = array (
		array('down_addtime','time',1,'function'),
		array('is_apply',0),
	);

	/*
		已下载的简历 列表
		@$data  company_down_resume 表中的条件
		@$state 简历标记状态
		@$pagesize 分页每页显示几条

		返回值 array 
		$rst['list'] 列表数据
		$rst['page'] 分页信息
		*/
	public function get_down_resume($data,$state,$pagesize=10)
	{
		$where = $data;
		$db_pre = C('DB_PREFIX');	
		$this_t = $db_pre.'company_down_resume';
		$join = 'left join '.$db_pre .'resume r on r.id='.$this_t.'.resume_id';
		if($state != '')
		{
			$where['is_reply']=intval($state);
		}
		$count = $this->where($where)->join($join)->count();
		$pager =  pager($count, $pagesize);
		$rst['list'] = $this->where($where)->join($join)->field($this_t.'.*,r.title,r.display_name,r.sex,r.fullname,r.sex_cn,r.birthdate,r.experience_cn,r.education_cn,r.intention_jobs,r.major_cn')->order('did desc')->limit($pager->firstRow . ',' . $pager->listRows)->select();
		foreach ($rst['list'] as $key => $val) 
		{
			
			$val['jobs_name_']=cut_str($val['jobs_name'],7,0,"...");
			$val['resume_url']=url_rewrite('QS_resumeshow',array('id'=>$val['resume_id'],'apply'=>1));
			$y=date("Y");
			if(intval($val['birthdate']) == 0)
			{
				$val['age']='';
			}
			else
			{
				$val['age']=$y-$val['birthdate'];
			}
			/* 教育经历 培训经历 */
			// $val['resume_education_list']=M('ResumeEducation')->where(array('uid'=>$val['ruid'],'pid'=>$val['resume_id']))->select();
			// $val['resume_work_list']=M('ResumeWork')->where(array('uid'=>$val['ruid'],'pid'=>$val['resume_id']))->select();
			/*
				获取简历标记
			*/
			// $val_state= M('CompanyLabelResume')->where(array('uid'=>$data['company_uid'],'resume_id'=>$val['resume_id']))->find();
			// $val['resume_state']=$val_state['resume_state'];
			// $val['resume_state_cn']=$val_state['resume_state_cn'];
			$rst['list'][$key] = $val;
		}
		$rst['count'] = $count;
		$rst['page'] = $pager->fshow();
		return $rst;
	}
	
	public function save_down_resume($addarr){
		if(false === $this->create($addarr)) return $this->getError();
		if(false === $insert_id = $this->add()) return $this->getError();
		return false;
	}
	/*
	 * 保存已下载简历
	 */
	public function add_down_resume($addarr,$user){
		if($user['utype']!=1){
			return array('state'=>0,'msg'=>'必须是企业会员才可以下载简历');
		}
		if($user['status']==2){
			return array('state'=>0,'msg'=>'您的账号处于暂停状态，请联系管理员设为正常后进行操作');
		}
		if(!$addarr['rid']){
			return array('state'=>0,'msg'=>'请选择简历');
		}
		$resume_num = count($addarr['rid']);
		if(C('qscms_down_resume_limit')==1){
			$user_jobs=D('Jobs')->count_auditjobs_num($user['uid']);
			if ($user_jobs==0)
			{
				return array('state'=>0,'msg'=>'你没有发布职位或审核未通过导致无法下载简历');
			}
		}
		else if(C('qscms_down_resume_limit')==3){
			$companyinfo = M('CompanyProfile')->where(array('uid'=>$user['uid']))->find();
			if ($companyinfo['audit']!=1)
			{
				return array('state'=>0,'msg'=>'你的营业执照未通过认证导致无法下载简历');
			}
		}
		$setmeal=D('MembersSetmeal')->get_user_setmeal($user['uid']);
		
		if($setmeal['download_resume_max']>0)
		{
			$downwhere['down_addtime'] = array('between',strtotime('today').','.strtotime('tomorrow'));
			$downwhere['company_uid'] = $user['uid'];
			$downnum = $this->where($downwhere)->count();
			if($downnum>=$setmeal['download_resume_max']){
				return array('state'=>0,'msg'=>'您今天已下载 <span class="font_yellow">'.$downnum.'</span> 份简历，已达到每天下载上限，请先收藏该简历，明天继续下载。');
			}
		}
		elseif ($setmeal['download_resume']<=0)
		{
			$members_points = D('MembersPoints')->get_user_points($user['uid']);
			if($members_points<C('qscms_download_resume_price')*C('qscms_payment_rate')*$resume_num){
				return array('state'=>0,'error'=>'你的'.C('qscms_points_byname')."不足，不能下载简历！");
			}
		}
		if(count($addarr['rid'])>1){
			//批量
			return $this->_add_down_resume_batch($addarr['rid'],$user,$setmeal);
		}else{
			//单个
			return $this->_add_down_resume_one($addarr,$user,$setmeal);
		}
	}
	/**
	 * 批量下载简历
	 */
	protected function _add_down_resume_batch($rid,$user,$setmeal){
		$num = 0;
		//检测是否下载过简历
		if ($user['uid'])
		{
			$resume_id_arr=$this->get_down_resume_id($rid,$user['uid']);
			foreach ($rid as $key => $value) {
				if(isset($resume_id_arr[$value])){
					unset($rid[$key]);
				}
			}
		}
		//检测是否申请过职位
		if($user['uid'] && $setmeal['show_apply_contact']=='1' && $rid){
			$resume_id_arr=D('PersonalJobsApply')->get_jobs_apply_resume_id($rid,$user['uid']);
			foreach ($rid as $key => $value) {
				if(isset($resume_id_arr[$value])){
					unset($rid[$key]);
				}
			}
		}
		if($rid){
			$resume_arr = D('Resume')->where(array('id'=>array('in',$rid)))->select();
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
				$company = M('CompanyProfile')->where(array('uid'=>$user['uid']))->find();
				$addarr["company_uid"]=$user['uid'];
				$addarr["resume_uid"]=$value['uid'];
				$addarr['company_name']=$company['companyname'];
				$addarr['down_addtime'] = time();
				$ruser = D('Members')->get_user_one(array('uid'=>$value['uid']));
					
				if (!$this->save_down_resume($addarr))
				{
					if($setmeal['download_resume']>0){
						D('MembersSetmeal')->action_user_setmeal($user['uid'],"download_resume",2);
						//写入会员日志
						write_members_log($user,1012,implode(",", $rid),'消耗套餐下载简历条数');
						$members_log['_t']='MembersSetmealLog';
					    $members_log['log_uid']=$user['uid'];
					    $members_log['log_utype']=$user['utype'];
					    $members_log['log_username']=$user['username'];
					    $members_log['log_leave']=$setmeal['download_resume']-1;
					    $members_log['log_value'] = '下载简历(简历名称：'.$addarr['resume_name'].')';
					    $members_log['log_source']='网页版';
					    setLog($members_log);
					    unset($members_log);
					}else{
						D('MembersPoints')->report_deal($user['uid'],2,C('qscms_download_resume_price')*C('qscms_payment_rate'));
						//写入会员日志
						write_members_log($user,1012,implode(",", $rid),C('qscms_points_byname').'兑换下载');
						// 写入会员积分操作日志
						$handsel['uid'] = $user['uid'];
						$handsel['htype'] = '下载简历';
						$handsel['htype_cn'] = '下载简历';
						$handsel['operate'] = 2;
						$handsel['points'] = C('qscms_download_resume_price')*C('qscms_payment_rate');
						$handsel['addtime'] = time();
						D('MembersHandsel')->members_handsel_add($handsel);
					}
					$num++;
				}
			}
		}
		if($num>0){
			return array('state'=>1,'msg'=>'成功下载'.$num.'份简历！');
		}else{
			return array('state'=>0,'msg'=>'下载失败！');
		}
	}
	/**
	 * 单个下载简历
	 */
	protected function _add_down_resume_one($addarr,$user,$setmeal){
		$addarr['resume_id'] = $addarr['rid'][0];
		//检测是否下载过简历
		if ($user['uid'])
		{
			$info=$this->check_down_resume($addarr['resume_id'],$user['uid']);
			if ($info)
			{
				return array('state'=>0,'msg'=>'您已经下载过此简历了');
			}
		}
		//检测是否申请过职位
		if($user['uid'] && $setmeal['show_apply_contact']=='1'){
			$has = D('PersonalJobsApply')->check_jobs_apply($addarr['resume_id'],$user['uid']);
			if($has){
				return array('state'=>0,'msg'=>'简历联系方式可见，您无需下载此简历');
			}
		}
		$resume=D('Resume')->get_resume_one($addarr['resume_id']);
		if ($resume['display_name']=="2")
		{
			$addarr['resume_name']="N".str_pad($resume['id'],7,"0",STR_PAD_LEFT);
		}
		elseif ($resume['display_name']=="3")
		{
			if($resume['sex']==1)
			{
					$addarr['resume_name']=cut_str($resume['fullname'],1,0,"先生");
			}
			elseif($resume['sex']==2)
			{
				$addarr['resume_name']=cut_str($resume['fullname'],1,0,"女士");
			}
		}
		else
		{
		$addarr['resume_name']=$resume['fullname'];
		}
		$company = M('CompanyProfile')->where(array('uid'=>$user['uid']))->find();
		$addarr["company_uid"]=$user['uid'];
		$addarr["resume_uid"]=$resume['uid'];
		$addarr['company_name']=$company['companyname'];
		$addarr['down_addtime'] = time();
		$ruser = D('Members')->get_user_one(array('uid'=>$resume['uid']));
		$r = $this->save_down_resume($addarr);
		if (!$r)
		{
			if($setmeal['download_resume']>0){
				D('MembersSetmeal')->action_user_setmeal($user['uid'],"download_resume",2);
				//写入会员日志
				write_members_log($user,1012,$addarr['resume_id'],'消耗套餐下载简历条数');
				$members_log['_t']='MembersSetmealLog';
			    $members_log['log_uid']=$user['uid'];
			    $members_log['log_utype']=$user['utype'];
			    $members_log['log_username']=$user['username'];
			    $members_log['log_leave']=$setmeal['download_resume']-1;
			    $members_log['log_value'] = '下载简历(简历名称：'.$addarr['resume_name'].')';
			    $members_log['log_source']='网页版';
			    setLog($members_log);
			    unset($members_log);
			}else{
				D('MembersPoints')->report_deal($user['uid'],2,C('qscms_download_resume_price')*C('qscms_payment_rate'));
				//写入会员日志
				write_members_log($user,1012,$addarr['resume_id'],C('qscms_points_byname').'兑换下载');
				// 写入会员积分操作日志
				$handsel['uid'] = $user['uid'];
				$handsel['htype'] = 'down_resume';
				$handsel['htype_cn'] = '下载简历';
				$handsel['operate'] = 2;
				$handsel['points'] = C('qscms_download_resume_price')*C('qscms_payment_rate');
				$handsel['addtime'] = time();
				D('MembersHandsel')->members_handsel_add($handsel);
			}
			return array('state'=>1,'msg'=>'下载简历成功');
		}else{
			return array('state'=>0,'msg'=>$r);
		}
	}
	/**
	 * 检测是否下载过简历
	 */
	public function check_down_resume($resume_id,$company_uid){
		$where['resume_id'] = $resume_id;
		$where['company_uid'] = $company_uid;
		return $this->where($where)->find();
	}
	/**
	 * 获取下载过的简历的id
	 */
	protected function get_down_resume_id($rid,$company_uid){
		$where['resume_id'] = array('in',$rid);
		$where['company_uid'] = $company_uid;
		return $this->where($where)->getField('resume_id,did');
	}
}
?>