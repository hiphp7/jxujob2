<?php
namespace Mall\Model;
use Think\Model;
class MallExchangeModel extends Model{
	protected $_validate = array(
		array('order_id,goods_id,goods_title,points,num,uid,username','identicalNull','',1,'callback'),
		array('order_id,goods_id,points,num,uid','identicalEnum','',0,'callback'),
	);
	protected $_auto = array ( 
		array('addtime','time',1,'function'),
	);
}
?>