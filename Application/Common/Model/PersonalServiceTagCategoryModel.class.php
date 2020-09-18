<?php
namespace Common\Model;
use Think\Model;
class PersonalServiceTagCategoryModel extends Model{
	public function tag_category_cache(){
		$category = $this->getfield('id,name');
		F('service_tag_category',$category);
		return $category;
	}
	/**
     * 后台有更新则删除缓存
     */
    protected function _before_write($data, $options) {
        F('service_tag_category',NULL);
    }
    /**
     * 后台有删除也删除缓存
     */
    protected function _after_delete($data,$options){
        F('service_tag_category',NULL);
    }
}
?>