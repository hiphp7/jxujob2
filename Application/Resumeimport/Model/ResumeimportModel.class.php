<?php
namespace Resumeimport\Model;
use Think\Model;
class ResumeimportModel extends Model{
	//获取采集配置数据二维数组
	public function load(){
		$data=$this->select();
		return $data;
	}
	//获取采集配置数据一维数组
	public function find_data(){
		$data=$this->load();
		$new_arr=array();
		foreach($data as $k=>$v){
			$new_arr[$v['name']]=$v['value'];
		}
		return $new_arr;
	}
	/*新增文章*/
	public function addArticle($post){
		return M("article")->add($post);
	}
	/*新增职位*/
	public function addJob($post){
		return M("jobs")->add($post);
	}
	
	/*新增搜索*/
	public function addJobSearch($post){
		return M("jobs_search")->add($post);
	}
	
	public function addJobSearchKey($post){
		return M("jobs_search_key")->add($post);
	}
	
	//检测文章标题是否已存在
	public function isTitExist($title){
		$res=M("article")->where(array("title"=>$title))->count();
		return $res?true:false;
	}
	
	//获取文章分类上级id
	public function getArticlePid($id){
		$res=M("article_category")->where(array("id"=>$id))->find();
		return $res['parentid'];
	}
	
	//获取公司信息
	public function getCompanyInfo($name){
		$res=M("company_profile")->where(array("companyname"=>$name))->find();
		return $res;
	}
	
