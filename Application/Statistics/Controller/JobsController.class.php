<?php
namespace Statistics\Controller;
use Statistics\Controller\CController;
class JobsController extends CController{
	private $type;
	public function _initialize() {
        parent::_initialize();
    }
	/**
     * 学历要求
     */
	public function edu(){
		$eduArr = array();
    	if($this->start_year==$this->end_year){
    		//只选了一个时间的情况,不做对比
    		$min = strtotime($this->start_year.'-'.$this->start_month);
			if($this->end_month==13){
				$max = strtotime(($this->start_year+1).'-1');
			}else{
				$max = strtotime($this->start_year.'-'.$this->end_month);
			}
			$cachename = $min.'_'.$max.'_job_edu_value.cache';
			$check_cache = check_cache($cachename,'module/');
			if($check_cache){
				$data = $check_cache;
			}else{
				$where['addtime'] = array(array('gt',$min),array('lt',$max),'and');
				$where['education'] = array('gt',0);
		    	$dataArr = array();
		    	$dArr = array();
				$education = D('Category')->get_category_cache('QS_education');
	    		foreach ($education as $key => $value) {
	    			$eduArr[$key]['label'] = $value;
	    		}
				$subQuery = D('StatisticsJobs')->field('education,amount')->where($where)->buildSql();
				$dataObj = $this->model->table($subQuery.' g')->field('sum(amount) AS num,g.education')->group('g.education')->order('num desc')->select();
		   
			    foreach ($dataObj as $key => $value) {
	    			$eduArr[$value['education']]['value'] = $value['num'];
	    		}
	    		$i = 0;
	    		foreach ($eduArr as $key => $value) {
	    			$dArr[$i]['label'] = $value['label'];
	    			$dArr[$i]['value'] = $value['value'];
	    			$i++;
	    		}
	    		usort($dArr, function($a, $b) {
		            $al = $a['value'];
		            $bl = $b['value'];
		            if ($al == $bl)
		                return 0;
		            return ($al > $bl) ? -1 : 1;
		        });	
	    		
			    $dataArr = array(
			    		$dArr
			    	);
			    $data = json_encode($dataArr);
			    write_cache($cachename,'module/',$data);
			}
	   		$this->assign('data',$data);
		}else{
			$compare = true;
			$min1 = strtotime($this->start_year.'-'.$this->start_month);
			$min2 = strtotime($this->end_year.'-'.$this->start_month);
			if($this->end_month==13){
				$max1 = strtotime(($this->start_year+1).'-1');
				$max2 = strtotime(($this->end_year+1).'-1');
			}else{
				$max1 = strtotime($this->start_year.'-'.$this->end_month);
				$max2 = strtotime($this->end_year.'-'.$this->end_month);
			}
			$cachename = $min1.'_'.$max1.'_'.$min2.'_'.$max2.'_job_edu_compare_value.cache';
			$cachename_category = $min1.'_'.$max1.'_'.$min2.'_'.$max2.'_job_edu_compare_category_value.cache';
			$check_cache = check_cache($cachename,'module/');
			$check_cache_category = check_cache($cachename_category,'module/');
			if($check_cache && $check_cache_category){
				$categories = $check_cache_category;
				$dataset = $check_cache;
			}else{
				$where['addtime'] = array(array('gt',$min1),array('lt',$max1),'and');
				$where['education'] = array('gt',0);
				$education = D('Category')->get_category_cache('QS_education');
	    		foreach ($education as $key => $value) {
	    			$eduArr['labelArr'][$key]['label'] = $value;
	    		}
				$subQuery = D('StatisticsJobs')->field('education,amount')->where($where)->buildSql();
				$dataObj = $this->model->table($subQuery.' g')->field('sum(amount) AS num,g.education')->group('g.education')->order('num desc')->select();
	   			foreach ($dataObj as $key => $value) {
	    			$eduArr['valueArr'][$value['education']]['value'] = $value['num'];
	    		}
				$where['addtime'] = array(array('gt',$min2),array('lt',$max2),'and');
				$subQuery = D('StatisticsJobs')->field('education,amount')->where($where)->buildSql();
				$dataObjTmp = $this->model->table($subQuery.' g')->field('sum(amount) AS num,g.education')->group('g.education')->order('num desc')->select();
	    		foreach ($dataObjTmp as $key => $value) {
	    			$eduArr['valueArrTmp'][$value['education']]['value'] = $value['num'];
	    		}

	    		//整理成无key值的数组start
	    		$labelArr = array();
	    		$valueArr = array();
	    		$valueArrTmp = array();
	    		foreach ($eduArr['labelArr'] as $key => $value) {
	    			$labelArr[] = $value;
	    			$valueArr[] = $eduArr['valueArr'][$key];
	    			$valueArrTmp[] = $eduArr['valueArrTmp'][$key];
	    		}
	    		//整理成无key值的数组end

			    $categories = array(
			    	'category'=>array(
			    		$labelArr
			    		)
			    	);
			    $categories = json_encode($categories);
			    $dataset = array(
			    	array(
			    		'seriesname'=>$this->start_year.'年'.$this->start_month.'月 - '.($this->end_month-1).'月',
				    	'data'=>array(
				    		$valueArr
				    		)
			    		),
			    	array(
			    		'seriesname'=>$this->end_year.'年'.$this->start_month.'月 - '.($this->end_month-1).'月',
				    	'data'=>array(
				    		$valueArrTmp
				    		)
			    		)
			    	);
			    $dataset = json_encode($dataset);
			    write_cache($cachename_category,'module/',$categories);
				write_cache($cachename,'module/',$dataset);
			}
		    $this->assign('categories',$categories);
		    $this->assign('dataset',$dataset);
			
		}
		$this->assign('compare',$compare);
	    $this->assign('caption','学历要求');
	    $this->assign('xAxisName','学历');
	    $this->assign('yAxisName','数值');

		if($compare){
			$this->display('mscolumn2d');
		}else{
			$this->display('column2d');
		}
	}
	/**
     * 工作经验
     */
	public function expe(){
		$expArr = array();
    	if($this->start_year==$this->end_year){
    		//只选了一个时间的情况,不做对比
    		$min = strtotime($this->start_year.'-'.$this->start_month);
			if($this->end_month==13){
				$max = strtotime(($this->start_year+1).'-1');
			}else{
				$max = strtotime($this->start_year.'-'.$this->end_month);
			}
			$cachename = $min.'_'.$max.'_job_exp_value.cache';
			
			$check_cache = check_cache($cachename,'module/');
			if($check_cache){
				$data = $check_cache;
			}else{
				$where['addtime'] = array(array('gt',$min),array('lt',$max),'and');
				$where['experience'] = array('gt',0);
				
		    	$dataArr = array();
		    	$dArr = array();
				$experience = D('Category')->get_category_cache('QS_experience');
	    		foreach ($experience as $key => $value) {
	    			$expArr[$key]['label'] = $value;
	    		}
				$subQuery = D('StatisticsJobs')->field('experience,amount')->where($where)->buildSql();
				$dataObj = $this->model->table($subQuery.' g')->field('sum(amount) AS num,g.experience')->group('g.experience')->order('num desc')->select();
		   
			    foreach ($dataObj as $key => $value) {
	    			$expArr[$value['experience']]['value'] = $value['num'];
	    		}
	    		$i = 0;
	    		foreach ($expArr as $key => $value) {
	    			$dArr[$i]['label'] = $value['label'];
	    			$dArr[$i]['value'] = $value['value'];
	    			$i++;
	    		}
	    		usort($dArr, function($a, $b) {
		            $al = $a['value'];
		            $bl = $b['value'];
		            if ($al == $bl)
		                return 0;
		            return ($al > $bl) ? -1 : 1;
		        });	
	    		
			    $dataArr = array(
			    		$dArr
			    	);
			    $data = json_encode($dataArr);
			    write_cache($cachename,'module/',$data);
			}
	   		$this->assign('data',$data);
		}else{
			$compare = true;
			$min1 = strtotime($this->start_year.'-'.$this->start_month);
			$min2 = strtotime($this->end_year.'-'.$this->start_month);
			if($this->end_month==13){
				$max1 = strtotime(($this->start_year+1).'-1');
				$max2 = strtotime(($this->end_year+1).'-1');
			}else{
				$max1 = strtotime($this->start_year.'-'.$this->end_month);
				$max2 = strtotime($this->end_year.'-'.$this->end_month);
			}
			$cachename = $min1.'_'.$max1.'_'.$min2.'_'.$max2.'_job_exp_compare_value.cache';
			$cachename_category = $min1.'_'.$max1.'_'.$min2.'_'.$max2.'_job_exp_compare_category_value.cache';
			$check_cache = check_cache($cachename,'module/');
			$check_cache_category = check_cache($cachename_category,'module/');
			if($check_cache && $check_cache_category){
				$categories = $check_cache_category;
				$dataset = $check_cache;
			}else{
				$where['addtime'] = array(array('gt',$min1),array('lt',$max1),'and');
				$where['experience'] = array('gt',0);
				$experience = D('Category')->get_category_cache('QS_experience');
	    		foreach ($experience as $key => $value) {
	    			$expArr['labelArr'][$key]['label'] = $value;
	    		}
				$subQuery = D('StatisticsJobs')->field('experience,amount')->where($where)->buildSql();
				$dataObj = $this->model->table($subQuery.' g')->field('sum(amount) AS num,g.experience')->group('g.experience')->order('num desc')->select();
	   			foreach ($dataObj as $key => $value) {
	    			$expArr['valueArr'][$value['experience']]['value'] = $value['num'];
	    		}
				$where['addtime'] = array(array('gt',$min2),array('lt',$max2),'and');
				$subQuery = D('StatisticsJobs')->field('experience,amount')->where($where)->buildSql();
				$dataObjTmp = $this->model->table($subQuery.' g')->field('sum(amount) AS num,g.experience')->group('g.experience')->order('num desc')->select();
	    		foreach ($dataObjTmp as $key => $value) {
	    			$expArr['valueArrTmp'][$value['experience']]['value'] = $value['num'];
	    		}

	    		//整理成无key值的数组start
	    		$labelArr = array();
	    		$valueArr = array();
	    		$valueArrTmp = array();
	    		foreach ($expArr['labelArr'] as $key => $value) {
	    			$labelArr[] = $value;
	    			$valueArr[] = $expArr['valueArr'][$key];
	    			$valueArrTmp[] = $expArr['valueArrTmp'][$key];
	    		}
	    		//整理成无key值的数组end

			    $categories = array(
			    	'category'=>array(
			    		$labelArr
			    		)
			    	);
			    $categories = json_encode($categories);
			    $dataset = array(
			    	array(
			    		'seriesname'=>$this->start_year.'年'.$this->start_month.'月 - '.($this->end_month-1).'月',
				    	'data'=>array(
				    		$valueArr
				    		)
			    		),
			    	array(
			    		'seriesname'=>$this->end_year.'年'.$this->start_month.'月 - '.($this->end_month-1).'月',
				    	'data'=>array(
				    		$valueArrTmp
				    		)
			    		)
			    	);
			    $dataset = json_encode($dataset);
			    write_cache($cachename_category,'module/',$categories);
				write_cache($cachename,'module/',$dataset);
			}
		    $this->assign('categories',$categories);
		    $this->assign('dataset',$dataset);
		}
		$this->assign('compare',$compare);
	    $this->assign('caption','工作经验要求');
	    $this->assign('xAxisName','工作经验');
	    $this->assign('yAxisName','数值');

		if($compare){
			$this->display('mscolumn2d');
		}else{
			$this->display('column2d');
		}
		
	}
}
?>