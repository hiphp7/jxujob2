<?php
namespace Subsite\Controller;
use Common\Controller\ConfigbaseController;
class AdminController extends ConfigbaseController{
	public function _initialize() {
        parent::_initialize();
        $this->_name = 'Subsite';
    }
    public function _before_index(){
        $this->order = 's_order desc,s_id';
    }
    /**
     * [config 分站配置项]
     */
    public function config(){
    	if(IS_POST){
            $site_domain = str_replace('http://','',C('qscms_site_domain'));
            $wap_domain = str_replace('http://','',C('qscms_wap_domain'));
            $subsites = D('Subsite')->get_subsite_cache();
            foreach (I('post.') as $key => $val) {
                $val = is_array($val) ? serialize($val) : $val;
                if(!in_array($key,array('subsite_method','sel_subsite_method'))){
                    $val = str_replace('http://','',$val);
                    if($domains[$val]){
                        $this->error('详情页域名('.$val.')不能重复！');
                    }
                    if($val == $site_domain){
                        $this->error('详情页域名('.$val.')不能与网站主域名重复！');
                    }
                    if(C('apply.Subsite') && $val == $wap_domain){
                        $this->error('详情页域名('.$val.')不能与触屏端域名重复！');
                    }
                    if($subsites[$val]){
                        $this->error('详情页域名('.$val.')不能与('.$subsites[$val]['s_sitename'].')域名重复！');
                    }
                    $domains[$val] = $key;
                }
            }
    		foreach (I('post.') as $key => $val) {
    			$val = is_array($val) ? serialize($val) : $val;
                if(in_array($key,array('subsite_method','sel_subsite_method'))){
                    D('Config')->where(array('name' => $key))->save(array('value' => $val));
                }else{
                    $val = str_replace('http://','',$val);
                    D('SubsiteConfig')->where(array('alias' => $key))->save(array('domain' => $val));
                }
	        }
    		$domain = D('Config')->sub_domain();
    		$this->update_config(array('APP_SUB_DOMAIN_RULES'=>$domain),CONF_PATH.'sub_domain.php');
    		$this->success(L('operation_success'));
    	}else{
            $info = M('SubsiteConfig')->getfield('alias,domain');
            $this->assign('info',$info);
    		$this->display();
    	}
    }
    public function _before_insert($data){
        $data['s_domain'] = str_replace('http://','',$data['s_domain']);
        $data['s_m_domain'] = str_replace('http://','',$data['s_m_domain']);
        $site_domain = str_replace('http://','',C('qscms_site_domain'));
        $wap_domain = str_replace('http://','',C('qscms_wap_domain'));
        if($data['s_domain'] == $site_domain || $data['s_m_domain'] == $site_domain){
            $this->error('分站域名不能与主域名重复！');
        }
        if($data['s_domain'] == $wap_domain || $data['s_m_domain'] == $wap_domain){
            $this->error('分站域名不能与触屏版域名重复！');
        }
        $subsites = D('Subsite')->get_subsite_cache();
        if($data['s_id']){
            if(($subsites[$data['s_domain']] && $subsites[$data['s_domain']]['s_id'] != $data['s_id']) || ($subsites[$data['s_m_domain']] && $subsites[$data['s_m_domain']]['s_id'] != $data['s_id'])){
                $d = $subsites[$data['s_domain']]['s_sitename'] ?:$subsites[$data['s_m_domain']]['s_sitename'];
                $this->error('分站域名不能与('.$d.')域名重复！');
            }
        }else{
            if($subsites[$data['s_domain']] || $subsites[$data['s_m_domain']]){
                $d = $subsites[$data['s_domain']]['s_sitename'] ?:$subsites[$data['s_m_domain']]['s_sitename'];
                $this->error('分站域名不能与('.$d.')域名重复！');
            }
        }
        $subsite_config = D('SubsiteConfig')->get_subsite_config();
        if($subsite_config[$data['s_domain']] || $subsite_config[$data['s_m_domain']]){
            $this->error('分站域名不能与分站详情页域名重复！');
        }
        $py = new \Common\qscmslib\pinyin;
        $data['s_spell'] = $py->getFirstPY($data['s_sitename']);
        $data['s_ordid'] = strtoupper(substr($data['s_spell'],0,1));
        $reg = M('CategoryDistrict')->where(array('id'=>$data['s_district']))->getfield('parentid');
        if($reg > 0){
            $data['s_level'] = 1;
        }else{
            $data['s_level'] = 2;
        }
        return $data;
    }
    public function _before_update($data){
        return $this->_before_insert($data);
    }
    public function _before_add(){
        if(!IS_POST){
            $dirs = getsubdirs(APP_PATH.'/Home/View/');
			$dirss = getsubdirs(APP_PATH.'/Mobile/View/');
            unset($dirs[array_search("tpl_company",$dirs)]);
            unset($dirs[array_search("tpl_resume",$dirs)]);
            $this->assign('dirs',$dirs);
			$this->assign('dirss',$dirss);
            $subsite_list = D('Subsite')->get_subsite_domain();
            foreach ($subsite_list as $key => $val) {
                $val['s_district'] && $subsites[] = $val['s_district'];
            }
            $this->assign('subsites',implode(',',$subsites));
        }
    }
    public function _before_edit(){
        if(IS_POST){
            $id = I('request.s_id',0,'intval');
            foreach (array('s_logo_home','s_logo_user','s_logo_other') as $val) {
                if(!$_FILES[$val]['name']) continue;
                $result = $this->_upload($_FILES[$val], 'resource/subsite_'.$id.'/', array(
                    'maxSize' => 2*1024,//图片最大2M
                    'uploadReplace' => true,
                    'attach_exts' => 'bmp,png,gif,jpeg,jpg'
                ),$val);
                if ($result['error']) {
                    $_POST[$val] = 'subsite_'.$id.'/'.$result['info'][0]['savename'];
                }
            }
        }
        $this->_before_add();
    }
    public function _after_update(){
        $domain = D('Config')->sub_domain();
        $this->update_config(array('APP_SUB_DOMAIN_RULES'=>$domain),CONF_PATH.'sub_domain.php');
    }
    public function _after_insert($id){
        foreach (array('s_logo_home','s_logo_user','s_logo_other') as $val) {
            if(!$_FILES[$val]['name']) continue;
            $result = $this->_upload($_FILES[$val], 'resource/subsite_'.$id.'/', array(
                'maxSize' => 2*1024,//图片最大2M
                'uploadReplace' => true,
                'attach_exts' => 'bmp,png,gif,jpeg,jpg'
            ),$val);
            if ($result['error']) {
                $data[$val] = 'subsite_'.$id.'/'.$result['info'][0]['savename'];
            }
        }
        if($data && $id){
            M($this->_name)->where(array('s_id'=>$id))->save($data);
        }
        $this->_after_update();
    }
    public function _before_del($data){
        $dirs = getsubdirs(APP_PATH.'/Home/View/');
        unset($dirs[array_search("tpl_company",$dirs)]);
        unset($dirs[array_search("tpl_resume",$dirs)]);
        foreach ($data as $val) {
            foreach ($dirs as $v) {
                $path = APP_PATH.C('DEFAULT_MODULE').'/View/'.$v.'/Config/'.$val['s_domain'];
                if(is_writable($path)){
                    rmdirs($path,true);
                }
            }
        }
    }
}
?>