	//添加职位
	public function addJobs($companyinfo){
		$locoyspider=$this->find_data();
		
		$post['uid']=$companyinfo['uid'];		
		$post['companyname']=$companyinfo['companyname'];
		$post['company_id']=$companyinfo['id'];		
		$post['company_addtime']=$companyinfo['addtime'];
		
		$post['jobs_name']=trim(cut_str($_POST['jobs_name'],25,0));
		if (empty($post['jobs_name']))  {
			$msg="职位名称丢失";
			exit($msg);
		}
		/*if ($this->isJobnameExist($post['jobs_name'],$post['uid'])) {
			$msg="职位名称有重复";
			exit($msg);
		}*/
		$_POST['jobs_contents']=str_replace("💰","",$_POST['jobs_contents']);
		$post['contents']=$this->html2text($_POST['jobs_contents']);
		$nature=$this->getJobsNature(trim($_POST['jobs_nature']));
		$post['nature']=$nature['id'];
		$post['nature_cn']=$nature['cn'];
		$sex=$this->getJobsSex(trim($_POST['jobs_sex']));
		$post['sex']=$sex['id'];
		$post['sex_cn']=$sex['cn'];
		
		$post['map_x']=$companyinfo['map_x'];
		$post['map_y']=$companyinfo['map_y'];
		//年龄要求
		
		if(empty($_POST['jobs_age'])){
			$post['age']="-";
		}else{
			$newstr =substr($_POST['jobs_age'],0,5);
			$post['age']=$newstr;
		}
		$post['amount']=$this->getJobsAmount(trim($_POST['jobs_amount']));
		$jobs_category=trim($_POST['jobs_category'])?trim($_POST['jobs_category']):$post['jobs_name'];
		$category=$this->getJobsCategory($jobs_category);
		//一级
		$post['topclass']=$category['topclass'];
		$post['category']=$category['category'];
		$post['subclass']=$category['subclass'];
		$post['category_cn']=$category['category_cn'];
		$post['trade']=$companyinfo['trade'];
		$post['trade_cn']=$companyinfo['trade_cn'];
		$district=$this->getDistrict(trim($_POST['jobs_district']),'jobs_district');
		$post['scale']=$companyinfo['scale'];
		$post['scale_cn']=$companyinfo['scale_cn'];
		$post['district']=$district['district'];
		$post['sdistrict']=$district['sdistrict'];
		$post['district_cn']=$district['district_cn'];
		//街道id 和 街道
		$post['street']=$companyinfo['street'];		
		$post['street_cn']=$companyinfo['street_cn'];
		
		$education=$this->getJobsEducation(trim($_POST['jobs_education']));
		$post['education']=$education['id'];
		$post['education_cn']=$education['cn'];
		
		$experience=$this->getExperience(trim($_POST['jobs_experience']));
		$post['experience']=$experience['id'];	
		$post['experience_cn']=$experience['cn'];
		
		$wage=explode("-",trim($_POST['jobs_wage']));
		
		$post['minwage']=intval($$wage[0]);
		$post['maxwage']=intval($$wage[1]);
		
		$post['addtime']=time();
		$post['deadline']=$this->getJobsDeadline();
		$post['refreshtime']=time();
		
		$key = get_tags($post['jobs_name'].$companyinfo['companyname'].$post['category_cn'].$post['district_cn'],100,true);
		$key1 = get_tags($post['contents'],100);
		$key = array_merge($key,$key1);
		$key = implode(' ',array_unique($key));
		$post['key'] = $key;
		$post['audit']=$locoyspider['jobs_audit'];
		$post['display']=$locoyspider['jobs_display'];
		$post['robot']=1;
		$pid=$this->addJob($post);
		
		if (!$pid) {
			$msg="添加招聘信息失败";
			exit($msg);
		}
		//职位联系方式
		$post_contact['contact']=trim($companyinfo['contact']);
		//QQ
		$post_contact['qq']=trim($_POST['qq']);
		if($companyinfo['telephone']){
			$post_contact['telephone']=trim($companyinfo['telephone']);
		}
		$post_contact['address']=trim($companyinfo['address']);
		if($companyinfo['email']){
			$post_contact['email']=$this->check_email(trim($companyinfo['email']));
		}
		
			//3.4新增字段,3.5也有
		$post_contact['contact_show']=1;
		$post_contact['telephone_show']=1;
		$post_contact['email_show']=1;
		$post_contact['address_show']=1;
		$post_contact['qq_show']=1;

		$post_contact['notify']=$locoyspider['jobs_notify'];
		$post_contact['pid']=$pid;
		$rst_contact =M('jobs_contact')->add($post_contact);
		
		if(!$rst_contact){
			$msg="添加招聘联系方式失败";
			exit($msg);
		}
		
		$searchtab['id']=$pid;
		$searchtab['uid']=$post['uid'];
		$searchtab['audit']=$post['audit'];
		$searchtab['emergency']=$post['emergency'];
		$searchtab['nature']=$post['nature'];
		$searchtab['sex']=$post['sex'];
		$searchtab['topclass']=$post['topclass'];
		$searchtab['category']=$post['category'];
		$searchtab['subclass']=$post['subclass'];
		$searchtab['trade']=$post['trade'];
		$searchtab['district']=$post['district'];
		$searchtab['sdistrict']=$post['sdistrict'];	
		$searchtab['tdistrict']=$post['tdistrict'];	
		$searchtab['street']=$companyinfo['street'];	
		$searchtab['education']=$post['education'];
		$searchtab['experience']=$post['experience'];
		$searchtab['minwage']=$post['minwage'];
		$searchtab['maxwage']=$post['maxwage'];
		$searchtab['scale']=$post['scale'];
		$searchtab['map_x']=$post['map_x'];
		$searchtab['map_y']=$post['map_y'];
		$searchtab['refreshtime']=$post['refreshtime'];
		$searchtab['key']=$post['key'];
		
		if(!$this->addJobSearchKey($searchtab)) {
			$msg="添加职位关键字搜索表失败";
			exit($msg);
		}
		
		unset($searchtab['key']);
		$searchtab['addtime']=$post['addtime'];
		
		$searchtab['jobs_name']=$post['jobs_name'];
		$searchtab['companyname']=$post['companyname'];
		if(!$this->addJobSearch($searchtab)) {
			$msg="添加职位搜索表失败";
			exit($msg);
		}
		
		//D("Jobs")->distribution_jobs($pid,$post['uid']);
		exit("添加成功");
	}
	
