<?php
namespace Statistics\Controller;
use Statistics\Controller\CController;
class TrendController extends CController{
	public function _initialize() {
        parent::_initialize();
    }
    /**
     * 求职人数
     */
	public function resume(){
		$compare = false;
		$resumeArr = array();
    	if($this->start_year==$this->end_year){
    		$cachename = $this->start_year.'_'.$this->start_month.'_'.$this->end_year.'_'.$this->end_month.'_trend_analysis_resume.cache';
			$check_cache = check_cache($cachename,'module/');
			if($check_cache){
				$data = $check_cache;
			}else{
	    		//只选了一个时间的情况,不做对比
	    		for($i=$this->start_month;$i<$this->end_month;$i++){
	    			$resumeArr[$i]['label'] = $i.'月';
	    			$min = strtotime($this->start_year.'-'.$i);
	    			if($i==12){
	    				$max = strtotime(($this->end_year+1).'-1');
	    			}else{
	    				$max = strtotime($this->end_year.'-'.($i+1));
	    			}
	    			$where['addtime'] = array(array('gt',$min),array('lt',$max),'and');
	    			$resume_count = D('StatisticsResume')->where($where)->count();
	    			$resumeArr[$i]['value'] = $resume_count;
	    		}
	    		$dArr = array();
	    		$item = 0;
	    		foreach ($resumeArr as $key => $value) {
	    			$dArr[$item]['label'] = $value['label'];
	    			$dArr[$item]['value'] = $value['value'];
	    			$item++;
	    		}
	    		$dataArr = array(
			    		$dArr
			    	);
			    $data = json_encode($dataArr);
			    write_cache($cachename,'module/',$data);
			}
	   		$this->assign('data',$data);
    	}else{
	    	//对比
	    	$compare = true;
    		
    		$cachename = $this->start_year.'_'.$this->start_month.'_'.$this->end_year.'_'.$this->end_month.'__trend_analysis_resume_compare.cache';
			$cachename_category = $this->start_year.'_'.$this->start_month.'_'.$this->end_year.'_'.$this->end_month.'__trend_analysis_resume_compare_category.cache';
			$check_cache = check_cache($cachename,'module/');
			$check_cache_category = check_cache($cachename_category,'module/');
			if($check_cache && $check_cache_category){
				$categories = $check_cache_category;
				$dataset = $check_cache;
			}else{
	    		for($i=$this->start_month;$i<$this->end_month;$i++){
	    			$resumeArr['labelArr'][$i]['label'] = $i.'月';
	    			$min1 = strtotime($this->start_year.'-'.$i);
	    			$min2 = strtotime($this->end_year.'-'.$i);
	    			if($i==12){
	    				$max1 = strtotime(($this->start_year+1).'-1');
	    				$max2 = strtotime(($this->end_year+1).'-1');
	    			}else{
	    				$max1 = strtotime($this->start_year.'-'.($i+1));
	    				$max2 = strtotime($this->end_year.'-'.($i+1));
	    			}
	    			$where['addtime'] = array(array('gt',$min1),array('lt',$max1),'and');
	    			$resume_count = D('StatisticsResume')->where($where)->count();
	    			$resumeArr['valueArr'][$i]['value'] = $resume_count;
	    			$where['addtime'] = array(array('gt',$min2),array('lt',$max2),'and');
	    			$resume_count_tmp = D('StatisticsResume')->where($where)->count();
	    			$resumeArr['valueArrTmp'][$i]['value'] = $resume_count_tmp;
	    		}
	    		//整理成无key值的数组start
				$labelArr = array();
				$valueArr = array();
				$valueArrTmp = array();
				foreach ($resumeArr['labelArr'] as $key => $value) {
					$labelArr[] = $value;
					$valueArr[] = $resumeArr['valueArr'][$key];
					$valueArrTmp[] = $resumeArr['valueArrTmp'][$key];
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
			    		'seriesname'=>$this->start_year.'年',
				    	'data'=>array(
				    		$valueArr
				    		)
			    		),
			    	array(
			    		'seriesname'=>$this->end_year.'年',
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
	    $this->assign('unit','人');
	    $this->assign('compare',$compare);
	    $this->assign('caption','求职人数趋势');
	    $this->assign('xAxisName','月份');
	    $this->assign('yAxisName','人数');

		if($compare){
			$this->display('msline');
		}else{
			$this->display('line');
		}
	}
	/**
     * 职位数
     */
	public function jobs(){
		$compare = false;
		$jobArr = array();
    	if($this->start_year==$this->end_year){
    		$cachename = $this->start_year.'_'.$this->start_month.'_'.$this->end_year.'_'.$this->end_month.'_trend_analysis_job.cache';
			$check_cache = check_cache($cachename,'module/');
			if($check_cache){
				$data = $check_cache;
			}else{
	    		//只选了一个时间的情况,不做对比
	    		for($i=$this->start_month;$i<$this->end_month;$i++){
	    			$jobArr[$i]['label'] = $i.'月';
	    			$min = strtotime($this->start_year.'-'.$i);
	    			if($i==12){
	    				$max = strtotime(($this->end_year+1).'-1');
	    			}else{
	    				$max = strtotime($this->end_year.'-'.($i+1));
	    			}
	    			$where['addtime'] = array(array('gt',$min),array('lt',$max),'and');
	    			$job_count = D('StatisticsJobs')->where($where)->count();
	    			$jobArr[$i]['value'] = $job_count;
	    		}
	    		$dArr = array();
	    		$item = 0;
	    		foreach ($jobArr as $key => $value) {
	    			$dArr[$item]['label'] = $value['label'];
	    			$dArr[$item]['value'] = $value['value'];
	    			$item++;
	    		}
	    		$dataArr = array(
			    		$dArr
			    	);
			    $data = json_encode($dataArr);
			    write_cache($cachename,'module/',$data);
			}
	   		$this->assign('data',$data);
    	}else{
	    	//对比
	    	$compare = true;
    		
    		$cachename = $this->start_year.'_'.$this->start_month.'_'.$this->end_year.'_'.$this->end_month.'__trend_analysis_job_compare.cache';
			$cachename_category = $this->start_year.'_'.$this->start_month.'_'.$this->end_year.'_'.$this->end_month.'__trend_analysis_job_compare_category.cache';
			$check_cache = check_cache($cachename,'module/');
			$check_cache_category = check_cache($cachename_category,'module/');
			if($check_cache && $check_cache_category){
				$categories = $check_cache_category;
				$dataset = $check_cache;
			}else{
	    		for($i=$this->start_month;$i<$this->end_month;$i++){
	    			$jobArr['labelArr'][$i]['label'] = $i.'月';
	    			$min1 = strtotime($this->start_year.'-'.$i);
	    			$min2 = strtotime($this->end_year.'-'.$i);
	    			if($i==12){
	    				$max1 = strtotime(($this->start_year+1).'-1');
	    				$max2 = strtotime(($this->end_year+1).'-1');
	    			}else{
	    				$max1 = strtotime($this->start_year.'-'.($i+1));
	    				$max2 = strtotime($this->end_year.'-'.($i+1));
	    			}
	    			$where['addtime'] = array(array('gt',$min1),array('lt',$max1),'and');
	    			$job_count = D('StatisticsJobs')->where($where)->count();
	    			$jobArr['valueArr'][$i]['value'] = $job_count;
	    			$where['addtime'] = array(array('gt',$min2),array('lt',$max2),'and');
	    			$job_count_tmp = D('StatisticsJobs')->where($where)->count();
	    			$jobArr['valueArrTmp'][$i]['value'] = $job_count_tmp;
	    		}
	    		//整理成无key值的数组start
				$labelArr = array();
				$valueArr = array();
				$valueArrTmp = array();
				foreach ($jobArr['labelArr'] as $key => $value) {
					$labelArr[] = $value;
					$valueArr[] = $jobArr['valueArr'][$key];
					$valueArrTmp[] = $jobArr['valueArrTmp'][$key];
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
			    		'seriesname'=>$this->start_year.'年',
				    	'data'=>array(
				    		$valueArr
				    		)
			    		),
			    	array(
			    		'seriesname'=>$this->end_year.'年',
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
	    $this->assign('unit','个');
	    $this->assign('compare',$compare);
	    $this->assign('caption','职位数趋势');
	    $this->assign('xAxisName','月份');
	    $this->assign('yAxisName','人数');

		if($compare){
			$this->display('msline');
		}else{
			$this->display('line');
		}
	}
}
?>