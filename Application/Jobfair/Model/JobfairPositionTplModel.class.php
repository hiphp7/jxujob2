<?php
namespace Jobfair\Model;
use Think\Model;
class JobfairPositionTplModel extends Model{
	/**
	 * 删除展位模板
	 */
	public function tpl_delete($id){
		!is_array($id) && $id=array($id);
		$sqlin=implode(",",$id);
		if (fieldRegex($sqlin,'in'))
		{
			$list = $this->where(array('id'=>array('in',$sqlin)))->field('position_img')->select();
			foreach ($list as $key => $value) {
				$img_list = unserialize($value['position_img']);
				foreach ($img_list as $k => $v) {
					@unlink(C('qscms_attach_path')."jobfair_tpl/".$v);
				}
			}
			$this->where(array('id'=>array('in',$sqlin)))->delete();
			return array('state'=>1,'msg'=>'删除成功！');
		}else{
			return array('state'=>0,'msg'=>'删除失败！');
		}
	}
}
?>