	//添加企业
	public function addCompany($companyname){
		
		$locoyspider=$this->find_data();
		$setsqlarr['uid']=$this->userRegister($this->check_email(trim($_POST['email'])));
		if ($setsqlarr['uid']=="") {
			$msg="添加会员出错";
			exit($msg);
		}
		$setsqlarr['companyname']=$companyname;
		
		$nature=$this->getJobsNature(trim($_POST['nature']));
		$setsqlarr['nature']=$nature['id'];
		$setsqlarr['nature_cn']=$nature['cn'];
		
		$trade=$this->getCompanyTrade(trim($_POST['trade']));
		$setsqlarr['trade']=$trade['id'];
		$setsqlarr['trade_cn']=$trade['cn'];
		
		$district=$this->getDistrict(trim($_POST['district']),'company_district');
		$setsqlarr['district']=$district['district'];
		$setsqlarr['sdistrict']=$district['sdistrict'];
		$setsqlarr['tdistrict']=$district['tdistrict'];
		$setsqlarr['district_cn']=$district['district_cn'];
		
		$scale=$this->getCompanyScale(trim($_POST['scale']));
		$setsqlarr['scale']=$scale['id'];
		$setsqlarr['scale_cn']=$scale['cn'];
		
		if($_POST['map_x']){
			$setsqlarr['map_x']=$_POST['map_x'];
		} else {
			$setsqlarr['map_x']="0.00";
		}
		if($_POST['map_y']){
			$setsqlarr['map_y']=$_POST['map_y'];
		} else {
			$setsqlarr['map_y']="0.00";
		}
		
		$registered=$this->getCompanyRegistered(trim($_POST['registered']));
		$setsqlarr['registered']=$registered['registered'];//注册资金
		$setsqlarr['currency']=$registered['currency'];//注册资金单位（人民币 or 美元）
		$setsqlarr['address']=trim($_POST['address']);
		$setsqlarr['contact']='';
		$setsqlarr['telephone']='';
		$setsqlarr['email']='';
		$setsqlarr['website']=trim($_POST['website']);
		$setsqlarr['contents']=$this->html2text($_POST['contents']);
		$setsqlarr['audit']=intval($locoyspider['company_audit']);
		$setsqlarr['addtime']=time();
		$setsqlarr['refreshtime']=time();
		$setsqlarr['user_status']=1;
		$setsqlarr['robot']=1;
			//3.4新增字段,3.5也有
		$setsqlarr['contact_show']=1;
		$setsqlarr['telephone_show']=1;
		$setsqlarr['email_show']=1;
		$setsqlarr['address_show']=1;
		$res=M("company_profile")->add($setsqlarr);
		if (!$res) {
			$msg="添加企业出错";
			exit($msg);
		}
		return true;
	}
	
	//采集注册会员
	private function userRegister($email=NULL,$utype='1'){
		$locoyspider=$this->find_data();
		$members=D("Members");
		$setsqlarr['username']=$locoyspider['reg_usname'].uniqid().time();
		$setsqlarr['pwd_hash']=$this->resRandStr();
		//密码=用户名
		if ($locoyspider['reg_password_tpye']=="1"){
			$pwd=$setsqlarr['username'];
		}elseif ($locoyspider['reg_password_tpye']=="3"){
			$pwd=$locoyspider['reg_password'];//密码=固定设置值
		}else{
			$pwd=$this->resRandStr(7);//长度为7的随机字符串
		}
		//email
		if (empty($email) || !preg_match("/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/",$email)){
			$email=time().uniqid().$locoyspider['reg_email'];
		}
		$setsqlarr['password']=$members->make_md5_pwd($pwd,$setsqlarr['pwd_hash']);
		$setsqlarr['email']=$email;
		$setsqlarr['utype']=$utype;
		$setsqlarr['reg_time']=time();
		$setsqlarr['robot']=1;//标记为采集
		$reg_id=M("Members")->add($setsqlarr);
		if (!$reg_id) return false;
		$setsqlarr['uid']=$reg_id;
		$members->user_register($setsqlarr);
		return $reg_id;
	}
	
	//获取随机字符串
	private function resRandStr($length=6)
	{
		$hash='';
		$chars= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz@#!~?:-=';   
		$max=strlen($chars)-1;   
		mt_srand((double)microtime()*1000000);   
		for($i=0;$i<$length;$i++){   
			$hash.=$chars[mt_rand(0,$max)];   
		}   
		return $hash;   
	}
	
	//检测职位名称是否有重复
	private function isJobnameExist($name,$uid){
		$res=M("jobs")->where(array("jobs_name"=>$name,"uid"=>$uid))->count();
		return $res?true:false;
	}
	
