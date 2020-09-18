<?php
namespace Mall\Model;
use Think\Model;
class MallCategoryModel extends Model{
	protected $_validate = array(
		array('categoryname','0,80','{%mall_category_length_error_categoryname}',0,'length'),
	);
	protected $_auto = array ( 
		array('category_order',0),
	);
	/**
	 * [mall_cache 获取商品分类数据写入缓存]
	 */
	public function mall_cache(){
		$mall = array();
		$mallData = $this->field('id,parentid,categoryname')->order('category_order desc,id')->select();
		foreach ($mallData as $key => $val) {
			$mall[$val['parentid']][$val['id']] = $val['categoryname'];
		}
		F('mall_cate',$mall);
		return $mall;
	}
	/**
	 * [get_mall_cache 读取商品分类数据]
	 */
	public function get_mall_cache($pid=0){
		if(false === $mall = F('mall_cate')){
			$mall = $this->mall_cache();
		}
		if($pid === 'all') return $mall;
		return $mall[intval($pid)];
	}
	/**
     * 后台有更新则删除缓存
     */
    protected function _before_write($data, $options) {
        F('mall_cate', NULL);
    }
    /**
     * 后台有删除也删除缓存
     */
    protected function _after_delete($data,$options){
        F('mall_cate', NULL);
    }
	public function category_delete($id){
		if (!is_array($id)) $id=array($id);
		$where['id']=array('in',$id);
		$where1['parentid']=array('in',$id);
		$num = $this->where($where)->delete();
		$num1 = $this->where($where1)->delete();
		if(false === $num || false === $num1) return array('state'=>0,'error'=>'删除失败！');
		return array('state'=>1,'num'=>$num+$num1);
	}
}
?>