<?php
namespace Statistics\Controller;
use Common\Controller\BackendController;
use Think\Model;
class CController extends BackendController{
	protected $model;
	protected $start_month;
	protected $end_month;
	protected $start_year;
	protected $end_year;
	public function _initialize() {
        parent::_initialize();
        $this->model = new Model();
        $this->start_month = isset($_GET['start_month'])?intval($_GET['start_month']):1;
		$this->end_month = isset($_GET['end_month'])?intval(($_GET['end_month']+1)):(date('m')+1);
		$this->start_year = isset($_GET['start_year'])?intval($_GET['start_year']):date('Y');
		$this->end_year = isset($_GET['end_year'])?intval($_GET['end_year']):date('Y');
		$this->assign('end_month',$this->end_month-1);
    }
    protected function setPercent($num,$total){
		return round($num/$total*100,2);
	}
}
?>