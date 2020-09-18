<?php
/**
 * 采集安装程序
 */
class LocoyspiderSetup{
	/**
     * [init 安装程序初始化程序]
     */
    public function setup_init(){
        return true;
    }
    /**
	 * [setup 安装程序]
	 */
    public function setup(){
        
        return true;
    }
    /**
     * [init 安装程序初始化程序]
     */
    public function unload_init(){
        return true;
    }
    /**
     * [unload 卸载程序]
     */
    public function unload(){
    	//D('Navigation')->where(array('pagealias'=>'QS_locoyspider'))->delete();
		return true;
    }
    /**
     * [getError 返回错误]
     */
    public function getError(){
        return $this->_error;
    }
}
?>