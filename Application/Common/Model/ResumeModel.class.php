<?php
/*
 *简历模型类
 */
namespace Common\Model;
use Think\Model\RelationModel;
class ResumeModel extends RelationModel{
	protected $_user = array();
	protected $_validate = array(
		array('title','1,24','{%resume_title_length_error}',0,'length'), // 简历标题
		array('fullname','1,15','{%resume_fullname_length_error}',0,'length'), // 姓名
		array('sex',array(1,2),'{%resume_sex_format_error}',0,'in'), // 性别
		array('marriage',array(1,2,3),'{%resume_marriage_between_error}',0,'in'), // 婚姻状况
		array('uid,fullname,nature,birthdate,experience,wage,education,current','identicalNull','',0,'callback'),
		array('uid,nature,birthdate,experience,wage,education,major,current,height','identicalEnum','',2,'callback'),
		array('telephone','mobile','{%resume_telephone_format_error}',2), // 手机号
		array('email','email','{%resume_email_format_error}',2), // 邮箱
		array('email','2,60','{%resume_email_length_error}',2,'length'), // 邮箱
		array('telephone','_repetition_mobile','{%resume_repetition_mobile}',2,'callback'),
		array('email','_repetition_email','{%resume_repetition_email}',2,'callback'),
		array('residence','0,30','{%resume_residence_length_error}',0,'length'), // 现居住地
		array('householdaddress','2,60','{%resume_householdaddress_length_error}',2,'length'), // 户口所在地
		array('specialty','0,1000','{%resume_specialty_length_error}',0,'length'), // 自我描述
		array('height','0,3','{%resume_height_length_error}',0,'length'), // 身高
		array('qq','number','{%resume_error_qq}',2),
		array('qq','0,11','{%resume_error_qq}',2,'length'),
		array('weixin','4,30','{%resume_length_error_weixin}',2,'length'),
	);
	protected $_auto = array (
		array('title','_title',1,'callback'),
		array('display',1),//是否显示
		array('display_name',1) , // 显示简历名称
		array('audit',2), // 简历审核
		array('email_notify',1), // 邮件接收通知
		array('photo',0), // 是否为照片简历
		array('photo_audit',2), // 照片审核
		array('addtime','time',1,'function'), //添加时间 
		array('refreshtime','time',1,'function'), //简历刷新时间
		array('stime','time',1,'function'),
		array('photo_display',1), // 是否显示照片
		array('entrust',0), // 简历委托
		array('talent',1), // 高级人才
		array('complete_percent',0), // 简历完整度
		array('click',1), // 查看次数
		array('tpl','default'),//简历模板
		array('resume_from_pc',0), // 简历来自PC(1->是)
		array('marriage',1,1)
	);
	protected function _title(){
		return '我的简历'.date('Ymd',time());
	}
	protected function _repetition_email($data){
		$uid = M('Members')->where(array('email'=>$data))->getfield('uid');
		if($uid && $uid != $this->_user['uid']) return false;
		return true;
	}
	protected function _repetition_mobile($data){
		$uid = M('Members')->where(array('mobile'=>$data))->getfield('uid');
		if($uid && $uid != $this->_user['uid']) return false;
		return true;
	}
	/*
		获取简历列表 get_resume_list
		@data array 简历条件 
		@countinterview 面试邀请数
		@countdown 下载数
		@countapply 申请职位数
		@views  关注我的数量
	*/
	public function get_resume_list($data){
		$init = array('where'=>array(),'field'=>'*','order'=>'','countinterview'=>false,'countdown'=>false,'countapply'=>false,'views'=>false,'stick'=>false,'strong_tag'=>false);
		$init = array_merge($init,$data);
        $list = $this->field($init['field'])->where($init['where'])->order($init['order'])->limit($init['limit'])->select();
        foreach ($list as $key => $value)
		{
			$value['number']="N".str_pad($value['id'],7,"0",STR_PAD_LEFT);
			if ($init['countinterview'])
			{
				$value['countinterview'] = M('CompanyInterview')->where(array('resume_uid'=>$value['uid'],'resume_id'=>$value['id']))->count();
			}
			if ($init['countdown'])
			{
				$value['countdown'] = M('CompanyDownResume')->where(array('resume_uid'=>$value['uid'],'resume_id'=>$value['id']))->count();
			}
			if ($init['countapply'])
			{
				$value['countapply'] = M('PersonalJobsApply')->where(array('personal_uid'=>$value['uid'],'resume_id'=>$value['id']))->count();
			}
			if ($init['views'])
			{
				$value['views'] = M('ViewResume')->where(array('resumeid'=>$value['id']))->count('id');
			}
			if ($init['stick'])
			{
				$value['stick_info'] = D('PersonalServiceStickLog')->check_stick_log(array('resume_id'=>$value['id']));
			}
			if ($init['strong_tag'])
			{
				$tag_log = D('PersonalServiceTagLog')->check_tag_log(array('resume_id'=>$value['id']));
				$tag_log['tag_name'] = D('PersonalServiceTagCategory')->where(array('id'=>$tag_log['tag_id']))->getField('name');
				$value['tag_info'] = $tag_log;
			}
			if ($value['audit'] == 2)
			{
                $value['_audit'] = C('qscms_resume_display') == 2 ? 1 : $value['audit'];
            } else {
                $value['_audit'] = $value['audit'];
            }
            if ($init['_audit'] == 1)
            {
                $value['_audit'] == 1 && $resume_list[] = $value;
            } else {
                $resume_list[] = $value;
            }
		}
		return $resume_list;
	}
	public function resume_one($uid){
		$where['uid']=$uid;
		$resume_one = $this->where($where)->find(); 
        return $resume_one;
	}
	public function get_resume_one($pid){
		$where['id']=$pid;
		$resume_one = $this->where($where)->find(); 
        return $resume_one;
	}
	// 统计简历数
	public function count_resume($data){
		$resume_num = $this->where($data)->count(); 
        return $resume_num;
	}
	public function get_resume_basic($uid,$id){
		$id=intval($id);
		$uid=intval($uid);
		$where['uid']=$uid;
		$where['id']=$id;
		$info = $this->where($where)->find();
		if(!$info) return false;
		$info['age']=date("Y")-$info['birthdate'];
		$info['number']="N".str_pad($info['id'],7,"0",STR_PAD_LEFT);
		$info['lastname']=$info['fullname'];
		return $info;
	}
	public function count_personal_attention_me($uid){
		$id_arr = array();
		$id_str = "";
		$total = 0;
		$where['uid']=$uid;
		$personal_resume = $this->where($where)->select(); 
		if($personal_resume){
		foreach ($personal_resume as $key => $value) {
			$id_arr[] = $value["id"];
		}
		$view_resume = M('ViewResume'); // 实例化User对象
		$count_attention_me = $view_resume->where(array('resumeid'=>array('in',$id_arr)))->count();
		}
        return $count_attention_me;
	}
	/**
	 * [refresh_resume 刷新简历更新‘简历刷新时间’，记录操作日志]
	 */
	public function refresh_resume($pid,$user){
		$uid=$user['uid'];
		$time=time();
		if (!is_array($pid)) $pid=array($pid);
		$pid_num = count($pid);
		$sqlin=implode(",",$pid);
		$data = array('refreshtime'=>$time,'stime'=>$time);
		$where['id']=array('in',$sqlin);
		$where['uid']=$uid;
		if (!$this->where($where)->save($data)) return false;
		if (!M('ResumeSearchPrecise')->where($where)->save($data)) return false;
		if (!M('ResumeSearchFull')->where($where)->save($data)) return false;
		$where['stick'] = 1;
		if($rids = $this->where($where)->getfield('id',true)){
			$data = array('stime'=>$time+100000000);
			$where = array('id'=>array('in',$rids));
			if (!$this->where($where)->save($data)) return false;
			if (!M('ResumeSearchPrecise')->where($where)->save($data)) return false;
			if (!M('ResumeSearchFull')->where($where)->save($data)) return false;
		}
		$r = D('TaskLog')->do_task($user,6);
		//写入会员日志
		write_members_log($user,2001,$sqlin);
		// 刷新日志
		$refresh_log['_t'] = 'RefreshLog';
		$refresh_log['uid'] = $uid;
		$refresh_log['type'] = '2001';
		$refresh_log['mode'] = 0;
		$refresh_log['addtime']=time();
		setLog($refresh_log);
		return $r;
	}

