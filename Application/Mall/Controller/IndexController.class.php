<?php
namespace Mall\Controller;
use Common\Controller\FrontendController;
class IndexController extends FrontendController{
	public function _initialize() {
        parent::_initialize();
    }
	public function index(){
		if(!I('get.org','','trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']){
			redirect(build_mobile_url(array('c'=>'Mall','a'=>'index')));
		}
		$issign = D('MembersHandsel')->check_members_handsel_day(array('uid'=>C('visitor.uid'),'htype'=>'task_sign'));
        $this->assign('issign',$issign ? 1 : 0);
		$this->display();
	}
	public function goods_list(){
		if(!I('get.org','','trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']){
			redirect(build_mobile_url(array('c'=>'Mall','a'=>'plist')));
		}
		if(C('visitor.uid')){
			$this->assign('user_points',D('MembersPoints')->get_user_points(C('visitor.uid')));
		}
		$page_seo = D('Page')->get_page();
		$this->_config_seo($page_seo[strtolower(MODULE_NAME).'_'.strtolower(CONTROLLER_NAME).'_'.strtolower(ACTION_NAME)],array('key'=>I('request.key')));
		$this->display();
	}
	public function goods_show(){
		if(!I('get.org','','trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']){
			redirect(build_mobile_url(array('c'=>'Mall','a'=>'show','params'=>'id='.intval($_GET['id']))));
		}
		if(IS_AJAX){
			$this->_ajax_get_exchange_list();
		}
		$this->display();
	}
	public function charts(){
		if(!I('get.org','','trim') && C('PLATFORM') == 'mobile' && $this->apply['Mobile']){
			redirect(build_mobile_url(array('c'=>'Mall','a'=>'index')));
		}
		$this->display();
	}
	/**
	 * ajax商品兑换(生成订单)
	 */
	public function ajax_exchange_commit(){
		if(IS_POST){
			$r = D('MallOrder')->order_add(C('visitor'),I('post.'));
			$this->ajaxReturn($r['state'],$r['msg']);
		}else{
			if(!C('visitor')){
				$this->ajaxReturn(0,'请先登录！','',1);
			}
			$goods_id = I('get.goods_id',0,'intval');
			$num = I('get.num',0,'intval');
			!$goods_id && $this->ajaxReturn(0,'请选择商品！');
			$goods_info = D('MallGoods')->find($goods_id);
			if($goods_info['goods_stock']<$num){
				$this->ajaxReturn(0,'库存不足，请重新选择商品！');
			}
			$log_count = D('MallOrder')->where(array('goods_id'=>$goods_id,'uid'=>C('visitor.uid')))->count();
			if($num+$log_count > $goods_info['goods_customer']){
				$this->ajaxReturn(0,'您已超过该商品的最大可兑换数量！');
			}
			$user_points = D('MembersPoints')->get_user_points(C('visitor.uid'));
			$total_points = $num*$goods_info['goods_points'];
			if($total_points>$user_points){
				$this->ajaxReturn(0,C('qscms_points_byname').'不足，无法兑换！');
			}
			if(C('visitor.utype') == 1){
				$contact_info = D('CompanyProfile')->where(array('uid'=>C('visitor.uid')))->find();
				$contact_info['contact'] = $contact_info['contact'];
				$contact_info['mobile'] = C('visitor.mobile');
				$contact_info['address'] = $contact_info['address'];
			}else{
				$contact_info = D('MembersInfo')->get_userprofile(array('uid'=>C('visitor.uid')));
				$contact_info['contact'] = $contact_info['realname'];
				$contact_info['mobile'] = C('visitor.mobile');
				$contact_info['address'] = '';
			}
			
			$this->assign('contact_info',$contact_info);
			$this->assign('goods_info',$goods_info);
			$this->assign('num',$num);
			$html = $this->fetch('ajax_exchange_commit');
			$this->ajaxReturn(1,'获取数据成功',$html);
		}
	}
	/**
	 * 商品详细页中ajax获取兑换记录
	 */
	protected function _ajax_get_exchange_list(){
		$html = $this->fetch("ajax_get_exchange_list");
		$this->ajaxReturn(1,'',$html);
	}
}
?>