	//匹配职位性质
	private function getJobsNature($str=NULL){
		$locoyspider=$this->find_data();
		$info=M("category")->where(array("c_alias"=>'QS_jobs_nature',"c_id"=>$locoyspider['jobs_nature']))->find();
		$default=array("id"=>$info['c_id'],"cn"=>$info['c_name']);
		if (empty($str))
		{
			return $default;
		}
		else
		{
			
			$info=M("category")->where(array("c_alias"=>'QS_jobs_nature'))->select();
			$return=$this->searchStr($info,$str,"c_name");
			if ($return){
				return array("id"=>$return['c_id'],"cn"=>$return['c_name']);
			}else{
				return $default;
			}
		}
	}
	
	//匹配职位 性别
	private function getJobsSex($sex_cn=NULL,$sex=NULL){
		$locoyspider=$this->find_data();
		if ($sex_cn=="男" || $sex=="1"){
			return array("id"=>1,"cn"=>"男");
		}elseif ($sex_cn=="女" ||  $sex=="2"){
			return array("id"=>2,"cn"=>"女");
		}elseif ($sex_cn=="不限"  ||  $sex=="3"){
			return array("id"=>3,"cn"=>"不限");
		}
		else{
			if($locoyspider['jobs_sex']=="0"){
				return $this->getJobsSex("",3);
			}else{
				return $this->getJobsSex("",intval($locoyspider['jobs_sex']));
			}
		}
	}
	
	//匹配职位 招聘人数
	private function getJobsAmount($str=NULL){
		$locoyspider=$this->find_data();
		$str=intval($str);
		if ($str>0){
			return $str;
		}else{
			return mt_rand(intval($locoyspider['jobs_amount_min']),intval($locoyspider['jobs_amount_max']));
		}
	}
	
	//匹配职位分类
	private function getJobsCategory($str=NULL){
		$locoyspider=$this->find_data();
		$info=M("category_jobs")->where(array("id"=>$locoyspider['jobs_subclass']))->find();
		$info2=M("category_jobs")->where(array("id"=>$info['parentid']))->find();
		$default=array("topclass"=>$info2['id'],"category"=>$info['id'],"subclass"=>0,"category_cn"=>$info['categoryname']);
		if (empty($str)){
			return $default;
		}else{
			
			$info=M("category_jobs")->select();
			$return=$this->searchStr($info,$str,"categoryname");
			//匹配到了
			if(!empty($return)){
				//它是一级的情况
				if($return['parentid']==0){
					return array("topclass"=>$return['id'],"category"=>0,"subclass"=>0,"category_cn"=>$return['categoryname']);
				}else{
					
					$info2=M("category_jobs")->where(array("id"=>$return['parentid']))->find();
					//二级的情况
					if($info2['parentid']==0){
						return array("topclass"=>$return['id'],"category"=>$info2['id'],"subclass"=>0,"category_cn"=>$return['categoryname']);
					}else{//三级的情况
						$info3=M("category_jobs")->where(array("id"=>$info2['parentid']))->find();
						return array("topclass"=>$info3['id'],"category"=>$info2['id'],"subclass"=>$return['id'],"category_cn"=>$return['categoryname']);
					}
				}
			}else{
				return $default;//没有匹配到
			}
			
		}
	}
	
	//匹配工作地区
	private function getDistrict($str=NULL,$type){
		$locoyspider=$this->find_data();
		$info=explode(".",$locoyspider[$type]);
		$default=array("district"=>$info[0],"sdistrict"=>$info[1],"tdistrict"=>$info[2],"district_cn"=>$info['categoryname']);
		if (empty($str)){
			return $default;
		}else{
			$info=M("category_district")->select();
			$return=$this->searchStr($info,$str,"categoryname");
			if ($return){
				$t=M("category_district")->where(array("id"=>$return['parentid']))->find();
				if($t['parentid']==0){
					$data['district']=$return['parentid'];
					$data['sdistrict']=$return['id'];
					$data['tdistrict']=0;
					$data['district_cn']=$return['categoryname'];
				} else {
					
					$data['district']=$t['parentid'];
					$data['sdistrict']=$return['parentid'];
					$data['tdistrict']=$return['id'];
					$data['district_cn']=$return['categoryname'];
				}
				return $data;
			}else{
				return $default;
			}
		}
	}
	
	
	
