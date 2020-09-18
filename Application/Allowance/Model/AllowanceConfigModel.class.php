<?php 
namespace Allowance\Model;
use Think\Model;
class AllowanceConfigModel extends Model{
	/**
     * 读取系统参数生成缓存文件
     */
    public function config_cache() {
        $config = array();
        $res = $this->where()->getField('name,value');
        foreach ($res as $key=>$val) {
            $un_result=unserialize($val);
        	$config[$key] = $un_result ? $un_result : $val;
            if(preg_match('/(http||https):\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is',$config[$key])){
                $config[$key] = htmlspecialchars_decode($config[$key],ENT_QUOTES);
            }
        }
        F('allowance_config', $config);
        return $config;
    }
    
    /**
     * 后台有更新则删除缓存
     */
    protected function _before_write($data, $options) {
        F('allowance_config', NULL);
    }
    /**
     * 后台有删除也删除缓存
     */
    protected function _after_delete($data,$options){
        F('allowance_config', NULL);
    }
}
?>