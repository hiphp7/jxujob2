<?php
namespace Allowance\Model;
use Think\Model;
class AllowanceEvaluateByCompanyModel extends Model{
	protected function _before_insert(&$data,$options){
		$timestamp = time();
		$data['addtime'] = $timestamp;
	}
}
?>