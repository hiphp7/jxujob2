<?php
namespace Ucenter\Controller;
use Common\Controller\FrontendController;
use Ucenter\qscmslib\Api\UcenterLib;
class IndexController extends FrontendController {
    public function index(){
    	UcenterLib::back();
    }
}