	//删除简历
	public function del_resume($user,$pid){
		if (!is_array($pid)) $pid=array($pid);
		$sqlin = implode(',',$pid);
		$where['id']=array('in',$pid);
		$where['uid']=$user['uid'];
		$_where['pid']=array('in',$pid);
		$_where['uid']=$user['uid'];
		if(false === $pid_num = $this->where($where)->delete()) return false;
		if(false === M('ResumeEducation')->where($_where)->delete()) return false;
		if(false === M('ResumeTraining')->where($_where)->delete()) return false;
		if(false === M('ResumeWork')->where($_where)->delete()) return false;
		if(false === M('ResumeCredent')->where($_where)->delete()) return false;
		if(false === M('ResumeLanguage')->where($_where)->delete()) return false;
		if(false === M('ResumeSearchPrecise')->where($where)->delete()) return false;
		if(false === M('ResumeSearchFull')->where($where)->delete()) return false;
		if(false === M('ViewResume')->where(array('resumeid'=>array('in',$sqlin)))->delete()) return false;
		if(false === M('ResumeEntrust')->where(array('resume_id'=>array('in',$sqlin)))->delete()) return false;

        //写入会员日志
        write_members_log($user,2008,$sqlin);
		return true;
	}
	/*
		创建简历
	*/
	public function add_resume($data,$user){
		$this->_user = $user;
		if(false === $d = $this->create($data)){
			return array('state'=>0,'error'=>$this->getError());
		}else{
			$data['title']=='' && $data['title'] = '我的简历'.date('Ymd');
			if($user['mobile_audit'] == 1) $this->telephone = $d['mobile'] = $user['mobile'];
            if($user['email_audit'] == 1) $this->email = $d['email'] = $user['email'];
			$category = D('Category')->get_category_cache();
			$major_category = D('CategoryMajor')->get_major_list();
            $sex = array('1'=>'男','2'=>'女');
            $marriage = array('1'=>'未婚','2'=>'已婚','3'=>'保密');
            //意向行业
            if($d['trade']){
            	foreach(explode(',',$d['trade']) as $val) {
	                $trade_cn[] = $category['QS_trade'][$val];
	            }
            }else{
            	$trade_cn = array();
            }
            
            //意向地区
            $city = D('CategoryDistrict')->get_district_cache('all');
            foreach(explode(',',$data['district']) as $val) {
                $val = explode('.',$val);
                $district[] = $val[2] ? $city[$val[1]][$val[2]] : ($val[1] ? $city[$val[0]][$val[1]] : $city[0][$val[0]]);
            }
            //意向职位
            $jobs = D('CategoryJobs')->get_jobs_cache('all');
            foreach(explode(',',$data['intention_jobs_id']) as $val) {
                $val = explode('.',$val);
                $intention[] = $val[2] ? $jobs[$val[1]][$val[2]] : ($val[1] ? $jobs[$val[0]][$val[1]] : $jobs[0][$val[0]]);
            }
            $this->uid             = $d['uid']                = $user['uid'];
            $this->sex_cn          = $d['sex_cn']             = $sex[$d['sex']];
            $this->marriage_cn     = $d['marriage_cn']        = $marriage[$d['marriage']];
            $this->education_cn    = $d['education_cn']       = $category['QS_education'][$d['education']];
            $this->experience_cn   = $d['experience_cn']      = $category['QS_experience'][$d['experience']];
            $this->wage_cn         = $d['wage_cn']            = $category['QS_wage_k'][$d['wage']];
            $this->current_cn      = $d['current_cn']         = $category['QS_current'][$d['current']];
            $this->nature_cn       = $d['nature_cn']          = $category['QS_jobs_nature'][$d['nature']];
            $this->trade_cn        = $d['trade_cn']           = implode(',',$trade_cn);
            $this->district_cn     = $d['district_cn']        = implode(',',$district);
            $this->intention_jobs  = $d['intention_jobs']     = implode(',',$intention);
            $this->audit           = 2;
            $this->photo_img	   = $user['is_avatars'] ? $user['avatar'] : '';
            $d['major'] && $this->major_cn        = $d['major_cn']           = $major_category[$d['major']]['categoryname'];
            $this->resume_from_pc  = 1;
            C('SUBSITE_VAL.s_id') && $this->subsite_id = $d['subsite_id'] = C('SUBSITE_VAL.s_id');
			if(false === $insert_id = $this->add()) return array('state'=>0,'error'=>'数据添加失败！');
		}
		$data = array_merge($data,$d);
		$searchtab['id'] = $insert_id;
		$searchtab['uid'] = $user['uid'];

		// 检查完整度,整理索引表
		$this->check_resume($user['uid'],$insert_id);
		// 委托投递
		if(intval($data['entrust'])){
			D('ResumeEntrust')->set_resume_entrust($insert_id,$user['uid']);
		}
		//创建简历的时候如果 会员信息不存在的话 插入会员信息
		if(!$info = D('MembersInfo')->get_userprofile(array('uid'=>$user['uid']))){
			$data['realname'] = $data['fullname'];
			$data['phone'] = $data['mobile'] ?:$data['telephone'];
			$reg = D('MembersInfo')->add_userprofile($data,$user);
			if(!$reg['state']) return array('state'=>0,'error'=>$reg['error']);
		}
		//写入会员日志
        write_members_log($user,2010,$insert_id);
		if(true !== $reg = D('Members')->update_user_info($data,$user)) array('state'=>0,'error'=>$reg);
		if(C('apply.Statistics')){
			$idata['pid'] = $insert_id;
			$idata['sex'] = $data['sex'];
			$idata['birthdate'] = $data['birthdate'];
			$idata['education'] = $data['education'];
			$idata['experience'] = $data['experience'];
			$idata['major'] = $data['major'];
			$idata['addtime'] = time();
			$class = new \Statistics\Model\CModel($idata);
			$class->resume_add();
		}
		if(C('apply.Allowance')){
			$setsqlarr['uid'] = $user['uid'];
			$setsqlarr['resume_id'] = $insert_id;
			$setsqlarr['intention_jobs'] = $data['intention_jobs_id'];
			$setsqlarr['intention_jobs_cn'] = $data['intention_jobs'];
			$setsqlarr['is_new_record'] = 1;
			D('Allowance/AllowanceEditIntentionLog')->add($setsqlarr);
		}
		return array('state'=>1,'id'=>$insert_id);
	}
	/*
	**	修改简历
	*/
	public function save_resume($data,$pid,$user){
		$this->_user = $user;
		$data['id']=$pid;
		$data['uid']=$user['uid'];
		C('qscms_audit_edit_resume')!='-1'?$data['audit']=intval(C('qscms_audit_edit_resume')):'';
		if(false === $d = $this->create($data)){
			return array('state'=>0,'error'=>$this->getError());
		}else{
			if($user['mobile_audit'] == 1) $this->telephone = $d['mobile'] = $user['mobile'];
        	if($user['email_audit'] == 1) $this->email = $d['email'] = $user['email'];
        	$category = D('Category')->get_category_cache();
	        $major_category = D('CategoryMajor')->get_major_list();
	        $sex = array('1'=>'男','2'=>'女');
	        $marriage = array('1'=>'未婚','2'=>'已婚','3'=>'保密');
	        $d['sex']             && $this->sex_cn           = $d['sex_cn']            = $sex[$d['sex']];
	        $d['major']           && $this->major_cn         = $d['major_cn']          = $major_category[$d['major']]['categoryname'];
	        $d['marriage']        && $this->marriage_cn      = $d['marriage_cn']       = $marriage[$d['marriage']];
	        $d['education']       && $this->education_cn     = $d['education_cn']      = $category['QS_education'][$d['education']];
	        $d['experience']      && $this->experience_cn    = $d['experience_cn']     = $category['QS_experience'][$d['experience']];
	        $d['wage']            && $this->wage_cn          = $d['wage_cn']           = $category['QS_wage_k'][$d['wage']];
	        $d['current']         && $this->current_cn       = $d['current_cn']        = $category['QS_current'][$d['current']];
	        $d['nature']          && $this->nature_cn        = $d['nature_cn']         = $category['QS_jobs_nature'][$d['nature']];
	        C('SUBSITE_VAL.s_id') && $this->subsite_id		 = $d['subsite_id']		   = C('SUBSITE_VAL.s_id');
	        //意向行业
	        if(isset($data['trade'])){
	        	if($data['trade']){
			        foreach(explode(',',$data['trade']) as $val) {
			            $trade_cn[] = $category['QS_trade'][$val];
			        }
			        $this->trade_cn = $d['trade_cn'] = implode(',',$trade_cn);
			    }else{
			    	$this->trade_cn = $d['trade_cn'] = '';
			    }
	        }
	        
	        //意向地区
	        if($data['district']){
		        $city = D('CategoryDistrict')->get_district_cache('all');
		        foreach(explode(',',$data['district']) as $val) {
		            $val = explode('.',$val);
		            if(isset($val[2]) && $val[2]>0){
		            	$district[] = $city[$val[1]][$val[2]];
		            }
		            else if(isset($val[1]) && $val[1]>0)
		            {
		            	$district[] = $city[$val[0]][$val[1]];
		            }
		            else
		            {
		            	$district[] = $city[0][$val[0]];
		            }
		        }
		        $this->district_cn = $d['district_cn'] = implode(',',$district);
		    }
		    $attach = '';
	        //意向职位
	        if($data['intention_jobs_id']){
		        $jobs = D('CategoryJobs')->get_jobs_cache('all');
		        foreach(explode(',',$data['intention_jobs_id']) as $val) {
		            $val = explode('.',$val);
		            if(isset($val[2]) && $val[2]>0){
		            	$intention[] = $jobs[$val[1]][$val[2]];
		            }
		            else if(isset($val[1]) && $val[1]>0)
		            {
		            	$intention[] = $jobs[$val[0]][$val[1]];
		            }
		            else
		            {
		            	$intention[] = $jobs[0][$val[0]];
		            }
		            // $intention[] = $val[2] ? $jobs[$val[1]][$val[2]] : $jobs[$val[0]][$val[1]];
		        }
		        $this->intention_jobs = $d['intention_jobs'] = implode(',',$intention);
		        if(C('apply.Allowance')){
		        	$check_result = D('Allowance/AllowanceEditIntentionLog')->check_intention_jobs($data['intention_jobs_id'],$d['intention_jobs'],$pid);
		        	if(!$check_result){
		        		if(false===$allowance_config=F('allowance_config')){
							$allowance_config = D('Allowance/AllowanceConfig')->config_cache();
						}
		        		$attach = '意向职位'.$allowance_config['resume_intentionjobs_edit_timespace'].'小时内只能修改一次';
		        		unset($this->intention_jobs);
		        		unset($this->intention_jobs_id);
		        	}
		        }
		    }
			if(false === $this->save()){
				return array('state'=>0,'error'=>'更新失败！');
			}
		}
		$data = array_merge($data,$d);
		$this->check_resume($user['uid'],intval($pid));
		$this->refresh_resume($pid,$user);
		//写入会员日志
		write_members_log($user,2012,$pid);
		if(true !== $reg = D('Members')->update_user_info($data,$user)) return array('state'=>0,'error'=>$reg);
		return array('state'=>1,'id'=>$pid,'attach'=>$attach);
	}
	/**
	 * [copy_resume_correlation 复制简历内容]
	 */
	public function copy_resume_correlation($where,$id){
		$array = array('ResumeEducation','ResumeWork','ResumeTraining','ResumeLanguage','ResumeCredent');
		foreach($array as $val){
			if($data = M($val)->where($where)->select()){
				foreach($data as $key=>$v){
					unset($data[$key]['id']);
					$data[$key]['pid'] = $id;
				}
				if(!M($val)->addAll($data)) return false;
			}
		}
		return true;
	}
	/**
	 * [copy_resume 复制简历内容并创建新简历]
	 */
	public function copy_resume($pid,$user){
		if(C('qscms_resume_max') <= $resume_count = $this->where(array('uid'=>$user['uid']))->count('id')){
            return array('state'=>0,'error'=>'您最多可以创建'.C('qscms_resume_max').'份简历,已经超出了最大限制！');
        }
		$resume = $this->where(array('id'=>$pid,'uid'=>$user['uid']))->find();
		if(!$resume) return array('state'=>0,'error'=>'简历不存在或已经删除！');
		unset($resume['id']);
		$resume['title'] = '我的简历'.date('Ymd');
		$resume['def'] = 0;
		if($resume['word_resume']){
			$suffix = pathinfo($resume['word_resume'], PATHINFO_EXTENSION);
			$newPath = date('ym/d/').md5($resume['word_resume'].mt_rand(1, 999999)).'.'.$suffix;
			if(copy(C('qscms_attach_path')."word_resume/".$resume['word_resume'],C('qscms_attach_path')."word_resume/".$newPath)){
				$resume['word_resume'] = $newPath;
			}
		}
		$resume['stick'] = 0;
		$resume['strong_tag'] = 0;
		$resume['entrust'] = 0;
		$resume['audit'] = 2;
		$resume['display'] = 1;
		$reg = array('state'=>0,'error'=>'简历创建失败，请重新操作！');
		if(!$id = $this->add($resume)) return $reg;
		if($data = M('ResumeSearchFull')->where(array('id'=>$pid))->find()){
			$data['id'] = $id;
			if(!M('ResumeSearchFull')->add($data)) return $reg;
		}

		if($data = M('ResumeSearchPrecise')->where(array('id'=>$pid))->find()){
			$data['id'] = $id;
			if($data && !M('ResumeSearchPrecise')->add($data)) return $reg;
		}
		if(false === $this->copy_resume_correlation(array('pid'=>$pid),$id)) return $reg;

		if($data=M('ResumeImg')->where(array('resume_id'=>$pid))->select()){
			foreach($data as $key=>$val){
				unset($data[$key]['id']);
				$data[$key]['resume_id'] = $id;
				if (false === strpos($val['img'], 'http://')) {
					$newPath = date('ym/d/').md5($val['img'].mt_rand(1, 999999)).'.jpg';
					if(copy(C('qscms_attach_path')."resume_img/".$val['img'],C('qscms_attach_path')."resume_img/".$newPath)){
						$data[$key]['img'] = $newPath;
					}
				}else{
					$data[$key]['img'] = '';
				}
			}
			if(!M('ResumeImg')->addAll($data)) return $reg;
		}
		
		//写入会员日志
		write_members_log($user,2011,$pid,$id);
		if(C('apply.Statistics')){
			$idata['pid'] = $pid;
			$idata['sex'] = $resume['sex'];
			$idata['birthdate'] = $resume['birthdate'];
			$idata['education'] = $resume['education'];
			$idata['experience'] = $resume['experience'];
			$idata['major'] = $resume['major'];
			$idata['addtime'] = time();
			$class = new \Statistics\Model\CModel($idata);
			$class->resume_add();
		}
		if(C('apply.Allowance')){
			$setsqlarr['uid'] = $user['uid'];
			$setsqlarr['resume_id'] = $pid;
			$setsqlarr['intention_jobs'] = $resume['intention_jobs_id'];
			$setsqlarr['intention_jobs_cn'] = $resume['intention_jobs'];
			$setsqlarr['is_new_record'] = 1;
			D('Allowance/AllowanceEditIntentionLog')->add($setsqlarr);
		}
		return array('state'=>1,'id'=>$id);
	}
	/*
		检查简历的完整度，并且完善简历索引信息
		@ $uid 会员uid
		@ $pid 简历id
	*/
	public function check_resume($uid,$pid)
	{
		$uid=intval($uid);
		$pid=intval($pid);
		$percent=0;
		$resume=$this->get_resume_basic($uid,$pid);
		$where = array('uid'=>$uid,'pid'=>$pid);
		$resume_education=M('ResumeEducation')->where($where)->select();
		$resume_work=M('ResumeWork')->where($where)->select();
		$resume_training=M('ResumeTraining')->where($where)->select();
		$resume_tag=$resume['tag_cn'];
		$resume_specialty=$resume['specialty'];
		$resume_photo=$resume['photo_img'];
		$resume_language=M('ResumeLanguage')->where($where)->select();
		$resume_credent=M('ResumeCredent')->where($where)->select();
		$where = array('uid'=>$uid,'resume_id'=>$pid);
		$resume_img=M('ResumeImg')->where($where)->select();
		if ($resume)$percent=$percent+35;
		if ($resume_education)$percent=$percent+15;
		if ($resume_work)$percent=$percent+15;
		if ($resume_training)$percent=$percent+5;
		if ($resume_tag)$percent=$percent+5;
		if ($resume_specialty)$percent=$percent+5;
		if ($resume_photo)$percent=$percent+5;
		if ($resume_language)$percent=$percent+5;//语言
		if ($resume_credent)$percent=$percent+5;//证书
		if ($resume_img)$percent=$percent+5;//附件
		if ($resume['photo_img'] && $resume['photo_display']=="1"){
			$setsqlarr['photo'] = $resume['photo'] = 1;
		}else{
			$setsqlarr['photo'] = $resume['photo'] = 0;
		}
		$setsqlarr['complete_percent']=$percent;

		//省市,职位,行业,标签,专业
		if($resume['district']){
			$t = explode(',',$resume['district']);
			foreach ($t as $key => $val) {
				$a = array_filter(explode('.',$val));
				for($i=count($a)-1;$i>=0;$i--){
					$d[] = 'city'.implode('_',$a);
					$a[$i] = 0;
				}
			}
		}
		if($resume['intention_jobs_id']){
			$t = explode(',',$resume['intention_jobs_id']);
			foreach ($t as $key => $val) {
				$a = array_filter(explode('.',$val));
				for($i=count($a)-1;$i>=0;$i--){
					$d[] = 'jobs'.implode('_',$a);
					$a[$i] = 0;
				}
			}
		}
		if($resume['trade']){
			$t = explode(',',$resume['trade']);
			foreach ($t as $key => $val) {
				$d[] = 'trade'.$val;
			}
		}
		if($resume['tag']){
			$t = explode(',',$resume['tag']);
			foreach ($t as $key => $val) {
				$d[] = 'tag'.$val;
			}
		}
		if($resume['subsite_id']){
			$d[] = 'sub'.$resume['subsite_id'];
		}
		//工作年限,学历,性别,是否照片简历,简历等级,简历更新时间
		foreach(array('mob'=>'mobile_audit','sex'=>'sex','audit'=>'audit','nat'=>'nature','bir'=>'birthdate','mar'=>'marriage','exp'=>'experience','wage'=>'wage','edu'=>'education','major'=>'major','photo'=>'photo','talent'=>'talent','level'=>'level','cur'=>'current') as $key=>$val) {
			if(isset($resume[$val])) $d[] = $key.$resume[$val];
		}
		/* 分词 start */
		$setsqlarr['key_precise'] = $resume['intention_jobs'];
		$setsqlarr['key_full'] = $resume['intention_jobs'].$resume['education_cn'];
		$setsqlarr['key_full'].=$resume['specialty'];
		if (!empty($resume_education)){
			foreach($resume_education as $li){
				$setsqlarr['key_full'].=$li['school'].$li['speciality'];
			}
			//$setsqlarr['key_precise'].=$resume_education[0]['school'].$resume_education[0]['speciality'];
		}
		if (!empty($resume_work)){
			foreach($resume_work as $li){
				$setsqlarr['key_full'].=$li['companyname'].$li['jobs'].$li['achievements'];
				$setsqlarr['key_precise'].=$li['jobs'];
			}
			//$setsqlarr['key_precise'].=$resume_work[0]['companyname'].$resume_work[0]['jobs'].$resume_work[0]['achievements'];
		}
		if (!empty($resume_training)){
			foreach($resume_training as $li){
				$setsqlarr['key_full'].=$li['agency'].$li['course'].$li['description'];
			}
			//$setsqlarr['key_precise'].=$resume_training[0]['agency'].$resume_training[0]['course'].$resume_training[0]['description'];
		}
		if(!empty($resume_language)){
			foreach($resume_language as $li){
				$setsqlarr['key_full'].=$li['language_cn'];
			}
		}
		$setsqlarr['key_full'] = implode(' ',array_merge($d,get_tags($setsqlarr['key_full'],100)));
		$setsqlarr['key_precise'] = implode(' ',array_merge($d,get_tags($setsqlarr['key_precise'],100)));
		/* 分词 end */
		$setsqlarr['refreshtime']=time();
		if($setsqlarr['complete_percent']<60){
			$setsqlarr['level'] = 1;
		}elseif($setsqlarr['complete_percent']>=60 && $setsqlarr['complete_percent']<90){
			$setsqlarr['level'] = 2;
		}elseif($setsqlarr['complete_percent']>=90){
			$setsqlarr['level'] = 3;
		}
		$reg = $this->where(array('uid'=>$uid,'id'=>$pid))->save($setsqlarr);
		if($reg === false) return array('state'=>0,'error'=>'简历信息保存失败！');
		// 更新索引表
		$resume = array_merge($resume,$setsqlarr);
		$this->resume_index(false,$resume);
		if($setsqlarr['complete_percent']>=60){
			D('TaskLog')->do_task(C('visitor'),12);
		}
		if($setsqlarr['complete_percent']>=90){
			D('TaskLog')->do_task(C('visitor'),11);
		}
		return array('state'=>1);
	}
	/**
	 * [resume_index 简历索引表更新]
	 */
	public function resume_index($id,$resume){
		if($id && !$resume) $resume = $this->where(array('id'=>$id))->find();
		if(!$resume) return array('state'=>0,'error'=>'简历不存在！');
		$precise = M('ResumeSearchPrecise');
		$full = M('ResumeSearchFull');
		$where = array('id'=>$resume['id']);
		if($resume['display'] != 1){
			$precise->where($where)->delete();
			$full->where($where)->delete();
		}else{
			$data['id'] = $resume['id'];
			$data['uid'] = $resume['uid'];
			$data['key'] = $resume['key_precise'];
			$data['stime'] = $resume['stime'];
			$data['refreshtime'] = $resume['refreshtime'];
			if($precise->where($where)->find()){
				$reg = $precise->where($where)->save($data);
			}else{
				$reg = $precise->add($data);
			}
			if($reg === false) return array('state'=>0,'error'=>'简历索引表更新失败！');
			$data['key'] = $resume['key_full'];
			if($full->where($where)->find()){
				$reg = $full->where($where)->save($data);
			}else{
				$reg = $full->add($data);
			}
			if($reg === false) return array('state'=>0,'error'=>'简历索引表更新失败！');
		}
		return array('state'=>1);
	}
	
