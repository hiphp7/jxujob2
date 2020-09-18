<?php
namespace Crm\Controller;
use Common\Controller\BaseController;
class IndexController extends BaseController{
	public function _initialize() {
        parent::_initialize();
        $this->check_auth();
    }
	public function check_auth(){
        $token = I('request.token','','trim');
        $auth_success = false;
        if((session('crm_token') && $token!=session('crm_token')) || !session('crm_token')){
            if(decrypt($token,C('PWDHASH'))==C('PWDHASH')){
                $auth_success = true;
            }
        }else{
            $auth_success = true;
        }
        if($auth_success){
            session('crm_token',$token);
        }else{
            $this->ajaxReturn(0,'验证失败！');
        }
    }
    /**
     * 处理订单
     */
    public function order_set(){
        $id = I('request.id',0,'intval');
        $notes = I('request.notes','','trim,badword');
        !$id && $this->ajaxReturn(0,'error');
        if (D('Order')->admin_order_paid($id))
        {
            D('Order')->where(array('id'=>array('eq',$id)))->setField('notes',$notes);
            $this->ajaxReturn(1,'操作成功！');
        }
        else
        {
            $this->ajaxReturn(0,'操作失败！');
        }
    }
    /**
     * 修改企业名称
     */
    public function companyname_edit(){
        $id = I('request.id',0,'intval');
        $companyname = I('request.companyname','','trim,badword');
        if(!$id || !$companyname){
            $this->ajaxReturn(0,'参数错误！');
        }
        $company_profile = D('CompanyProfile')->find($id);
        !$company_profile && $this->ajaxReturn(0,'企业不存在！');
        // 判断企业名称是否重复
        if (C('qscms_company_repeat')=="0")
        {
            $info = D('CompanyProfile')->where(array('id'=>array('neq',$id),'companyname'=>$companyname))->getField('id');
            if($info) $this->ajaxReturn(0,"{$companyname}已经存在，同公司信息不能重复注册");
        }
        $setsqlarr['companyname'] = $companyname;
        $num = D('CompanyProfile')->where(array('id'=>$id))->save($setsqlarr);
        if($num){
            M('Jobs')->where(array('uid'=>$company_profile['uid']))->save($setsqlarr);
            M('JobsTmp')->where(array('uid'=>$company_profile['uid']))->save($setsqlarr);
            C('apply.Jobfair') && M('JobfairExhibitors')->where(array('uid'=>$company_profile['uid']))->setField('companyname',$companyname);
            M('JobsSearch')->where(array('uid'=>$company_profile['uid']))->save($setsqlarr);
            unset($setsqlarr['companyname']);
            M('JobsSearchKey')->where(array('uid'=>$company_profile['uid']))->save($setsqlarr);
            $comname_key = get_tags($company_profile['companyname'],100,true);
            foreach ($comname_key as $key => $value) {
                M('JobsSearchKey')->execute("UPDATE ".C('DB_PREFIX')."jobs_search_key SET `key`=replace(`key`,'".$value."','') WHERE ( `uid` = ".$company_profile['uid']." )");
                M('Jobs')->execute("UPDATE ".C('DB_PREFIX')."jobs SET `key`=replace(`key`,'".$value."','') WHERE ( `uid` = ".$company_profile['uid']." )");
            }
            $comname_key_new = get_tags($companyname,100,true);
            $key_str = ' '.implode(' ',array_unique($comname_key_new));
            M('JobsSearchKey')->execute("UPDATE ".C('DB_PREFIX')."jobs_search_key SET `key`=CONCAT(`key`,'".$key_str."') WHERE ( `uid` = ".$company_profile['uid']." )");
            M('Jobs')->execute("UPDATE ".C('DB_PREFIX')."jobs SET `key`=CONCAT(`key`,'".$key_str."') WHERE ( `uid` = ".$company_profile['uid']." )");
            $this->ajaxReturn(1,'保存成功！');
        }else{
            $this->ajaxReturn(0,'保存失败！');
        }
    }
    /**
     * 登录会员中心
     */
    public function members_login(){
        $uid = I('request.uid',0,'intval');
        $u = D('Members')->get_user_one(array('uid'=>$uid));
        if (!empty($u))
        {
            $user_visitor = new \Common\qscmslib\user_visitor;
            $user_visitor->logout();
            $user_visitor->assign_info($u);
            redirect(U('home/members/index'));
        }
    }
    /**
     * 审核简历
     */
    public function audit_resume(){
        $id = I('request.id');
        if(!$id){
            $this->ajaxReturn(0,'请选择简历！');
        }
        $id = explode(",", $id);
        $audit = I('request.audit',0,'intval');
        $pms_notice = I('request.pms_notice',0,'intval');
        $reason = I('request.reason','','trim');
        !D('Resume')->admin_edit_resume_audit($id,$audit,$reason,$pms_notice)?$this->ajaxReturn(0,'设置失败！'):$this->ajaxReturn(1,"设置成功！");
    }
    /**
     * 审核企业营业执照
     */
    public function audit_company_auth(){
        $id = I('request.id');
        if(!$id){
            $this->ajaxReturn(0,'请选择企业');
        }
        $uids = D('CompanyProfile')->where(array('id'=>array('in',$id)))->getField('uid',true);
        if(!$uids){
            $this->ajaxReturn(0,'请选择企业！');
        }
        $audit = I('request.audit',0,'intval');
        $pms_notice = I('request.pms_notice',0,'intval');
        $reason = I('request.reason','','trim');
        $result = D('CompanyProfile')->admin_edit_company_audit($uids,$audit,$reason);
        if($result){
            $this->ajaxReturn(1,"设置成功！");
        }else{
            $this->ajaxReturn(0,'设置失败！');
        }
    }
    /**
     * 修改简历联系方式（手机和邮箱）
     */
    public function resume_basic_info(){
        $id = I('request.id',0,'intval');
        if(!$id){
            $this->ajaxReturn(0,'请选择简历！');
        }
        $resume = M('Resume')->find($id);
        if(!$resume) $this->ajaxReturn(0,'简历不存在或已经删除！');
        $trims = array('telephone','email');
        foreach ($trims as $val) {
            $setsqlarr[$val] = I('request.'.$val,'','trim,badword');
        }
        $userinfo = D('Members')->find($resume['uid']);
        $rst=D('Resume')->save_resume($setsqlarr,$resume['id'],$userinfo);
        if($rst['state']) $this->ajaxReturn(1,'数据保存成功！');
        $this->ajaxReturn(0,$rst['error']);
    }
    /**
     * 添加企业订单
     */
    public function order_add_company(){
        $order_type = I('request.order_type',0,'intval');
        if(!array_key_exists($order_type, D('Order')->order_type) && $order_type!=15){
            $this->ajaxReturn(0,'订单类型错误！');
        }
        if($order_type==1 || $order_type==6 || $order_type==7){
            $setmeal_id = I('request.setmeal_id',0,'intval');
            if($setmeal_id==0){
                $this->ajaxReturn(0,'请选择套餐！');
            }
            $points = 0;
        }else if($order_type==2){
            $setmeal_id = 0;
            $points = I('request.points',0,'intval');
            if($points==0){
                $this->ajaxReturn(0,'请填写充值积分数！');
            }
        }else{
            $setmeal_id = 0;
            $points = 0;
        }
        $payment = I('request.payment','','trim');
        $payment_cn = I('request.payment_cn','','trim');
        if($payment=='' || $payment_cn==''){
            $this->ajaxReturn(0,'请选择支付方式！');
        }
        $setmeal_name = $this->_get_setmeal_info($order_type,$setmeal_id);
        $amount = I('request.amount','','trim');
        if($amount==''){
            $this->ajaxReturn(0,'请填写支付金额！');
        }
        $ordtotal_fee = I('request.ordtotal_fee','','trim');
        if($ordtotal_fee==''){
            $this->ajaxReturn(0,'请填写实付金额！');
        }
        $uid = I('request.uid',0,'intval');
        if($uid==0){
            $this->ajaxReturn(0,'请选择会员！');
        }
        $description = '购买服务：'.$setmeal_name.';'.$payment_cn.$ordtotal_fee;
        $userinfo = D('Members')->find($uid);
        $notes = I('request.notes','','trim');
        $oid = strtoupper(substr($payment,0,1))."-".date('ymd',time())."-".date('His',time());
        $insert_id = D('Order')->add_order($userinfo,$oid,$order_type,$amount,$ordtotal_fee,0,$setmeal_name,$payment,$payment_cn,$description,time(),1,$points,$setmeal_id,0,'','',$notes);
        if($insert_id) $this->ajaxReturn(1,'订单添加成功！');
        $this->ajaxReturn(0,'订单添加失败！');
    }
    /**
     * 获取订单信息
     */
    protected function _get_setmeal_info($order_type,$setmeal_id=0){
        switch($order_type){
            case 1:
                return D('Setmeal')->where(array('id'=>$setmeal_id))->getField('setmeal_name');
                break;
            case 2:
                return '充值'.C('qscms_points_byname');
                break;
            case 6:
            case 7:
                return D('SetmealIncrement')->where(array('id'=>$setmeal_id))->getField('name');
                break;
            case 15:
                return "广告位、招聘会等";
                break;
        }
    }
}
?>