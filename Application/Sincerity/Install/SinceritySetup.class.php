<?php
/**
 * 诚聘通安装程序
 */
class SinceritySetup{
	/**
     * [init 安装程序初始化程序]
     */
    public function setup_init(){
        return true;
    }
    /**
     * [setup 安装程序]
     */
    public function setup(){}
    /**
     * [init 安装程序初始化程序]
     */
    public function unload_init(){
        return true;
    }
    /**
     * [unload 卸载程序]
     */
    public function unload(){}
    /**
     * [getError 返回错误]
     */
    public function getError(){
        return $this->_error;
    }
}
?>