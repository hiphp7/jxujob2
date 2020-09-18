<?php
namespace Sincerity\Model;
class CModel{
  	/**
     * 审核企业诚聘通
     */
    public function admin_edit_company_famous($uid,$famous,$reason,$audit_man=''){
      if (!is_array($uid)) $uid=array($uid);
      $sqlin=implode(",",$uid);
      if (fieldRegex($sqlin,'in'))
      {
        $comlist = M('CompanyProfile')->where(array('uid'=>array('in',$sqlin)))->select();
        if(false===M('CompanyProfile')->where(array('uid'=>array('in',$sqlin)))->setField('famous',$famous)) return false;
        if(false===M('Jobs')->where(array('uid'=>array('in',$sqlin)))->setField('famous',$famous)) return false;
        if(false===M('JobsTmp')->where(array('uid'=>array('in',$sqlin)))->setField('famous',$famous)) return false;
        if(false===M('JobsSearch')->where(array('uid'=>array('in',$sqlin)))->setField('famous',$famous)) return false;
        if(false===M('JobsSearchKey')->where(array('uid'=>array('in',$sqlin)))->setField('famous',$famous)) return false;
        admin_write_log("将企业uid为".$sqlin."的企业的诚聘通认证状态修改为".$famous, C('visitor.username'),3);
        
        $reasona=$reason==''?'无':$reason;
        foreach($comlist as $list){
          $auditsqlarr['company_id']=$list['id'];
          $auditsqlarr['reason']=$reasona;
          $auditsqlarr['status']=$famous==1?'是':'否';
          $auditsqlarr['addtime']=time();
          $auditsqlarr['audit_man']=$audit_man;
          $auditsqlarr['famous']=1;
          M('AuditReason')->data($auditsqlarr)->add();
        }
        
        return true;
      }
      return false;
    }
}
?>