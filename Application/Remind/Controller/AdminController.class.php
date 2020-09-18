<?php
namespace Remind\Controller;
use Common\Controller\ConfigbaseController;
class AdminController extends ConfigbaseController{
	public function _initialize() {
        parent::_initialize();
    }
    public function per_login_remind(){
        parent::_edit();
        $this->display();
    }
    public function com_login_remind(){
        parent::_edit();
        $this->display();
    }
}
?>