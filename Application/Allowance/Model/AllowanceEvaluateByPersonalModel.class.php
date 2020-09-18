<?php
namespace Allowance\Model;
use Think\Model;
class AllowanceEvaluateByPersonalModel extends Model{
	protected function _before_insert(&$data,$options){
		$timestamp = time();
		$data['addtime'] = $timestamp;
	}
}
?>