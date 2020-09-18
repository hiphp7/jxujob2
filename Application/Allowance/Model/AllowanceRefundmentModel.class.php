<?php
namespace Allowance\Model;
use Think\Model;
class AllowanceRefundmentModel extends Model{
	protected function _before_insert(&$data,$options){
		$data['status'] = 1;
	}
}
?>