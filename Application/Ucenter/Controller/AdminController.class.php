<?php
namespace Ucenter\Controller;
use Common\Controller\ConfigbaseController;
class AdminController extends ConfigbaseController {
    public function _initialize() {
        parent::_initialize();
        $this->_mod = D('UcConfig');
    }
    public function index(){
        if(IS_POST){
            $str = '';
            foreach (I('post.') as $key => $val) {
                $this->_mod->where(array('name' => $key))->save(array('value' => $val));
                $str.='define("'.$key.'", "'.$val.'");'.PHP_EOL;
            }
            $content  = "<?php".PHP_EOL.$str."?>";
            file_put_contents(APP_PATH.'Ucenter/Conf/uc.php',$content);
            $this->success(L('operation_success'));
            exit;
        }
        $ucdata = $this->_mod->select();
        $uc = array();
        foreach ($ucdata as $key => $value) {
            $uc[$value['name']] = $value['value'];
        }
        $this->assign('uc',$uc);
        $this->display();
    }
    /**
     * 开启/关闭
     */
    public function setOpen(){
        $this->_edit();
    }
}
?>