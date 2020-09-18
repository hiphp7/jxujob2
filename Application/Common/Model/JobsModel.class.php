<?php
namespace Common\Model;
use Think\Model;
class JobsModel extends Model{
	protected $_user=array();
	protected $_validate = array(
		array('uid,jobs_name,companyname,company_id,company_addtime,nature,topclass,category,trade,scale,district,contents,setmeal_id,key','identicalNull','',0,'callback'),
		array('uid,company_id,company_addtime,stick,nature,sex,topclass,category,trade,scale,district,sdistrict,tdistrict,education,experience,minwage,maxwage,deadline,setmeal_deadline,setmeal_id','identicalEnum','',0,'callback'),
		array('minwage,maxwage','requireone','{%wage_requireone_error}',2,'function',3),
		array('minwage','0,999999','{%minwage_between_error}',2,'between',3),
		array('maxwage','0,999999','{%maxwage_between_error}',2,'between',3),
		array('minwage,maxwage','wage_between','{%wage_between_error}',2,'callback',3),
		array('jobs_name','2,50','{%jobs_length_error_jobs_name}',0,'length'),
		//array('companyname','2,50','{%jobs_length_error_companyname}',0,'length'),
		array('companyname','2,60','{%jobs_length_error_companyname}',0,'length'),
		array('tpl','2,50','{%jobs_length_error_tpl}',2,'length'),
		array('amount','0,2','{%jobs_length_error_amount}',0,'length'),
		array('contents','0,4000','{%jobs_length_error_contents}',0,'length'),
		// array('tag','in','%tag_format_error',0,'regex')
		//array('telephone','_repetition_mobile','{%jobs_repetition_mobile}',2,'callback'),
		//array('email','_repetition_email','{%jobs_repetition_email}',2,'callback'),
	);
	protected $_auto = array ( 
		array('amount',0),//招聘人数
		array('company_audit',0),//审核
		array('emergency',0),//紧急招聘
		array('negotiable',0),//面议
		array('addtime','time',1,'function'),//添加时间
		array('refreshtime','time',1,'function'),//刷新时间
		array('stime','time',1,'function'),//刷新时间
		array('audit',0),//是否审核通过
		array('display',1),//是否显示
		array('click',1),//点击量
		array('user_status',1),//用户身份
		array('robot',0),//是否为采集信息
		array('add_mode',1),//添加方式
		array('is_entrust',0),//接收委托简历
		array('sex',0),//性别
	);
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
	
	/**
	 * [wage_between 验证月薪范围是否合格(最大值是最小值的2倍)]
	 */
	protected function wage_between($data){
		if(!$data['minwage'] || !$data['maxwage']) return true;
		if($data['maxwage'] <= $data['minwage'] || $data['maxwage'] / $data['minwage'] > 2) return false;
		return true;
	}
	/*
		企业会员中心获取职位列表
		@ $where 查询条件 array
		@ $order 排序  str
		@ $tabletype  表类型 str 传表名或者 all
		@ $pagesize 每页显示 几条数据默认 10
		@ $countresume 是否统计个人申请　某个职位数　默认不统计
		返回值 array
		$rst['list']  职位列表
		$rst['page']　分页

	*/
	
