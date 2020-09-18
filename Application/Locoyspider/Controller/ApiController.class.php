<?php
namespace Locoyspider\Controller;
use Common\Controller\BackendController;
class ApiController extends BackendController{
	
	private $data=array();
	
	public function _initialize() {
        //parent::_initialize();
		
        $locoyspider=D('Locoyspider');
		$this->data=$locoyspider->find_data();
		if ($this->data['open']<>"1"){
			exit("请在网站后台开启火车头采集");
		}
    }
    
	public function index(){
		$act=I('post.act');
		
		$locoyspider=D('Locoyspider');
		if($act=='news'){
			$post['title']=trim($_POST['title'])?trim($_POST['title']):exit('文章标题不能为空！');
			if ($locoyspider->isTitExist($post['title'])){
				exit("添加失败，新闻标题有重复");
			}
			$post['type_id']=trim($_POST['type_id'])?trim($_POST['type_id']):exit('文章所属分类不能为空！');
			$post['parentid']=$locoyspider->getArticlePid($post['type_id']);
			$post['content']=trim($_POST['content'])?trim($_POST['content']):exit('文章内容不能为空！');
			$post['tit_color']=intval($_POST['tit_color']);
			$post['tit_b']=intval($_POST['tit_b']);
			$post['author']=trim($_POST['author']);
			//判断是否设置，否则启用系统默认
			if ($_POST['focos']==""){
				$post['focos']=$this->data['article_focos'];
			}else{
				$post['focos']=intval($_POST['focos']);
			}
			//判断是否设置，否则启用系统默认
			if ($_POST['is_display']==""){
				$post['is_display']=$this->data['article_display'];
			}else{
				$post['is_display']=intval($_POST['is_display']);
			}
			$post['source']=trim($_POST['source']);
			$post['is_url']=trim($_POST['is_url'])==""? "http://":trim($_POST['is_url']);
			$post['seo_keywords']=trim($_POST['seo_keywords']);
			$post['seo_description']=trim($_POST['seo_description']);
			$post['article_order']=trim($_POST['article_order']);
			$post['click']=intval($_POST['click']);
			$post['Small_img']=trim($_POST['Small_img']);
			$post['addtime']=time();
			$post['robot']=1;
			$result=$locoyspider->addArticle($post);
			if ($result){
				exit("添加成功");
			}else{
				exit("添加失败");
			}
			
			
		}elseif($act=="jobs"){
			
			
			$companyname=isset($_POST['companyname'])?trim($_POST['companyname']):exit('公司名称不能为空！');
			$companyinfo=$locoyspider->getCompanyInfo($companyname);
			
			if ($companyinfo){
				$locoyspider->addJobs($companyinfo);
			}else{
				if($locoyspider->addCompany($companyname)){
					$companyinfo=$locoyspider->getCompanyinfo($companyname);
					$locoyspider->addJobs($companyinfo);
				}else{
					exit($msg);
				}
			}
			
		}
	}
    
}
?>