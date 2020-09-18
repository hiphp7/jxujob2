<?php
namespace Subject\Model;
use Think\Model;
class SubjectCompanyModel extends Model{
    protected $_validate = array(
        array('company_uid','require','请填写企业UID！',1),
    );

    protected $_auto = array (
        array('addtime','time',1,'function'),//添加时间
    );
    /**
	 * 后台审核参会企业
	 */
	public function admin_subject_audit($id,$audit){
		!is_array($id) && $id=array($id);
		$sqlin=implode(",",$id);
		$num = 0;
		if (fieldRegex($sqlin,'in'))
		{
			$num = M('SubjectCompany')->where(array('id'=>array('in',$sqlin)))->setField('s_audit',$audit);
		}
		return $num;
	}
	 /**
	 * 后台设置参会企业微信直面
	 */
	public function admin_subject_wx_audit($id,$audit){
		!is_array($id) && $id=array($id);
		$sqlin=implode(",",$id);
		$num = 0;
		if (fieldRegex($sqlin,'in'))
		{
			$num = M('SubjectCompany')->where(array('id'=>array('in',$sqlin)))->setField('add_status',$audit);
		}
		return $num;
	}
	/**
	 * 后台设置参会企业置顶
	 */
	public function admin_subject_order($id,$order){
		!is_array($id) && $id=array($id);
		$sqlin=implode(",",$id);
		$num = 0;
		if (fieldRegex($sqlin,'in'))
		{
			$num = M('SubjectCompany')->where(array('id'=>array('in',$sqlin)))->setField('c_order',$order);
		}
		return $num;
	}
}
?>