<?php
namespace Mobile\Controller;

use Common\Controller\ConfigbaseController;

class AdminController extends ConfigbaseController
{
    public function _initialize()
    {
        parent::_initialize();
        $this->_name = 'Page';
        $this->_mod = D('Page');
        $this->tpl_dir = APP_PATH.'/Mobile/View/';
    }
    public function edit()
    {
        if (IS_POST) {
            if ($_POST['wap_domain']) {
                if (false === strpos(strtolower($_POST['wap_domain']), 'http://')) {
                    $_POST['wap_domain'] = 'http://'.$_POST['wap_domain'];
                }
                if ($_POST['wap_domain'] == C('qscms_site_domain')) {
                    $this->error('触屏版域名不能与主域名重复！');
                }
                $domain = str_replace('http://', '', $_POST['wap_domain']);
                if (C('apply.Subsite')) {
                    $repeat_where['s_domain'] = $domain;
                    $repeat_where['s_m_domain'] = $domain;
                    $repeat_where['_logic'] = 'or';
                    $repeat = D('Subsite')->where($repeat_where)->find();
                    if ($repeat) {
                        $this->error('触屏版域名不能与('.$repeat['s_sitename'].')域名重复！');
                    }
                    $subsite_config = D('SubsiteConfig')->get_subsite_config();
                    if ($subsite_config[$domain]) {
                        $this->error('触屏版域名不能与分站详情页域名重复！');
                    }
                }
            }
            foreach (I('post.') as $key => $val) {
                $val = is_array($val) ? serialize(htmlspecialchars_decode($val, ENT_QUOTES)) : htmlspecialchars_decode($val, ENT_QUOTES);
                D('Config')->where(array('name' => $key))->save(array('value' => $val));
            }
            if ($_POST['wap_domain']) {
                $domain = D('Config')->sub_domain();
                $this->update_config(array('APP_SUB_DOMAIN_RULES'=>$domain), CONF_PATH.'sub_domain.php');
            }
        }
        $this->_edit();
        $this->display();
    }
    /**
     * 页面列表
     */
    public function page()
    {
        $_GET['subsite_id'] = $_REQUEST['subsite_id'] = I('request.subsite_id', 0, 'intval');
        $_GET['type'] = $_REQUEST['type'] = 'Mobile';
        $this->order = 'id asc';
        $this->_tpl = 'page';
        parent::index();
    }
    public function page_add()
    {
        if (IS_POST) {
            substr(I('post.alias'), 0, 3)=='QS_'?$this->error('调用名称不允许 QS_ 开头！'):'';
            if ($this->apply['Subsite'] && D('Subsite')->get_subsite_domain()) {
                $subsite_id = I('post.subsite_id', 0, 'intval');
            }
            if ($this->_mod->ck_page_alias(I('post.alias'), null, $subsite_id)) {
                $this->error("调用ID ".$_POST['alias']." 已经存在！请重新填写");
            }
        }
        parent::add();
    }
    public function _after_select($data)
    {
        $data['variate'] = unserialize($data['variate']);
        return $data;
    }
    public function page_edit()
    {
        if (IS_POST) {
            if (I('post.systemclass')<>"1") {//非系统内置
                $_POST['pagetpye']=I('post.pagetype', 1, 'trim');
                $_POST['alias']=I('post.alias', '', 'trim')?I('post.alias', '', 'trim'):$this->error('调用ID不能为空！');
                substr($_POST['alias'], 0, 3)=='QS_'?$this->error('调用名称不允许 QS_ 开头！'):'';
            }
            if (in_array(trim($_POST['alias']), $this->norewrite) && $_POST['url']=='1') {
                $_POST['url']=0;
            }
            if (in_array(trim($_POST['alias']), $this->nocaching)) {
                $_POST['caching']=0;
            }
            if ($this->apply['Subsite'] && D('Subsite')->get_subsite_domain()) {
                $subsite_id = I('post.subsite_id', 0, 'intval');
            }
            if ($this->_mod->ck_page_alias($_POST['alias'], I('post.id'), $subsite_id)) {
                $this->error("调用ID ".$_POST['alias']." 已经存在！请重新填写");
            }
        }
        parent::edit();
    }
    /**
     * 设置页面URL
     */
    public function set_url()
    {
        $id =I('post.id', 0, 'intval')?I('post.id', 0, 'intval'):$this->error("你没有选择页面！");
        if ($this->_mod->set_page_url($id, I('post.url', 0, 'intval'), $this->norewrite)) {
            $this->admin_write_log_unify();
            $this->success("设置成功！");
        } else {
            $this->error("设置失败！");
        }
    }
    /**
     * 设置页面缓存时间
     */
    public function set_caching()
    {
        $id =I('post.id', 0, 'intval')?I('post.id', 0, 'intval'):$this->error("你没有选择页面！");
        if ($this->_mod->set_page_caching($id, I('post.caching', 0, 'intval'), $this->nocaching)) {
            $this->admin_write_log_unify();
            $this->success("设置成功！");
        } else {
            $this->error("设置失败！");
        }
    }
    /**
     * 删除页面
     */
    public function page_delete()
    {
        $id=I('request.id');
        if (empty($id)) {
            $this->error("请选择项目！");
        }
        if ($num=$this->_mod->del_page($id)) {
            $this->admin_write_log_unify();
            $this->success("删除成功！共删除".$num."行");
        } else {
            $this->error("删除失败！");
        }
    }
    /**
     * 模板切换
     */
    public function tpl()
    {
        $dirs = getsubdirs($this->tpl_dir);
        $list=array();
        foreach ($dirs as $k => $val) {
            $list[$k]['thumb_dir']=$this->tpl_dir.$val;
            $list[$k]['dir']=$val;
            $list[$k]['info']=$this->_get_templates_info($this->tpl_dir.$val."/Config/info.txt");
        }
    
        $this->assign('list', $list);
        $templates['thumb_dir']=$this->tpl_dir.C('qscms_m_template_dir');
        $templates['dir']=C('qscms_m_template_dir');
        $templates['info']=$this->_get_templates_info($this->tpl_dir.$templates['dir']."/Config/info.txt");
        $this->assign('templates', $templates);
        $this->display();
    }
    public function _get_templates_info($file)
    {
        $file_info = array('name'=>'', 'version'=> '', 'author'=>'', 'authorurl'=>'');
        if (!$fp = @fopen($file, 'rb')) {
            return false;
        }
        $str = fread($fp, 200);
        @fclose($fp);
        $arr = explode("\n", $str);
        foreach ($arr as $val) {
            $pos = strpos($val, ':');
            if ($pos > 0) {
                $type = trim(substr($val, 0, $pos), "-\n\r\t ");
                $value = trim(substr($val, $pos+1), "/\n\r\t ");
                if ($type == 'name') {
                    $file_info['name'] = $value;
                } elseif ($type == 'version') {
                    $file_info['version'] = $value;
                } elseif ($type == 'author') {
                    $file_info['author'] = $value;
                } elseif ($type == 'authorurl') {
                    $file_info['authorurl'] = $value;
                }
            }
        }
        return $file_info;
    }
     /**
     * 备份模板
     */
    public function backup()
    {
        $tpl = I('request.tpl_name', '', 'trim');
        if (dirname($tpl)<>'.') {
            $this->error("操作失败！");
        }
        $filename = TPL_BACKUP_PATH . $tpl . '_' . date('Ymd') . '.zip';
        $zip = new \Common\qscmslib\phpzip;
        $done = $zip->zip($this->tpl_dir . $tpl . '/', $filename);
        if ($done) {
            $this->admin_write_log_unify($tpl);
            header("Location:".$filename."");
        } else {
            $this->error("操作失败！");
        }
    }
    /**
     * 更换模板
     */
    public function set()
    {
        $tpl_dir = I('request.tpl_dir', '', 'trim');
        $templates_info=$this->_get_templates_info($this->tpl_dir.$tpl_dir."/info.txt");
        D('Config')->where(array('name'=>'m_template_dir'))->setField('value', $tpl_dir);
        $this->admin_write_log_unify();
        $this->update_config(array('DEFAULT_THEME'=>$tpl_dir), MOBILE_CONFIG_PATH. 'config.php');
        $this->success('设置成功！');
    }
}