	//外发简历 模板
	public function get_outward_resumes_tpl($uid,$resume_id)
	{
		$resume_basic=$this->get_resume_basic($uid,$resume_id);
		$resume_basic['telephone'] = contact_hide($resume_basic['telephone'],2);
		$resume_basic['email'] = contact_hide($resume_basic['email'],3);
		if($resume_basic['tag_cn'])
		{
			$resume_basic['tag_arr']=explode(',',$resume_basic['tag_cn']);
		}else{
			$resume_basic['tag_arr'] = array();
		}
		$resume_work=D('ResumeWork')->get_resume_work($resume_id,$resume_basic['uid']);
		$controller = new \Common\Controller\BaseController;
		$html = $controller->assign_resume_tpl(array('resume_basic'=>$resume_basic,'resume_work'=>$resume_work),APP_PATH.'Home/View/'.C('DEFAULT_THEME').'/Emailtpl/outward_resume.html');
		return $html;
	}

	/**
	 * ========================后台用的function====================================
	 */
	public function admin_edit_resume_audit($id,$audit,$reason,$pms_notice,$audit_man='')
	{
		!is_array($id) && $id=array($id);
		$sqlin=implode(",",$id);
		if (fieldRegex($sqlin,'in'))
		{
			$resume_list = $this->field('id,uid,display,audit,stime,refreshtime,key_full,key_precise,fullname,title')->where(array('id'=>array('in',$sqlin)))->select();
			foreach ($resume_list as $key => $val) {
				$search = '/audit(\d+)/';
				$replace = 'audit'.$audit;
				$val['key_precise'] = $d['key_precise'] = preg_replace($search,$replace,$val['key_precise']);
				$val['key_full'] = $d['key_full'] = preg_replace($search,$replace,$val['key_full']);
				$val['audit'] = $d['audit'] = $audit;
				if(false===$this->where(array('id'=>$val['id']))->save($d)) return false;
				$this->resume_index(false,$val);
			}
			foreach ($id as $key => $value) {
				$this->admin_set_resume_entrust($value);
			}
			// distribution_resume($id);
			//填写管理员日志
			$audit_man && admin_write_log("修改简历id为".$sqlin."的审核状态为".$audit, $audit_man,3);
			$reasona=$reason==''?'无':$reason;
			//发送站内信
			if ($pms_notice=='1')
			{
				foreach ($resume_list as $key => $value) {
					$user_info=D('Members')->find($value['uid']);
					$setsqlarr['message']=$audit=='1'?"您创建的简历：{$value['title']}（真实姓名：{$value['fullname']}）成功通过网站管理员审核！":"您创建的简历：{$value['title']}（真实姓名：{$value['fullname']}）未通过网站管理员审核，原因：{$reasona}";
					D('Pms')->write_pmsnotice($user_info['uid'],$user_info['username'],$setsqlarr['message']);
				}
			}
			foreach($id as $list){
				$auditsqlarr['resume_id']=$list;
				$auditsqlarr['reason']=$reasona;
				$auditsqlarr['status']=$audit==1?'审核通过':'审核未通过';
				$auditsqlarr['addtime']=time();
				$auditsqlarr['audit_man']=$audit_man?$audit_man:'未知';
				M('AuditReason')->data($auditsqlarr)->add();
			}

			//发送邮件
			$mailconfig=D('Mailconfig')->get_cache();//获取邮件规则
			$sms=D('SmsConfig')->get_cache();
			if ($audit=="1" && $mailconfig['set_resumeallow']=="1")//审核通过
			{
				foreach ($resume_list as $key => $value) {
					$useremail = D('Members')->get_user_one(array('uid'=>$value['uid']));
					if($useremail['email']){
						$send_mail['send_type'] = 'set_resumeallow';
						$send_mail['sendto_email'] = $useremail['email'];
						$send_mail['subject']='set_resumeallow_title';
						$send_mail['body']='set_resumeallow';
						$replac_mail['personalfullname'] = $value['fullname'];
						$replac_mail['resume_title'] = $value['title'];
						$replac_mail['service_url'] = rtrim(C('qscms_site_domain').C('qscms_site_dir'),'/').U('Home/PersonalService/increment');
						D('Mailconfig')->send_mail($send_mail,$replac_mail);
					}
				}
			}

			if ($audit=="3" && $mailconfig['set_resumenotallow']=="1")//审核未通过
			{
				foreach ($resume_list as $key => $value) {
					$useremail = D('Members')->get_user_one(array('uid'=>$value['uid']));
					if($useremail['email']){
						$send_mail['send_type'] = 'set_resumenotallow';
						$send_mail['sendto_email'] = $useremail['email'];
						$send_mail['subject']='set_resumenotallow_title';
						$send_mail['body']='set_resumenotallow';
						$replac_mail['personalfullname'] = $value['fullname'];
						$replac_mail['resume_title'] = $value['title'];
						$replac_mail['editurl'] = rtrim(C('qscms_site_domain').C('qscms_site_dir'),'/').U('Home/Personal/resume_edit',array('pid'=>$value['id']));
						D('Mailconfig')->send_mail($send_mail,$replac_mail);
					}
				}
			}
			//sms
			if ($audit=="1" && C('qscms_sms_open')==1 && $sms['set_resumeallow']=="1" )
			{
				$mobilearray = array();
				foreach ($resume_list as $key => $value) {
					$usermobile = D('Members')->get_user_one(array('uid'=>$value['uid']));
					if ($usermobile['mobile_audit']=="1" && !is_array($value['mobile'],$mobilearray))
					{
						$mobilearray[] = $usermobile['mobile'];
					}
				}
				if(!empty($mobilearray)){
					$mobilestr = implode(",",$mobilearray);
					D('Sms')->sendSms('notice',array('mobile'=>$mobilestr,'tpl'=>'set_resumeallow'));
				}
			}
			//sms
			if ($audit=="3" && C('qscms_sms_open')==1 && $sms['set_resumenotallow']=="1" )//认证未通过
			{
				$mobilearray = array();
				foreach ($resume_list as $key => $value) {
					$usermobile = D('Members')->get_user_one(array('uid'=>$value['uid']));
					if ($usermobile['mobile_audit']=="1" && !is_array($value['mobile'],$mobilearray))
					{
						$mobilearray[] = $usermobile['mobile'];
					}
				}
				if(!empty($mobilearray)){
					$mobilestr = implode(",",$mobilearray);
					D('Sms')->sendSms('notice',array('mobile'=>$mobilestr,'tpl'=>'set_resumenotallow'));
				}
			}
			//微信通知
			if(C('apply.Weixin')){
				if($audit=="1")
				{
					foreach ($resume_list as $k => $v) {
						D('Weixin/TplMsg')->set_resumeallow($v['uid'],$v['title'],'审核通过');
					}
				}
				if($audit=="3")
				{
					foreach ($resume_list as $k => $v) {
						D('Weixin/TplMsg')->set_resumeallow($v['uid'],$v['title'],'审核未通过',$reasona);
					}
				}
			}
			return true;
		}
		return false;
	}
	public function admin_edit_resume_photo_audit($id,$audit,$reason,$pms_notice,$audit_man='')
	{
		!is_array($id) && $id=array($id);
		$sqlin=implode(",",$id);
		if (fieldRegex($sqlin,'in'))
		{
			
			$resume_list = $this->field('id,uid,photo_img,photo_display,fullname')->where(array('id'=>array('in',$sqlin)))->select();
			foreach ($resume_list as $key => $val) {
				if($val['photo_img'] && $audit == 1 && $val['photo_display'] == 1){
					$d['photo'] = 1;
				}else{
					$d['photo'] = 0;
				}
				$d['photo_audit'] = $audit;
				if(true === D('Members')->update_user_info($d,array('uid'=>$val['uid'],'utype'=>2))){
					$uids[] = $val['uid'];
					//站内信
					if ($pms_notice=='1')
					{
						$reason=$reason==''?'未知':$reason;
						foreach ($resume_list as $key => $value) {
							$user_info=D('Members')->find($value['uid']);
							$setsqlarr['message']=$audit=='1'?"你的简历头像成功通过网站管理员审核！":"你的简历头像未通过网站管理员审核，{$note}原因：{$reason}";
							D('Pms')->write_pmsnotice($user_info['uid'],$user_info['username'],$setsqlarr['message']);
						}
					}
					
					//邮件
					$mailconfig=D('Mailconfig')->get_cache();//获取邮件规则
					if ($audit=="1" && $mailconfig['set_resume_photoallow']=="1")//审核通过
					{
						$useremail = D('Members')->get_user_one(array('uid'=>$val['uid']));
						if($useremail['email']){
							$send_mail['send_type'] = 'set_resume_photoallow';
							$send_mail['sendto_email'] = $useremail['email'];
							$send_mail['subject']='set_resume_photoallow_title';
							$send_mail['body']='set_resume_photoallow';
							$replac_mail['personalfullname'] = $val['fullname'];
							$replac_mail['service_url'] = rtrim(C('qscms_site_domain').C('qscms_site_dir'),'/').U('Home/PersonalService/increment');
							D('Mailconfig')->send_mail($send_mail,$replac_mail);
						}
					}

					if ($audit=="3" && $mailconfig['set_resume_photonotallow']=="1")//审核未通过
					{
						$useremail = D('Members')->get_user_one(array('uid'=>$val['uid']));
						if($useremail['email']){
							$send_mail['send_type'] = 'set_resume_photonotallow';
							$send_mail['sendto_email'] = $useremail['email'];
							$send_mail['subject']='set_resume_photonotallow_title';
							$send_mail['body']='set_resume_photonotallow';
							$replac_mail['personalfullname'] = $val['fullname'];
							$replac_mail['editurl'] = rtrim(C('qscms_site_domain').C('qscms_site_dir'),'/').U('Home/Personal/resume_edit',array('pid'=>$val['id']));
							D('Mailconfig')->send_mail($send_mail,$replac_mail);
						}
					}
					$sms=D('SmsConfig')->get_cache();
					if ($audit=="1" && C('qscms_sms_open')==1 && $sms['set_resume_photoallow']=="1" )
					{
						$usermobile = D('Members')->get_user_one(array('uid'=>$val['uid']));
						if($usermobile['mobile_audit']==1){
							D('Sms')->sendSms('notice',array('mobile'=>$usermobile['mobile'],'tpl'=>'set_resume_photoallow'));
						}
					}
					//sms
					if ($audit=="3" && C('qscms_sms_open')==1 && $sms['set_resume_photonotallow']=="1" )//认证未通过
					{
						$usermobile = D('Members')->get_user_one(array('uid'=>$val['uid']));
						if($usermobile['mobile_audit']==1){
							D('Sms')->sendSms('notice',array('mobile'=>$usermobile['mobile'],'tpl'=>'set_resume_photonotallow'));
						}
					}
					if(C('apply.Weixin')){
						D('Weixin/TplMsg')->set_resume_photoallow($val['uid'],$audit==1?'审核通过':'审核未通过',$reason);
					}
				}
			}
			admin_write_log("修改会员uid为".implode(',',$uids)."的照片审核状态为".$audit, C('visitor.username'),3);
			return true;
		}
		return false;
	}
	/**
	 * 刷新简历
	 */
	public function admin_refresh_resume($id){
		$return=0;
		$time = time();
		if (!is_array($id))$id=array($id);
		$sqlin=implode(",",$id);
		if (fieldRegex($sqlin,'in'))
		{
			$data = array('refreshtime'=>$time,'stime'=>$time);
			$return=$this->where(array('id'=>array('in',$sqlin)))->save($data);
			if(false===M('ResumeSearchPrecise')->where(array('id'=>array('in',$sqlin)))->save($data)) return false;
			if(false===M('ResumeSearchFull')->where(array('id'=>array('in',$sqlin)))->save($data)) return false;
			if($rids = $this->where(array('id'=>array('in',$sqlin),'stick'=>1))->getfield('id',true)){
				$data = array('stime'=>$time+100000000);
				if(false===$this->where(array('id'=>array('in',$rids)))->save($data)) return false;
				if(false===M('ResumeSearchPrecise')->where(array('id'=>array('in',$rids)))->save($data)) return false;
				if(false===M('ResumeSearchFull')->where(array('id'=>array('in',$rids)))->save($data)) return false;
			}
			$return = $return===false?0:$return;
		}
		//填写管理员日志
		admin_write_log("刷新简历id为".$sqlin."的简历 , 共刷新".$return."行", C('visitor.username'),3);
		return $return;
	}
	/**
	 * 刷新简历
	 */
	public function admin_refresh_resume_by_uid($uid){
		$return=0;
		$time = time();
		if (!is_array($uid))$uid=array($uid);
		$sqlin=implode(",",$uid);
		if (fieldRegex($sqlin,'in'))
		{
			$data = array('refreshtime'=>$time,'stime'=>$time);
			$return=$this->where(array('uid'=>array('in',$sqlin)))->save($data);
			if(false===M('ResumeSearchPrecise')->where(array('uid'=>array('in',$sqlin)))->save($data)) return false;
			if(false===M('ResumeSearchFull')->where(array('uid'=>array('in',$sqlin)))->save($data)) return false;
			if($rids = $this->where(array('uid'=>array('in',$sqlin),'stick'=>1))->getfield('id',true)){
				$data = array('stime'=>$time+100000000);
				if(false===$this->where(array('id'=>array('in',$rids)))->save($data)) return false;
				if(false===M('ResumeSearchPrecise')->where(array('id'=>array('in',$rids)))->save($data)) return false;
				if(false===M('ResumeSearchFull')->where(array('id'=>array('in',$rids)))->save($data)) return false;
			}
			$return = $return===false?0:$return;
		}
		//填写管理员日志
		admin_write_log("刷新简历id为".$sqlin."的简历 , 共刷新".$return."行", C('visitor.username'),3);
		return $return;
	}
	/**
	 * 根据uid删除简历
	 */
	public function admin_del_resume_for_uid($uid){
		if (!is_array($uid)) $uid=array($uid);
		$sqlin=implode(",",$uid);
		$return=0;
		if (fieldRegex($sqlin,'in'))
		{
			$resumelist = $this->where(array('uid'=>array('in',$sqlin)))->select();
			foreach ($resumelist as $key => $value) {
				$rid[] = $value['id'];
			}
			if (empty($rid))
			{
			return true;
			}
			else
			{
			return $this->admin_del_resume($rid);
			}		
		}
	}
	/**
	 * 删除简历
	 */
	public function admin_del_resume($id){
		if (!is_array($id)) $id=array($id);
		$sqlin=implode(",",$id);
		$return=0;
		if (fieldRegex($sqlin,'in'))
		{
			$return = $this->where(array('id'=>array('in',$sqlin)))->delete();
			if(false === M('ResumeEducation')->where(array('pid'=>array('in',$sqlin)))->delete()) return false;
			if(false === M('ResumeTraining')->where(array('pid'=>array('in',$sqlin)))->delete()) return false;
			if(false === M('ResumeWork')->where(array('pid'=>array('in',$sqlin)))->delete()) return false;
			if(false === M('ResumeCredent')->where(array('pid'=>array('in',$sqlin)))->delete()) return false;
			if(false === M('ResumeLanguage')->where(array('pid'=>array('in',$sqlin)))->delete()) return false;
			if(false === M('ResumeSearchPrecise')->where(array('id'=>array('in',$sqlin)))->delete()) return false;
			if(false === M('ResumeSearchFull')->where(array('id'=>array('in',$sqlin)))->delete()) return false;
			if(false === M('ViewResume')->where(array('resumeid'=>array('in',$sqlin)))->delete()) return false;
			if(false === M('ResumeEntrust')->where(array('resume_id'=>array('in',$sqlin)))->delete()) return false;
			//填写管理员日志
			admin_write_log("删除简历id为".$id."的简历 , 共删除".$return."行", C('visitor.username'),3);
		}
		return $return;
	}
	/**
	 * 设置简历委托
	 */
	public function admin_set_resume_entrust($resume_id){
		$resume = $this->field('audit,uid,fullname,addtime,entrust')->where(array('id'=>$resume_id))->find();
		if($resume["audit"]=="1" && $resume["entrust"]=="1"){
			$has = M('ResumeEntrust')->where(array('id'=>$resume_id))->find();
			if(!$has){
				$setsqlarr['id'] = $resume_id;
				$setsqlarr['uid'] = $resume['uid'];
				$setsqlarr['fullname'] = $resume['fullname'];
				$setsqlarr['resume_addtime'] = $resume['addtime'];
				M('ResumeEntrust')->data($setsqlarr)->add();
				$this->where(array('id'=>$resume_id))->data(array("entrust"=>0))->save();
			}
		}
		else
		{
			M('ResumeEntrust')->where(array('id'=>$resume_id))->delete();
		}
		return true;
	}

