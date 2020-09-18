<?php
namespace Statistics\Controller;
use Statistics\Controller\CController;
class CompanyController extends CController{
	private $type;
	public function _initialize() {
        parent::_initialize();
    }
    /**
     * 企业性质分布
     */
	public function company_type(){
		$companytypeArr = array();
		if($this->start_year==$this->end_year){
    		//只选了一个时间的情况,不做对比
    		$min = strtotime($this->start_year.'-'.$this->start_month);
			if($this->end_month==13){
				$max = strtotime(($this->start_year+1).'-1');
			}else{
				$max = strtotime($this->start_year.'-'.$this->end_month);
			}
			$cachename = $min.'_'.$max.'_company_companytype_value.cache';
			
			$check_cache = check_cache($cachename,'module/');
			if($check_cache){
				$data = $check_cache;
			}else{
				$where['addtime'] = array(array('gt',$min),array('lt',$max),'and');
				
		    	$dataArr = array();
		    	$dArr = array();
		    	$companytype = D('Category')->get_category_cache('QS_company_type');
		    	foreach ($companytype as $key => $value) {
	    			$companytypeArr[$key]['label'] = $value;
	    		}
	    		$dataObj = D('StatisticsCompany')->where($where)->group('nature')->field('nature,count(*) as num')->select();
			   
			    foreach ($dataObj as $key => $value) {
			    	if($value['nature']){
			    		$companytypeArr[$value['nature']]['value'] = $value['num'];
			    	}
	    		}
	    		$i = 0;
	    		foreach ($companytypeArr as $key => $value) {
	    			if(strstr($value['label'],'其他')===false && strstr($value['label'],'其它')===false){
		    			$dArr[$i]['label'] = $value['label'];
		    			$dArr[$i]['value'] = $value['value'];
		    			$i++;
		    		}
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
			$cachename = $min1.'_'.$max1.'_'.$min2.'_'.$max2.'_company_companytype_compare_value.cache';
			$cachename_category = $min1.'_'.$max1.'_'.$min2.'_'.$max2.'_company_companytype_compare_category_value.cache';
			$check_cache = check_cache($cachename,'module/');
			$check_cache_category = check_cache($cachename_category,'module/');
			if($check_cache && $check_cache_category){
				$categories = $check_cache_category;
				$dataset = $check_cache;
			}else{
				$where['addtime'] = array(array('gt',$min1),array('lt',$max1),'and');
				$where2['addtime'] = array(array('gt',$min2),array('lt',$max2),'and');
				$companytype = D('Category')->get_category_cache('QS_company_type');
	    		foreach ($companytype as $key => $value) {
	    			$companytypeArr['labelArr'][$key]['label'] = $value;
	    		}
    			$dataObj = D('StatisticsCompany')->where($where)->group('nature')->field('nature,count(*) as num')->select();
	    		foreach ($dataObj as $key => $value) {
	    			if($value['nature']){
	    				$companytypeArr['valueArr'][$value['nature']]['value'] = $value['num'];
	    			}
	    		}
    			$dataObjTmp = D('StatisticsCompany')->where($where2)->group('nature')->field('nature,count(*) as num')->select();
	    		foreach ($dataObjTmp as $key => $value) {
	    			if($value['nature']){
	    				$companytypeArr['valueArrTmp'][$value['nature']]['value'] = $value['num'];
	    			}
	    		}

	    		//整理成无key值的数组start
	    		$labelArr = array();
	    		$valueArr = array();
	    		$valueArrTmp = array();
	    		foreach ($companytypeArr['labelArr'] as $key => $value) {
	    			if(strstr($value['label'],'其他')===false && strstr($value['label'],'其它')===false){
		    			$labelArr[] = $value;
		    			$valueArr[] = $companytypeArr['valueArr'][$key];
		    			$valueArrTmp[] = $companytypeArr['valueArrTmp'][$key];
		    		}
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
	    $this->assign('caption','企业性质分布');
	    $this->assign('xAxisName','企业性质');
	    $this->assign('yAxisName','数值');

		if($compare){
			$this->display('mscolumn2d');
		}else{
			$this->display('column2d');
		}
	}
    /**
     * 公司规模分布
     */
	public function scale(){
		$companytypeArr = array();
		if($this->start_year==$this->end_year){
    		//只选了一个时间的情况,不做对比
    		$min = strtotime($this->start_year.'-'.$this->start_month);
			if($this->end_month==13){
				$max = strtotime(($this->start_year+1).'-1');
			}else{
				$max = strtotime($this->start_year.'-'.$this->end_month);
			}
			$cachename = $min.'_'.$max.'_company_scale_value.cache';
			
			$check_cache = check_cache($cachename,'module/');
			if($check_cache){
				$data = $check_cache;
			}else{
				$where['addtime'] = array(array('gt',$min),array('lt',$max),'and');
		    	$dataArr = array();
		    	$dArr = array();
		    	$companytype = D('Category')->get_category_cache('QS_scale');
		    	foreach ($companytype as $key => $value) {
	    			$companytypeArr[$key]['label'] = $value;
	    		}
	    		$dataObj = D('StatisticsCompany')->where($where)->group('scale')->field('scale,count(*) as num')->select();
			   
			    foreach ($dataObj as $key => $value) {
			    	if($value['scale']){
			    		$companytypeArr[$value['scale']]['value'] = $value['num'];
			    	}
	    		}
	    		$i = 0;
	    		foreach ($companytypeArr as $key => $value) {
	    			if(strstr($value['label'],'其他')===false && strstr($value['label'],'其它')===false){
		    			$dArr[$i]['label'] = $value['label'];
		    			$dArr[$i]['value'] = $value['value'];
		    			$i++;
		    		}
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
			$cachename = $min1.'_'.$max1.'_'.$min2.'_'.$max2.'_company_scale_compare_value.cache';
			$cachename_category = $min1.'_'.$max1.'_'.$min2.'_'.$max2.'_company_scale_compare_category_value.cache';
			$check_cache = check_cache($cachename,'module/');
			$check_cache_category = check_cache($cachename_category,'module/');
			if($check_cache && $check_cache_category){
				$categories = $check_cache_category;
				$dataset = $check_cache;
			}else{
				$where['addtime'] = array(array('gt',$min1),array('lt',$max1),'and');
				$where2['addtime'] = array(array('gt',$min2),array('lt',$max2),'and');
				$companytype = D('Category')->get_category_cache('QS_scale');
	    		foreach ($companytype as $key => $value) {
	    			$companytypeArr['labelArr'][$key]['label'] = $value;
	    		}
    			$dataObj = D('StatisticsCompany')->where($where)->group('scale')->field('scale,count(*) as num')->select();
	    		foreach ($dataObj as $key => $value) {
	    			if($value['scale']){
	    				$companytypeArr['valueArr'][$value['scale']]['value'] = $value['num'];
	    			}
	    		}
    			$dataObjTmp = D('StatisticsCompany')->where($where2)->group('scale')->field('scale,count(*) as num')->select();
	    		foreach ($dataObjTmp as $key => $value) {
	    			if($value['scale']){
	    				$companytypeArr['valueArrTmp'][$value['scale']]['value'] = $value['num'];
	    			}
	    		}

	    		//整理成无key值的数组start
	    		$labelArr = array();
	    		$valueArr = array();
	    		$valueArrTmp = array();
	    		foreach ($companytypeArr['labelArr'] as $key => $value) {
	    			if(strstr($value['label'],'其他')===false && strstr($value['label'],'其它')===false){
		    			$labelArr[] = $value;
		    			$valueArr[] = $companytypeArr['valueArr'][$key];
		    			$valueArrTmp[] = $companytypeArr['valueArrTmp'][$key];
		    		}
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
	    $this->assign('caption','公司规模分布');
	    $this->assign('xAxisName','公司规模');
	    $this->assign('yAxisName','数值');

		if($compare){
			$this->display('mscolumn2d');
		}else{
			$this->display('column2d');
		}
		
	}
}
?>