<?php
namespace Recommend\Controller;
use Common\Controller\FrontendController;
class IndexController extends FrontendController{
	// 初始化函数
	public function _initialize(){
		parent::_initialize();
		if(C('apply.Subsite') && C('SUBSITE_VAL.s_id') > 0) $this->sub = C('SUBSITE_VAL.s_id');
	}
	public function index(){
		cookie('_index_recommend_resume'.$this->sub,null);
	}
	public function set_resume($key){
		$data = cookie('_index_recommend_resume'.$this->sub);
		$time = cookie('_index_recommend_time'.$this->sub);
		if(strtotime('today') > $time){
			$data = array();
			$data[] = $key;
		}elseif(count($data) >= 3){
			$data[2] = $key;
		}else{
			$data[] = $key;
		}
		cookie('_index_recommend_time'.$this->sub,time());
		cookie('_index_recommend_resume'.$this->sub,$data,2592000);
	}
	public function set_jobs($key){
		$data = cookie('_index_recommend_jobs'.$this->sub);
		$time = cookie('_index_recommend_time'.$this->sub);
		if(strtotime('today') > $time){
			$data = array();
			$data[] = $key;
		}elseif(count($data) >= 3){
			$data[2] = $key;
		}else{
			$data[] = $key;
		}
		cookie('_index_recommend_time'.$this->sub,time());
		cookie('_index_recommend_jobs'.$this->sub,$data,2592000);
	}
	public function get_recommend(){
		if($resume = cookie('_index_recommend_resume'.$this->sub)){
			$where = array(
				'关键字' => implode(' ',$resume),
	    		'显示数目' => 12,
	    		'分页显示' => 1,
	    		'关健字类型' => 'or'
	    	);
	    	$resume_mod = new \Common\qscmstag\resume_listTag($where);
	    	$resume_list = $resume_mod->run();
	    	$this->assign('resume_list',$resume_list['list']);
	    	$data['html'] = $this->fetch('index_resume_list');
	    	$data['isfull'] = $resume_list['page_params']['nowPage'] >= $resume_list['page_params']['totalPages'];
	    	$this->ajaxReturn(1,'简历列表信息获取成功！',$data);
		}elseif($jobs = cookie('_index_recommend_jobs'.$this->sub)){
			$where = array(
	    		'关键字' => implode(' ',$jobs),
	    		'搜索类型' => 'full',
	    		'排序' => 'rtime',
	    		'显示数目' => 12,
	    		'分页显示' => 1,
	    		'关健字类型' => 'or'
	    	);
	    	$jobs_mod = new \Common\qscmstag\jobs_listTag($where);
	    	$jobs_list = $jobs_mod->run();
	    	$this->assign('jobs_list',$jobs_list['list']);
	    	$data['html'] = $this->fetch('index_jobs_list');
	    	$data['isfull'] = $jobs_list['page_params']['nowPage'] >= $jobs_list['page_params']['totalPages'];
	    	$this->ajaxReturn(1,'职位列表信息获取成功！',$data);
		}
		$this->ajaxReturn(0);
	}
}
?>