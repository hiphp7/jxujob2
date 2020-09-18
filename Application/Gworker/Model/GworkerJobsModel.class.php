<?php
namespace Gworker\Model;
use Think\Model;
class GworkerJobsModel extends Model{
    const DISPLAY_HIDDEN = 0;
    const DISPLAY_SHOW = 1;
    public $display_arr = array(DISPLAY_HIDDEN=>'隐藏',DISPLAY_SHOW=>'显示');
    protected $_validate = array(
        array('jobs_name','require','请填写招聘岗位',1),
        array('district','require','请选择工作地区',1),
        array('wage','require','请填写薪资待遇',1),
        array('jobs_name','2,50','招聘岗位应在1~25个字内',0,'length'),
        array('welfare','0,4000','吃住福利长度应在2000个字内',0,'length'),
        array('intro','0,4000','工作简介长度应在2000个字内',0,'length'),
        array('demand','0,4000','招聘要求长度应在2000个字内',0,'length'),
    );
	protected $_auto = array (
        array('apply_num',0),//报名数
        array('display',self::DISPLAY_SHOW),//显示状态
		array('click',0),//浏览次数
		array('amount',0),//招聘人数
		array('addtime','time',1,'function'),//添加时间
		array('refreshtime','time',1,'function'),//刷新时间
        array('district1',0),
        array('district2',0),
        array('district3',0),
        array('district4',0),
        array('district5',0),
        array('district6',0),
	);
	/**
	 * 新增前置操作
	 */
	protected function _before_insert(&$data, $option)
    {
        $data['display'] = self::DISPLAY_SHOW;
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

    /**
     * 删除后置操作
     */
    protected function _after_delete($data,$options) {
        M('GworkerJobsApply')->where(array('pid'=>$options['where']['id']))->delete();
    	return true;
    }

    private function _format_data($data){
    	if($data['district']){
    		$district_info = get_city_info($data['district']);
    		$data['district'] = $district_info['district'];
    		$data['district_cn'] = $district_info['district_cn'];
            $district_arr = self::_split_district($district_info['district']);
            foreach ($district_arr as $key => $value) {
                $data['district'.$key] = $value;
            }
    	}
    	if($data['wage']){
    		$category_wage = D('Gworker/GworkerCategory')->get_category_cache('QS_wage');
    		$data['wage_cn'] = $category_wage[$data['wage']];
    	}
        if($data['education']){
            $category_education = D('Gworker/GworkerCategory')->get_category_cache('QS_education');
            $data['education_cn'] = $category_education[$data['education']];
        }
    	if(isset($data['sex'])){
    		$data['sex_cn'] = $data['sex']==0?'性别不限':($data['sex']==1?'男':'女');
    	}
        if($data['tag']){
            $category_tag = D('Gworker/GworkerCategory')->get_category_cache('QS_tag');
            $posttag = $data['tag'];
            $tagArr = explode(",",$posttag);
            $r_arr = array();
            foreach ($tagArr as $key => $value) {
                $r_arr[] = $value.'|'.$category_tag[$value];
            }
            if(!empty($r_arr)){
                $data['tag'] = implode(",",$r_arr);
            }else{
                $data['tag'] = '';
            }
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
    	$return = $this->where($map)->setField('refreshtime',$timestamp);
    	return $return;
    }

    public function display_jobs($id,$display){
        $id = is_array($id)?$id:explode(",", $id);
        $map['id'] = array('in',$id);
        $this->where($map)->setField('display',$display);
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