<?php
namespace Jobfair\Model;
use Think\Model;
use Common\ORG\qiniu;
class JobfairModel extends Model{
	protected $_validate = array(
		array('predetermined_status,title,introduction,address,contact,phone,holddate_start,holddate_end,addtime','identicalNull','',0,'callback'),
		array('predetermined_status,holddate_start,holddate_end,map_zoom','identicalEnum','',0,'callback'),
		array('map_x,map_y','identicalLength_50','',0,'callback'),
		array('contact,phone','identicalLength_100','',0,'callback'),
		array('title,address','identicalLength_200','',0,'callback'),
	);
	protected $_auto = array ( 
		array('display',1),//是否显示
		array('map_x',0),
		array('map_y',0),
		/*array('predetermined_web',1),//在线预定
		array('predetermined_tel',1),//电话预定
		array('predetermined_start',0),//预定开始时间
		array('predetermined_end',0),//预定结束时间*/
		array('ordid',0),//排序
		array('click',1),//点击量
		array('addtime','time',1,'function'),//添加时间
	);
	/**
	 * 验证招聘会表字段合法性
	 * 验证指定字段长度
	 * @param array $data 被验证字段
	 * 
	 * @return Boolean/string 验证结果(true:合法,false:不合法,字符串：不合法提示语)
	 */
	protected function identicalLength_50($data){
		foreach($data as $key=>$val){
			if(strlen($val)>=50) return 'jobfair_length_error_'.$key;
		}
		return true;
	}
	protected function identicalLength_100($data){
		foreach($data as $key=>$val){
			if(strlen($val)>=100) return 'jobfair_length_error_'.$key;
		}
		return true;
	}
	protected function identicalLength_200($data){
		foreach($data as $key=>$val){
			if(strlen($val)>=200) return 'jobfair_length_error_'.$key;
		}
		return true;
	}
	protected function identicalLength_250($data){
		foreach($data as $key=>$val){
			if(strlen($val)>=250) return 'jobfair_length_error_'.$key;
		}
		return true;
	}
	/**
	 * [get_lately_jobfair_list 企业会员中心获取最近招聘会列表]
	 */
	public function get_lately_jobfair_list($user,$pagesize=10){
		$db_pre = C('DB_PREFIX');
		$this_t = $db_pre.'jobfair';
		$join = 'left join '.$db_pre .'jobfair_exhibitors j on j.jobfair_id='.$this_t.'.id and j.uid='.$user['uid'];
		$where['display'] = 1;
		if($count = $this->where($where)->join($join)->count()){
			$pager =  pager($count,$pagesize);
			$rst['count'] = $count;
			$rst['list'] = $this->where($where)->join($join)->field($this_t.'.*,j.audit')->order('ordid desc,'.$this_t.'.id desc')->limit($pager->firstRow . ',' . $pager->listRows)->select();
			$rst['page'] = $pager->fshow();
		}
		return $rst;
	}
	/**
	 * [get_jobfair_list 获取招聘会列表]
	 * @param  	data 	搜索条件 (显示状态 等)
	 * @param 	pagesize 若开启分页 则表示 一页显示条数 ; 若没有开启分页 则表示 要显示条数
	 * @return 	result   招聘会数据 (list:数据  page:分页)
	 */
	public function get_jobfair_list($data,$pagesize=10)
	{
		$time = time();
		$count = $this->where($data)->count();
		$pager =  pager($count,$pagesize);
		$jobfair_list = $this->where($data)->order('ordid desc,holddate_start desc')->limit($pager->firstRow . ',' . $pager->listRows)->select();
		$result['page']=$pager->fshow();
		$result['next_page']=$pager->ajax_show(0);
		// 处理数据信息
		foreach ($jobfair_list as $key => $val)
		{
			/*if($val['predetermined_web']==1){
                if($val['predetermined_status']=="1" && $val['predetermined_start']>$time)
                {
                    $val['predetermined_ok'] = 1; // 未开始
                }
                else if ($val['predetermined_status']=="1" && $val['holddate_start']>$time && ($val['predetermined_end']=="0" || $val['predetermined_end']>$time) && ($val['predetermined_web']=="1" || $val['predetermined_tel']=="1"))
                {
                    $val['predetermined_ok'] = 2; // 预定中
                }
                else
                {
                    $val['predetermined_ok'] = 0; // 已结束
                } 
            }else{
                $val['predetermined_ok'] = 0;
            }*/
            if($val['holddate_start']>$time){
	        	$val['predetermined_ok'] = 2; // 预定中
	        }else{
	        	$val['predetermined_ok'] = 0; // 已结束
	        }
			$result['list'][$key] = $val;
		}
		return $result;
	}
	/**
	 * [get_jobfair_one 获取招聘会详细信息]
	 * @param  	data 	查询条件 (关键字 审核状态 地区等)
	 * @return 	result   招聘会详情数据
	 */
	public function get_jobfair_one($data)
	{
		$time = time();
		$val = $this->where($data)->find();
		if(!$val) return false;
		// 处理数据信息
		/*if($val['predetermined_web']==1){
            if($val['predetermined_status']=="1" && $val['predetermined_start']>$time)
            {
                $val['predetermined_ok'] = 1; // 未开始
            }
            else if ($val['predetermined_status']=="1" && $val['holddate_start']>$time && ($val['predetermined_end']=="0" || $val['predetermined_end']>$time) && ($val['predetermined_web']=="1" || $val['predetermined_tel']=="1"))
            {
                $val['predetermined_ok'] = 2; // 预定中
            }
            else
            {
                $val['predetermined_ok'] = 0; // 已结束
            } 
        }else{
            $val['predetermined_ok'] = 0;
        }*/
        if($val['holddate_start']>$time){
        	$val['predetermined_ok'] = 2; // 预定中
        }else{
        	$val['predetermined_ok'] = 0; // 已结束
        }
		//参会企业
		$val["exhibitors"] = D('Jobfair/JobfairExhibitors')->get_jobfair_exhibitors_list($val['id']);
		return $val;
	}
	/**
	 * [jobfair_booth 在线预订招聘会]
	 * @param  	user 	会员信息
	 * @param  	jobfair_id 		招聘会id
	 * @param  	position_id 	展位id
	 * @return 	result   预订结果
	 */
	public function jobfair_booth($user,$jobfair_id,$position_id)
	{
		if(empty($jobfair_id))
		{
			return array('state'=>0,'msg'=>'招聘会id丢失！');
		}
		if(empty($position_id))
		{
			return array('state'=>0,'msg'=>'请选择展位！');
		}
		elseif($user['utype'] != 1)
		{
			return array('state'=>0,'msg'=>'只有企业会员可以预定！');
		}
		$time = time();
		// 招聘会信息
		$jobfair = $this->get_jobfair_one(array('id'=>$jobfair_id));
		//允许预订 举办开始日期大于当前日期  预订结束时间大于当前时间  允许在线预订
		$predetermined_ok = false;
		if ($jobfair['holddate_start']>$time) $predetermined_ok = true; // 预定中
		if ($predetermined_ok)
		{
			/*if($time<$jobfair['predetermined_start'])
			{
				return array('state'=>0,'msg'=>"此招聘会还未开始预订！开始预订时间：".date("Y-m-d",$jobfair['predetermined_start']));
			}*/
			$booth_info = M('JobfairExhibitors')->where(array('jobfair_id'=>$jobfair_id,'uid'=>$user['uid']))->find();
			if ($booth_info)
			{
				return array('state'=>0,'msg'=>'你已经预定过此招聘会的展位：'.$booth_info['position'].'，不能重复预定');
			}
			// 判断积分是否满足
			$points = D('MembersPoints')->get_user_points($user['uid']);
			/*if($jobfair['predetermined_point'] > $points)
			{
				return array('state'=>0,'msg'=>"你的".C('qscms_points_byname')."不足，请充值后再预定！");
			}*/
			$position_info = M('JobfairPosition')->where(array('id'=>$position_id))->find();
			if($position_info['company_id']){
				return array('state'=>0,'msg'=>"该展位已被预订，请重新选择展位");
			}
			// 获取该企业信息
			$company_profile = M('CompanyProfile')->where(array('uid'=>$user['uid']))->find();
			$setsqlarr['etype']=1;
			$setsqlarr['audit']=2;
			$setsqlarr['uid']=intval($user['uid']);
			$setsqlarr['companyname']=$company_profile['companyname'];
			$setsqlarr['company_id']=$company_profile['id'];
			$setsqlarr['company_addtime']=$company_profile['addtime'];
			$setsqlarr['eaddtime']=$time;
			$setsqlarr['jobfair_id']=$jobfair_id;
			$setsqlarr['jobfair_title']=$jobfair['title'];
			$setsqlarr['jobfair_addtime']=$jobfair['addtime'];
			$setsqlarr['position_id']=$position_id;
			$setsqlarr['position']=$position_info['position'];
			$note = "，已成功扣除".C('qscms_points_byname')." {$jobfair['predetermined_point']}";
			$setsqlarr['note']="{$user['username']} 预定了招聘会 《{$jobfair['title']}》 的展位。展位编号：".$position_info['position'].$note;	
			$result = D('Jobfair/JobfairExhibitors')->add_jobfair_exhibitors($setsqlarr);
			if($result['state'])
			{
				$position_save['company_id']=$company_profile['id'];
				$position_save['company_uid']=$user['uid'];
				$position_save['company_name']=$company_profile['companyname'];
				$position_save['status']=2;
				M('JobfairPosition')->where(array('id'=>$position_id,'jobfair_id'=>$jobfair_id))->save($position_save);
				if ($jobfair['predetermined_point']>0)
				{
					$p_rst = D('MembersPoints')->report_deal($user['uid'],2,$jobfair['predetermined_point']);
					if($p_rst){
		                $handsel['uid'] = $user['uid'];
		                $handsel['htype'] = 'jobfair';
		                $handsel['htype_cn'] = '预定招聘会';
		                $handsel['operate'] = 2;
		                $handsel['points'] = $jobfair['predetermined_point'];
		                $handsel['addtime'] = time();
		                D('MembersHandsel')->members_handsel_add($handsel);
		            }
					$user_points=D('MembersPoints')->get_user_points($user['uid']);
					$operator="-";
				}
				write_members_log($user,1033,$jobfair_id,$position_id);
				return array('state'=>1,'msg'=>'预订成功，请等待管理员审核','id'=>$result['id']);
			}
			else
			{
				return array('state'=>0,'msg'=>$result['error']);
			}
		}
		else
		{
			return array('state'=>0,'msg'=>'该招聘会已结束预定！');
		}
	}
	/**
	 * 后台删除招聘会
	 */
	public function admin_jobfair_delete($id){
		!is_array($id) && $id=array($id);
		$sqlin=implode(",",$id);
		if (fieldRegex($sqlin,'in'))
		{
			if(C('qscms_qiniu_open')==1){
	            $qiniu = new qiniu;
	        }
			$list = $this->where(array('id'=>array('in',$sqlin)))->field('thumb,intro_img')->select();
			foreach ($list as $key => $value) {
				@unlink(C('qscms_attach_path')."jobfair/".$value['thumb']);
				@unlink(C('qscms_attach_path')."jobfair/".$value['intro_img']);
				if(C('qscms_qiniu_open')==1){
	                $qiniu->delete($value['thumb']);
	                $qiniu->delete($value['intro_img']);
	            }
			}
			$list = M('JobfairPositionImg')->where(array('jobfair_id'=>array('in',$sqlin)))->field('img')->select();
			foreach ($list as $key => $value) {
				@unlink(C('qscms_attach_path')."jobfair/".$value['img']);
			}
			M('JobfairArea')->where(array('jobfair_id'=>array('in',$sqlin)))->delete();
			M('JobfairExhibitors')->where(array('jobfair_id'=>array('in',$sqlin)))->delete();
			M('JobfairPosition')->where(array('jobfair_id'=>array('in',$sqlin)))->delete();
			M('JobfairPositionImg')->where(array('jobfair_id'=>array('in',$sqlin)))->delete();
			$this->where(array('id'=>array('in',$sqlin)))->delete();
			return array('state'=>1,'msg'=>'删除成功！');
		}else{
			return array('state'=>0,'msg'=>'删除失败！');
		}
	}
	/**
	 * 后台删除参会企业
	 */
	public function admin_exhibitors_delete($id){
		!is_array($id) && $id=array($id);
		$sqlin=implode(",",$id);
		$num = 0;
		if (fieldRegex($sqlin,'in'))
		{
			$list = M('JobfairExhibitors')->where(array('id'=>array('in',$sqlin)))->select();
			$position_idarr = array();
			foreach ($list as $key => $value) {
				$position_idarr[] = $value['position_id'];
			}
			if(!empty($position_idarr)){
				$position_save['company_id'] = 0;
				$position_save['company_uid'] = 0;
				$position_save['company_name'] = '';
				$position_save['status'] = 0;
				M('JobfairPosition')->where(array('id'=>array('in',$position_idarr)))->save($position_save);
			}
			$num = M('JobfairExhibitors')->where(array('id'=>array('in',$sqlin)))->delete();
		}
		return $num;
	}
	/**
	 * 后台审核参会企业
	 */
	public function admin_exhibitors_audit($id,$audit){
		!is_array($id) && $id=array($id);
		$sqlin=implode(",",$id);
		$num = 0;
		if (fieldRegex($sqlin,'in'))
		{
			$list = M('JobfairExhibitors')->where(array('id'=>array('in',$sqlin)))->select();
			$position_idarr = array();
			foreach ($list as $key => $value) {
				$position_idarr[] = $value['position_id'];
			}
			if(!empty($position_idarr)){
				M('JobfairPosition')->where(array('id'=>array('in',$position_idarr)))->setField('status',$audit);
			}
			$num = M('JobfairExhibitors')->where(array('id'=>array('in',$sqlin)))->setField('audit',$audit);
		}
		return $num;
	}
	/**
	 * 后台获取可预订的招聘会列表
	 */
	public function admin_get_jobfair_audit()
	{
		$map['display'] = array('eq',1);
		$map['holddate_end'] = array(array('gt',time()),array('eq',0),'or');
		$info = $this->where($map)->order('ordid desc,id desc')->select();
		return $info;
	}
}
?>