<?php
/**
 * 招聘会安装程序
 */
class JobfairSetup{
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
        //初始化附件添加
        $imgs = array('57e887cf02d37.jpg','57c96d5b37541.jpg','57e888401f3c0.png');
        $path = C('qscms_attach_path').'jobfair/16/09/26/';
        $org = APP_PATH.'Jobfair/Install/attach/';
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
    	D('Navigation')->where(array('pagealias'=>'QS_jobfairlist'))->delete();
    	D('Page')->where(array('module'=>'Jobfair'))->delete();
    }
    /**
     * [getError 返回错误]
     */
    public function getError(){
        return $this->_error;
    }
}
?>