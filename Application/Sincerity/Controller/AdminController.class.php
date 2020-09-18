<?php
namespace Sincerity\Controller;
use Common\Controller\ConfigbaseController;
use Sincerity\Model\CModel;
class AdminController extends ConfigbaseController{
	public function _initialize() {
        parent::_initialize();
    }
	public function set_margin(){
        if(IS_POST){
            if($_FILES['famous_company_img']['name']){
                $date = date('y/m/d/');
                $result = $this->_upload($_FILES['famous_company_img'], 'images/'.$date, array(
                        'maxSize' => 100,//图片最大100K
                        'uploadReplace' => true,
                        'attach_exts' => 'bmp,png,gif,jpeg,jpg'
                ));
                if ($result['error']) {
                    $_POST['famous_company_img'] = $date.$result['info'][0]['savename'];
                }
            }
        }
        parent::_edit();
        $this->display('index');
    }
    /**
     * 认证企业诚聘通
     */
    public function com_audit_famous(){
        $uid = I('request.y_id');
        if(!$uid){
            $this->error('请选择企业');
        }
        $famous = I('post.famous',0,'intval');
        $reason = I('post.reason','','trim');
        $model = new CModel;
        $result = $model->admin_edit_company_famous($uid,$famous,$reason,C('visitor.username'));
        if($result){
            $this->success("设置成功！");
        }else{
            $this->error('设置失败！');
        }
    }
}
?>