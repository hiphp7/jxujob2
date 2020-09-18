<?php
namespace Allowance\Model;
use Think\Model;
class AllowanceRefundmentRecordModel extends Model{
	const TYPE_COMPANY_APPLY = 1;//企业申请
	const TYPE_NOFINISH = 2;//流程未结束，系统判定
	const TYPE_MANAGER_SET = 3;//结果有争议或者管理员直接判定
    public $typelist = array(self::TYPE_COMPANY_APPLY=>'企业申请',self::TYPE_NOFINISH=>'流程未结束，系统判定',self::TYPE_MANAGER_SET=>'管理员判定');
	protected function _before_insert(&$data,$options){
		$data['refundtime'] = 0;
	}
    public function record_add($data){
    	$timestamp = time();
    	$refund = D('Allowance/AllowanceRefundment')->where(array('uid'=>$data['uid']))->find();
    	if(!$refund){
    		$pid = D('Allowance/AllowanceRefundment')->add(array('uid'=>$data['uid'],'lasttime'=>$timestamp,'amount'=>$data['amount']));
    		$data['pid'] = $pid;
    	}else{
    		D('Allowance/AllowanceRefundment')->where(array('uid'=>$data['uid']))->save(array('lasttime'=>$timestamp,'status'=>1,'amount'=>$refund['amount']+$data['amount']));
    		$data['pid'] = $refund['id'];
    	}
    	$data['addtime'] = $timestamp;
    	return $this->add($data);
    }
    /**
     * 管理员设置退款申请状态为已退款
     */
    public function record_set($id,$uid){
    	$timestamp = time();
    	$id_arr = is_array($id)?$id:array($id);
        $total_amount = $this->where(array('uid'=>$uid,'id'=>array('in',$id_arr),'refundtime'=>0))->sum('amount');
    	$this->where(array('uid'=>$uid,'id'=>array('in',$id_arr),'refundtime'=>0))->save(array('refundtime'=>$timestamp));
    	//检测是否还有需要退款的记录，如果没有了，把分组记录的状态改为0
    	$count = $this->where(array('uid'=>$uid,'refundtime'=>0))->count();
    	if($count==0){
    		D('Allowance/AllowanceRefundment')->where(array('uid'=>$uid))->save(array('status'=>0,'amount'=>0));
    	}else{
            D('Allowance/AllowanceRefundment')->where(array('uid'=>$uid))->setDec('amount',$total_amount);
        }
        //短信提醒
        if(C('qscms_sms_open')==1){
            if(false === $sms = F('sms_config')) $sms = D('Common/SmsConfig')->config_cache();
            //通知企业
            $company_user_info=M('Members')->where(array('uid'=>$uid))->find();
            if ($sms['set_allowance_admin_refund_company']=="1"  && $company_user_info['mobile_audit']=="1")
            {
                $sendSms['mobile']=$company_user_info['mobile'];
                $sendSms['tpl']='set_allowance_admin_refund_company';
                $sendSms['data']=array('sitename'=>C('qscms_site_name'),'amount'=>$total_amount);
                D('Common/Sms')->sendSms('notice',$sendSms);
            }
        }
        //微信通知
        if(C('apply.Weixin')){
            $baseinfo = D('Allowance/AllowanceInfo')->find($id);
            //通知个人
            D('Allowance/AllowanceTplMsg')->set_allowance_admin_refund_company($uid,$baseinfo['jobsname'].'【'.D('Allowance/AllowanceRecord')->get_type_cn($baseinfo['type_alias']).'】',$total_amount);
        }
    	return true;
    }
}
?>