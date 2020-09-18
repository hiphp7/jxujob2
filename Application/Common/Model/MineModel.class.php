<?php
namespace Common\Model;
use Think\Model;
class MineModel extends Model{
	protected $_validate = array(
		array('name,value','identicalNull','',0,'callback'),
	);
	/**
	 * [text_cache description]
	 */
	public function text_cache(){
		$text_list = $this->getfield('name,value');
		F('mine_list',$text_list);
		return $text_list;
	}
	/**
     * 后台有更新则删除缓存
     */
    protected function _before_write($data, $options) {
        F('mine_list', NULL);
    }
    /**
     * 后台有删除也删除缓存
     */
    protected function _after_delete($data,$options){
        F('mine_list', NULL);
    }
}
?>