	//匹配要求学历
	private function getJobsEducation($str=NULL){
		$locoyspider=$this->find_data();
		$info=M("category")->where(array("c_alias"=>"QS_education","c_id"=>$locoyspider['jobs_education']))->find();
		$default=array("id"=>$info['c_id'],"cn"=>$info['c_name']);
		if (empty($str)){
			return $default;
		}else{
			$info=M("category")->where(array("c_alias"=>"QS_education"))->select();
			$return=$this->searchStr($info,$str,"c_name");
			if ($return){
				return array("id"=>$return['c_id'],"cn"=>$return['c_name']);
			}else{
				return $default;
			}
		}
	}
	//匹配要求工作经验3(适合51job采集)
	private function getExperience_2($str=NULL){
		$str=intval($str);
		$arr=array();
		if($str=="1年" || $str=="2年"){
			$arr=array('id'=>76,'cn'=>'1-3年');
		}elseif($str=="3-4年"){
			$arr=array('id'=>77,'cn'=>'3-5年');
		}elseif($str=="5-7年" || $str=="8-9年"){
			$arr=array('id'=>78,'cn'=>'5-10年');
		}elseif($str=="10年以上"){
			$arr=array('id'=>79,'cn'=>'10年以上');
		}else{
			$arr=array('id'=>74,'cn'=>'无经验');
		}
		return $arr;
	}

	//匹配要求工作经验4(适合中华英才网采集)
	private function getExperience($str=NULL){
		$locoyspider=$this->find_data();
		$info=M("category")->where(array("c_alias"=>"QS_experience","c_id"=>$locoyspider['jobs_experience']))->find();
		$default=array("id"=>$info['c_id'],"cn"=>$info['c_name']);
		if (empty($str)){
			return $default;
		}else{
			$str=trim($str);
			$arr=array();
			if($str=="经验在读生"){
				$arr=array('id'=>74,'cn'=>'无经验');
			}elseif($str=="经验应届毕业生"){
				$arr=array('id'=>75,'cn'=>'1年以下');
			}elseif($str==" 经验1年"){
				$arr=array('id'=>77,'cn'=>'1-3年');
			}elseif($str==" 经验2年"){
				$arr=array('id'=>77,'cn'=>'3-5年');
			}elseif($str=="经验3年" || $str=="经验4年"){
				$arr=array('id'=>78,'cn'=>'5-10年');
			}elseif($str=="经验5年"){
				$arr=array('id'=>79,'cn'=>'10年以上');
			}else{
				return $default;
			}
			return $arr;
		}
	}
	
	//修改后的匹配薪资待遇
	private function getJobsWage($str=NULL){
		$locoyspider=$this->find_data();
		
		$info=M("category")->where(array("c_alias"=>"QS_wage","c_id"=>$locoyspider['jobs_wage']))->find();
		$default=array("id"=>$info['c_id'],"cn"=>$info['c_name']);
		if (empty($str)){
			return $default;
		}else{
			$str = trim($str);
			if($str=='面议'){
				return array('id'=>55,'cn'=>'面议');
			}elseif($str=="1500以下"){
				return array('id'=>56,'cn'=>'1000~1500元/月');
			}elseif($str=="1500-1999"){
				return array('id'=>57,'cn'=>'1500~2000元/月');
			}elseif($str=="2000-2999"){
				return array('id'=>58,'cn'=>'2000~3000元/月');
			}elseif($str=="3000-4499" || $str=="3500+" || $str=="3500-6000"){
				return array('id'=>59,'cn'=>'3000~5000元/月');
			}elseif($str=="4500-5999" || $str=="5000-8000" || $str=="6000-7999" || $str=="8000-9999"){
				return array('id'=>60,'cn'=>'5000~10000元/月');
			}elseif($str=="10000-14999" || $str=="15000-19999"){
				return array('id'=>61,'cn'=>'一万以上/月');
			}else{
				return array('id'=>$info['c_id'],'cn'=>$info['c_name']);
			}
		}
	}
	
