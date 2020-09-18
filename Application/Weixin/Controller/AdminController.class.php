<?php
namespace Weixin\Controller;
use Common\Controller\ConfigbaseController;
class AdminController extends ConfigbaseController{
	public function _initialize() {
        parent::_initialize();
    }
	/**
	 * [menu 微信自定义菜单]
	 */
	public function menu(){
		$parentid = I('get.parentid',0,'intval');
        $menu_list = M('WeixinMenu')->field('id,title,key,type,url,menu_order,status')->where(array('parentid'=>$parentid))->order('menu_order desc,id')->select();
        if(IS_AJAX) $this->ajaxReturn(1,'菜单获取成功！',$menu_list);
        $this->assign('list', $menu_list);
        $this->display();
	}
	/**
	 * [menu_add 添加菜单]
	 */
	public function menu_add(){
		if(IS_POST){
			$this->_name = 'WeixinMenu';
            $this->add();
        }else{
        	if(false === $menus = F('weixin_menu_list')){
                $menus = D('WeixinMenu')->menu_chche();
            }
            $this->assign('menus',$menus);
            $this->assign('parentid',I('get.parentid',0,'intval'));
            $this->display();
        }
	}
	/**
	 * [menu_edit 修改菜单]
	 */
	public function menu_edit(){
		if(!IS_POST){
			if(false === $menus = F('weixin_menu_list')){
                $menus = D('WeixinMenu')->menu_chche();
            }
            $this->assign('menus',$menus);
        }
		$this->_name = 'WeixinMenu';
        $this->edit();
	}
	/**
	 * [menu_del 删除微信菜单]
	 */
	public function menu_del(){
		$this->_name = 'WeixinMenu';//数据表
		parent::delete();//调用父类delete方法
	}
	/**
	 * [menu_sync 同步微信菜单]
	 */
	public function menu_sync(){
		if(true === $reg = \Common\qscmslib\weixin::menu_sync()){
        	$this->admin_write_log_unify();
			$this->success('微信菜单同步成功！',U('Admin/menu'));
		}else{
			$this->error($reg,U('Admin/menu'));
		}
	}
	/**
	 * [bind_list 微信用户绑定列表]
	 */
	public function bind_list(){
        $db_pre = C('DB_PREFIX');
        $this_t = $db_pre.'members_bind';
		$this->_name = 'MembersBind';//数据表
		$this->custom_fun = '_bind_list_after_search';//查询结果后的回调方法
		$this->join = $db_pre."members as m on ".$this_t.".uid=m.uid";
		parent::index();//调用父类index方法
	}
	/**
	 * [bind_del 解除绑定]
	 */
	public function bind_del(){
		$this->_name = 'MembersBind';//数据表
		parent::delete();//调用父类delete方法
	}
	/**
	 * [_before_search 列表信息查询回调]
	 */
	public function _before_search($where){
		$where['type'] = 'weixin';
		if($_GET['utype']){
			$where['utype'] = $_GET['utype'];
		}
		return $where;
	}
	/**
	 * [bind_list_after_search 绑定用户查询结果回调]
	 */
	public function _bind_list_after_search($list){
		foreach ($list as $key => $val) {
			$list[$key]['info'] = unserialize($val['info']);
			$list[$key]['info']['keyname'] = unicode_emoji($list[$key]['info']['keyname']);
		}
		return $list;
	}
	/**
	 * [msg_list 微信消息列表]
	 */
	public function msg_list(){
		$this->_name = 'WeixinMsgList';
		$this->pagesize = 10;//列表单页显示多少条数据
		parent::index();
	}
	/**
	 * [msg_send 发送微信消息]
	 */
	public function msg_send(){
		if(IS_POST){
			$openid = I('post.weixin_openid','','trim');
			$content = I('post.content','','trim,badword');
			if(true === $reg = \Common\qscmslib\weixin::send_msg($openid,$content)){
				$this->_name = 'WeixinMsgList';
				$this->add();
			}else{
				$this->error($reg);
			}
		}else{
			$uid = I('get.uid',0,'intval');
			!$uid && $this->error('请选择用户！');
			$user = M('MembersBind')->field('uid,keyid as weixin_openid')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			!$user && $this->error('该用户还没有绑定微信公众号！');
			$user_info = M('Members')->field('username,utype')->where(array('uid'=>$uid))->find();
			$user = array_merge($user,$user_info);
			$this->assign('userinfo',$user);
			$this->display();
		}
	}
	/**
	 * [msg_queue_send 批量发送微信消息]
	 */
	public function msg_queue_send(){
		if(IS_POST){
			$utype = I('post.utype',0,'intval');
			$content = I('post.content','','trim,badword');
			if(true === $reg = \Common\qscmslib\weixin::send_queue_msg($utype,$content)){
        		$this->admin_write_log_unify();
				$this->success('微信消息发送成功！',U('Admin/msg_list'));
			}else{
				$this->error($reg);
			}
		}else{
			$this->display();
		}
	}
	/**
	 * [msg_del 删除微信消息]
	 */
	public function msg_del(){
		$this->_name = 'WeixinMsgList';//数据表
		parent::delete();//调用父类delete方法
	}
	/**
	 * [rule 微信通知规则]
	 */
	public function rule(){
		if(IS_POST){
			$post = I('post.');
			foreach ($post['alias'] as $key => $val) {
				unset($data);
				$data['value'] = $post[$val.'_value'];
				$data['template_id'] = $post['template_id'][$key];
	        	D('WeixinConfig')->where(array('alias' => $val))->save($data);
	        }
        	I('post.') && $this->admin_write_log_unify();
	        $this->success(L('operation_success'));
		}else{
			$weixin_config_list = D('WeixinConfig')->select();
			$this->assign('weixin_config_list',$weixin_config_list);
			$this->display();
		}
	}
}
?>