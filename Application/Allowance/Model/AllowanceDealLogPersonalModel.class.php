<?php
namespace Allowance\Model;
use Think\Model;
class AllowanceDealLogPersonalModel extends Model{
	protected function _before_insert(&$data,$options){
		$timestamp = time();
		$data['addtime'] = $timestamp;
		$data['enable_count'] = 1;
	}
	public function check($uid){
		$days = 3;
		$times = 1;
		$map['uid'] = $uid;
		$map['addtime'] = array('egt',strtotime('-'.$days.' days'));
		$map['enable_count'] = 1;
		$count = $this->where($map)->count();
		if($count>=$times){
			return false;
		}else{
			return true;
		}
	}
}
?>