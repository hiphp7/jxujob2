<?php 
namespace Common\Model;
use Think\Model;
class AdminLogModel extends Model
{
	protected $_validate = array(
		array('admin_name','require','{%admin_log_null_error_admin_name}'),
		array('log_value','require','{%admin_log_null_error_log_value}'),
	);
	protected $_auto = array (
		array('add_time','time',1,'function'),
		array('log_ip','get_client_ip',1,'function'),
		array('log_type',1),
	);
}
?>