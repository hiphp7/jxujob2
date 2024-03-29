<?php
/**
 * 合并加载JS和CSS文件
 *
 * @author brivio
 */
namespace Common\qscmstag;
defined('THINK_PATH') or exit();
class resume_listTag {
	protected $mod;                                                             //索引表
    protected $params           = array();                                      //全部传值
    protected $field 			= 'id';
    protected $where            = array();                                      //查询条件
    protected $order;                                                           //排序
    protected $join;
    protected $firstRow         = 0;                                            //分页开始位置
    protected $spage_size       = 10;                                           //单页条数
    protected $default_order    = 'stime desc';                 				//默认排序字段
    protected $order_array      = array(//排序枚举列表
        'score'                 =>  'score %s',
        'rtime'                 =>  'refreshtime %s',
        'null'                  =>  ''
    );
    protected $enum             =   array(
		'开始位置'				=>	'firstRow',
		'显示数目'				=>	'spage_size',
		'搜索类型'				=>	'search_type',
		'应届生简历'				=>	'campu_sresume',
		'院校名称'				=>	'campusname',
		'更新时间'				=>	'refreshtime',
		'姓名长度'				=>	'namelen',
		'特长描述长度'			=>	'specialtylen',
		'意向职位长度'			=>	'jobslen',
		'专业长度'				=>	'majorlen',
		'填补字符'				=>	'dot',
		'日期范围'				=>	'settr',
		'职位分类'				=>	'jobcategory',
		'职位大类'				=>	'topclass',
		'职位中类'				=>	'category',
		'职位小类'				=>	'subclass',
		'地区分类'				=>	'citycategory',
		'地区大类'				=>	'district',
		'地区中类'				=>	'sdistrict',
		'地区小类'				=>	'tdistrict',
        '工作性质'              =>  'nature',
		'行业'					=>	'trade',
		'所学专业'				=>	'major',
		'标签'					=>	'tag',
        '年龄'                  =>  'age',
        '手机认证'              =>  'mobile_audit',
		'学历'					=>	'education',
		'工作经验'				=>	'experience',
        '工资'                  =>  'wage',
		'等级'					=>	'talent',
		'性别'					=>	'sex',
		'照片'					=>	'photo',
		'关键字'					=>	'key',
		'排序'					=>	'displayorder',
		'分页显示'				=>	'page',
		'页面'					=>	'showname',
		'列表页'					=>	'listpage',
		'浏览过的简历'			=>	'readresume',
		'招聘会id'				=>  'fair_id',
		'签到'					=>  'sign',
        '关健字类型'            	=>  'key_type'
    );
    public function __construct($options) {
    	foreach ($options as $key => $val) {
            $this->params[$this->enum[$key]] = $val;
        }
        if(!$this->params['search_type']) $this->params['search_type'] = C('qscms_resumesearch_key_first_choice') ? 'precise' : 'full';
        if($sort = trim($this->params['displayorder'])){
            $sort = explode('>',$sort);
            if(!$order = $this->order_array[$sort[0]]) $order = $this->default_order;
            if($sort[1]=='desc'){
                $sort[1]="desc";
            }elseif($sort[1]=="asc"){
                $sort[1]="asc";
            }else{
                $sort[1]="desc";
            }
            $this->order = str_replace('%s',$sort[1],$order);
		}else{
            $this->order = $this->default_order;
        }
        $map = array();
        if(C('SUBSITE_VAL.s_id') > 0 && !$this->params['citycategory']){
            $this->params['citycategory'] = C('SUBSITE_VAL.s_district');
        }
        //省市,职位,行业,标签，专业
		foreach(array(1=>'citycategory',2=>'jobcategory',3=>'trade',4=>'tag',5=>'major',6=>'age') as $v) {
			$name = '_where_'.$v;
            if(false !== $w = $this->$name(trim($this->params[$v])))  $map[] = $w;
		}
		//性别,是否照片简历,简历等级,简历更新时间
		foreach(array('sex'=>'sex','photo'=>'photo','talent'=>'talent','mob'=>'mobile_audit','nat'=>'nature','exp'=>'experience','wage'=>'wage') as $key=>$val) {
			if($d =  intval($this->params[$val])) $map[] = '+'.$key.$d;
		}
        if(C('qscms_resume_display') == 1){
            $map[] = '+audit1';
        }else{
            $map[] = '+(audit1 audit2)';
        }
        if($education = intval($this->params['education'])){
            $category = D('Category')->get_category_cache();
            $w = '';
            foreach ($category['QS_education'] as $key => $val) {
                if($key >= $education) $w[] = 'edu'.$key;
            }
            if($w){
                $map[] = '+('.implode(' ',$w).')';
            }
        }
        /*if($experience = intval($this->params['experience'])){
            !$category && $category = D('Category')->get_category_cache();
            $w = '';
            foreach ($category['QS_experience'] as $key => $val) {
                if($key >= $experience) $map[] = 'exp'.$key;
            }
            if($w){
                $map[] = '+('.implode(' ',$w).')';
            }
        }*/
		if($refreshtime = intval($this->params['refreshtime'])) $this->where['refreshtime'] = array('gt',strtotime("-".$refreshtime."day"));
		if($settr = intval($this->params['settr'])) $this->where['refreshtime'] = array('gt',strtotime("-".$settr."day"));
        if(!empty($this->params['key'])){
            $this->params['key'] = urldecode(urldecode($this->params['key']));
            $key = trim($this->params['key']);
            $key = get_tags($key);
			if($this->params['search_type'] == 'full'){
				$this->field = 'id,MATCH (`key`) AGAINST ("'.implode(' ',$key).'") as score';
				// !$this->params['displayorder'] && $this->order = '`score` desc';
			}
            D('Hotword')->set_inc_batch($key);
            if(C('apply.Recommend')){
                $class = new \Recommend\Controller\IndexController();
                $class->set_resume($this->params['key']);
            }
            if('or' == $this->params['key_type']){
                $map[] = '+('.implode(' ',$key).')';
            }else{
                foreach ($key as $k => $v) {
                    $key[$k] = '+'.$v;
                }
                $map = array_merge($map,$key);
            }
        }else{
            $this->field = 'id';
        }
        //屏蔽企业
        if(C('visitor.utype') == 1){
            $companyname = M('CompanyProfile')->where(array('uid'=>C('visitor.uid')))->getfield('companyname');
            $companyname = get_tags($companyname,100,true,true);
            foreach ($companyname as $key => $val) {
                $like_str[] = '%'.$val.'%';
            }
            $uids = M('PersonalShieldCompany')->where(array('comkeyword'=>array('like',$like_str)))->getfield('uid',true);
            $uids && $this->where['uid'] = array('not in',array_unique($uids));
        }
		//签到人才
		if($this->params['fair_id'] && $this->params['sign']){//招聘会签到的企业
			$fair_id = $this->params['fair_id'];
			$hold_time_info = M('jobfair')->where(array('id'=>$fair_id))->field('holddate_start,holddate_end')->find();
			$wh = array('jobfair_id'=>$fair_id,'utype'=>2);
			if($hold_time_info){
				isset($hold_time_info['holddate_start']) && $wh['createtime'][]=array('egt',$hold_time_info['holddate_start']);
				isset($hold_time_info['holddate_end']) && $wh['createtime'][]=array('elt',$hold_time_info['holddate_end']);
			}
			$ids = M('jobfairsignin')->where($wh)->order('createtime desc')->getField('uid',true);
			$this->where['uid'] = array('in',$ids);
		}
        if($map) $this->where['key'] = array('match_mode',$map);
		$this->mod = $this->params['search_type'] == 'full' ? 'resume_search_full' : 'resume_search_precise';
		isset($this->params['firstRow']) && $this->firstRow = intval($this->params['firstRow']);
        isset($this->params['spage_size']) && $this->spage_size = intval($this->params['spage_size']);
        $this->params['dot'] = isset($this->params['dot']) ? trim($this->params['dot']) : '...';
        $this->params['showname'] = isset($this->params['showname']) ? $this->params['showname'] : 'QS_resumeshow';
        $this->params['listpage'] = isset($this->params['listpage']) ? $this->params['listpage'] : 'QS_resumelist';
    }
    /**
     * [list description]
     * @param  [type] $options [description]
     */
    public function get_max_page(){
        $varPage      =   C('VAR_PAGE') ? C('VAR_PAGE') : 'p' ;
        if(!isset($_GET[$varPage])) $varPage = 'page';
        $page = I('request.'.$varPage);
        if($page <= 3){
            $p = 5;
        }else{
            $p = 2 + $page;
        }
        $db_pre = C('DB_PREFIX');
        $model = new \Think\Model;
        for($i = $p;$i>=$page;$i--){
            $firstRow = abs($i - 1) * $this->spage_size;
            $s = $model->field('r.id')->Table($db_pre.$this->mod.' r')->where($this->where)->join($this->join)->limit($firstRow . ',1')->select();
            if($s) return $i * $this->spage_size;
        }
    }
    public function run($options){
    	$db_pre = C('DB_PREFIX');
    	$model = new \Think\Model;
		if($this->params['page']){
            if($this->params['readresume']){
                $result['total'] = count(cookie('view_resume_log'));
            }else{
                if(C('qscms_resumesearch_mod')){
                    if($result['total'] = $this->get_max_page()){
                        if (C('qscms_resume_list_max') > 0){
                            $result['total'] > intval(C('qscms_resume_list_max')) && $result['total']=intval(C('qscms_resume_list_max'));
                        }
                        $pager = pager($result['total'],$this->spage_size);
                        $this->firstRow = abs($pager->firstRow - 1) * $this->spage_size;
                        $son_map = $this->where;
                        $son_map['_string'] = 'r.id=b.id';
                        $sql = $model->Table($db_pre.$this->mod.' b')->field('b.id')->where($son_map)->join($this->join)->select(false);
                        $resume = $model->Table($db_pre.$this->mod.' r')->field($this->field)->where('EXISTS ('.$sql.')')->order($this->order)->limit($pager->firstRow . ',' .$this->spage_size)->select();
                        // var_dump($model->getLastSql()); 
                        $pager->path = $this->params['listpage'];
                        $pager->showname = $this->params['listpage'];
                        $result['page'] = $pager->fshow();
                        $result['page_params'] = $pager->get_page_params();
                    }else{
                        $result['page'] = '';
                    }
                }else{
                    if($result['total'] = $model->Table($db_pre.$this->mod.' r')->where($this->where)->join($this->join)->count('id')){
                        if (C('qscms_resume_list_max') > 0){
                            $result['total'] > intval(C('qscms_resume_list_max')) && $result['total']=intval(C('qscms_resume_list_max'));
                        }
                        $pager = pager($result['total'],$this->spage_size);
                        $this->firstRow = abs($pager->firstRow - 1) * $this->spage_size;
                        $resume = $model->Table($db_pre.$this->mod.' r')->field($this->field)->where($this->where)->join($this->join)->order($this->order)->limit($pager->firstRow . ',' .$this->spage_size)->select();

                        $pager->path = $this->params['listpage'];
                        $pager->showname = $this->params['listpage'];
                        $result['page'] = $pager->fshow();
                        $result['page_params'] = $pager->get_page_params();
                    }else{
                        $result['page'] = '';
                    }
                }
            }
        }else{
            $resume = $model->Table($db_pre.$this->mod.' r')->field($this->field)->where($this->where)->join($this->join)->order($this->order)->limit($this->firstRow . ',' .$this->spage_size)->select();
            $result['page'] = '';
            $result['total'] = 0;
        }
        if($this->params['readresume']){
            $rids = cookie('view_resume_log');
        }else{
        	foreach ($resume as $key => $val) {
        		$rids[] = $val['id'];
        	}
        }
        if($rids){
            $field = 'uid,id,display,strong_tag,stick,display_name,nature_cn,fullname,sex,major_cn,specialty,intention_jobs,trade_cn,photo,photo_img,photo_display,refreshtime,birthdate,tag_cn,talent,education_cn,sex_cn,wage,wage_cn,experience_cn,district_cn,current_cn';
            $resume = M('Resume')->where(array('id'=>array('in',$rids)))->order('field(id,'.implode(',',$rids).')')->limit($this->spage_size)->field($field)->select();
            foreach ($resume as $key => $value) {
                $mids[] = $value['id'];
            }
            if($mids){
            	$mobile = M('Members')->where(array('uid'=>array('in',$mids)))->limit(count($mids))->getfield('uid,mobile_audit');
            }
            $language = M('ResumeLanguage')->where(array('pid'=>array('in',$rids)))->getfield('pid,id,uid,language,language_cn,level,level_cn');
            $category = D('Category')->get_category_cache('QS_wage');
            foreach ($resume as $key => $val) {
                if ($val['display_name']== 2){
					$val['fullname']="N".str_pad($val['id'],7,"0",STR_PAD_LEFT);
					$val['fullname_']=$val['fullname'];
				}elseif($val['display_name']==3){ 
					if($val['sex']==1){
						$val['fullname']=cut_str($val['fullname'],1,0,"先生");
					}elseif($val['sex'] == 2){
						$val['fullname']=cut_str($val['fullname'],1,0,"女士");
					}else{
						$val['fullname']=cut_str($val['fullname'],1,0,"**");
					}	
				}else{
					$val['fullname_']=$val['fullname'];
					$this->params['namelen'] && $val['fullname']=cut_str($val['fullname'],$this->params['namelen'],0,$this->params['dot']);
				}
				$val['specialty_']=strip_tags($val['specialty']);
				if ($this->params['specialtylen']>0){
					$val['specialty']=cut_str(strip_tags($val['specialty']),$this->params['specialtylen'],0,$this->params['dot']);
				}
				$val['intention_jobs_'] = $val['intention_jobs'];
				if ($this->params['jobslen']>0){
					$val['intention_jobs']=cut_str(strip_tags($val['intention_jobs']),$this->params['jobslen'],0,$this->params['dot']);
				}
				if ($this->params['majorlen']>0){
					$val['major_cn']=cut_str(strip_tags($val['major_cn']),$this->params['majorlen'],0,$this->params['dot']);
				}
				$val['trade_cn_'] = $val['trade_cn'];
				$val['trade_cn'] = cut_str(strip_tags($val['trade_cn']),10,0,"..");
				$val['resume_url']=url_rewrite($this->params['showname'],array('id'=>$val['id']));
				$val['refreshtime_cn']=daterange(time(),$val['refreshtime'],'Y-m-d',"#FF3300");
				$val['age']=date("Y")-$val['birthdate'];
				if ($val['tag_cn']){
					$val['tag_cn']=explode(',',$val['tag_cn']);
				}else{
					$val['tag_cn']=array();
				}
                $val['strong_tag'] = $val['strong_tag']>0?M('PersonalServiceTagCategory')->where(array('id'=>$val['strong_tag']))->getField('name'):'';
                $default_avatar = $val['sex']==1?'no_photo_male.png':'no_photo_female.png';
				// 照片显示方式
				if ($val['photo']==1){
					if($val['photo_display']==1 && $val['photo_img']){
						$val['photosrc']=attach($val['photo_img'],'avatar');
					}else{
						$val['photosrc']=attach($default_avatar,'resource');
					}
				}else{
					$val['photosrc']=attach($default_avatar,'resource');
				}
                if(C('qscms_wage_unit') == 2){
                    $val['wage_cn'] = $category[$val['wage']];
                }
				//判断手机是否验证
				$val['is_audit_mobile'] = $mobile[$val['uid']];
				//语言能力
				$val['language'] = $language[$val['id']];
				$list[] = $val;
            }
            $result['list'] = $list;
        }else{
            $result['list'] = '';
        }
		return $result;
    }
    protected function _parse($k,$str){
        if($str){
        	if(is_array($str)){
        		$arr = array_slice($str,0,20);
        	}else{
        		$arr = explode(',',$str);
        		$arr = array_slice($arr,0,10);
        	}
            foreach($arr as $v) {
                $a = explode('.',$v);
                $t = range(0,2);
                foreach ($t as $key => $val) {
                	$t[$key] = isset($a[$key]) ? intval($a[$key]) : 0;
                }
		    	for($i = 2;$i>=0;$i--){
		    		$d[] = $k.implode('_',$t);
		    		unset($t[$i]);
		    	}
            }
            return $d ? '+('.implode(' ',array_unique($d)).')' : false;
        }
        return false;
    }
    protected function _where_citycategory($data){
        if($data){
            if (!strpos($data,".")){
                if(APP_SPELL && !fieldRegex($data,'in')){
                    if(false === $result = F('city_cate_list')) $result = D('CategoryDistrict')->city_cate_cache();
                    $arr=explode(",",$data);
                    foreach ($arr as $key => $val) {
                        $arr[$key] = $result['spell'][$val]['id'];
                    }
                }else{
                    if(fieldRegex($data,'in')){
                        $arr=explode(",",$data);
                    }
                }
                $arr=array_unique($arr);
                if($arr){
                    if(false === $city_cate = F('city_search_cate')) $city_cate = D('CategoryDistrict')->city_search_cache();
                    foreach ($arr as $key => $val) {
                        if(C('SUBSITE_VAL.s_id') > 0){
                            $reg = strpos(C('SUBSITE_VAL.s_district'),',') ? '/^\_'.str_replace(',','|',C('SUBSITE_VAL.s_district')).'\_/' : '_'.C('SUBSITE_VAL.s_district').'_';
                            if(preg_match($reg,'_'.$city_cate[$val].'_') !== 1) continue;
                        }
                        $s[] = str_replace('_','.',$city_cate[$val]);
                    }
                }
                return $this->_parse('city',$s);
            }else{
                return $this->_parse('city',$data);
            }
        }else{
            $district = $sdistrict = $tdistrict = array();
            if (trim($this->params['district'])){
            	if (fieldRegex($this->params['district'],'in')){
                    $arr=explode(",",$this->params['district']);
                    $district = array_slice($arr,0,20);
                }
            }
            if (trim($this->params['sdistrict'])){
            	if (fieldRegex($this->params['sdistrict'],'in')){
                    $arr=explode(",",$this->params['sdistrict']);
                    $sdistrict = array_slice($arr,0,20);
                }
            }
            if (trim($this->params['tdistrict'])){
            	if (fieldRegex($this->params['tdistrict'],'in')){
                    $arr=explode(",",$this->params['tdistrict']);
                    $tdistrict = array_slice($arr,0,20);
                }
            }
            if($d = array_merge($district,$sdistrict,$tdistrict)){
            	$d=array_unique($d);
            	if(false === $city_cate = F('city_search_cate')) $city_cate = D('CategoryDistrict')->city_search_cache();
            	foreach ($d as $key => $val) {
            		$s[] = 'city'.$city_cate[$val];
            	}
            }
            return $s ? '+('.implode(' ',array_unique($s)).')' : false;
        }
    }
    protected function _where_jobcategory($data){
        if($data){
            if (!strpos($data,".")){
                if(false === $result = F('jobs_cate_list')) $result = D('CategoryJobs')->jobs_cate_cache();
                if(!fieldRegex($data,'in')){
                    $arr=explode(",",$data);
                    foreach ($arr as $key => $val) {
                        $arr[$key] = $result['spell'][$val]['id'];
                    }
                }
                $arr=array_unique($arr);
                if(false === $jobs_cate = F('jobs_search_cate')) $jobs_cate = D('CategoryJobs')->jobs_search_cache();
                foreach ($arr as $key => $val) {
                    $s[] = str_replace('_','.',$jobs_cate[$val]);
                }
                return $this->_parse('jobs',$s);
            }else{
                return $this->_parse('jobs',$data);
            }
        }else{
            $topclass = $category = $subclass = array();
            if (trim($this->params['topclass'])){
            	if (fieldRegex($this->params['topclass'],'in')){
                    $arr=explode(",",$this->params['topclass']);
                    $topclass = array_slice($arr,0,20);
                }
            }
            if (trim($this->params['category'])){
            	if (fieldRegex($this->params['category'],'in')){
                    $arr=explode(",",$this->params['category']);
                    $category = array_slice($arr,0,20);
                }
            }
            if (trim($this->params['subclass'])){
            	if (fieldRegex($this->params['subclass'],'in')){
                    $arr=explode(",",$this->params['subclass']);
                    $subclass = array_slice($arr,0,20);
                }
            }
            if($d = array_merge($topclass,$category,$subclass)){
            	$d=array_unique($d);
            	if(false === $jobs_cate = F('jobs_search_cate')) $jobs_cate = D('CategoryJobs')->jobs_search_cache();
            	foreach ($d as $key => $val) {
            		$s[] = 'jobs'.$jobs_cate[$val];
            	}
            }
            return $s ? '+('.implode(' ',array_unique($s)).')' : false;
        }
    }
    protected function _where_trade($data){
        if($data){
            if (strpos($data,',')){
                $arr = explode(',',$data);
                $arr=array_unique($arr);
                $arr = array_slice($arr,0,10);
                $arr = array_map('intval', $arr);
                $sqlin = implode(' trade',$arr);
                return '+(trade'.$sqlin.')';
            }else{
                return '+trade'.intval($data);
            }
        }
        return false;
    }
    protected function _where_tag($data){
    	if($data){
            if (strpos($data,',')){
                $arr = explode(',',$data);
                $arr=array_unique($arr);
                $arr = array_slice($arr,0,10);
                $sqlin = implode(' tag',$arr);
                return '+(tag'.$sqlin.')';
            }else{
                return '+tag'.intval($data);
            }
        }
        return false;
    }
    protected function _where_major($data){
    	if($data){
            if (strpos($data,',')){
                $arr = explode(',',$data);
                $arr=array_unique($arr);
                $arr = array_slice($arr,0,10);
                $arr = array_map('intval', $arr);
                $sqlin = implode(' major',$arr);
                return '+(major'.$sqlin.')';
            }else{
                return '+major'.intval($data);
            }
        }
        return false;
    }
    protected function _where_age($data){
        if($data){
            $category = D('Category')->get_category_cache('QS_age');
            $age = $category[intval($data)];
            if(!$s = preg_match_all('(\d+)',$age,$reg)) return false;
            $reg = $reg[0];
            if(!$reg[1]) $reg[1] = $reg[0] + 15;
            $num = $reg[1] - $reg[0];
            for($reg[0];$reg[0] <= $reg[1];$reg[0]++){
                $arr[] = 'bir'.(Date('Y') - intval($reg[0]));
            }
            if($arr) return '+('.implode(' ',$arr).')';
        }
        return false;
    }
}