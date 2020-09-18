<?php
namespace Allowance\Model;
use Think\Model;
class AllowanceTplMsgModel extends Model{
	private $wxconfig;
	public function __construct(){
		$this->wxconfig = D('Allowance/AllowanceWeixinTplmsg')->get_cache();
	}
	/*
	 * 企业添加打赏职位 - 通知企业 
	 * $uid    					企业会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_jobs_add($uid,$service,$type_alias)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_jobs_add']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				switch($type_alias){
					case 'apply':
						$first_value = "您已成功发布投递红包打赏职位，符合条件的个人只要投递简历红包后红包自动发放";
						break;
					case 'interview':
						$first_value = "您已成功发布面试红包打赏职位，符合条件的个人只要参加面试，必须发放面试红包";
						break;
					case 'entry':
						$first_value = "您已成功发布入职红包打赏职位，符合条件条件的个人投递简历后，成功入职签合同后须发放红包";
						break;
					default:
						$first_value = "";break;
				}
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_jobs_add']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode($first_value."\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("进行中"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("\\n职位打赏设置成功后，中途不可取消！如果打赏不成功，请联系您的招聘顾问返回您的账户"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【投递红包】个人申请职位后待审核 - 通知个人 
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_apply_audit_personal($uid,$service)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_apply_audit_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_apply_audit_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("您已成功领取红包，管理员审核通过后红包将发放到您的零钱中，请注意查收\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("等待管理员审核"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => "",
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【投递红包】个人申请职位后待审核 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_apply_audit_company($uid,$service,$fullname)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_apply_audit_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_apply_audit_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode($fullname."已成功申请你发布的红包职位，管理员审核通过后红包将发放到个人的零钱中\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("等待管理员审核"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => "",
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【面试红包】个人申请面试 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_apply_interview_personal($uid,$service)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_apply_interview_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_apply_interview_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("你已成功提交面试申请，请等待企业同意面试\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("等待企业同意面试"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("请及时查收企业同意面试通知，您在参加面试后即可获得红包！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【面试红包】个人申请面试 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_apply_interview_company($uid,$service,$fullname)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_apply_interview_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_apply_interview_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode($fullname."已成功提交面试申请，请您及时处理\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("等待企业同意面试"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("请及时处理面试申请，如果【7】天内未处理，系统将结束此红包。"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【入职红包】个人申请 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_apply_entry_personal($uid,$service)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_apply_entry_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_apply_entry_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("你已成功申请职位，请入职后提交确认入职申请\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("等待个人确认入职"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => "",
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【入职红包】个人申请 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_apply_entry_company($uid,$service,$fullname)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_apply_entry_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_apply_entry_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode($fullname."已成功申请职位，请等待个人提交确认入职申请\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("等待个人确认入职"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => "",
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【投递红包】个人申请 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_apply_signon_personal($uid,$service,$charge,$amount)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_apply_signon_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_apply_signon_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("你已成功申请职位，红包已发放到您的零钱中，请注意查收\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("红包已发放"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("本次服务收取手续费".$charge."元，实际发放".$amount."元；微信到账时间可能有延迟，如果【48】小时后仍未收到，请及时联系网站客服！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【投递红包】个人申请 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_apply_signon_company($uid,$service,$fullname,$surplus_num)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_apply_signon_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_apply_signon_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode($fullname."已成功申请职位，红包已发放\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("红包已发放"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("您当前职位剩余 ".$surplus_num." 个红包，感谢您使用红包打赏服务！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【面试红包】企业同意面试 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_agree_interview_personal($uid,$service,$companyname)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_agree_interview_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_agree_interview_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode($companyname."已同意你的面试申请，请及时参加面试！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("等待个人确认面试"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("参加面试后请及时提交已面试申请，企业确认后即可获得面试红包！请您如实提交申请，如果虚假申请将永久失去本平台发布红包悬赏的特权！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【面试红包】企业同意面试 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_agree_interview_company($uid,$service,$fullname)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_agree_interview_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_agree_interview_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("您已同意【".$fullname."】提出的面试申请\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("等待个人确认面试"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("如果个人提出已面试申请请务必及时对申请做出判定处理，如24小时内未处理，系统将自动判定为已面试并发放红包！请您如实判定，如果虚假处理将永久失去本平台发布红包悬赏的特权！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【面试红包】企业拒绝面试 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_refuse_interview_personal($uid,$service,$companyname)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_refuse_interview_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_refuse_interview_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode($companyname."已拒绝你的面试申请\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("企业拒绝面试"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("快换个职位试试吧！世界那么大，相信总有欣赏你的人！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【面试红包】企业拒绝面试 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_refuse_interview_company($uid,$service,$fullname)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_refuse_interview_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_refuse_interview_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("您已拒绝【".$fullname."】提出的面试申请\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("企业拒绝面试"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("此红包流程已结束，请联系网站客服退回红包！建议您立即主动搜索，相信总有适合您的人！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【面试红包】个人确认面试 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_ever_interview_personal($uid,$service,$companyname)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_ever_interview_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_ever_interview_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("你已向【".$companyname."】提出已面试申请\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("等待企业确认面试"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("企业确认后即可获得红包，若企业判定有异议请及时联系网站客服。"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【面试红包】个人确认面试 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_ever_interview_company($uid,$service,$fullname)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_ever_interview_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_ever_interview_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("【".$fullname."】提出已面试申请，请及时判定处理！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("等待企业确认面试"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("如24小时内未处理，系统将自动判定为已面试并发放红包！请您如实判定，如果虚假处理将永久失去本平台发布红包悬赏的特权！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【面试红包】企业确认面试 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_confirm_interview_personal($uid,$service,$companyname,$charge,$amount)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_confirm_interview_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_confirm_interview_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("【".$companyname."】已确认您已参加面试，面试红包已成功发放！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("红包已发放"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("本次服务收取手续费".$charge."元，实际发放".$amount."元；微信到账时间可能有延迟，如果【48】小时后仍未收到，请及时联系网站客服！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【面试红包】企业确认面试 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_confirm_interview_company($uid,$service,$fullname,$surplus_num)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_confirm_interview_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_confirm_interview_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("您已确认【".$fullname."】已参加面试\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("红包已发放"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("您当前职位剩余 ".$surplus_num." 个红包，感谢您使用红包打赏服务！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【面试红包】确认个人缺席 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_absent_interview_personal($uid,$service,$companyname)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_absent_interview_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_absent_interview_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("很抱歉，【".$companyname."】判定您缺席本次面试，请等待管理员审核\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("等待管理员审核"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("如果企业判定与事实不符，请立即向网站客服投诉举报，我们将协助维护您的正当权益！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【面试红包】确认个人缺席 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_absent_interview_company($uid,$service,$fullname,$surplus_num)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_absent_interview_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_absent_interview_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("您判定【".$fullname."】缺席面试，请等待管理员审核！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("等待管理员审核"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("您当前职位剩余 ".$surplus_num." 个红包，感谢您使用红包打赏服务！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【入职红包】个人确认入职 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_ever_entry_personal($uid,$service,$companyname)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_ever_entry_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_ever_entry_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("你向【".$companyname."】提交已入职申请，请等待企业确认\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("等待企业确认"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("建议您及时备份入职合同，企业确认入职后即可获得红包，若企业判定有异议请及时联系网站客服！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【入职红包】个人确认入职 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_ever_entry_company($uid,$service,$fullname)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_ever_entry_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_ever_entry_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("【".$fullname."】已提出已入职申请，请及时判定处理！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("等待企业确认"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("建议您如实判定，如果虚假处理将永久失去本平台发布红包悬赏的特权！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【入职红包】企业确认入职 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_confirm_entry_personal($uid,$service,$companyname,$charge,$amount)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_confirm_entry_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_confirm_entry_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("【".$companyname."】确认您已入职，入职红包已成功发放！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("红包已发放"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("本次服务收取手续费".$charge."元，实际发放".$amount."元；微信到账时间可能有延迟，如果【48】小时后仍未收到，请及时联系网站客服！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【入职红包】企业确认入职 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_confirm_entry_company($uid,$service,$fullname,$surplus_num)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_confirm_entry_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_confirm_entry_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("您已确认【".$fullname."】已入职\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("红包已发放"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("您当前职位剩余 ".$surplus_num." 个红包，感谢您使用红包打赏服务！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【入职红包】确认个人缺席 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_absent_entry_personal($uid,$service,$companyname)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_absent_entry_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_absent_entry_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("很抱歉，【".$companyname."】判定您未入职，请等待管理员审核\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("等待管理员审核"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("如果企业判定与事实不符，请立即向网站客服投诉举报，我们将协助维护您的正当权益！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【入职红包】确认个人缺席 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_absent_entry_company($uid,$service,$fullname,$surplus_num)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_absent_entry_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_absent_entry_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("您判定【".$fullname."】未入职，请等待管理员审核！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("等待管理员审核"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("您当前职位剩余 ".$surplus_num." 个红包，感谢您使用红包打赏服务！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【投递红包】审核通过 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_audit_pass_personal($uid,$service,$jobsname,$charge,$amount)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_audit_pass_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_audit_pass_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("经网站审核，您的简历已成功投递【".$jobsname."】，投递红包已发放！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("红包已发放"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("本次服务收取手续费".$charge."元，实际发放".$amount."元；微信到账时间可能有延迟，如果【48】小时后仍未收到，请及时联系网站客服！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【投递红包】审核通过 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_audit_pass_company($uid,$service,$fullname,$surplus_num)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_audit_pass_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_audit_pass_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("经网站审核，【".$fullname."】已成功投递您的职位，投递红包已发放！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("红包已发放"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("您当前职位剩余 ".$surplus_num." 个红包，感谢您使用红包打赏服务！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【投递红包】审核未通过 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_audit_nopass_personal($uid,$service)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_audit_nopass_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_audit_nopass_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("经网站审核，您的职位红包申请未通过审核，红包发放失败！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("退款中"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => "",
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【投递红包】审核未通过 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_audit_nopass_company($uid,$service,$fullname,$surplus_num)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_audit_nopass_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_audit_nopass_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("经网站审核，".$fullname."向您的职位红包申请未通过审核，请联系网站客服退回红包。\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("退款中"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("您当前职位剩余 ".$surplus_num." 个红包，感谢您使用红包打赏服务！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【面试红包】管理员仲裁给企业退款 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_arbitrate_refund_interview_personal($uid,$service)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_arbitrate_refund_interview_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_arbitrate_refund_interview_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("经网站初步审查，确认您未参加面试，红包发放失败！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("已结束"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("如有异议就及时联系网站客服提交更多补充说明。建议您如实提交申请，如果多次虚假申请将永久失去本平台领取红包悬赏的特权！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【面试红包】管理员仲裁给企业退款 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_arbitrate_refund_interview_company($uid,$service,$fullname,$surplus_num)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_arbitrate_refund_interview_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_arbitrate_refund_interview_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("经网站审查，已确认【".$fullname."】未参加面试，请联系网站客服退回红包！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("退款中"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("您当前职位剩余 ".$surplus_num." 个红包，感谢您使用红包打赏服务！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【入职红包】管理员仲裁给企业退款 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_arbitrate_refund_entry_personal($uid,$service)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_arbitrate_refund_entry_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_arbitrate_refund_entry_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("经网站初步审查，确认您未入职，红包发放失败！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("已结束"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("如有异议就及时联系网站客服提交更多补充说明。建议您如实提交申请，如果多次虚假申请将永久失去本平台领取红包悬赏的特权！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【入职红包】管理员仲裁给企业退款 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_arbitrate_refund_entry_company($uid,$service,$fullname,$surplus_num)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_arbitrate_refund_entry_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_arbitrate_refund_entry_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("经网站审查，已确认【".$fullname."】未入职，请联系网站客服退回红包！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("退款中"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("您当前职位剩余 ".$surplus_num." 个红包，感谢您使用红包打赏服务！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【面试红包】管理员仲裁给个人发放 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_arbitrate_putout_interview_personal($uid,$service,$companyname,$charge,$amount)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_arbitrate_putout_interview_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_arbitrate_putout_interview_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("经网站初步审查，您已参加【".$companyname."】面试，红包已发放！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("红包已发放"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("本次服务收取手续费".$charge."元，实际发放".$amount."元；微信到账时间可能有延迟，如果【48】小时后仍未收到，请及时联系网站客服！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【面试红包】管理员仲裁给个人发放 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_arbitrate_putout_interview_company($uid,$service,$fullname,$surplus_num)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_arbitrate_putout_interview_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_arbitrate_putout_interview_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("经网站审查，已确认【".$fullname."】参加面试，红包已发放！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("红包已发放"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("您当前职位剩余 ".$surplus_num." 个红包，感谢您使用红包打赏服务！如有异议就及时联系网站客服提交更多补充说明，建议您如实判定，如果多次虚假处理将永久失去本平台发布红包悬赏的特权！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【入职红包】管理员仲裁给个人发放 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_arbitrate_putout_entry_personal($uid,$service,$companyname,$charge,$amount)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_arbitrate_putout_entry_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_arbitrate_putout_entry_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("经网站初步审查，您已入职【".$companyname."】，红包已发放！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("红包已发放"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("本次服务收取手续费".$charge."元，实际发放".$amount."元；微信到账时间可能有延迟，如果【48】小时后仍未收到，请及时联系网站客服！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 【入职红包】管理员仲裁给个人发放 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_arbitrate_putout_entry_company($uid,$service,$fullname,$surplus_num)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_arbitrate_putout_entry_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_arbitrate_putout_entry_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("经网站审查，已确认【".$fullname."】已入职，红包已发放！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("红包已发放"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("您当前职位剩余 ".$surplus_num." 个红包，感谢您使用红包打赏服务！如有异议就及时联系网站客服提交更多补充说明，建议您如实判定，如果多次虚假处理将永久失去本平台发布红包悬赏的特权！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 管理员退款成功 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_admin_refund_company($uid,$service,$amount)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_arbitrate_putout_entry_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_arbitrate_putout_entry_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("您好，未发放红包已成功退款到指定账户，请注意查收！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("已完成退款"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("退款已成功，共计".$amount."元。到账时间可能有延迟，如果【48】小时后仍未收到，请及时联系网站客服！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 个人7天内未确认自动退款 - 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_7days_noconfirm_auto_refund_personal($uid,$service)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_7days_noconfirm_auto_refund_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_7days_noconfirm_auto_refund_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("抱歉，因企业同意您参加面试后7天内未及时提出已面试申请，红包发放失败！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("已结束"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("如有异议就及时联系网站客服提交更多补充说明，建议您如实判定，如果多次虚假处理将永久失去本平台发布红包悬赏的特权！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 个人7天内未确认自动退款 - 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_7days_noconfirm_auto_refund_company($uid,$service,$fullname,$surplus_num)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_7days_noconfirm_auto_refund_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_7days_noconfirm_auto_refund_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("因【".$fullname."】7天内未及时参加面试，此流程已结束！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("退款中"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("您当前职位剩余 ".$surplus_num." 个红包，本次红包流程已结束，请联系网站客服退回红包！感谢您使用红包打赏服务！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * [面试红包]企业24小时内未确认面试自动发放- 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_24hours_noconfirm_auto_putout_personal($uid,$service,$charge,$amount)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_24hours_noconfirm_auto_putout_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_24hours_noconfirm_auto_putout_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("因企业24小时内未确认面试，系统自动设定发放红包，请注意查收！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("红包已发放"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("本次服务收取手续费".$charge."元，实际发放".$amount."元；微信到账时间可能有延迟，如果【48】小时后仍未收到，请及时联系网站客服！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * [面试红包]企业24小时内未确认面试自动发放- 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_24hours_noconfirm_auto_putout_company($uid,$service,$fullname,$surplus_num)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_24hours_noconfirm_auto_putout_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_24hours_noconfirm_auto_putout_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("因您24小时内未及时确认面试（简历：【".$fullname."】），系统自动设定发放红包！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("红包已发放"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("您当前职位剩余 ".$surplus_num." 个红包，感谢您使用红包打赏服务！如有异议就及时联系网站客服提交更多补充说明，建议您如实判定，如果多次虚假处理将永久失去本平台发布红包悬赏的特权！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * [面试红包]企业72小时内未同意面试自动退款- 通知个人
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_3days_noagree_auto_refund_personal($uid,$service,$companyname)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_3days_noagree_auto_refund_personal']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_3days_noagree_auto_refund_personal']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("由于".$companyname."72小时内未同意面试，发放红包失败\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("已结束"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => "",
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * [面试红包]企业72小时内未同意面试自动退款- 通知企业
	 * $uid    					会员uid 
	 * $service                 服务名称
	*/
	public function set_allowance_3days_noagree_auto_refund_company($uid,$service,$fullname)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_3days_noagree_auto_refund_company']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_3days_noagree_auto_refund_company']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("因您72小时内未同意面试（简历：【".$fullname."】），系统自动设定结束流程，请联系管理员退款。\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($service),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("退款中"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("您当前职位剩余 ".$surplus_num." 个红包，感谢您使用红包打赏服务！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
  	/*
	 * 微信提醒：加入黑名单 
	 * $uid    					企业会员uid 
	*/
	public function set_allowance_add_blacklist($uid)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_allowance_add_blacklist']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_allowance_add_blacklist']['template_id'],
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("抱歉，您已被网站管理员限制使用红包打赏功能！\\n"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode("红包打赏 - 打赏红包"),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode("禁止使用"),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("\\n系统监测到您近期操作异常，如了解详细情况请联系网站客服。"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
}
?>