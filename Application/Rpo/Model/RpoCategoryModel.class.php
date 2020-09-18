<?php
namespace Rpo\Model;
use Think\Model;
class RpoCategoryModel extends Model{
	protected $_validate = array(
		array('title','require','服务名称不能为空！',self::EXISTS_VALIDATE,'regex'),
		array('title','1,20','服务名称应在1-20个字内！',self::EXISTS_VALIDATE,'length'),
		array('desc','require','服务描述不能为空！',self::EXISTS_VALIDATE,'regex'),
        array('desc','1,50','服务名称应在1-50个字内！',self::EXISTS_VALIDATE,'length'),
        array('type','require','所属组别不能为空！',self::EXISTS_VALIDATE,'regex'),
	);
	protected $_auto = array ( 

	);
}
?>