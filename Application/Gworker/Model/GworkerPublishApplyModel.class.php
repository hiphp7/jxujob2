<?php
namespace Gworker\Model;
use Think\Model;
class GworkerPublishApplyModel extends Model{
    const STATUS_WAIT = 0;
    const STATUS_PASS = 1;
    public $status_arr = array(STATUS_WAIT=>'未处理',STATUS_PASS=>'已处理');
	protected $_validate = array(
		array('jobs_name','require','请填写招聘岗位',1),
		array('district','require','请选择工作地区',1),
		array('wage','require','请填写薪资待遇',1),
		array('contact','require','请填写联系人',1),
		array('mobile','require','请填写联系电话',1),
		array('jobs_name','2,50','招聘岗位应在1~25个字内',0,'length'),
	);
	protected $_auto = array (
        array('apply_num',0),//报名数
        array('status',self::STATUS_WAIT),//审核状态
		array('amount',0),//招聘人数
		array('addtime','time',1,'function'),//添加时间
	);
	/**
	 * 新增前置操作
	 */
	protected function _before_insert(&$data, $option)
    {
        $data['status'] = self::STATUS_WAIT;
    	$data = self::_format_data($data);
    }

    /**
     * 新增后置操作
     */
    protected function _after_insert($data, $option)
    {
        
    }

    /**
     * 更新前置操作
     */
    protected function _before_update(&$data,$options) {
    	$data = self::_format_data($data);
    }


    private function _format_data($data){
    	if($data['district']){
    		$district_info = get_city_info($data['district']);
    		$data['district'] = $district_info['district'];
    		$data['district_cn'] = $district_info['district_cn'];
    	}
    	if($data['wage']){
    		$category_wage = D('Gworker/GworkerCategory')->get_category_cache('QS_wage');
    		$data['wage_cn'] = $category_wage[$data['wage']];
    	}
    	return $data;
    }

    private function _check_auth($mobile,$secretkey){
    	$check_auth = D('Authentication')->where(array('mobile'=>$mobile,'secretkey'=>$secretkey))->find();
    	if($check_auth){
    		return $check_auth;
    	}else{
    		return false;
    	}
    }

    public function set_status($id,$status){
        $id = is_array($id)?$id:explode(",", $id);
        $map['id'] = array('in',$id);
        $this->where($map)->setField('status',$status);
        return true;
    }
}
?>