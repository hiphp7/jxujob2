<?php
namespace Statistics\Controller;
use Statistics\Controller\CController;
class AdminController extends CController{
	public function _initialize() {
        parent::_initialize();
    }
	public function index(){
		//求职新增曲线图
		$cachename = 'main_add_personal_line.cache';
		$check_cache = check_cache($cachename,'module/');
		if($check_cache){
			$data = $check_cache;
		}else{
			for($i=30;$i>0;$i--){
				$day = date("m/d",strtotime("-{$i} day"));
				$datelist[$day]=0;
			}
			$result = D('StatisticsResume')->where(array('addtime'=>array('gt',strtotime("-30 day"))))->select();
			foreach ($result as $key => $value) {
				$date=date("m/d",$value['addtime']);
				$datelist[$date]++;
			}
			$i = 0;
			foreach ($datelist as $key => $value) {
				$resumeArr[$i]['label'] = $key;
				$resumeArr[$i]['value'] = $value;
				$i++;
			}
			$dataArr = array(
		    		$resumeArr
		    	);
		    $data = json_encode($dataArr);
		    write_cache($cachename,'module/',$data);
		}
	    $this->assign('add_personal_member_line',$data);
	    unset($datelist,$resumeArr,$dataArr,$data);

	    //岗位新增曲线图
		$cachename = 'main_add_jobs_line.cache';
		$check_cache = check_cache($cachename,'module/');
		if($check_cache){
			$data = $check_cache;
		}else{
			for($i=30;$i>0;$i--){
				$day = date("m/d",strtotime("-{$i} day"));
				$datelist[$day]=0;
			}
			$result = D('StatisticsJobs')->where(array('addtime'=>array('gt',strtotime("-30 day"))))->select();
			foreach ($result as $key => $value) {
				$date=date("m/d",$value['addtime']);
				$datelist[$date]++;
			}
			
			$i = 0;
			foreach ($datelist as $key => $value) {
				$jobsArr[$i]['label'] = $key;
				$jobsArr[$i]['value'] = $value;
				$i++;
			}
			$dataArr = array(
		    		$jobsArr
		    	);
		    $data = json_encode($dataArr);
		    write_cache($cachename,'module/',$data);
		}
	    $this->assign('add_jobs_line',$data);
	    unset($datelist,$jobsArr,$dataArr,$data);

	    //个人信息-工作经验饼状图
		$cachename = 'main_exp_pie.cache';
		$check_cache = check_cache($cachename,'module/');
		if($check_cache){
			$data = $check_cache;
		}else{
			$count_resume = D('StatisticsResume')->count();
			$dataArr = array();
			$dArr = array();
			$experience = D('Category')->get_category_cache('QS_experience');
			foreach ($experience as $key => $value) {
				$expArr[$key]['label'] = $value;
			}
			$subQuery = D('StatisticsResume')->field('experience')->buildSql();
			$dataObj = $this->model->table($subQuery.' g')->field('count(*) as num,g.experience')->group('g.experience')->select();
		   
		    foreach ($dataObj as $key => $value) {
				$expArr[$value['experience']]['value'] = $this->setPercent($value['num'],$count_resume);
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
	    $this->assign('exp_pie',$data);
	    unset($count_resume,$experience,$dataArr,$dArr,$data,$dataObj);


	    //个人信息-学历饼状图
		$cachename = 'main_edu_pie.cache';
		$check_cache = check_cache($cachename,'module/');
		if($check_cache){
			$data = $check_cache;
		}else{
			$count_resume = D('StatisticsResume')->count();
			$dataArr = array();
			$dArr = array();
			$education = D('Category')->get_category_cache('QS_education');
			foreach ($education as $key => $value) {
				$eduArr[$key]['label'] = $value;
			}
			$subQuery = D('StatisticsResume')->field('education')->buildSql();
			$dataObj = $this->model->table($subQuery.' g')->field('count(*) as num,g.education')->group('g.education')->select();
		   
		    foreach ($dataObj as $key => $value) {
				$eduArr[$value['education']]['value'] = $this->setPercent($value['num'],$count_resume);
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
	    $this->assign('edu_pie',$data);
	    unset($count_resume,$education,$dataArr,$dArr,$data,$dataObj);
		$this->display();
	}
}
?>