<?php
/**
 * 分站安装程序
 */
class RemindSetup{
	/**
	 * [setup_init 安装程序初始化程序]
	 */
	public function setup_init(){
        return true;
	}
	/**
	 * [setup 安装程序]
	 */
    public function setup(){
        copy(APP_PATH.'Remind/Install/php/RemindLogin.class.php',APP_PATH.'Common/Cron/RemindLogin.class.php');
        rmdirs(RUNTIME_PATH.'Data',true);
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
        D('Crons')->where(array('filename'=>'RemindLogin'))->delete();
        @unlink(APP_PATH.'Common/Cron/RemindLogin.class.php');
        rmdirs(RUNTIME_PATH.'Data',true);
    }
    /**
     * [getError 返回错误]
     */
    public function getError(){
    	return $this->_error;
    }
}
?>