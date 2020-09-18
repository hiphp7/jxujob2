<?php
namespace Parttime\Model;
use Think\Model;
class ParttimeJobsApplyModel extends Model{
	protected $_validate = array(
		array('realname','require','请填写真实姓名！',1),
		array('birthdate','require','请选择出生年份！',1),
		array('identity','require','请选择身份！',1),
		array('mobile','require','请填写手机号！',1),
	);

	protected $_auto = array ( 
		array('addtime','time',1,'function'),//添加时间
	);

    /**
     * 新增前置操作
     */
    protected function _before_insert(&$data, $option)
    {
        $data = self::_format_data($data);
    }
    /**
     * 新增后置操作
     */
    protected function _after_insert($data, $option)
    {
        M('ParttimeJobs')->where(array('id'=>$data['pid']))->setInc('apply_num',1);
    }

    private function _format_data($data){
    	if($data['identity']){
    		$category_identity = D('Parttime/ParttimeCategory')->get_category_cache('QS_identity_apply');
    		$data['identity_cn'] = $category_identity[$data['identity']];
    	}
    	if(isset($data['sex'])){
    		$data['sex_cn'] = $data['sex']==0?'男女不限':($data['sex']==1?'男':'女');
    	}
    	return $data;
    }
}
?>