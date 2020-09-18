<?php
/**
 * 招聘外包（RPO）安装程序
 */
class RpoSetup{
	/**
     * [init 安装程序初始化程序]
     */
    public function setup_init(){
        if(false === $apply = F('apply_info_list')) $apply = D('Apply')->apply_info_cache();
        if($apply['Home']['version']){
            $version =  explode('.', $apply['Home']['version']);
            $v = $version[0] * 1000000 + $version[1] * 10000 + $version[2];
            if($v >= 4020013) return true;
            $this->_error = '请将基础版程序升级至4.2.13版本以上（含）！';
            return false;
        }
        return true;
    }
    /**
     * [setup 安装程序]
     */
    public function setup(){
        //初始化附件添加
        $imgs = array('1.png','2.png','3.png','4.png','5.png','6.png');
        $path = C('qscms_attach_path').'rpo/';
        $org = APP_PATH.'Rpo/Install/attach/';
        foreach ($imgs as $key => $val) {
            if (!is_dir($path)) mkdir($path,0755,true);
            copy($org.$val,$path.$val);
        }
        return true;
    }
    /**
     * [init 卸载程序初始化程序]
     */
    public function unload_init(){
        return true;
    }
    /**
     * [unload 卸载程序]
     */
    public function unload(){
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