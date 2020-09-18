<?php
namespace Jobfair\Controller;
use Common\Controller\FrontendController;
class IndexController extends FrontendController{
	public function _initialize() {
        parent::_initialize();
    }
	public function index(){
		if(!I('get.org','','trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']){
			redirect(build_mobile_url(array('c'=>'Jobfair','a'=>'index')));
		}
		$this->display();
	}
	public function jobfair_com(){
		if(!I('get.org','','trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']){
			redirect(build_mobile_url(array('c'=>'Jobfair','a'=>'comlist','params'=>'id='.intval($_GET['id']))));
		}
		$this->display();
	}
	public function jobfair_reserve(){
		if(!I('get.org','','trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']){
			redirect(build_mobile_url(array('c'=>'Jobfair','a'=>'show','params'=>'id='.intval($_GET['id']))));
		}
		$this->display();
	}
	public function jobfair_retrospect(){
		if(!I('get.org','','trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']){
			redirect(build_mobile_url(array('c'=>'Jobfair','a'=>'show','params'=>'id='.intval($_GET['id']))));
		}
		$this->display();
	}
	public function jobfair_show(){
		if(!I('get.org','','trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']){
			redirect(build_mobile_url(array('c'=>'Jobfair','a'=>'show','params'=>'id='.intval($_GET['id']))));
		}
		$this->display();
	}
	public function jobfair_traffic(){
		if(!I('get.org','','trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']){
			redirect(build_mobile_url(array('c'=>'Jobfair','a'=>'show','params'=>'id='.intval($_GET['id']))));
		}
		$this->display();
	}
	public function reserve($jobfair_id,$position_id){
		$r = D('Jobfair')->jobfair_booth(C('visitor'),$jobfair_id,$position_id);
		$this->ajaxReturn($r['state'],$r['msg']);
	}
	/**
	 * 参会企业页面中ajax加载更多参会企业
	 */
	public function ajax_jobfair_com(){
		$page = I('get.page',0,'intval');
		$start = $page*10;
		$this->assign('start',$start);
		$html = $this->fetch('ajax_jobfair_com');
		if($html){
			$this->ajaxReturn(1,'',$html);
		}else{
			$this->ajaxReturn(0);
		}
	}
	/**
	 * 招聘会首页中ajax加载更多招聘会
	 */
	public function ajax_jobfair_list(){
		$html = $this->fetch('ajax_jobfair_list');
		if($html){
			$this->ajaxReturn(1,'',$html);
		}else{
			$this->ajaxReturn(0);
		}
	}
}
?>