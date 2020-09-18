<?php
/**
 * 商城安装程序
 */
class MallSetup{
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
    	$this->setup_ad();
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
    	D('AdCategory')->where(array('org'=>'Mall'))->delete();
        D('Page')->where(array('module'=>'Mall'))->delete();
    }
    /**
     * [setup_ad 安装商城广告位]
     */
    protected function setup_ad(){
    	$path = APP_PATH.C('DEFAULT_MODULE').'/View/'.C('DEFAULT_THEME').'/Config/';
		$adcate = F('config_ads','',$path)?:array();
		$ads = F('Mall/Install/config_ads','',APP_PATH);
		$adcate = array_merge($adcate,$ads);
		F('config_ads',$adcate,$path);
    }
    /**
     * [getError 返回错误]
     */
    public function getError(){
    	return $this->_error;
    }
}
?>