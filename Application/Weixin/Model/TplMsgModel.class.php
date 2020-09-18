<?php
namespace Weixin\Model;
use Think\Model;
class TplMsgModel extends Model{
	private $wxconfig;
	public function __construct(){
		$this->wxconfig = D('WeixinConfig')->get_cache();
	}
  	/*
	 * 微信提醒：申请职位 
	 * $uid    					企业会员uid 
	 * $resumeid 				简历id
	 * $keyword1 				职位名称
	 * $keyword2 		        个人姓名
	 * $notes 					申请说明
	*/
	public function set_applyjobs($uid,$resumeid,$keyword1,$keyword2,$notes='')
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_applyjobs']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$resume_url=build_mobile_url(array('c'=>'Resume','a'=>'show','params'=>'id='.$resumeid.'&openid='.$user['keyid']));
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_applyjobs']['template_id'],
					'url' => $resume_url,
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("你收到了一份新简历，请及时查阅"),
						//'first' => array('value' => urlencode("你收到了一份新简历，请及时登录".C('qscms_site_name')."查阅"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($keyword2),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode($keyword1),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("\\n".$notes),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	  /*
	 * 微信提醒：邀请面试
	 * $uid    					个人会员uid 
	 * $jobsid 					职位id
	 * $companyname 			公司名称
	 * $jobs_name 				职位名称
	 * $jobs_address 			职位地址
	 * $jobs_contact 			职位联系人
	 * $jobs_telephone 			职位联系电话
	 * $notes 					邀请说明
	*/
	public function set_invite($uid,$jobsid,$companyname,$jobs_name,$time,$jobs_address,$jobs_contact,$jobs_telephone,$notes)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_invite']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$jobs_url=build_mobile_url(array('c'=>'Jobs','a'=>'show','params'=>'id='.$jobsid.'&openid='.$user['keyid']));
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_invite']['template_id'],
					'url' => $jobs_url,
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode($companyname."邀请您参加公司面试"),
										'color' => "#743A3A",
							),
						'job' => array('value' => urlencode($jobs_name),
										'color' => "#743A3A",
							),
						'company' => array('value' => urlencode($companyname),
										'color' => "#743A3A",
							),
						'time' => array('value' => urlencode($time),
										'color' => "#743A3A",
							),
						'address' => array('value' => urlencode($jobs_address),
										'color' => "#743A3A",
							),
						'contact' => array('value' => urlencode($jobs_contact),
										'color' => "#743A3A",
							),
						'tel' => array('value' => urlencode($jobs_telephone),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("\\n".$notes),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 微信提醒：新增订单
	 * $uid    					会员uid 
	 * $keyword1 				订单编号
	 * $keyword2 				订单类型
	 * $keyword3 				金额
	*/
	public function add_order($uid,$keyword1,$keyword2,$keyword3)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_order']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_order']['template_id'],
					'url' => '',
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("尊敬的用户，您有以下订单需要完成支付："),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($keyword1),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode($keyword2),
										'color' => "#743A3A",
							),
						'keyword3' => array('value' => urlencode($keyword3.'元'),
										'color' => "#743A3A",
							),
						'keyword4' => array('value' => urlencode(date('Y-m-d',time())),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("\\n您可以查看该订单后支付。"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 微信提醒：充值成功
	 * $uid    					会员uid 
	 * $keyword1 				商品名称
	 * $keyword2 				编号
	 * $keyword3 				金额
	*/
	public function set_payment($uid,$keyword1,$keyword2,$keyword3)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_payment']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_payment']['template_id'],
					'url' => '',
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("尊敬的客户，您已充值成功"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($keyword1),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode($keyword2),
										'color' => "#743A3A",
							),
						'keyword3' => array('value' => urlencode($keyword3),
										'color' => "#743A3A",
							),
						'keyword4' => array('value' => urlencode(date('Y-m-d',time())),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("\\n感谢您对".C('qscms_site_name')."的支持。"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 微信提醒：职位审核通知
	 * $uid    					会员uid 
	 * $keyword1 				职位名称
	 * $keyword2 				审核结果
	 * $keyword3 				原因
	*/
	public function set_jobsallow($uid,$keyword1,$keyword2,$keyword3='')
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_jobsallow']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_jobsallow']['template_id'],
					'url' => '',
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("尊敬的用户，您好，您所发布的职位有新的审核信息"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($keyword1),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode($keyword2),
										'color' => "#743A3A",
							),
						'keyword3' => array('value' => urlencode($keyword3),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("\\n请您修改相关信息后重新提交，谢谢。"),
										'color' => "#743A3A",
							)
						)
					);
				$keyword2=='审核通过' && $template['data']['remark']['value'] = '';
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 微信提醒：简历审核通知
	 * $uid    					会员uid 
	 * $keyword1 				简历名称
	 * $keyword3 				审核结果
	 * $keyword4 				原因
	*/
	public function set_resumeallow($uid,$keyword1,$keyword2,$keyword3='')
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_resumeallow']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_resumeallow']['template_id'],
					'url' => '',
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("你好！你的简历审核结果如下，敬请留意！"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($keyword1),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode(date('Y-m-d',time())),
										'color' => "#743A3A",
							),
						'keyword3' => array('value' => urlencode($keyword2),
										'color' => "#743A3A",
							),
						'keyword4' => array('value' => urlencode($keyword3),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("\\n一份完整、详实的简历是您美好职业未来的敲门砖。"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 微信提醒：修改密码
	 * $uid    					会员uid 
	 * $keyword1 				登录账号
	 * $keyword2 				密码
	*/
	public function set_editpwd($uid,$keyword1,$keyword2)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_editpwd']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_editpwd']['template_id'],
					'url' => '',
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("亲爱的用户，您的账户密码已修改!"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($keyword1),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode(date('Y-m-d',time())),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("\\n请妥善保管。"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 微信提醒：营业执照审核通知
	 * $uid    					会员uid 
	 * $keyword1 				审核结果
	 * $keyword2 				原因
	*/
	public function set_licenseallow($uid,$keyword1,$keyword2)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_licenseallow']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_licenseallow']['template_id'],
					'url' => '',
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("您好，已完成您的营业执照审核"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($keyword1),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode($keyword2),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("\\n感谢您对".C('qscms_site_name')."的支持！"),
										'color' => "#743A3A",
							)
						)
					);
				\Common\qscmslib\weixin::build_tpl_msg(urldecode(json_encode($template)));
			}
		}
	}
	/*
	 * 微信提醒：简历头像审核通知
	 * $uid    					会员uid 
	 * $keyword1 				审核结果
	 * $keyword2 				原因
	*/
	public function set_resume_photoallow($uid,$keyword1,$keyword2)
	{
		if(C('qscms_weixin_apiopen')==1 && $this->wxconfig['set_resume_photoallow']['value']=='1')
		{
			$user = D('MembersBind')->where(array('uid'=>$uid,'type'=>'weixin'))->find();
			if($user['keyid'])
			{
				$template = array(
					'touser' => $user['keyid'],
					'template_id' => $this->wxconfig['set_resume_photoallow']['template_id'],
					'url' => '',
					'topcolor' => "#7B68EE",
					'data' => array(
						'first' => array('value' => urlencode("您好，已完成您的简历头像审核"),
										'color' => "#743A3A",
							),
						'keyword1' => array('value' => urlencode($keyword1),
										'color' => "#743A3A",
							),
						'keyword2' => array('value' => urlencode($keyword2),
										'color' => "#743A3A",
							),
						'remark' => array('value' => urlencode("\\n感谢您对".C('qscms_site_name')."的支持！"),
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