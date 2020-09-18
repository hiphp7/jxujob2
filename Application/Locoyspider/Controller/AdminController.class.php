<?php
namespace Locoyspider\Controller;
use Common\Controller\BackendController;
class AdminController extends BackendController{
	private $data=array();
	private $val=array();
	public function _initialize() {
        parent::_initialize();
        $this->_mod = D('Locoyspider');
		$this->data=$this->_mod->load();
		
		foreach($this->data as $k=>$v){
			$this->val[$v['name']]=$v['value'];
		}
		
		$this->assign("show",$this->val);
    }
    /**
     * 基本设置列表
     */
    public function index(){
        parent::index();
    }
	
	/**
     * 资讯采集列表
     */
    public function set_news(){
		if(false === $article_property = F('article_property')){
			$article_property = D('ArticleProperty')->article_property_cache();
			$this->assign('article_property',$article_property);
		} else{
			$this->assign('article_property',F('article_property'));
		}
		
        parent::index();
    }
	
	/**
     * 企业采集列表
     */
    public function set_company(){
		$category=D("Category");
		$district=D("CategoryDistrict");
		
		
		$this->val['company_district']=explode(".",$this->val['company_district']);
		$data_1=$district->get_district_cache();
		$data_2=$district->get_district_cache($this->val['company_district'][0]);
		$data_3=$district->get_district_cache($this->val['company_district'][1]);
		$current_district=$data_1[$this->val['company_district'][0]]."/".$data_2[$this->val['company_district'][1]]."/".$data_3[$this->val['company_district'][2]];
		//echo "<pre>";print_r($this->val['company_district']);
		
		$this->assign("company_type",$category->get_category_cache("QS_company_type"));
		$this->assign("company_trade",$category->get_category_cache("QS_trade"));
		$this->assign("company_district",$district->get_district_cache());
		$this->assign("company_scale",$category->get_category_cache("QS_scale"));
		$this->assign("current_district",$current_district);
		$this->assign('givesetmeal',D('Setmeal')->where(array('display'=>1))->order('show_order desc,id')->getField('id,setmeal_name'));
        parent::index();
    }
	
	
	/**
     * 职位采集列表
     */
    public function set_jobs(){
		$category=D("Category");
		$jobs=D("CategoryJobs");
		$district=D("CategoryDistrict");
		
		
		
		if($this->val['jobs_subclass']){
			$current_jobs=$jobs->get_jobs_cache($this->val['jobs_category']);
			$current_jobs=$current_jobs[$this->val['jobs_subclass']];
		} else {
			$current_jobs=$jobs->get_jobs_cache($this->val['jobs_topclass']);
			$current_jobs=$current_jobs[$this->val['jobs_category']];
		}
		
		
		$this->val['jobs_district']=explode(".",$this->val['jobs_district']);
		$data_1=$district->get_district_cache();
		$data_2=$district->get_district_cache($this->val['jobs_district'][0]);
		$data_3=$district->get_district_cache($this->val['jobs_district'][1]);
		$current_district=$data_1[$this->val['jobs_district'][0]]."/".$data_2[$this->val['jobs_district'][1]]."/".$data_3[$this->val['jobs_district'][2]];
		
		
		$this->assign("jobs_nature",$category->get_category_cache("QS_jobs_nature"));
		$this->assign("jobs_education",$category->get_category_cache("QS_education"));
		$this->assign("jobs_experience",$category->get_category_cache("QS_experience"));
		$this->assign("jobs_wage",$category->get_category_cache("QS_wage"));
		$this->assign("current_jobs",$current_jobs);
		$this->assign("current_district",$current_district);
        parent::index();
    }
	
	
	 /**
     * 基本设置列表
     */
    public function set_user(){
        parent::index();
    }
	
    /**
     * 编辑
     */
    public function basic_mod(){
		if(!IS_POST) return false;
		
		$post=I('post.');
		foreach ($post as $key => $val) {
        	
			switch($key){
				case "jobcategory":
					$jobcategory=explode(".",$val);
					$post['jobs_topclass']=$jobcategory[0];
					$post['jobs_category']=$jobcategory[1];
					$post['jobs_subclass']=$jobcategory[2];
				break;
				/*case "jobs_district":
					$jobs_district=explode(".",$val);
					if($jobs_district[2]==0){
						$post['jobs_district']=$jobs_district[1];
					} else {
						$post['jobs_district']=$jobs_district[2];
					}
				break;*/
			}
        	
        }
		
        foreach ($post as $key => $val) {
        	$val = is_array($val) ? serialize($val) : $val;
			
        	$this->_mod->where(array('name' => $key))->save(array('value' => $val));
        }
        $this->success(L('operation_success'));
        exit;
    }
    
    
}
?>