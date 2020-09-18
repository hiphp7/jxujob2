<?php
namespace Parttime\Model;
use Think\Model;
class ParttimeJobsModel extends Model{
    const AUDIT_WAIT = 0;
    const AUDIT_PASS = 1;
    const AUDIT_NOPASS = 2;
    public $audit_status = array(AUDIT_WAIT=>'审核中',AUDIT_PASS=>'审核通过',AUDIT_NOPASS=>'审核未通过');
	protected $_validate = array(
		array('jobs_name','require','请填写职位名称！',1),
		array('category','require','请选择职位类型！',1),
		array('district','require','请选择工作地区！',1),
		array('wage','require','请填写薪资！',1),
		array('settlement','require','请选择结算方式！',1),
		array('worktime','arrayRequire','请选择工作时间！',1,'callback',3),
		array('contents','require','请填写职位描述！',1),
		array('contact','require','请填写联系人！',1),
		array('mobile','require','请填写手机号！',1),
		array('cycle_starttime,cycle_endtime','timeRequire','工作周期不能为空！',1,'callback',3),
		array('jobs_name','2,50','职位名称应在1~25个字内！',0,'length'),
		array('contents','0,4000','职位描述长度应在2000个字内！',0,'length'),
	);

	protected $_auto = array ( 
        array('apply_num',0),//报名数
        array('audit',self::AUDIT_WAIT),//审核状态
		array('click',0),//浏览次数
		array('amount',0),//招聘人数
		array('long_period',0),//长期合作
		array('addtime','time',1,'function'),//添加时间
		array('refreshtime','time',1,'function'),//刷新时间
	);

	public function arrayRequire($data) {
        if(!empty($data)) {
            return true;
        } else {
            return false;
        }
    }

    public function timeRequire($data) {
        if($_POST['long_period'] == 0 && (!$data['cycle_starttime'] || !$data['cycle_endtime'])) {
            return false;
        }else{
            return true;
        }
    }
	/**
	 * 新增前置操作
	 */
	protected function _before_insert(&$data, $option)
    {
        !$data['audit'] && $data['audit'] = self::AUDIT_WAIT;
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
        if(C('qscms_audit_edit_parttime')=='1'){
            $data['audit'] = self::AUDIT_WAIT;
        }
    	$data = self::_format_data($data);
    }

    /**
     * 更新后置操作
     */
    public function update_search($id) {
        $data = $this->find($id);
        M('ParttimeJobsSearch')->delete($data['id']);
        if($data['audit'] == self::AUDIT_PASS){
            unset($search);
            $search['id'] = $data['id'];
            $search['uid'] = $data['uid'];
            $search['category'] = $data['category'];
            $district_arr = self::_split_district($data['district']);
            foreach ($district_arr as $key => $value) {
                $search['district'.$key] = $value;
            }
            $search['refreshtime'] = $data['refreshtime'];
            $search['settlement'] = $data['settlement'];
            $search['key'] = $data['jobs_name'].','.$data['category_cn'];
            M('ParttimeJobsSearch')->add($search);
        }
    }

    /**
     * 删除前置操作
     */
    protected function set_apply_dec($id) {
        $id = is_array($id)?$id:explode(",", $id);
        foreach ($id as $key => $value) {
            $info = D('Parttime/ParttimeJobsApply')->find($value);
            $this->where(array('id'=>$info['pid']))->setDec('apply_num',1);
        }
        return true;
    }

    /**
     * 删除后置操作
     */
    protected function _after_delete($data,$options) {
    	M('ParttimeJobsSearch')->where(array('id'=>$options['where']['id']))->delete();
        M('ParttimeJobsApply')->where(array('pid'=>$options['where']['id']))->delete();
    	return true;
    }

    private function _format_data($data){
    	if($data['category']){
    		$category_jobs = D('Parttime/ParttimeCategory')->get_category_cache('QS_category_jobs');
    		$data['category_cn'] = $category_jobs[$data['category']];
    	}
    	if($data['district']){
    		$district_info = get_city_info($data['district']);
    		$data['district'] = $district_info['district'];
    		$data['district_cn'] = $district_info['district_cn'];
    	}
    	if($data['wage_type']){
    		$category_wage_type = D('Parttime/ParttimeCategory')->get_category_cache('QS_wage_type');
    		$data['wage_type_cn'] = $category_wage_type[$data['wage_type']];
    	}
    	if($data['settlement']){
    		$category_settlement = D('Parttime/ParttimeCategory')->get_category_cache('QS_settlement');
    		$data['settlement_cn'] = $category_settlement[$data['settlement']];
    	}
    	if($data['identity']){
    		$category_identity = D('Parttime/ParttimeCategory')->get_category_cache('QS_identity');
    		$data['identity_cn'] = $category_identity[$data['identity']];
    	}
    	if($data['height']){
    		$category_height = D('Parttime/ParttimeCategory')->get_category_cache('QS_height');
    		$data['height_cn'] = $category_height[$data['height']];
    	}
    	if(isset($data['sex'])){
    		$data['sex_cn'] = $data['sex']==0?'男女不限':($data['sex']==1?'男':'女');
    	}

    	if($data['worktime']){
    		$data['worktime'] = serialize($data['worktime']);
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

    public function refresh_jobs($id,$uid=0){
        $id = is_array($id)?$id:explode(",", $id);
        $map['id'] = array('in',$id);
        if($uid){
            $map['uid'] = $uid;
        }
    	$timestamp = time();
    	if (false !== $return = $this->where($map)->setField('refreshtime',$timestamp)){
            M('ParttimeJobsSearch')->where($map)->setField('refreshtime',$timestamp);
        }
    	return $return;
    }

    public function audit_jobs($id,$audit){
        $id = is_array($id)?$id:explode(",", $id);
        $map['id'] = array('in',$id);
        $list = $this->where($map)->select();
        if(!$list){
            return false;
        }
        $this->where($map)->setField('audit',$audit);
        M('ParttimeJobsSearch')->where($map)->delete();
        if($audit==self::AUDIT_PASS){
            foreach ($list as $key => $info) {
                unset($search);
                $search['id'] = $info['id'];
                $search['uid'] = $info['uid'];
                $search['category'] = $info['category'];
                $district_arr = self::_split_district($info['district']);
                foreach ($district_arr as $key => $value) {
                    $search['district'.$key] = $value;
                }
                $search['refreshtime'] = $info['refreshtime'];
                $search['settlement'] = $info['settlement'];
                $search['key'] = $info['jobs_name'].','.$info['category_cn'];
                M('ParttimeJobsSearch')->add($search);
            }
        }
        return true;
    }

    private function _split_district($district){
    	$district_arr = explode(".", $district);
    	$count = count($district_arr);
    	$return = array();
    	for ($i=1; $i <= $count; $i++) { 
    		$return[$i] = $return[$i-1]?($return[$i-1].'.'.$district_arr[$i-1]):$district_arr[$i-1];
    	}
    	return $return;
    }
}
?>