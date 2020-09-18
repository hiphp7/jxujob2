<?php
/**
 * 分站安装程序
 */
class SubsiteSetup{
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
        $str = str_replace('http://','',C('qscms_site_domain'));
        if(preg_match('/com.cn|net.cn|gov.cn|org.cn$/',$str) === 1){
            $domain = array_slice(explode('.', $str), -3, 3);
        }else{
            $domain = array_slice(explode('.', $str), -2, 2);
        }
        $domain = '.'.implode('.',$domain);
        $config['SESSION_OPTIONS'] = array('domain'=>$domain);
        $config['COOKIE_DOMAIN'] = $domain;
        $this->update_config($config,CONF_PATH.'url.php');
    }
    protected function update_config($new_config, $config_file = '') {
        !is_file($config_file) && $config_file = HOME_CONFIG_PATH . 'config.php';
        if (is_writable($config_file)) {
            $config = require $config_file;
            $config = array_merge($config, $new_config);
            file_put_contents($config_file, "<?php \nreturn " . stripslashes(var_export($config, true)) . ";", LOCK_EX);
            @unlink(RUNTIME_FILE);
            return true;
        } else {
            return false;
        }
    }
    /**
     * [init 安装程序初始化程序]
     */
    public function unload_init(){
        $dirs = getsubdirs(APP_PATH.'/Home/View/');
        unset($dirs[array_search("tpl_company",$dirs)]);
        unset($dirs[array_search("tpl_resume",$dirs)]);
        $subsite_list = D('Subsite')->get_subsite_domain();
        foreach ($subsite_list as $key => $val) {
            foreach ($dirs as $v) {
                $path = APP_PATH.C('DEFAULT_MODULE').'/View/'.$v.'/Config/'.$val['s_domain'];
                if(is_writable($path)){
                    rmdirs($path,true);
                }
            }
        }
        //删除分站数据
        $where['subsite_id'] = array('gt',0);
        D('Page')->where($where)->delete();
        D('AdCategory')->where($where)->delete();
        D('Ad')->where($where)->delete();
        D('PmsSys')->where($where)->delete();
        D('Article')->where($where)->delete();
        D('Explain')->where($where)->delete();
        D('Link')->where($where)->delete();
        D('Notice')->where($where)->delete();
    }
    /**
     * [unload 卸载程序]
     */
    public function unload(){
        F('subsite_list', NULL);
        F('subsite_domain_list',NULL);
        F('subsite_config', NULL);
        F('subsite_alias', NULL);
    }
    /**
     * [getError 返回错误]
     */
    public function getError(){
    	return $this->_error;
    }
}
?>