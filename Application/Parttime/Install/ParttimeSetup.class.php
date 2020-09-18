<?php
/**
 * 兼职招聘安装程序
 */
class ParttimeSetup{
	/**
     * [init 安装程序初始化程序]
     */
    public function setup_init(){
        if(false === $apply = F('apply_info_list')) $apply = D('Apply')->apply_info_cache();
        if($apply['Home']['version']){
            $version =  explode('.', $apply['Home']['version']);
            $v = $version[0] * 1000000 + $version[1] * 10000 + $version[2];
            if($v < 4020064) {
                $this->_error = '请将基础版程序升级至4.2.64版本以上（含）！';
                return false;
            }
        }
        if($apply['Mobile']['version']){
            $version =  explode('.', $apply['Mobile']['version']);
            $v = $version[0] * 1000000 + $version[1] * 10000 + $version[2];
            if($v < 4020053) {
                $this->_error = '请将触屏版程序升级至4.2.53版本以上（含）！';
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
        $imgs = array('parttime.png');
        $path = C('qscms_attach_path').'resource/mobile_nav/';
        $org = APP_PATH.'Parttime/Install/attach/';
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