<?php
/**
 * 用法：
 * if(C('apply.Statistics')){
 * 		$idata = array('category'=>$category);
 * 		$class = new \Statistics\Model\CModel($idata);
 * 		$class->jobs_apply_add();
 * }
 */
namespace Statistics\Model;
class CModel{
	private $data;
	public function __construct($data){
		$this->data = $data;
		$this->data['addtime'] = time();
	}
  	public function resume_add(){
  		M('StatisticsResume')->add($this->data);
  	}
  	public function jobs_add(){
  		M('StatisticsJobs')->add($this->data);
  	}
  	public function company_add(){
  		M('StatisticsCompany')->add($this->data);
  	}
}
?>