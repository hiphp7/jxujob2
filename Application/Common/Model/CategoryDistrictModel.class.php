<?php 
namespace Common\Model;
use Think\Model;
class CategoryDistrictModel extends Model
{
	protected $_validate = array(
		array('categoryname','1,60','{%category_district_length_error_categoryname}',1,'length'),
		array('spell','','{%category_district_exist_error_spell}',2,'unique',3),
	);
	protected $_auto = array (
		array('parentid',0),
		array('category_order',0),
	);
	/**
	 * [custom description]
	 */
	public function custom_district_cache(){
		$district = array();
		$districtData = $this->field('id,parentid,categoryname,spell')->order('category_order desc')->select();
		foreach ($districtData as $key => $val) {
			$district[$val['parentid']][$val['id']] = $val;
		}
		F('district_custom_cate',$district);
		return $district;
	}
	/**
	 * [district_cache 获取省市数据写入缓存]
	 */
	public function district_cache(){
		$district = array();
		$districtData = $this->field('id,parentid,categoryname')->order('category_order desc')->select();
		foreach ($districtData as $key => $val) {
			$district[$val['parentid']][$val['id']] = $val['categoryname'];
		}
		F('district',$district);
		return $district;
	}
	/**
	 * [get_district_cache 读取省市数据]
	 */
	public function get_district_cache($pid=0){
		if(false === $district = F('district')){
			$district = $this->district_cache();
		}
		if($pid === 'all') return $district;
		return $district[intval($pid)];
	}
	/**
	 * [city_search_cache 地区搜索缓存]
	 */
	public function city_search_cache(){
		$city = $city_list = array();
		$t = range(0,2);
		$cityData = $this->field('id,parentid')->order('parentid asc')->select();
		foreach ($cityData as $key => $val) {
			if(!$val['parentid']){
				$city_list[$val['id']] = $val['id'].'_0_0';
				$city[$val['id']] = array('tier'=>1,'spid'=>$val['id']);
			}else{
				if($city[$val['parentid']]['tier'] == 1){
					$city_list[$val['id']] = $city[$val['parentid']]['spid'].'_'.$val['id'].'_0';
					$city[$val['id']] = array('tier'=>2,'spid'=>$city[$val['parentid']]['spid'].'_'.$val['id']);
				}elseif($city[$val['parentid']]['tier'] == 2){
					$city_list[$val['id']] = $city[$val['parentid']]['spid'].'_'.$val['id'];
				}
			}
		}
		F('city_search_cate',$city_list);
		return $city_list;
	}
	/**
	 * [city_cate_cache 地区列表缓存]
	 */
	public function city_cate_cache(){
		$citySpell = $this->order('parentid desc')->getfield('spell,id,parentid,categoryname');
		foreach ($citySpell as $key => $val) {
			$cityId[$val['id']] = $val;
		}
		$city = array('spell'=>$citySpell,'id'=>$cityId);
		F('city_cate_list',$city);
		return $city;
	}
	/**
     * 后台有更新则删除缓存
     */
    protected function _before_write($data, $options) {
        F('district', NULL);
        F('city_search_cate',NULL);
    }
    /**
     * 后台有删除也删除缓存
     */
    protected function _after_delete($data,$options){
        F('district', NULL);
        F('city_search_cate',NULL);
    }
	public function category_delete($id,$num=0){
		if (!is_array($id)) $id=array($id);
		foreach ($id as $key => $value) {
			$child = $this->where(array('parentid'=>$value))->getfield('id',true);
			if($child){
				$num = $this->category_delete($child,$num);
			}
			$this->where(array('id'=>$value))->delete();
			$num++;
		}
		return $num;
	}
}
?>