<?php
/**
 * 智能推荐安装程序
 */
class RecommendSetup{
	/**
	 * [setup_init 安装程序初始化程序]
	 */
	public function setup_init(){
		if(false === $apply = F('apply_info_list')) $apply = D('Apply')->apply_info_cache();
        if($apply['Home']['version']){
            $version =  explode('.', $apply['Home']['version']);
            $v = $version[0] * 1000000 + $version[1] * 10000 + $version[2];
            if($v >= 4020016) return true;
            $this->_error = '请将基础版程序升级至4.2.16版本以上（含）！';
            return false;
        }
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