	public function get_jobs($where,$orderby,$tabletype,$pagesize=10,$countresume=false,$promotion=false,$auto_refresh=false,$views=false){
		if($tabletype=="all"){
			if($pagesize!='-1'){
				$count = M('Jobs')->where($where)->count();
				$count1 = M('JobsTmp')->where($where)->count();
				$count = $count + $count1;
				$pager =  pager($count,$pagesize);
				$union_sql = M('JobsTmp')->where($where)->order($orderby)->buildSql();
			}else{
				$union_sql = M('JobsTmp')->where($where)->order($orderby)->buildSql();
			}
			$list = M('Jobs')->union($union_sql,true)->where($where)->order($orderby)->limit($pager->firstRow . ',' . $pager->listRows)->select();
		} elseif($tabletype=="jobs") {
			if($pagesize!='-1'){
                C('qscms_jobs_display') == 1 && $where['audit'] = 1 ;
				$count = M($tabletype)->where($where)->count();
				$pager =  pager($count,$pagesize);
				$list = M($tabletype)->where($where)->order($orderby)->limit($pager->firstRow . ',' . $pager->listRows)->select();
			}else{
				$list = M($tabletype)->where($where)->order($orderby)->select();
			}
		} elseif($tabletype=="jobs_tmp") {
            if($pagesize!='-1'){
                if(C('qscms_jobs_display') == 1){
                    $count = M($tabletype)->where($where)->count();
                    $condition['audit'] = 2 ;
                    $count1 = M('Jobs')->where(array_merge($where,$condition))->count();
                    $count = $count + $count1;
                    $pager =  pager($count,$pagesize);
                    $union_sql = M('Jobs')->where(array_merge($where,$condition))->order($orderby)->buildSql();
                    $list = M($tabletype)->union($union_sql,true)->where($where)->limit($pager->firstRow . ',' . $pager->listRows)->select();
                } else {
                    $count = M($tabletype)->where($where)->count();
                    $pager =  pager($count,$pagesize);
                    $list = M($tabletype)->where($where)->order($orderby)->limit($pager->firstRow . ',' . $pager->listRows)->select();
                }
            }else{
                if(C('qscms_jobs_display') == 1){
                    $condition['audit'] = 2 ;
                    $union_sql = M('Jobs')->where(array_merge($where,$condition))->order($orderby)->buildSql();
                    $list = M($tabletype)->union($union_sql,true)->where($where)->select();
                } else {
                    $list = M($tabletype)->where($where)->order($orderby)->select();
                }
            }
        }
		if(C('apply.Subsite')){
            if(false === $subsite_district = F('subsite_district')) $subsite_district = D('Subsite')->subsite_district_cache();
        }
		foreach ($list as $key => $val){
			C('apply.Subsite') && $subsite_id = $subsite_district[$val['tdistrict']]?:($subsite_district[$val['sdistrict']]?:$subsite_district[$val['district']]);
            $val['jobs_url']=url_rewrite('QS_jobsshow',array('id'=>$val['id']),$subsite_id);
			$val['jobs_name_']=$val['jobs_name'];
			$val['jobs_name']=cut_str($val['jobs_name'],10,0,"...");
			if(APP_SPELL){
           		if(false === $jobs_cate = F('jobs_cate_list')) $jobs_cate = D('CategoryJobs')->jobs_cate_cache();
	            $spell = $val['subclass'] ? $val['subclass'] : $val['category'];
	            !$spell && $spell = $val['topclass'];
	            $val['jobcategory'] = $jobs_cate['id'][$spell]['spell'];
	        }else{
	        	$val['jobcategory'] = intval($val['topclass']).".".intval($val['category']).".".intval($val['subclass']);
	        }
	        if($val['negotiable']==0){
                $val['minwage'] = $val['minwage']%1000==0?($val['minwage']/1000):round($val['minwage']/1000,1);
                $val['maxwage'] = $val['maxwage']?($val['maxwage']%1000==0?($val['maxwage']/1000):round($val['maxwage']/1000,1)):0;
                if($val['maxwage']==0){
                    $val['wage_cn'] = '面议';
                }else{
                    if($val['minwage']==$val['maxwage']){
                        $val['wage_cn'] = $val['minwage'].'K/月';
                    }else{
                        $val['wage_cn'] = $val['minwage'].'K-'.$val['maxwage'].'K/月';
                    }
                }
            }else{
                $val['wage_cn'] = '面议';
            }
            if ($val['audit'] == 2)
            {
                $val['_audit'] = C('qscms_jobs_display') == 2 ? 1 : $val['audit'];
            } else {
                $val['_audit'] = $val['audit'];
            }
            if ($val['_audit'] == 2) {
                $val['status_cn'] = '审核中';
            }
            elseif ($val['_audit'] == 3) {
                $val['status_cn'] = '审核未通过';
                $val['reason'] = M('AuditReason')->where(array('jobs_id'=>$val['id']))->getField('reason');
            }
			if($val['display']==2 || ($val['deadline']>0 && $val['deadline']<time())){
				$val['status_cn'] = '已关闭';
			}
			if ($countresume){
				$val['applys']=M('PersonalJobsApply')->where(array('company_uid'=>$val['uid'],'jobs_id'=>$val['id'],'is_reply'=>array('eq',0)))->count('did');
			}
			if($views){
				$val['views']=M('ViewJobs')->where(array('jobsid'=>$val['id']))->count();
			}
			if($promotion){
				$promotionVal = M('Promotion')->where(array('cp_uid'=>$val['uid'],'cp_jobid'=>$val['id']))->getfield('cp_ptype,cp_days,cp_starttime,cp_endtime');
				$cate = array('emergency','stick');
				foreach($cate as $_key=>$_val){
					if($promotionVal[$_val]){
						$d['already'] = intval((time() - $promotionVal[$_val]['cp_starttime']) / 86400);
						$d['already'] = $d['already']==0?1:$d['already'];
						$d['surplus'] = $promotionVal[$_val]['cp_days'] - $d['already'];
						$d['total_days'] = $promotionVal[$_val]['cp_days'];
						$d['starttime'] = $promotionVal[$_val]['cp_starttime'];
						$d['endtime'] = $promotionVal[$_val]['cp_endtime'];
						$val['promotion'][$_val] = $d;
					}else{
						$val['promotion'][$_val] = '';
					}
				}
			}
			$val['auto_refresh'] = 0;
			$auto_refresh = M('QueueAutoRefresh')->where(array('type'=>1,'pid'=>$val['id']))->find();
			if($auto_refresh)
			{
				$order_list = D('Order')->where(array('uid'=>$val['uid'],'order_type'=>12))->order('id desc')->select();
				foreach ($order_list as $k => $v) {
					$order_params = unserialize($v['params']);
					if($order_params['jobs_id']==$val['id']){
						$val['auto_refresh'] = 1;
						$val['auto_refresh_starttime'] = date('Y-m-d',$order_params['starttime']);
						$val['auto_refresh_endtime'] = date('Y-m-d',$order_params['endtime']);
						break;
					}
				}
			}
			if(C('apply.Allowance')){
				$val['allowance_open'] = 1;
				if($val['allowance_id']>0){
					$allowance = D('Allowance/AllowanceInfo')->find($val['allowance_id']);
					$allowance['type_cn'] = D('Allowance/AllowanceInfo')->get_alias_cn($allowance['type_alias']);
				}else{
					$allowance = array();
				}
			}else{
				$val['allowance_open'] = 0;
			}
			$val['allowance'] = $allowance;
			$jobs[$val['id']]=$val;
		}
		$rst['count']=$count;
		$rst['list']=$jobs;
		if($pagesize!='-1'){
			$rst['page']=$pager->fshow();
		}else{
			$rst['page']='';
		}
		return $rst;
	}
	/*
		获取VIP企业的职位列表
		@ $where 查询条件 array
		@ $order 排序  str
		@ $page  是否开启分页 (1=>开启 0=>不开启)
		@ $pagesize 若开启分页 表示 每页显示 几条数据 ; 若没有开启分页 表示要显示几条数据  默认 10
		返回值 array
		$rst['list']  职位列表
		$rst['page']　分页
	*/
	public function get_vip_jobs($where,$orderby,$page=0,$pagesize=10)
	{
		$db_pre = C('DB_PREFIX');
		$this_t = $db_pre.'jobs';
		$join = $db_pre .'members_setmeal m on m.uid='.$this_t.'.uid';
		if($page)
		{
			$count =$this->where($where)->join($join)->count();
			$pager =  pager($count,$pagesize);
			$vip_jobs_list = $this->where($where)->join($join)->field($this_t.'.jobs_name,'.$this_t.'.companyname,'.$this_t.'.wage_cn,'.$this_t.'.refreshtime,'.$this_t.'.district_cn')->order('stick,emergency,refreshtime')->limit($pagesize)->select();
			$result['page']=$pager->fshow();
		}
		else
		{
			$vip_jobs_list = $this->where($where)->join($join)->field($this_t.'.jobs_name,'.$this_t.'.companyname,'.$this_t.'.wage_cn,'.$this_t.'.refreshtime,'.$this_t.'.district_cn')->order('stick,emergency,refreshtime')->limit($pagesize)->select();
		}
		// 处理数据信息
		foreach ($vip_jobs_list as $key => $val)
		{
			$val['refreshtime'] = daterange(time(),$val['refreshtime'],'Y-m-d',"#FF3300");
			$page?$result['list'][$key] = $val:$result[$key] = $val;
		}
		return $result;
	}
	/* 
		获取单条职位 
		@ $data array 职位条件
		
		返回值 
			如果有返回职位数组，否则返回 false
	*/
	public function get_jobs_one($data)
	{
		$val = $this->where($data)->find();
		!$val && $val= M('JobsTmp')->where($data)->find();
		if(!$val) return false;
		$val['contact']= M('JobsContact')->where(array('pid'=>$val['id']))->find();
		$val['deadline_days']=($val['deadline']-time())>0?"距到期时间还有<strong style=\"color:#FF0000\">".sub_day($val['deadline'],time())."</strong>":"<span style=\"color:#FF6600\">目前已过期</span>";
		return $val;
	}
	
