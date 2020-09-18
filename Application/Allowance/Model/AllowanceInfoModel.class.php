<?php
namespace Allowance\Model;
use Think\Model;
class AllowanceInfoModel extends Model{
	public $type_alias_cn = array('apply'=>'投递红包','interview'=>'面试红包','entry'=>'入职红包');
	/**
	 * 设置红包职位
	 */
	public function add_allowance_job($data){
		return $this->add($data);
	}
	/**
	 * 红包职位付款后设置成功
	 */
	public function set_allowance_job($oid){
		$data['status'] = 1;
		$data['addtime'] = time();
		$this->where(array('oid'=>array('eq',$oid)))->save($data);
		$info = $this->where(array('oid'=>array('eq',$oid)))->find();
		$this->_after_set($info);
	}
	protected function _before_insert(&$data,$options){
		$timestamp = time();
		if(!$data['complete_percent']){
			$data['complete_percent'] = 0;
		}
		$data['jobsname'] = D('Common/Jobs')->where(array('id'=>$data['jobsid']))->getField('jobs_name');
		if(!$data['jobsname']){
			$data['jobsname'] = D('Common/JobsTmp')->where(array('id'=>$data['jobsid']))->getField('jobs_name');
		}
		if(!$data['education']){
			$data['education'] = '';
			$data['education_cn'] = '不限';
		}else{
			$edu_arr = explode(",", $data['education']);
			$data['education_cn'] = '';
			$category_education = D('Common/Category')->get_category_cache('QS_education');
			foreach ($edu_arr as $key => $value) {
				$data['education_cn'] .= $category_education[$value].',';
			}
			$data['education_cn'] = rtrim($data['education_cn'],',');
		}
		if(!$data['experience']){
			$data['experience'] = '';
			$data['experience_cn'] = '不限';
		}else{
			$exp_arr = explode(",", $data['experience']);
			$data['experience_cn'] = '';
			$category_experience = D('Common/Category')->get_category_cache('QS_experience');
			foreach ($exp_arr as $key => $value) {
				$data['experience_cn'] .= $category_experience[$value].',';
			}
			$data['experience_cn'] = rtrim($data['experience_cn'],',');
		}
		if(!$data['intention_jobs']){
			$data['intention_jobs'] = '';
			$data['intention_jobs_cn'] = '不限';
		}else{
            $category_jobs = D('Common/CategoryJobs')->get_jobs_cache('all');
            foreach(explode(',',$data['intention_jobs']) as $val) {
                $val = explode('.',$val);
                $intention[] = $val[2] ? $category_jobs[$val[1]][$val[2]] : ($val[1] ? $category_jobs[$val[0]][$val[1]] : $category_jobs[0][$val[0]]);
            }
            $data['intention_jobs_cn'] = implode(',',$intention);
		}
		if($data['payment']){
			$data['payment_cn'] = D('Common/Payment')->get_payment_info($data['payment'],true);
			$data['oid'] = strtoupper(substr($data['payment'],0,1))."-".date('ymd',$timestamp)."-".date('His',$timestamp);
		}
		if($data['per_amount']>0 && $data['total_num']>0){
			$data['surplus_num'] = $data['total_num'];
			$data['pay_amount'] = $data['per_amount'] * $data['total_num'];
		}
		$data['status'] = 0;
		$data['addtime'] = $timestamp;
	}
	/**
	 * 付款开通后设置职位状态
	 */
	protected function _after_set($info){
        D('Common/Jobs')->where(array('id'=>array('eq',$info['jobsid'])))->setField('allowance_id',$info['id']);
        D('Common/JobsTmp')->where(array('id'=>array('eq',$info['jobsid'])))->setField('allowance_id',$info['id']);
        D('Common/JobsSearch')->where(array('id'=>array('eq',$info['jobsid'])))->setField('allowance_id',$info['id']);
        D('Common/JobsSearchKey')->where(array('id'=>array('eq',$info['jobsid'])))->setField('allowance_id',$info['id']);
        //写入交易记录
        $deallog['uid'] = $info['uid'];
        $companyinfo = D('Common/CompanyProfile')->where(array('uid'=>$info['uid']))->find();
        $deallog['companyname'] = $companyinfo['companyname'];
    	$deallog['amount'] = $info['pay_amount'];
    	$deallog['info_id'] = $info['id'];
    	$deallog['note'] = '设置【'.$this->get_alias_cn($info['type_alias']).'】；红包数量：'.$info['total_num'].'；单个红包金额：'.$info['per_amount'].'元；职位id：'.$info['jobsid'].'；职位名称：'.$info['jobsname'];
    	D('Allowance/AllowanceDealLogCompany')->add($deallog);
    	//短信通知
    	if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
		$userinfo=M('Members')->where(array('uid'=>$info['uid']))->find();
    	if (C('qscms_sms_open')==1 && $sms['set_allowance_jobs_add']=="1"  && $userinfo['mobile_audit']=="1")
		{
            $sendSms['mobile']=$userinfo['mobile'];
            $sendSms['tpl']='set_allowance_jobs_add';
            $sendSms['data']=array('sitename'=>C('qscms_site_name'),'jobsname'=>$info['jobsname'],'type'=>$this->get_alias_cn($info['type_alias']),'num'=>$info['total_num'],'per_amount'=>$info['per_amount']);
            D('Common/Sms')->sendSms('notice',$sendSms);
		}
        //微信提醒
        if(C('apply.Weixin')){
            D('Allowance/AllowanceTplMsg')->set_allowance_jobs_add($info['uid'],$info['jobsname'].'【'.$this->get_alias_cn($info['type_alias']).'】（'.$info['per_amount'].'元/个*'.$info['total_num'].'个）',$info['type_alias']);
        }
    }
    public function get_alias_cn($alias){
    	return $this->type_alias_cn[$alias];
    }
    /**
     * 检查剩余红包数量
     */
    public function check_surplusnum($info_id){
    	$info = $this->find($info_id);
    	return $info['surplus_num'];
    }
    /**
     * 剩余红包数减$num
     * 如果红包数减少到0，将对应职位的红包设置为0
     */
    public function surplusnum_minus($info_id,$num = 1){
    	$this->where(array('id'=>array('eq',$info_id)))->setDec('surplus_num',$num);
    	$info = $this->find($info_id);
    	if($info['surplus_num']<=0){
    		D('Common/Jobs')->where(array('id'=>array('eq',$info['jobsid'])))->setField('allowance_id',0);
        	D('Common/JobsTmp')->where(array('id'=>array('eq',$info['jobsid'])))->setField('allowance_id',0);
	        D('Common/JobsSearch')->where(array('id'=>array('eq',$info['jobsid'])))->setField('allowance_id',0);
	        D('Common/JobsSearchKey')->where(array('id'=>array('eq',$info['jobsid'])))->setField('allowance_id',0);
    		$this->where(array('id'=>array('eq',$info_id)))->save(array('status'=>0));
    	}
    }
    /**
     * 检查是否绑定微信
     */
    public function check_binding_weixin($uid){
    	$userbind = M('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
    	return $userbind;
    }
    /**
     * 检查投递的简历是否符合企业设置的条件
     */
    public function check_intention($id,$resumeinfo){
    	$tip = array(
    		'complete_percent'=>1,
    		'education'=>1,
    		'experience'=>1,
    		'intention_jobs'=>1
    	);
    	$info = $this->find($id);
    	if($info['complete_percent'] && $resumeinfo['complete_percent']<$info['complete_percent']){
    		$tip['complete_percent'] = 0;
    	}
    	$education = $info['education']?explode(",", $info['education']):array();
    	if(!empty($education) && !in_array($resumeinfo['education'], $education)){
    		$tip['education'] = 0;
    	}
    	$experience = $info['experience']?explode(",", $info['experience']):array();
    	if(!empty($experience) && !in_array($resumeinfo['experience'], $experience)){
    		$tip['experience'] = 0;
    	}
    	$info_intention_jobs = $info['intention_jobs']?explode(",", $info['intention_jobs']):array();
    	if(!empty($info_intention_jobs)){
    		$match = false;
    		$all_arr = array();
	    	foreach ($info_intention_jobs as $key => $value) {
	    		$arr = explode(".", $value);
	    		$all_arr[] = $arr[0].'.0.0';
	    		$all_arr[] = $arr[0].'.'.$arr[1].'.0';
	    		$all_arr[] = $arr[0].'.'.$arr[1].'.'.$arr[2];
	    	}
	    	array_unique($all_arr);
	    	$resume_intention_jobs = $resumeinfo['intention_jobs_id']?explode(",", $resumeinfo['intention_jobs_id']):array();
	    	foreach ($resume_intention_jobs as $key => $value) {
	    		if(in_array($value,$all_arr)){
	    			$match = true;
	    			break;
	    		}
	    	}
	    	if(!$match){
	    		$tip['intention_jobs'] = 0;
	    	}
    	}
    	if(in_array(0,$tip)){
    		return array('status'=>0,'tip'=>$tip);
    	}else{
    		return array('status'=>1);
    	}
    }
}
?>