	//匹配企业行业
	function getCompanyTrade($str=NULL){	
		$locoyspider=$this->find_data();
		$info=M("category")->where(array("c_alias"=>"QS_trade","c_id"=>$locoyspider['company_trade']))->find();
		$default=array("id"=>$info['c_id'],"cn"=>$info['c_name']);
		if (empty($str)){
			return $default;
		}else{
			
			$info=M("category")->where(array("c_alias"=>"QS_trade"))->select();
			$return=$this->searchStr($info,$str,"c_name");
			if ($return){
				return array("id"=>$return['c_id'],"cn"=>$return['c_name']);
			}else{
				return $default;
			}
		}
	}
	
	//匹配企业注册资金
	function getCompanyRegistered($str=NULL){
		$locoyspider=$this->find_data();
		if (empty($str)){
			return array("registered"=>$locoyspider['company_registered'],"currency"=>$locoyspider['company_currency']);
		}else{
			return array("registered"=>$str,"currency"=>"");
		}
	}
	
	//匹配企业规模
	function getCompanyScale($str=NULL){
		$locoyspider=$this->find_data();
		$info=M("category")->where(array("c_alias"=>"QS_scale","c_id"=>$locoyspider['company_scale']))->find();
		$default=array("id"=>$info['c_id'],"cn"=>$info['c_name']);
		if (empty($str)){
			return $default;
		}elseif(trim($str)=='少于50人'){
			return array("id"=>'80',"cn"=>'20人以下');
		}elseif(trim($str)=='50-150人'){
			return array("id"=>'81',"cn"=>'20-99人');
		}elseif(trim($str)=='150-500人'){
			return array("id"=>'82',"cn"=>'100-499人');
		}elseif(trim($str)=='500-1000人'){
			return array("id"=>'83',"cn"=>'500-999人');
		}elseif(trim($str)=='1000-5000人' || trim($str)=='5000-10000人'){
			return array("id"=>'84',"cn"=>'1000-9999人');
		}elseif(trim($str)=='10000人以上'){
			return array("id"=>'85',"cn"=>'10000人以上');
		}else{
			return $default;		
		}
	}
	
	//生成到期时间
	private function getJobsDeadline(){
		$locoyspider=$this->find_data();
		$jobs_days_min=intval($locoyspider['jobs_days_min']);
		$jobs_days_max=intval($locoyspider['jobs_days_max']);
		if ($jobs_days_min==0 && $jobs_days_max==0){
			return strtotime("30 day");
		}else{
			return strtotime("".mt_rand($jobs_days_min,$jobs_days_max)." day");
		}
	}
	
	
	
	//模糊搜索
	private function searchStr($arr,$str,$arrinname){
		$locoyspider=$this->find_data();
		foreach ($arr as $key =>$list){
			similar_text($list[$arrinname],$str,$percent);
			$od[$percent]=$key;
		}
		krsort($od);
		foreach ($od as $key =>$li){
			if ($key>=$locoyspider['search_threshold']){
				return $arr[$li];
			}else{
				return false;
			}
		}	
	}
	/*
	 * 功能：修正矿工采集的图片生成的email
	 * params：待修正的email
	 * return：修正后的email
	 */
	private function check_email($val){
		$str_email = strtolower($val);
		$str_email=str_replace(' ','',$str_email);//删除掉半角空格
		$str_email=str_replace('　','',$str_email);//删除掉全角空格
		$str_email=str_replace('c0m','com',$str_email);
		$str_email=str_replace('-com','.com',$str_email);
		$str_email=stripslashes($str_email);
		$str_email=str_replace("co|\\",'com',$str_email);
		return $str_email;
	}
	//转换为纯文本
	private function html2text($str){
		$str = preg_replace("/<sty(.*)\\/style>|<scr(.*)\\/script>|<!--(.*)-->/isU","",$str);
		$alltext = "";
		$start = 1;
		for($i=0;$i<strlen($str);$i++)
		{
			if($start==0 && $str[$i]==">")
			{
				$start = 1;
			}
			else if($start==1)
			{
				if($str[$i]=="<")
				{
					$start = 0;
					$alltext .= " ";
				}
				else if(ord($str[$i])>31)
				{
					$alltext .= $str[$i];
				}
			}
		}
		$alltext = str_replace("　"," ",$alltext);
		$alltext = preg_replace("/&([^;&]*)(;|&)/","",$alltext);
		$alltext = preg_replace("/[ ]+/s"," ",$alltext);
		return $alltext;
	}
}
?>