<?php
/**
 * 微信安装程序
 */
class WeixinSetup{
	/**
	 * [init 安装程序初始化程序]
	 */
	public function setup_init(){
		if(false === $apply = F('apply_list')) $apply = D('Apply')->apply_cache();
		if(!$apply['Mobile']){
			$this->_error = '请先安装触屏端应用！';
			return false;
		}
		return true;
	}
	/**
	 * [setup 安装程序]
	 */
    public function setup(){
        $urls = M('WeixinMenu')->where(array('type'=>'view'))->getfield('id,url');
        foreach ($urls as $key => $val) {
            if(C('qscms_weixin_appid')){
                $val = str_replace('|appid|',C('qscms_weixin_appid'),$val);
            }
            $val = str_replace('|domain|',C('qscms_site_domain'),$val);
            M('WeixinMenu')->where(array('id'=>$key))->setfield('url',$val);
        }
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
    public function unload(){}
    /**
     * [getError 返回错误]
     */
    public function getError(){
    	return $this->_error;
    }
}
?>