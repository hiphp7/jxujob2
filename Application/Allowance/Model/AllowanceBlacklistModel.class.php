<?php
namespace Allowance\Model;
use Think\Model;
class AllowanceBlacklistModel extends Model{
	public $timelimit_options = array(0=>'永久',3=>'3天',7=>'7天',15=>'15天',30=>'30天');
	protected function _before_insert(&$data,$options){
		if($this->where(array('uid'=>$data['uid']))->find()){
			$this->where(array('uid'=>$data['uid']))->delete();
		}
		$timestamp = time();
		$data['addtime'] = $timestamp;
		if($data['robot']==1){
			$data['admin_name'] = '';
			$data['note'] = '';
		}
	}
	public function release($uid){
		$uid_arr = is_array($uid)?$uid:array($uid);
		D('Allowance/AllowanceEditIntentionLog')->where(array('uid'=>array('in',$uid_arr)))->setField('enable',0);
		D('Allowance/AllowanceDealLogPersonal')->where(array('uid'=>array('in',$uid_arr)))->setField('enable_count',0);
		$this->where(array('uid'=>array('in',$uid_arr)))->delete();
		return true;
	}
}
?>