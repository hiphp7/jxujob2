<?php
namespace Mall\Model;
use Think\Model;
class MallHotwordModel extends Model{
	protected $_validate = array(
		array('w_word','0,20','{%mall_hotword_length_error_w_word}',0,'length'),
	);
	protected $_auto = array ( 
		array('w_hot',1),
	);
}
?>