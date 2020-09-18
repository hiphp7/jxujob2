<?php
/**
 * Uc安装程序
 */
class UcenterSetup{
	/**
	 * [setup_init 安装程序初始化程序]
	 */
	public function setup_init(){
        $path = APP_PATH . 'Ucenter/qscmslib/Api/uc_client/data';
        if(!is_writable($path)){
            $this->_error = '请为该文件设置读写权限“'.$path.'”';
            return false;
        }
        if(false === $apply = F('apply_info_list')) $apply = D('Apply')->apply_info_cache();
        if($apply['Home']['version']){
            $version =  explode('.', $apply['Home']['version']);
            $v = $version[0] * 1000000 + $version[1] * 10000 + $version[2];
            if($v >= 4020000) return true;
            $this->_error = '请将基础版程序升级至4.2.0版本以上（含）！';
            return false;
        }
		return true;
	}
	/**
	 * [setup 安装程序]
	 */
    public function setup(){
        $org = APP_PATH . 'Ucenter/Install/php/Api/';
        $path = './Api/';
        if (!is_dir($path)) mkdir($path,0755,true);
        copy($org.'uc.php',$path.'uc.php');
        copy($org.'index.html',$path.'index.html');
        return true;
    }
    /**
     * [init 安装程序初始化程序]
     */
    public function unload_init(){
        $path = './Api';
        if(!is_writable($path)){
            $this->_error = '请为该文件设置读写权限“'.$path.'”';
            return false;
        }
        return true;
    }
    /**
     * [unload 卸载程序]
     */
    public function unload(){
        rmdirs('./Api',true);
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