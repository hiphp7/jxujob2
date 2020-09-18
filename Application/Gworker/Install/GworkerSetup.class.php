<?php
/**
 * 普工招聘安装程序
 */
class GworkerSetup{
	/**
     * [init 安装程序初始化程序]
     */
    public function setup_init(){
        if(false === $apply = F('apply_info_list')) $apply = D('Apply')->apply_info_cache();
        if($apply['Home']['version']){
            $version =  explode('.', $apply['Home']['version']);
            $v = $version[0] * 1000000 + $version[1] * 10000 + $version[2];
            if($v < 4020072){
                $this->_error = '请将基础版程序升级至4.2.72版本以上（含）！';
                return false;
            }
        }
        if($apply['Mobile']['version']){
            $version =  explode('.', $apply['Mobile']['version']);
            $v = $version[0] * 1000000 + $version[1] * 10000 + $version[2];
            if($v < 4020060){
                $this->_error = '请将触屏版程序升级至4.2.60版本以上（含）！';
                return false;
            }
        }
        return true;
    }
    /**
	 * [setup 安装程序]
	 */
    public function setup(){
        //初始化附件添加
        $imgs = array('gworker.png');
        $path = C('qscms_attach_path').'resource/mobile_nav/';
        $org = APP_PATH.'Gworker/Install/attach/';
        foreach ($imgs as $key => $val) {
            if (!is_dir($path)) mkdir($path,0755,true);
            copy($org.$val,$path.$val);
        }
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
    }
    /**
     * [getError 返回错误]
     */
    public function getError(){
        return $this->_error;
    }
}
?>