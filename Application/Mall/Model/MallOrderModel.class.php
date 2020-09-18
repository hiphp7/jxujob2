<?php
namespace Mall\Model;
use Think\Model;
class MallOrderModel extends Model{
	protected $_validate = array(
		array('uid,username,goods_id,goods_title,goods_points,goods_num,order_points,contact,mobile,address','identicalNull','',1,'callback'),
		array('uid,goods_id,goods_points,goods_num,order_points','identicalEnum','',1,'callback'),
		array('contact','0,30','{%mall_order_length_error_contact}',0,'length'),
		array('address','0,100','{%mall_order_length_error_address}',0,'length'),
		array('mobile','mobile','{%mall_order_format_error_mobile}'),
	);
	protected $_auto = array ( 
		array('status',1),
		array('addtime','time',1,'function'),
	);

	// 订单设置 
	public function order_set($id,$status)
	{
		if(!is_array($id)) $id=array($id);
		$return = 0;
		foreach ($id as $value)
		{	
			$order_info = $this->where(array('id'=>$value))->find();
			$goods_info = D('MallGoods')->where(array('id'=>$order_info['goods_id']))->find();
			if($order_info['status']!=1){
				continue;
			}
			// 审核不通过 退回企业积分,退回库存
			if($status==3)
			{
				$p_rst = D('MembersPoints')->report_deal($order_info['uid'],1,$order_info['order_points']);
				if($p_rst){
	                $handsel['uid'] = $order_info['uid'];
	                $handsel['htype'] = 'return_points';
	                $handsel['htype_cn'] = '退回'.C('qscms_points_byname');
	                $handsel['operate'] = 1;
	                $handsel['points'] = $order_info['order_points'];
	                $handsel['addtime'] = time();
	                D('MembersHandsel')->members_handsel_add($handsel);
	            }
				$save['goods_stock']=$goods_info['goods_stock']+$order_info['goods_num'];
				D('MallGoods')->where(array('id'=>array('eq',$order_info['goods_id'])))->save($save);
			}
			// 审核通过 写入兑换记录
			else
			{
				$setsqlarr['order_id'] = $order_info['id'];
				$setsqlarr['goods_id'] = $order_info['goods_id'];
				$setsqlarr['goods_title'] = $order_info['goods_title'];
				$setsqlarr['points'] = $order_info['goods_points'];
				$setsqlarr['num'] = $order_info['goods_num'];
				$setsqlarr['uid'] = $order_info['uid'];
				$setsqlarr['username'] = $order_info['username'];
				$setsqlarr['addtime'] = time();
				D('MallExchange')->add($setsqlarr);
				D('MallGoods')->where(array('id'=>array('eq',$order_info['goods_id'])))->setInc('ex_time',$order_info['goods_num']);
			}
			$r = $this->where(array('id'=>$value))->setField('status',$status);
			$r && $return++;
		}
		return $return;
	}
	/**
	 * 删除订单
	 */
	public function order_delete($id){
		if(!is_array($id)) $id=array($id);
		return $this->where(array('id'=>array('in',$id)))->delete();
	}
	/**
	 * 生成订单
	 */
	public function order_add($user,$data){
		if(!$user){
			return array('state'=>0,'msg'=>'请先登录帐号！');
		}
		if(isset($data['goods_id']) && intval($data['goods_id'])>0){
			$goods_id = intval($data['goods_id']);
		}else{
			return array('state'=>0,'msg'=>'请选择兑换的商品！');
		}
		$goods_info = D('MallGoods')->where(array('id'=>$goods_id))->find();
		if(!$goods_info){
			return array('state'=>0,'msg'=>'商品不存在，请重新选择！');
		}
		if(!$data['contact']){
			return array('state'=>0,'msg'=>'请填写联系人！');
		}
		if(!$data['mobile']){
			return array('state'=>0,'msg'=>'请填写联系电话！');
		}
		if(!$data['address']){
			return array('state'=>0,'msg'=>'请填写联系地址！');
		}
		$ex_number = intval($data['ex_number']);
		if($ex_number<=0){
			$ex_number = 1;
		}
		else if($ex_number>$goods_info['goods_customer'])
		{
			$ex_number = $goods_info['goods_customer'];
		}
		if($goods_info['goods_stock']<$ex_number){
			return array('state'=>0,'msg'=>'库存不足，请重新选择商品！');
		}
		$log_count = $this->where(array('goods_id'=>$goods_id,'uid'=>$user['uid']))->count();
		if($ex_number+$log_count > $goods_info['goods_customer']){
			return array('state'=>0,'msg'=>'您已超过该商品的最大可兑换数量！');
		}
		$user_points = D('MembersPoints')->get_user_points($user['uid']);
		$total_points = $ex_number*$goods_info['goods_points'];
		if($total_points>$user_points){
			return array('state'=>0,'msg'=>C('qscms_points_byname').'不足，无法完成兑换！');
		}
		$setsqlarr['uid'] = $user['uid'];
		$setsqlarr['username'] = $user['username'];
		$setsqlarr['goods_id'] = $goods_id;
		$setsqlarr['goods_title'] = $goods_info['goods_title'];
		$setsqlarr['goods_points'] = $goods_info['goods_points'];
		$setsqlarr['goods_num'] = $ex_number;
		$setsqlarr['order_points'] = $total_points;
		$setsqlarr['contact'] = $data['contact'];
		$setsqlarr['mobile'] = $data['mobile'];
		$setsqlarr['address'] = $data['address'];
		$setsqlarr['status'] = 1;
		$setsqlarr['addtime'] = time();
		$check = $this->create($setsqlarr);
		if($check!==false){
			C('SUBSITE_VAL.s_id') && $this->subsite_id = C('SUBSITE_VAL.s_id');
			$r = $this->add();
		}else{
			$r = false;
		}
		if($r){
			$save['goods_stock']=$goods_info['goods_stock']-$ex_number;
			D('MallGoods')->where(array('id'=>$goods_id))->save($save);
			$p_rst = D('MembersPoints')->report_deal($user['uid'],2,$setsqlarr['order_points']);
			if($p_rst){
                $handsel['uid'] = $user['uid'];
                $handsel['htype'] = 'exchange_goods';
                $handsel['htype_cn'] = '兑换商品';
                $handsel['operate'] = 2;
                $handsel['points'] = $setsqlarr['order_points'];
                $handsel['addtime'] = time();
                D('MembersHandsel')->members_handsel_add($handsel);
            }
			return array('state'=>1,'msg'=>'等待审核，请到会员中心的商品订单中查看！');
		}else{
			return array('state'=>0,'msg'=>'参数错误！');
		}
	}
	/*
		订单列表
		@$data 订单查询条件
	*/
	public function get_order_list($data,$pagesize=10)
	{
		$count = $this->where($data)->count();
		$pager =  pager($count, $pagesize);
		$rst['list'] = $this->where($data)->order('id desc')->limit($pager->firstRow . ',' . $pager->listRows)->select();
		$rst['page']=$pager->fshow();
		return $rst;
	}
}
?>