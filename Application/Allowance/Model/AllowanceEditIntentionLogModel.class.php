<?php
namespace Allowance\Model;
use Think\Model;
class AllowanceEditIntentionLogModel extends Model{
	protected function _before_insert(&$data,$options){
		$timestamp = time();
		$data['addtime'] = $timestamp;
		$data['enable'] = 1;
	}
	public function check($uid){
		//15天内修改意向职位4次，触发流程控制
		if(!$this->_check($uid,4,15)){
			return false;
		}
		//累计修改意向职位10次，触发流程控制
		if(!$this->_check($uid,10)){
			return false;
		}
		return true;		
	}
	protected function _check($uid,$times,$days=0){
		$map['uid'] = $uid;
		$map['enable'] = 1;
		$days>0 && $map['addtime'] = array('egt',strtotime('-'.$days.' days'));
		$count = $this->where($map)->count();
		if($count>=$times){
			return false;
		}else{
			return true;
		}
	}
	public function check_intention_jobs($intention_jobs_id,$intention_jobs_cn,$resumeid){
		$intention_jobs_id_arr = explode(",", $intention_jobs_id);
		$resume = D('Common/Resume')->find($resumeid);
		$old_intention_jobs_id_arr = explode(",", $resume['intention_jobs_id']);
		$result1 = array_diff($intention_jobs_id_arr, $old_intention_jobs_id_arr);
		$result2 = array_diff($old_intention_jobs_id_arr,$intention_jobs_id_arr);
		if(!empty($result1) || !empty($result2)){
			//如果提交的意向职位和原意向职位不一致，检测是否超出时间限制
			//如果超出限制返回不允许修改，否则新增一条修改日志
			if(false===$config=F('allowance_config')){
				$config = D('Allowance/AllowanceConfig')->config_cache();
			}
			if($config['resume_intentionjobs_edit_timespace']>0){
				//最后一次修改记录
				$last_edit_info = $this->where(array('uid'=>$resume['uid'],'enable'=>1))->order('addtime desc')->find();
				if($last_edit_info && $last_edit_info['addtime']>(time()-$config['resume_intentionjobs_edit_timespace']*3600)){
					return false;
				}
			}
			$setsqlarr['uid'] = $resume['uid'];
			$setsqlarr['resume_id'] = $resumeid;
			$setsqlarr['intention_jobs'] = $intention_jobs_id;
			$setsqlarr['intention_jobs_cn'] = $intention_jobs_cn;
			$setsqlarr['is_new_record'] = 0;
			$this->add($setsqlarr);
		}
		return true;
	}
}
?>