<?php 
namespace Parttime\Model;
use Think\Model;
class ParttimeCategoryModel extends Model
{
    public function category_cache() {
        $category = array();
        $categoryData = $this->field('id,alias,name')->order('ordid desc')->select();
        foreach ($categoryData as $key=>$val){
        	$category[$val['alias']][$val['id']] = $val['name'];
        }
        F('parttime_category', $category);
        return $category;
    }
    /**
     * [get_category_cache 读取缓存]
     * @param  string $type [单一分类名称]
     * @return array       [分类集]
     */
    public function get_category_cache($type='')
    {
        if(false === $category = F('parttime_category')){
            $category = $this->category_cache();
        }
        if($type) return $category[$type];
        return $category;
    }
	/**
     * 后台有更新则删除缓存
     */
    protected function _before_write($data, $options) {
        F('parttime_category', NULL);
    }
    /**
     * 后台有删除也删除缓存
     */
    protected function _after_delete($data,$options){
        F('parttime_category', NULL);
    }
}
?>