	/**
	 * 保存word简历
	 * 传值时注意：如果$id是数组，说明传值是did，需要先查出简历id；如果$id不是数组，那么$id就是简历id
	 */
	public function save_as_doc_word($id,$mod,$user,$zip=0){
		if (is_array($id) && $mod)//如果是did
		{
			// 批量导出为word  先查询简历id
			$sqlin=implode(",",$id);
			if (!fieldRegex($sqlin,'in')) return false;
			$idarr = $mod->where(array('did'=>array('in',$sqlin)))->field('resume_id')->select();
			foreach ($idarr as $key=>$value) {
				$idarr[$key]=$value['resume_id'];
			}
			$id=$idarr;
		}
		else//如果是简历id
		{
			$id=array($id);
		}
		$sqlin=implode(",",$id);
		if (!fieldRegex($sqlin,'in')) return false;
		$result = $this->where(array('id'=>array('in',$sqlin)))->select();
		if(!$result){
			return false;
		}
		$list = array();
		foreach ($result as $n)
		{
			$val = $n;
			$val['education_list']=D('ResumeEducation')->get_resume_education($val['id'],$val['uid']);
			$val['work_list']=D('ResumeWork')->get_resume_work($val['id'],$val['uid']);
			$val['training_list']=D('ResumeTraining')->get_resume_training($val['id'],$val['uid']);
			$val['age']=date("Y")-$val['birthdate'];
			$val['tagcn']=preg_replace("/\d+/", '',$val['tag']);
			$val['tagcn']=preg_replace('/\,/','',$val['tagcn']);
			$val['tagcn']=preg_replace('/\|/','&nbsp;&nbsp;&nbsp;',$val['tagcn']);
		
			// 最近登录时间
			$last_login_time = D('Members')->where(array('uid'=>array('eq',$val['uid'])))->getField('last_login_time');
			$val['last_login_time']=date('Y-m-d',$last_login_time);
			
			if ($val['display_name']=="2")
	        {
	            $val['fullname']="N".str_pad($val['id'],7,"0",STR_PAD_LEFT);
	        }
	        elseif($val['display_name']=="3")
	        {
	            if($val['sex']==1){
	            	$val['fullname']=cut_str($val['fullname'],1,0,"先生");
	            }elseif($val['sex'] == 2){
	            	$val['fullname']=cut_str($val['fullname'],1,0,"女士");
	            }
	        }
			$val['has_down'] = false;
	        $val['is_apply'] = false;
	        $val['label_id'] = 0;
	        $val['show_contact'] = $this->_get_show_contact($val,$val['has_down'],$val['is_apply'],$val['label_id'],$user);
	        if($val['show_contact']===false){
	            $val['telephone'] = contact_hide($val['telephone'],2);
	            $val['email'] = contact_hide($val['email'],3);
	        }
			$avatar_default = $val['sex']==1?'no_photo_male.png':'no_photo_female.png';
	        if ($val['photo']=="1")
	        {   
	            $val['photosrc']=C("qscms_site_domain").attach($val['photo_img'],'avatar');
	        }
	        else
	        {
	            $val['photosrc']=C("qscms_site_domain").attach($avatar_default,'resource');
	        }
			$list[] = $val;
		}
		$controller = new \Common\Controller\BaseController;
		if($zip){
			$path = QSCMS_DATA_PATH.'upload/resume_tmp/'.C('visitor.uid').'/';
	        if(is_dir($path)){//如果目录已存在，先删掉，以防将之前的文档也打包
	        	rmdirs($path);
	        }
	        mkdir($path,0777,true);
			foreach ($list as $key => $value) {
				$word = new \Common\qscmslib\word(); 
			    $wordname = $value['fullname']."的个人简历.doc"; 
			    $wordname = iconv("UTF-8", "GBK", $wordname); 
			    $html = $controller->assign_resume_tpl(array('list'=>array($value)),'Emailtpl/word_resume');
			    echo $html;
			    $word->save($path.$wordname); 
			}
			$savename = '来自'.C('qscms_site_name').'的简历.zip';
			if(strtoupper(substr(PHP_OS,0,3))==='WIN'){
				$filename = $path.iconv("UTF-8", "GBK", $savename); 
			}else{
				$filename = $path.$savename; 
			}
	        $zip = new \Common\qscmslib\phpzip;
	        $done = $zip->zip($path.'/',$filename);
	        if ($done)
	        {        
	        	//写入会员日志
				write_members_log($user,1014,$sqlin);
				return array('zip'=>1,'name'=>$savename,'dir'=>'resume_tmp/'.C('visitor.uid'),'path'=>$path);
				// 
	        }
		}else{
			$html = $controller->assign_resume_tpl(array('list'=>$list),'Emailtpl/word_resume');
			//写入会员日志
			write_members_log($user,1014,$sqlin);
			header("Cache-Control: no-cache, must-revalidate");
			header("Pragma: no-cache");
			header("Content-Type: application/doc");
			$ua = $_SERVER["HTTP_USER_AGENT"];
			$filename="{$val['fullname']}的个人简历.doc";
			$filename = urlencode($filename);
			$filename = str_replace("+", "%20", $filename);
			if (preg_match("/MSIE/", $ua)) {
			    header('Content-Disposition: attachment; filename="' . $filename . '"');
			} else if (preg_match("/Firefox/", $ua)) {
			    header('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');
			} else {
			    header('Content-Disposition: attachment; filename="' . $filename . '"');
			}
			echo $html;
		}
	}
	/**
     * 是否显示联系方式
     */
    protected function _get_show_contact($val,&$down,&$apply,&$label_id,$user){
        $show_contact = false;
        //情景1：游客访问
        if(!$user){
            C('qscms_showresumecontact')==0 && $show_contact = true;
        }
        //情景2：个人会员访问并且是该简历发布者
        else if($user['utype']==2 && $user['uid']==$val['uid'])
        {
            $show_contact = true;
        }
        //情景3：企业会员访问
        else if($user['utype']==1)
        {
            //情景3-1：其他企业会员
            if(C('qscms_showresumecontact')==1){
                $show_contact = true;
            }
            //情景3-2：下载过该简历
            $down_resume = D('CompanyDownResume')->check_down_resume($val['id'],$user['uid']);
            if($down_resume){
                $label_id = $down_resume['is_reply'];
                $show_contact = true;
                $down = true;
            }
            //情景3-3：该简历申请过当前企业发布的职位
            $jobs_apply = D('PersonalJobsApply')->check_jobs_apply($val['id'],$user['uid']);
           /*  if($jobs_apply){
                $label_id = $jobs_apply['is_reply'];
                $show_contact = true;
                $apply = true;
            } */
            //情景3-3：该简历申请过当前企业发布的职位
            $setmeal=D('MembersSetmeal')->get_user_setmeal($user['uid']);
            if($jobs_apply && $setmeal['show_apply_contact']=='1'){
                $show_contact = true;
            }
        }
        return $show_contact;
    }