	/* 
		获取单条职位(在招的) 
		@ $data array 职位条件
		
		返回值 
			如果有返回职位数组，否则返回 false
	*/
	public function get_auditjobs_one($data)
	{
		$val = $this->where($data)->find();
		if(!$val) return false;
		$val['contact']= M('JobsContact')->where(array('pid'=>$val['id']))->find();
		$val['deadline_days']=($val['deadline']-time())>0?"距到期时间还有<strong style=\"color:#FF0000\">".sub_day($val['deadline'],time())."</strong>":"<span style=\"color:#FF6600\">目前已过期</span>";
		return $val;
	}
	public function add_jobs($data,$user){
		$this->_user = $user;
		if(false === $d = $this->create($data)){
			return array('state'=>0,'error'=>$this->getError());
		}else{
			/*if($data['basis_contact']){
				if(!$this->_repetition_mobile($data['telephone'])) return array('state'=>0,'error'=>L('jobs_repetition_mobile'));
				if(!$this->_repetition_email($data['email'])) return array('state'=>0,'error'=>L('jobs_repetition_email'));
			}*/
			$category = D('Category')->get_category_cache();
			$jobs_cates = D('CategoryJobs')->get_jobs_cache('all');
			$city = D('CategoryDistrict')->get_district_cache('all');
			$this->nature_cn=$category['QS_jobs_nature'][$this->nature];
			$this->education_cn=$this->education==0?'不限':$category['QS_education'][$this->education];
			$this->experience_cn=$this->experience==0?'不限':$category['QS_experience'][$this->experience];
			$this->category_cn= $this->subclass?$jobs_cates[$this->category][$this->subclass]:$jobs_cates[$this->topclass][$this->category];
			$this->district_cn= $this->sdistrict?$city[0][$this->district].'/'.$city[$this->district][$this->sdistrict]:$city[0][$this->district];
			$this->district_cn= $this->tdistrict?$this->district_cn.'/'.$city[$this->sdistrict][$this->tdistrict]:$this->district_cn;
			$this->age = $data['minage']."-".$data['maxage'];
			$sex = array('0'=>'不限','1'=>'男','2'=>'女','3'=>'不限');
        	$this->sex_cn=$sex[$data['sex']];
        	foreach(explode(',',$this->tag) as $val){
        		$tag_cn[] = $category['QS_jobtag'][$val];
        	}
        	$this->tag_cn=implode(',',$tag_cn);
        	// key 分词
        	$key = get_tags($this->jobs_name.$this->companyname.$this->category_cn.$this->district_cn,100,true);
			$key1 = get_tags($this->contents,100);
			$key = array_merge($key,$key1);
			$this->key = implode(' ',array_unique($key));
			$data['key'] = $this->key;
			C('SUBSITE_VAL.s_id') && $this->subsite_id = $data['subsite_id'] = C('SUBSITE_VAL.s_id');
			if(false === $insert_id = $this->add()) return array('state'=>0,'error'=>'数据添加失败！');
		}
		$data = array_merge($data,$d);
		// 职位联系人
		$data['pid'] = $insert_id;
		$rst_contact =D('JobsContact')->add_jobs_contact($data);
		if(!$rst_contact['state']) return array('state'=>0,'error'=>$rst_contact['error']);
		// 索引信息
		$data['id'] = $insert_id;
		$data['refreshtime'] = $d['refreshtime'];
		M('JobsSearchKey')->add($data);
		M('JobsSearch')->add($data);
		// 职位标签
		if(!D('JobsTag')->add_jobs_tag($insert_id,$user['uid'],$data['tag']))  return array('state'=>0,'error'=>'添加职位标签,保存失败！');
		$this->distribution_jobs($insert_id,$user['uid']);//整理职位
		//写入会员日志
		write_members_log($user,1002,$insert_id,'','',false,$insert_id);
		$members_setmeal = D('MembersSetmeal')->get_user_setmeal($user['uid']);
        $total[0]=M('Jobs')->where(array('uid'=>$user['uid']))->count();
        $total[1]=M('JobsTmp')->where(array('uid'=>$user['uid'],'display'=>array('neq',2)))->count();
        $total=$total[0]+$total[1];
		$members_log['_t']='MembersSetmealLog';
	    $members_log['log_uid']=$user['uid'];
	    $members_log['log_utype']=$user['utype'];
	    $members_log['log_username']=$user['username'];
	    $members_log['log_leave']=$members_setmeal['jobs_meanwhile']-$total;
	    $members_log['log_value'] = '发布职位(职位名称：'.$data['jobs_name'].')';
	    $members_log['log_source']='网页版';
	    setLog($members_log);
	    unset($members_log);
	    if(C('apply.Statistics')){
			$idata['pid'] = $insert_id;
			$idata['category'] = $data['category'];
			$idata['subclass'] = $data['subclass'];
			$idata['amount'] = $data['amount'];
			$idata['education'] = $data['education'];
			$idata['experience'] = $data['experience'];
			$idata['addtime'] = time();
			$class = new \Statistics\Model\CModel($idata);
			$class->jobs_add();
		}
		return array('state'=>1,'id'=>$insert_id);
	}
	/*
		修改职位
		@$data POST 值
	*/
	public function edit_jobs($data,$user){
		$r = $this->_edit_jobs($data,$user);
		//写入会员日志
		$r['state'] && write_members_log($user,1003,$data['id'],'','',false,$data['id']);
		return $r;
	}
	/*
		整理职位表
	*/
	public function distribution_jobs($id,$uid=false){
		if(!$id) return false;
		$uid && $where['uid']=$uid;
		if(!is_array($id))$id=array($id);
		$time=time();
		foreach($id as $v){
			$where['id']=intval($v);
			$t1 = $this->where(array('id'=>intval($v)))->find();
			$t2 = M('JobsTmp')->where(array('id'=>intval($v)))->find();
			if ((!$t1 && !$t2) || ($t1 && $t2)){
				continue;
			}else{
				$j = $t1?$t1:$t2;
				if (!empty($t1) &&  ($j['audit']==1 || $j['audit']==2) && $j['display']==1 && $j['user_status']==1){
					continue;
				}elseif ($t2 && (($j['audit']!=1 && $j['audit']!=2) || $j['display']!=1 || $j['user_status']!=1)){
					continue;
				}
				//检测完毕
				if ($t1){
					M('JobsTmp')->where($where)->delete();
					$this->where($where)->delete();
					if (M('JobsTmp')->add($j)){
						M('JobsSearch')->where($where)->delete();
						M('JobsSearchKey')->where($where)->delete();
					}
				}else{
					M('JobsTmp')->where($where)->delete();
					$this->where($where)->delete();
					if ($this->add($j)){
						$searchtab['id']=$j['id'];
						$searchtab['uid']=$j['uid'];
						$searchtab['audit']=$j['audit'];
						$searchtab['stick']=$j['stick'];
						$searchtab['emergency']=$j['emergency'];
						$searchtab['nature']=$j['nature'];
						$searchtab['sex']=$j['sex'];
						$searchtab['topclass']=$j['topclass'];
						$searchtab['category']=$j['category'];
						$searchtab['subclass']=$j['subclass'];
						$searchtab['trade']=$j['trade'];
						$searchtab['district']=$j['district'];
						$searchtab['sdistrict']=$j['sdistrict'];
						$searchtab['tdistrict']=$j['tdistrict'];
						$searchtab['education']=$j['education'];
						$searchtab['experience']=$j['experience'];
						$searchtab['minwage']=$j['minwage'];
						$searchtab['maxwage']=$j['maxwage'];
						$searchtab['addtime']=$j['addtime'];
						$searchtab['refreshtime']=$j['refreshtime'];
						$searchtab['stime']=$j['stime'];
						$searchtab['scale']=$j['scale'];
						$searchtab['setmeal_id']=$j['setmeal_id'];
						$searchtab['map_x']=$j['map_x'];
						$searchtab['map_y']=$j['map_y'];
						$searchtab['click']=$j['click'];
						$searchtab['jobs_name']=$j['jobs_name'];
						$searchtab['companyname']=$j['companyname'];
						C('apply.Subsite') && $searchtab['subsite_id']=$j['subsite_id'];
						C('apply.Sincerity') && $searchtab['famous']=$j['famous'];
						C('apply.Allowance') && $searchtab['allowance_id']=$j['allowance_id'];
						M('JobsSearch')->add($searchtab);
						$searchtab['key']=$j['key'];
						M('JobsSearchKey')->add($searchtab);
						unset($searchtab);
					}
				}		
			}
		}
	}
	public function distribution_jobs_uid($uid)
	{
		$uid=is_array($uid) ? $uid : array($uid);
		$union_count_sql = M('JobsTmp')->where(array('uid'=>array('in',$uid)))->field('id')->buildSql();
		$id = $this->union($union_count_sql,true)->where(array('uid'=>array('in',$uid)))->getField('id',true);
		return $this->distribution_jobs($id,$uid);
	}
	/*
		职位操作
		刷新，关闭，恢复，删除,
		@data array
		$data['yid'] 职位id 多个为数组
		$data['perform_type'] 操作类型 refresh,close,display,delete
		$data['user']  array 用户信息
		
		返回值
    */
	public function jobs_perform($data)
	{
		$perform_arr=array('close','display','delete');
		if(!in_array($data['perform_type'], $perform_arr)) return array('state'=>0,'error'=>'操作类型,错误！');
		if (!is_array($data['yid'])) $data['yid']=array($data['yid']);
		if(empty($data['yid'])) return array('state'=>0,'error'=>'您没有选择职位！');
		$name = 'jobs_'.$data['perform_type'];
		return $this->$name($data);
	}
	// 刷新
	public function jobs_refresh($data)
	{
		$jobs_num =count($data['yid']);
		$refresh_log_mod = D('RefreshLog');
		$setmeal=D('MembersSetmeal')->get_user_setmeal($data['user']['uid']);
		if(!$setmeal) return array('state'=>0,'error'=>'您还没有开通服务，请开通!','link'=>__APP__.'/company/setmeal_list');
		if($setmeal['expire']==1 && $setmeal['setmeal_id']>1) return array('state'=>0,'error'=>'您的服务已经到期，请重新开通!','link'=>__APP__.'/companyService/index');
		$refrestime = $refresh_log_mod->get_last_refresh_date(array('uid'=>$data['user']['uid'],'type'=>1001,'mode'=>2));
		$duringtime=time()-$refrestime;
		$space = C('qscms_refresh_jobs_space')*60;
		if($space>0 && $duringtime<=$space){
			return array('state'=>0,'error'=>C('qscms_refresh_jobs_space')."分钟内不能重复刷新职位！");
		}
		$mode = 2;
		//获取今天免费刷新的次数
		$refresh_time = $refresh_log_mod->get_today_refresh_times(array('uid'=>$data['user']['uid'],'type'=>1001,'mode'=>2));
		$free_mode = 0;
		if($refresh_time>=$setmeal['refresh_jobs_free'])//免费刷新次数已到
		{
			$members_points = D('MembersPoints')->get_user_points($data['user']['uid']);
			if($members_points<C('qscms_refresh_jobs_price')*C('qscms_payment_rate')*$jobs_num){
				return array('state'=>0,'error'=>'你的'.C('qscms_points_byname')."不足，不能刷新职位！");
			}
			$mode = 1;
			D('MembersPoints')->report_deal($data['user']['uid'],2,C('qscms_refresh_jobs_price')*C('qscms_payment_rate')*$jobs_num);
			// 写入会员积分操作日志
			$handsel['uid'] = $data['user']['uid'];
			$handsel['htype'] = 'refresh_jobs';
			$handsel['htype_cn'] = '刷新职位';
			$handsel['operate'] = 2;
			$handsel['points'] = C('qscms_refresh_jobs_price')*C('qscms_payment_rate')*$jobs_num;
			$handsel['addtime'] = time();
			D('MembersHandsel')->members_handsel_add($handsel);
			$log_paymode = C('qscms_points_byname').'兑换刷新';
		}else{
			$free_mode = 1;
			$log_paymode = '套餐内免费刷新';
		}
		// 刷新操作
		$time=time();
		$where = array('uid'=>$data['user']['uid'],'id'=>array('in',$data['yid']));
		$data_save = array('refreshtime'=>$time);
		M('CompanyProfile')->where(array('uid'=>$data['user']['uid']))->save($data_save);
		$data_save['stime'] = $time;
		$this->where($where)->save($data_save);
		M('JobsTmp')->where($where)->save($data_save);
		M('JobsSearch')->where($where)->save($data_save);
		M('JobsSearchKey')->where($where)->save($data_save);
		//置顶时间
		$where['stick'] = 1;
		$time = $time + 100000000;
		$this->where($where)->setField('stime',$time);
		M('JobsTmp')->where($where)->setField('stime',$time);
		M('JobsSearch')->where($where)->setField('stime',$time);
		M('JobsSearchKey')->where($where)->setField('stime',$time);
		//写入会员日志
		$idstr = is_array($data['yid'])?implode(",", $data['yid']):$data['yid'];
		write_members_log($data['user'],1006,$idstr,$log_paymode);
		/* 记录刷新日志 */
		$refresh_log['_t']='RefreshLog';
		$refresh_log['uid']=$data['user']['uid'];
		$refresh_log['type']=1001;
		$refresh_log['mode']=$mode;
		for ($i=0; $i < $jobs_num; $i++) { 
			setLog($refresh_log);
		}
		unset($refresh_log);
		if($free_mode){
			$today = D('RefreshLog')->get_today_refresh_times(array('uid'=>$data['user']['uid'],'type'=>1001,'mode'=>2));
			$jobs_name_arr = $this->where(array('id'=>array('in',$data['yid'])))->getField('id,jobs_name');
			$members_log['_t']='MembersSetmealLog';
		    $members_log['log_uid']=$data['user']['uid'];
		    $members_log['log_utype']=$data['user']['utype'];
		    $members_log['log_username']=$data['user']['username'];
		    $members_log['log_leave']=$setmeal['refresh_jobs_free']-$today;
		    $members_log['log_value'] = '刷新职位(职位名称：'.implode(",", $jobs_name_arr).')';
		    $members_log['log_source']='网页版';
		    setLog($members_log);
		    unset($members_log);
		}
		return array('state'=>1,'error'=>'刷新成功！');
	}
	// 关闭
	public function jobs_close($data)
	{
		$this->where(array('uid'=>$data['user']['uid'],'id'=>array('in',$data['yid'])))->setField(array('display'=>2));
		M('JobsTmp')->where(array('uid'=>$data['user']['uid'],'id'=>array('in',$data['yid'])))->setField(array('display'=>2));
		$this->distribution_jobs($data['yid'],$data['user']['uid']);
		//写入会员日志
		$id_arr = is_array($data['yid'])?$data['yid']:explode(",", $data['yid']);
		foreach ($id_arr as $key => $value) {
			write_members_log($data['user'],1007,$value,'','',false,$value);
		}
		return array('state'=>1);
	}
	// 恢复
	protected function jobs_display($data)
	{
		$setmeal=D('MembersSetmeal')->get_user_setmeal($data['user']['uid']);
		$jobs_num= $this->where(array('uid'=>$data['user']['uid']))->count();
		if($jobs_num>=$setmeal['jobs_meanwhile']) return array('state'=>0,'error'=>'当前显示的职位已经超过了最大限制，请升级服务套餐，或将不招聘的职位设为关闭！');
		$time=time();
		$this->where(array('uid'=>$data['user']['uid'],'id'=>array('in',$data['yid'])))->setField(array('display'=>1));
		M('JobsTmp')->where(array('uid'=>$data['user']['uid'],'id'=>array('in',$data['yid'])))->setField(array('display'=>1));

		$this->distribution_jobs($data['yid'],$data['user']['uid']);
		//写入会员日志
		$id_arr = is_array($data['yid'])?$data['yid']:explode(",", $data['yid']);
		foreach ($id_arr as $key => $value) {
			write_members_log($data['user'],1008,$value,'','',false,$value);
		}
		return array('state'=>1);

	}
	// 删除
	protected function jobs_delete($data)
	{
		//如果安装了红包打赏，生成退款记录
		if(C('apply.Allowance')){
			$list = $this->where(array('id'=>array('in',$data['yid']),'uid'=>$data['user']['uid'],'allowance_id'=>array('gt',0)))->select();
			if($list){
				foreach ($list as $key => $value) {
					$info = D('Allowance/AllowanceInfo')->find($value['allowance_id']);
			        $setsqlarr['uid'] = $info['uid'];
			        $setsqlarr['type'] = 1;
			        $setsqlarr['amount'] = $info['per_amount'] * $info['surplus_num'];
			        $setsqlarr['note'] = '删除职位，发起退款';
			        D('Allowance/AllowanceRefundmentRecord')->record_add($setsqlarr);
			        D('Allowance/AllowanceInfo')->surplusnum_minus($value['allowance_id'],$info['surplus_num']);
				}
			}
			$list_tmp = M('JobsTmp')->where(array('id'=>array('in',$data['yid']),'uid'=>$data['user']['uid'],'allowance_id'=>array('gt',0)))->select();
			if($list_tmp){
				foreach ($list_tmp as $key => $value) {
					$info = D('Allowance/AllowanceInfo')->find($value['allowance_id']);
			        $setsqlarr['uid'] = $info['uid'];
			        $setsqlarr['type'] = 1;
			        $setsqlarr['amount'] = $info['per_amount'] * $info['surplus_num'];
			        $setsqlarr['note'] = '删除职位，发起退款';
			        D('Allowance/AllowanceRefundmentRecord')->record_add($setsqlarr);
			        D('Allowance/AllowanceInfo')->surplusnum_minus($value['allowance_id'],$info['surplus_num']);
				}
			}
		}
		if(false === $num = $this->where(array('id'=>array('in',$data['yid']),'uid'=>$data['user']['uid']))->delete()) return array('state'=>0,'error'=>'删除失败！');
		if(false === $num_tmp = M('JobsTmp')->where(array('id'=>array('in',$data['yid']),'uid'=>$data['user']['uid']))->delete()) return array('state'=>0,'error'=>'删除失败,jobs_tmp！');
		if(false === M('JobsContact')->where(array('pid'=>array('in',$data['yid'])))->delete()) return array('state'=>0,'error'=>'删除失败,jobs_contact');
		if(false === M('Promotion')->where(array('cp_jobid'=>array('in',$data['yid'])))->delete()) return array('state'=>0,'error'=>'删除失败,promotion');
		if(false === M('JobsSearch')->where(array('id'=>array('in',$data['yid']),'uid'=>$data['user']['uid']))->delete()) return array('state'=>0,'error'=>'删除失败,jobs_search');
		if(false === M('JobsSearchKey')->where(array('id'=>array('in',$data['yid']),'uid'=>$data['user']['uid']))->delete()) return array('state'=>0,'error'=>'删除失败,jobs_search_key');
		if(false === M('JobsTag')->where(array('pid'=>array('in',$data['yid']),'uid'=>$data['user']['uid']))->delete()) return array('state'=>0,'error'=>'删除失败,jobs_tag');
		if(false === M('ViewJobs')->where(array('jobsid'=>array('in',$data['yid'])))->delete()) return array('state'=>0,'error'=>'删除失败,view_jobs');
		if(false === M('QueueAutoRefresh')->where(array('type'=>1,'pid'=>array('in',$data['yid'])))->delete()) return array('state'=>0,'error'=>'删除失败,queue_auto_refresh');
		//写入会员日志
		$idstr = is_array($data['yid'])?implode(",", $data['yid']):$data['yid'];
		write_members_log($data['user'],1009,$idstr);
		return array('state'=>1);
	}
	/*
	*	统计有效职位(包括审核通过和等待审核的)数量
	*/
	public function count_jobs_num($uid)
	{
		$where = array('uid'=>$uid,'audit'=>1,'display'=>1);
		$jobs_count = M('Jobs')->where($where)->count();
		$where_tmp = array('uid'=>$uid,'audit'=>2,'display'=>1);
		$jobs_count_tmp = M('Jobs')->where($where_tmp)->count();
		$com_jobs_num=$jobs_count+$jobs_count_tmp;
		return $com_jobs_num;
	}
	/**
	 * [count_auditjobs_num 统计审核通过的有效职位]
	 */
	public function count_auditjobs_num($uid){
		$list_map['uid'] = $uid;
		if(C('qscms_jobs_display')==1){
			$list_map['audit'] = 1;
		}else{
			$list_map['id'] = array('gt',0);
		}
		$count = $this->where($list_map)->count('id');
		return $count;
	}
	/**
	 * 获取用户所有职位
	 */
	public function get_jobs_by_uid($uid){
		$where['uid'] = $uid;
		$list = $this->where($where)->select();
		$list_tmp =  M('JobsTmp')->where($where)->select();
		return array_merge($list,$list_tmp);
	}
	/**
	 * 整理后台职位列表
	 */
	public function admin_format_jobs_list($list){
		if(C('apply.Subsite')){
            if(false === $subsite_district = F('subsite_district')) $subsite_district = D('Subsite')->subsite_district_cache();
        }
		foreach ($list as $key => $value) {
			$arr = $value;
			$arr['jobs_name']=cut_str($value['jobs_name'],12,0,"...");
			$arr['companyname']=cut_str($value['companyname'],18,0,"...");
			$arr['company_url']=url_rewrite('QS_companyshow',array('id'=>$value['company_id']));
			C('apply.Subsite') && $subsite_id = $subsite_district[$value['tdistrict']]?:($subsite_district[$value['sdistrict']]?:$subsite_district[$value['district']]);
			$arr['jobs_url']=url_rewrite('QS_jobsshow',array('id'=>$value['id']),$subsite_id);
			$list[$key] = $arr;
		}
		return $list;
	}
	/**
	 * 后台删除职位
	 */
	public function admin_del_jobs($id){
		if (!is_array($id)) $id=array($id);
		$sqlin=implode(",",$id);
		$return=0;
		if (fieldRegex($sqlin,'in'))
		{
			if(false === $num = $this->where(array('id'=>array('in',$sqlin)))->delete()) return false;
			if(false === $num_tmp = M('JobsTmp')->where(array('id'=>array('in',$sqlin)))->delete()) return false;
			if(false === M('JobsContact')->where(array('pid'=>array('in',$sqlin)))->delete()) return false;
			if(false === M('Promotion')->where(array('cp_jobid'=>array('in',$sqlin)))->delete()) return false;
			if(false === M('JobsSearch')->where(array('id'=>array('in',$sqlin)))->delete()) return false;
			if(false === M('JobsSearchKey')->where(array('id'=>array('in',$sqlin)))->delete()) return false;
			if(false === M('JobsTag')->where(array('pid'=>array('in',$sqlin)))->delete()) return false;
			if(false === M('ViewJobs')->where(array('jobsid'=>array('in',$sqlin)))->delete()) return false;
			if(false === M('QueueAutoRefresh')->where(array('type'=>1,'pid'=>array('in',$sqlin)))->delete()) return false;
			$return = $num+$num_tmp;
		}
		return $return;
	}
	public function admin_delete_jobs_for_uid($uid){
		!is_array($uid) && $uid=array($uid);
		$sqlin=implode(",",$uid);
		if (fieldRegex($sqlin,'in'))
		{
			$unionsql = M('JobsTmp')->field('id')->where(array('uid'=>array('in',$sqlin)))->buildSql();
			$result = $this->field('id')->where(array('uid'=>array('in',$sqlin)))->union($unionsql,true)->select();
			$pidarr = array();
			foreach ($result as $key => $value) {
				$pidarr[] = $value['id'];
			}
			if(!empty($pidarr)){
				M('JobsContact')->where(array('pid'=>array('in',$pidarr)))->delete();
			}
			$this->where(array('uid'=>array('in',$sqlin)))->delete();
			M('JobsTmp')->where(array('uid'=>array('in',$sqlin)))->delete();
			M('JobsSearch')->where(array('uid'=>array('in',$sqlin)))->delete();
			M('JobsSearchKey')->where(array('uid'=>array('in',$sqlin)))->delete();
			M('JobsTag')->where(array('uid'=>array('in',$sqlin)))->delete();
			M('QueueAutoRefresh')->where(array('uid'=>array('in',$sqlin)))->delete();
			admin_write_log("删除企业uid为".$sqlin."的企业发布的职位", C('visitor.username'),3);
			return true;
		}
		return false;
	}
	/**
	 * 后台审核职位
	 */
	public function admin_edit_jobs_audit($id,$audit,$reason,$audit_man='')
	{
		!is_array($id) && $id=array($id);
		$sqlin=implode(",",$id);
		if (fieldRegex($sqlin,'in'))
		{
			if(false===$this->where(array('id'=>array('in',$sqlin)))->data(array('audit'=>$audit))->save()) return false;
			if(false===M('JobsTmp')->where(array('id'=>array('in',$sqlin)))->data(array('audit'=>$audit))->save()) return false;
			if(false===M('JobsSearch')->where(array('id'=>array('in',$sqlin)))->data(array('audit'=>$audit))->save()) return false;
			if(false===M('JobsSearchKey')->where(array('id'=>array('in',$sqlin)))->data(array('audit'=>$audit))->save()) return false;
			$this->distribution_jobs($id);
			
			//填写管理员日志
			admin_write_log("将职位id为".$sqlin."的职位,审核状态设置为".$audit, C('visitor.username'),3);
			
			$reasona=$reason==''?'无':$reason;
			foreach($id as $list){
				$auditsqlarr['jobs_id']=$list;
				$auditsqlarr['reason']=$reasona;
				$auditsqlarr['status']=$audit==1?'审核通过':'审核未通过';
				$auditsqlarr['addtime']=time();
				$auditsqlarr['audit_man']=$audit_man;
				M('AuditReason')->data($auditsqlarr)->add();
			}
			//站内信
			$union = D('JobsTmp')->where(array('id'=>array('in',$sqlin)))->buildSql();
			$result = D('Jobs')->where(array('id'=>array('in',$sqlin)))->union($union)->select();
			foreach ($result as $key => $value) {
				$user_info = D('Members')->get_user_one(array('uid'=>$value['uid']));
				$pms_message = $audit=='1'?("您发布的职位：".$value['jobs_name']."，成功通过网站管理员审核！"):("您发布的职位：".$value['jobs_name']."，未通过网站管理员审核，原因：".$reasona);
				D('Pms')->write_pmsnotice($user_info['uid'],$user_info['username'],$pms_message);
			}
			
			//发送邮件
			$mailconfig=D('Mailconfig')->get_cache();//获取邮件规则
			$sms=D('SmsConfig')->get_cache();
			if ($audit=="1" && $mailconfig['set_jobsallow']=="1")//审核通过
			{
				foreach ($result as $key => $value) {
					$useremail = D('Members')->get_user_one(array('uid'=>$value['uid']));
					if($useremail['email']){
						$send_mail['send_type'] = 'set_jobsallow';
						$send_mail['sendto_email'] = $useremail['email'];
						$send_mail['subject']='set_jobsallow_title';
						$send_mail['body']='set_jobsallow';
						$replac_mail['jobsname'] = $value['jobs_name'];
						$replac_mail['service_url'] = rtrim(C('qscms_site_domain').C('qscms_site_dir'),'/').U('Home/CompanyService/increment');
						D('Mailconfig')->send_mail($send_mail,$replac_mail);
					}
				}
			}
			if ($audit=="3" && $mailconfig['set_jobsnotallow']=="1")//审核未通过
			{
				foreach ($result as $key => $value) {
					$useremail = D('Members')->get_user_one(array('uid'=>$value['uid']));
					if($useremail['email']){
						$send_mail['send_type'] = 'set_jobsnotallow';
						$send_mail['sendto_email'] = $useremail['email'];
						$send_mail['subject']='set_jobsnotallow_title';
						$send_mail['body']='set_jobsnotallow';
						$replac_mail['jobsname'] = $value['jobs_name'];
						$replac_mail['service_url'] = rtrim(C('qscms_site_domain').C('qscms_site_dir'),'/').U('Home/CompanyService/increment');
						$replac_mail['editurl'] = rtrim(C('qscms_site_domain').C('qscms_site_dir'),'/').U('Home/Company/jobs_edit',array('id'=>$value['id']));
						D('Mailconfig')->send_mail($send_mail,$replac_mail);
					}
				}
			}
			//sms
			if ($audit=="1" && C('qscms_sms_open')==1 && $sms['set_jobsallow']=="1" )
			{
				$mobilearray = array();
				foreach ($result as $key => $value) {
					$usermobile = D('Members')->get_user_one(array('uid'=>$value['uid']));
					if ($usermobile['mobile_audit']=="1" && !is_array($value['mobile'],$mobilearray))
					{
						D('Sms')->sendSms('notice',array('mobile'=>$usermobile['mobile'],'tpl'=>'set_jobsallow','data'=>array('jobsname'=>$value['jobs_name'])));
					}
				}
			}
			//sms
			if ($audit=="3" && C('qscms_sms_open')==1 && $sms['set_jobsnotallow']=="1" )//认证未通过
			{
				$mobilearray = array();
				foreach ($result as $key => $value) {
					$usermobile = D('Members')->get_user_one(array('uid'=>$value['uid']));
					if ($usermobile['mobile_audit']=="1" && !is_array($value['mobile'],$mobilearray))
					{
						D('Sms')->sendSms('notice',array('mobile'=>$usermobile['mobile'],'tpl'=>'set_jobsnotallow','data'=>array('jobsname'=>$value['jobs_name'])));
					}
				}
			}
			//微信通知
			if(C('apply.Weixin')){
				if($audit=="1")
				{
					foreach ($result as $k => $v) {
						D('Weixin/TplMsg')->set_jobsallow($v['uid'],$v['jobs_name'],'审核通过');
					}
				}
				if($audit=="3")
				{
					foreach ($result as $k => $v) {
						D('Weixin/TplMsg')->set_jobsallow($v['uid'],$v['jobs_name'],'审核未通过',$reasona);
					}
				}
			}
			
			return true;
		}
		return false;
	}
	/**
	 * 后台刷新职位
	 */
	public function admin_refresh_jobs($id){
		$return=0;
		$time = time();
		if (!is_array($id))$id=array($id);
		$sqlin=implode(",",$id);
		if (fieldRegex($sqlin,'in'))
		{
			$time=time();
			$where = array('id'=>array('in',$sqlin));
			$data = array('refreshtime'=>$time,'stime'=>$time);
			$return=$this->where($where)->save($data);
			if(false===M('JobsTmp')->where($where)->save($data)) return false;
			if(false===M('JobsSearch')->where($where)->save($data)) return false;
			if(false===M('JobsSearchKey')->where($where)->save($data)) return false;
			//置顶时间
			$where['stick'] = 1;
			$realtime = $time;
			$time = $time + 100000000;
			$this->where($where)->setField('stime',$time);
			M('JobsTmp')->where($where)->setField('stime',$time);
			M('JobsSearch')->where($where)->setField('stime',$time);
			M('JobsSearchKey')->where($where)->setField('stime',$time);
			$uidObj = $this->distinct(true)->where(array('id'=>array('in',$sqlin)))->field('uid')->select();
			$uidArr = array();
			if($uidObj){
				foreach ($uidObj as $key => $value) {
					$uidArr[] = $value['uid'];
				}
			}
			if(!empty($uidArr)){
				M('CompanyProfile')->where(array('uid'=>array('in',$uidArr)))->data(array('refreshtime'=>$realtime))->save();
			}
			$return = $return===false?0:$return;
		}
		//填写管理员日志
		admin_write_log("刷新职位id为".$sqlin."的职位", C('visitor.username'),3);
		return $return;
	}
	/**
	 * 刷新职位
	 */
	public function admin_refresh_jobs_by_uid($uid){
		$return=0;
		$time = time();
		if (!is_array($uid))$uid=array($uid);
		$sqlin=implode(",",$uid);
		if (fieldRegex($sqlin,'in'))
		{
			$time=time();
			$where = array('uid'=>array('in',$sqlin));
			$data = array('refreshtime'=>$time,'stime'=>$time);
			$return=$this->where($where)->save($data);
			if(false===M('JobsTmp')->where($where)->save($data)) return false;
			if(false===M('JobsSearch')->where($where)->save($data)) return false;
			if(false===M('JobsSearchKey')->where($where)->save($data)) return false;
			M('CompanyProfile')->where($where)->data(array('refreshtime'=>$time))->save();
			//置顶时间
			$where['stick'] = 1;
			$time = $time + 100000000;
			$this->where($where)->setField('stime',$time);
			M('JobsTmp')->where($where)->setField('stime',$time);
			M('JobsSearch')->where($where)->setField('stime',$time);
			M('JobsSearchKey')->where($where)->setField('stime',$time);
			$return = $return===false?0:$return;
		}
		//填写管理员日志
		admin_write_log("刷新职位id为".$sqlin."的职位", C('visitor.username'),3);
		return $return;
	}
	/**
	 * 获取企业可发布的职位数
	 */
	public function count_surplus_jobs_num($uid,$setmeal){
		$num1 = $this->where(array('uid'=>$uid))->count();
		$num2 = D('JobsTmp')->where(array('uid'=>$uid,'display'=>1))->count();
		return intval($setmeal['jobs_meanwhile']-$num1-$num2);
	}
	/*
		后台修改职位
		@$data POST 值
	*/
	public function admin_edit_jobs($data,$user){
		$r = $this->_edit_jobs($data,$user);
		$r['state'] && $this->distribution_jobs($data['id']);
		return $r;
	}
	/*
		修改职位
		@$data POST 值
	*/
	protected function _edit_jobs($data,$user){
		$this->_user = $user;
		if(false === $this->create($data)){
			return array('state'=>0,'error'=>$this->getError());
		}else{
			/*if($data['basis_contact']){
				if(!$this->_repetition_mobile($data['telephone'])) return array('state'=>0,'error'=>L('jobs_repetition_mobile'));
				if(!$this->_repetition_email($data['email'])) return array('state'=>0,'error'=>L('jobs_repetition_email'));
			}*/
			$category = D('Category')->get_category_cache();
			$jobs_cates = D('CategoryJobs')->get_jobs_cache('all');
			$city = D('CategoryDistrict')->get_district_cache('all');
			$this->nature_cn=$category['QS_jobs_nature'][$this->nature];
			$this->education_cn=$this->education==0?'不限':$category['QS_education'][$this->education];
			$this->experience_cn=$this->experience==0?'不限':$category['QS_experience'][$this->experience];
			$this->category_cn= $this->subclass?$jobs_cates[$this->category][$this->subclass]:$jobs_cates[$this->topclass][$this->category];
			$this->district_cn= $this->sdistrict?$city[0][$this->district].'/'.$city[$this->district][$this->sdistrict]:$city[0][$this->district];
			$this->district_cn= $this->tdistrict?$this->district_cn.'/'.$city[$this->sdistrict][$this->tdistrict]:$this->district_cn;
			$this->age = $data['minage']."-".$data['maxage'];
			$sex = array('0'=>'不限','1'=>'男','2'=>'女','3'=>'不限');
        	$this->sex_cn=$sex[$data['sex']];
        	foreach(explode(',',$this->tag) as $val){
        		$tag_cn[] = $category['QS_jobtag'][$val];
        	}
        	$this->tag_cn=implode(',',$tag_cn);
        	// key 分词
			$this->key = $this->jobs_name.$this->companyname.$this->category_cn.$this->district_cn.$this->contents;
			$this->key = implode(' ',get_tags($this->key,100));
			$data['key'] = $this->key;
			C('SUBSITE_VAL.s_id') && $this->subsite_id = $data['subsite_id'] = C('SUBSITE_VAL.s_id');
			$r = $this->where(array('id'=>$data['id'],'uid'=>$user['uid']))->save();
			if(!$r){
				$r = D('JobsTmp')->where(array('id'=>$data['id'],'uid'=>$user['uid']))->save($data);
			}
			if(false === $r) return array('state'=>0,'error'=>'数据修改失败！');
		}
		// 职位联系人
		$data['pid'] = $data['id'];
		unset($data['id']);
		$rst_contact =D('JobsContact')->edit_jobs_contact($data);
		if(!$rst_contact['state']) return array('state'=>0,'error'=>$rst_contact['error']);
		// 索引信息
		$data = $this->where(array('id'=>$data['pid']))->find();
		if(!$data){
			$data = D('JobsTmp')->where(array('id'=>$data['pid']))->find();
		}
		M('JobsSearchKey')->where(array('id'=>$data['id']))->save($data);
		M('JobsSearch')->where(array('id'=>$data['id']))->save($data);
		// 职位标签
		if(!D('JobsTag')->add_jobs_tag($data['id'],$user['uid'],$data['tag']))  return array('state'=>0,'error'=>'添加职位标签,保存失败！');
		return array('state'=>1,'error'=>'保存成功！','id'=>$data['id']);
	}
}
?>