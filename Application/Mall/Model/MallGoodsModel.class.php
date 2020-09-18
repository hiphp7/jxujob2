<?php
namespace Mall\Model;
use Think\Model;
class MallGoodsModel extends Model{
	protected $_validate = array(
		array('category,scategory,category_cn,goods_number,goods_title,goods_brand,goods_stock,goods_customer,goods_points','identicalNull','',1,'callback'),
		array('category,scategory,goods_stock,goods_customer,goods_points','identicalEnum','',0,'callback'),
	);
	protected $_auto = array ( 
		array('recommend',0),
		array('addtime','time',1,'function'),
		array('click',0),
		array('ex_time',0),
	);
	public function goods_delete($id){
		if (!is_array($id)) $id=array($id);
		$where['id']=array('in',$id);
		$num = $this->where($where)->delete();
		if(false === $num) return array('state'=>0,'error'=>'删除失败！');
		return array('state'=>1,'num'=>$num);
	}
}
?>