	/**
	 * 单条简历发送
	 */
	public function send_to_email_one($resume_id,$email){
		$resume_mod = new \Common\qscmstag\resume_showTag(array('简历id'=>$resume_id));
    	$resume = $resume_mod->run();
		$resume['tag_arr']=$resume['tag_cn'];
		$controller = new \Common\Controller\BaseController;
		$html = $controller->assign_resume_tpl(array('resume_basic'=>$resume),'Emailtpl/outward_resume');
		D('Mailconfig')->send_mail(array('sendto_email'=>$email,'subject'=>$resume_basic['fullname'].'的简历','body'=>$html));
	}
	/**
	 * 发送到邮箱
	 */
	public function send_to_email($resume_id,$email){
		!is_array($resume_id) && $resume_id = explode(',',$resume_id);
		foreach ($resume_id as $key => $value) {
			$this->send_to_email_one($value,$email);
		}
		return true;
	}
	/**
	 * 标记简历
	 */
	public function company_label_resume($did,$mod_name,$company_uid,$state){
		if($mod_name=='PersonalJobsApply'){
			$data['personal_look'] = 2;
			$data['reply_time'] = time();
			$old_apply_info = D('PersonalJobsApply')->where(array('did'=>$did,'is_reply'=>0))->find();
		}
		$data['is_reply'] = $state;
		$num = D($mod_name)->where(array('did'=>$did,'company_uid'=>$company_uid))->save($data);
		if(false === $num){
			return array('state'=>0,'msg'=>'标记失败');
		}else{
			$user = D('Members')->get_user_one(array('uid'=>$company_uid));
			$r = false;
			if($mod_name=='PersonalJobsApply'){
				$apply_info = D('PersonalJobsApply')->where(array('did'=>$did))->find();
				//处理时间为3天内
				if($old_apply_info && $data['reply_time']-$apply_info['apply_addtime']<=3600*24*3){
					$userinfo = D('Members')->get_user_one(array('uid'=>$company_uid));
					$r = D('TaskLog')->do_task($userinfo,28);
				}
				//写入会员日志
				write_members_log($user,1029,$did,D('PersonalJobsApply')->state_arr[$state]);
			}else{
				//写入会员日志
				write_members_log($user,1030,$did,D('CompanyDownResume')->state_arr[$state]);
			}
			return array('state'=>1,'msg'=>'标记成功','task'=>$r);
		}
	}
	/**
	 * 获取用户简历
	 */
	public function get_user_resume($uid,$audit=1){
		$map['uid'] = $uid;
		$map['audit'] = $audit;
		return $this->where($map)->getField('id,title');
	}
}
?>