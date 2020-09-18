<?php
namespace Jobclickup\Controller;
use Common\Controller\ConfigbaseController;
class AdminController extends ConfigbaseController{
	public function _initialize() {
        parent::_initialize();
    }
	public function index(){
        if(IS_POST){
            $_POST['job_clickup_range'] = $_POST['min'].','.$_POST['max'];
            parent::_edit();
        }
        parent::edit();
    }
    public function _after_select($info){
        $range = explode(",", C('qscms_job_clickup_range'));
        $info['min'] = $range[0];
        $info['max'] = $range[1];
        return $info;
    }
}
?>