<?php
namespace Subject\Model;
use Think\Model;
class SubjectPersonalModel extends Model{
    protected $_validate = array(
        array('resume_uid','require','请填写简历UID！',1),
    );

    protected $_auto = array (
        array('addtime','time',1,'function'),//添加时间
    );
    /**
	 * 后台审核参会企业
	 */
	public function admin_personal_audit($id,$audit){
		!is_array($id) && $id=array($id);
		$sqlin=implode(",",$id);
		$num = 0;
		if (fieldRegex($sqlin,'in'))
		{
			$num = M('SubjectPersonal')->where(array('id'=>array('in',$sqlin)))->setField('s_audit',$audit);
		}
		return $num;
	}
}
?>