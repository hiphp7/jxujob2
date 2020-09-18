<?php
namespace Resumeimport\Controller;
use Common\Controller\BackendController;
class ResumeimportController extends BackendController{
    public function _initialize() {
        parent::_initialize();
    }
    /**
     * 导入简历
     */
    public function import(){        
        //import("Org.Util.PHPExcel");
		import("Library.Org.Util.PHPExcel",dirname(__FILE__). '/../');
		$wbmb = $this->_upload($_FILES['wbmb'], 'wbmb/' . $date, array(
        'maxSize' => 200*1024,//word最大20M
        'uploadReplace' => true,
        'attach_exts' => 'xls'
        ));        
        $wbmb_name=$wbmb['info'][0]['savepath'].$wbmb['info'][0]['savename'];
		$PHPExcel=new \PHPExcel();
		import("Library.Org.Util.PHPExcel.Reader.Excel5",dirname(__FILE__). '/../');
		$PHPReader=new \PHPExcel_Reader_Excel5();
		$objPHPExcel=$PHPReader->load($wbmb_name);
		$currentSheet=$objPHPExcel->getSheet(0);//获取总列数
		$highestColumn=$currentSheet->getHighestColumn();//获取总行数
		$highestRow=$currentSheet->getHighestRow();
		for($i=3;$i<=$highestRow;$i++)
        {      
            if((string)$objPHPExcel->getActiveSheet()->getCell("R".$i)->getValue()){
                $personal['utype']="2";
                $personal['username']='dr'.(string)$objPHPExcel->getActiveSheet()->getCell("R".$i)->getValue(); 
                $personal['username']=str_replace(" ","",$personal['username']);
				$password='123456';
				$randstr='UU?D0P';
				$personal['password']=md5(md5($password).$randstr.C('PWDHASH'));
                $personal['pwd_hash']=$randstr;
                $personal['mobile']=(string)$objPHPExcel->getActiveSheet()->getCell("R".$i)->getValue(); 
                $personal['email']=(string)$objPHPExcel->getActiveSheet()->getCell("S".$i)->getValue(); 
                $personal['reg_time']=time();
                $personal['status']=1;
                $members=M('members')->where(array('username'=>$personal['username']))->find();
                if($members){
                $setsqlarr['uid']=$members['uid'];
                }else{
                $uid=M('members')->add($personal);
                $setsqlarr['uid']= $uid;
                }
                $setsqlarr['telephone']=(string)$objPHPExcel->getActiveSheet()->getCell("R".$i)->getValue(); 
                $setsqlarr['email']=(string)$objPHPExcel->getActiveSheet()->getCell("S".$i)->getValue();
                $setsqlarr['display']=1;
                $setsqlarr['display_name']=1;
                $setsqlarr['audit']=1;
                $setsqlarr['title']=(string)$objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue(); 
                $setsqlarr['fullname']=(string)$objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue(); 
                $setsqlarr['sex_cn']=(string)$objPHPExcel->getActiveSheet()->getCell("C".$i)->getValue(); 
                if($setsqlarr['sex_cn']=='男'){
                $setsqlarr['sex']="1";
                
                }elseif($setsqlarr['sex_cn']=='女'){
                $setsqlarr['sex']="2";
                }
                //工作性质
                $nature_cn=(string)$objPHPExcel->getActiveSheet()->getCell("J".$i)->getValue(); 
                if($nature_cn=='全职'){
                $setsqlarr['nature']=62;
                $setsqlarr['nature_cn']='全职';
                }elseif($nature_cn=='兼职'){
                $setsqlarr['nature']=63;
                $setsqlarr['nature_cn']='兼职';
                }else{
                $setsqlarr['nature']=63;
                $setsqlarr['nature_cn']='实习';
                }
                //出生日期
                $setsqlarr['birthdate']=(string)$objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue(); 
                //现居住地
                $setsqlarr['residence']=(string)$objPHPExcel->getActiveSheet()->getCell("T".$i)->getValue();
                //工作经验
                $experience=$this->get_experience((string)$objPHPExcel->getActiveSheet()->getCell("H".$i)->getValue());
                $setsqlarr['experience']=$experience['id'];                                                   //-------------所在街道
                $setsqlarr['experience_cn']=$experience['cn'];
                $jobs_c_arr=(string)$objPHPExcel->getActiveSheet()->getCell("U".$i)->getValue();
                $jobs_c_arr=explode(",",$jobs_c_arr);
                foreach($jobs_c_arr as $j){
                $jobs_c_arrs=$this->get_jobs_cat($j);
                $setsqlarr['intention_jobs_id'][]=$jobs_c_arrs['topclass'].'.'.$jobs_c_arrs['category'].'.'.$jobs_c_arrs['subclass'];
                $setsqlarr['intention_jobs'][]=$jobs_c_arrs['category_cn'];
                }
                $setsqlarr['intention_jobs_id']=implode(",",$setsqlarr['intention_jobs_id']);
                $setsqlarr['intention_jobs']=implode(",",$setsqlarr['intention_jobs']);
                
                if(strstr($setsqlarr['intention_jobs'], '不限职位')){
                $setsqlarr['wgwpp']=1;
                }
                $setsqlarr['photo']=0;
                $setsqlarr['photo_img']='';
                $setsqlarr['photo_audit']=1;
                $setsqlarr['photo_display']=1;
                $setsqlarr['addtime']=strtotime((string)$objPHPExcel->getActiveSheet()->getCell("W".$i)->getValue());
                if(I('post.gxsj')==1){
                    $setsqlarr['refreshtime']=time();
                }else{
                    $setsqlarr['refreshtime']=strtotime((string)$objPHPExcel->getActiveSheet()->getCell("W".$i)->getValue());
                }
                $setsqlarr['entrust']='';
                $setsqlarr['talent']='';
                $setsqlarr['level']=1;
                $setsqlarr['complete_percent']=50;
                $setsqlarr['current']=241;
                $setsqlarr['current_cn']='我目前已离职，可快速到岗';
                $setsqlarr['word_resume']='';
                $setsqlarr['click']=1;
                $setsqlarr['tpl']='';
                $setsqlarr['resume_from_pc']=1;
				$wages=(string)$objPHPExcel->getActiveSheet()->getCell("M".$i)->getValue();
                $wage=$this->get_wage((string)$objPHPExcel->getActiveSheet()->getCell("M".$i)->getValue());
				$setsqlarr['wage']=$wage['id']; 
				if($wage['cn']=='面议'){
					$setsqlarr['wage_cn']=$wage['cn'];
				}elseif($wage['cn']=='25000以上'){
					$setsqlarr['wage_cn']='25K以上';
				}else{
					$wage_cn=explode("-",$wages); 
					$wage_cns=round($wage_cn[0] / 1000 * 100) / 100 . 'K';
					$wage_cnss=round($wage_cn[1] / 1000 * 100) / 100 . 'K';
					$setsqlarr['wage_cn']=$wage_cns.'~'.$wage_cnss.'/月';
				}                
                $education=$this->get_education((string)$objPHPExcel->getActiveSheet()->getCell("I".$i)->getValue());
                $setsqlarr['education']=$education['id'];                                                   //-------------所在街道
                $setsqlarr['education_cn']=$education['cn'];
                $l=explode("-",(string)$objPHPExcel->getActiveSheet()->getCell("L".$i)->getValue());
				$l2=$l[count($l)-1];
                $l1=$l[count($l)-2];
                $l0=$l[count($l)-3];
                
                if($l1=='不限'){
                $l=$l0;
                }elseif($l2=='不限'){
                $l=$l1;
                }else{
                $l=$l2;
                }                
                $district=$this->get_district($l);//工作地区
                $setsqlarr['district']=$district['district'].'.'.$district['sdistrict'].'.'.$district['tdistrict'];
				if($district['district_cn']==''){
					$setsqlarr['district_cn']=(string)$objPHPExcel->getActiveSheet()->getCell("T".$i)->getValue();
                }else{
					$setsqlarr['district_cn']=$district['district_cn'];
				}
                $setsqlarr['specialty']=(string)$objPHPExcel->getActiveSheet()->getCell("V".$i)->getValue(); 
                $setsqlarr['height']=(string)$objPHPExcel->getActiveSheet()->getCell("E".$i)->getValue(); 
                $setsqlarr['householdaddress']=(string)$objPHPExcel->getActiveSheet()->getCell("F".$i)->getValue(); 
                $marriage=(string)$objPHPExcel->getActiveSheet()->getCell("G".$i)->getValue();
                if($marriage=='保密'){
                    $setsqlarr['marriage']="0";
                }elseif($marriage=='未婚'){
                    $setsqlarr['marriage']="1";
                }elseif($marriage=="已婚"){
                    $setsqlarr['marriage']="2";
                }
                $setsqlarr['marriage_cn']=$marriage;        
                $resume=M('resume')->add($setsqlarr);              
                $jyjl=(string)$objPHPExcel->getActiveSheet()->getCell("N".$i)->getValue();
                if($jyjl){
                    $jyjl=explode("【#】",$jyjl);
                    foreach($jyjl as $jy){
                    $jyjl_cr_ac['pid']=$resume;     
                    $jyjl_cr_ac['uid']=$setsqlarr['uid'];     
                    $jyjl_cr=explode("|",$jy);
                    $jyjl_cr_sj=explode("-",$jyjl_cr[0]);
                    $kssj=explode("/",$jyjl_cr_sj[0]);
                    $jyjl_cr_ac['startyear']=$kssj[0];
                    $jyjl_cr_ac['startmonth']=$kssj[1];
                    $jssj=explode("/",$jyjl_cr_sj[1]);
                    $jyjl_cr_ac['endyear']=$jssj[0];
                    $jyjl_cr_ac['endmonth']=$jssj[1];
                    $jyjl_cr_ac['school']=$jyjl_cr[1];
                    $jyjl_cr_ac['speciality']=$jyjl_cr[2];
                    $education=$this->get_education($jyjl_cr[3]);
                    $jyjl_cr_ac['education']=$education['id'];                                                   //-------------所在街道
                    $jyjl_cr_ac['education_cn']=$education['cn'];
                    
                    M('resume_education')->add($jyjl_cr_ac);
                    }
                    unset($jyjl_cr_ac);
                }
                $gzjl=(string)$objPHPExcel->getActiveSheet()->getCell("O".$i)->getValue();
                if($gzjl){
                    $gzjl=explode("【#】",$gzjl);
                    foreach($gzjl as $gz){
                    $gzjl_cr_ac['pid']=$resume;     
                    $gzjl_cr_ac['uid']=$setsqlarr['uid'];     
                    $gzjl_cr=explode("|",$gz);
                    $gzjl_cr_sj=explode("-",$gzjl_cr[0]);
                    $kssj=explode("/",$gzjl_cr_sj[0]);
                    $gzjl_cr_ac['startyear']=$kssj[0];
                    $gzjl_cr_ac['startmonth']=$kssj[1];
                    $jssj=explode("/",$gzjl_cr_sj[1]);
                    $gzjl_cr_ac['endyear']=$jssj[0];
                    $gzjl_cr_ac['endmonth']=$jssj[1];
                    $gzjl_cr_ac['companyname']=$gzjl_cr[1];
                    $gzjl_cr_ac['jobs']=$gzjl_cr[2];
                    $gzjl_cr_ac['achievements']=$gzjl_cr[3];
                    M('resume_work')->add($gzjl_cr_ac);
                    }
                    unset($gzjl_cr_ac);   
                }
                $yyjl=(string)$objPHPExcel->getActiveSheet()->getCell("P".$i)->getValue();
                if($yyjl){
                    $yyjl=explode("【#】",$yyjl);
                    foreach($yyjl as $yy){
                    
                    $yyjl_cr_ac['pid']=$resume;     
                    $yyjl_cr_ac['uid']=$setsqlarr['uid'];     
                    $lang=explode("|",$yy);
                    $language=$this->get_language($lang[0]);
                    $yyjl_cr_ac['language']=$language['id'];                                                   //-------------所在街道
                    $yyjl_cr_ac['language_cn']=$language['cn'];
                    $level=$this->get_level($lang[1]);
                    $yyjl_cr_ac['level']=$level['id'];                                                   //-------------所在街道
                    $yyjl_cr_ac['level_cn']=$level['cn'];
                    
                    M('resume_language')->add($yyjl_cr_ac);
                    }
                    unset($yyjl_cr_ac);
                }
                $zsjl=(string)$objPHPExcel->getActiveSheet()->getCell("Q".$i)->getValue();
                if($zsjl){
                    $zsjl=explode("【#】",$zsjl);
                    foreach($zsjl as $zs){
                    
                    $zsjl_cr_ac['pid']=$resume;     
                    $zsjl_cr_ac['uid']=$setsqlarr['uid'];     
                    $zsjl_cr=explode("|",$zs);
                    $zsjl_cr_ac['name']=$zsjl_cr[0];   
                    
                    $kssj=explode("/",$zsjl_cr[1]);
                    $zsjl_cr_ac['year']=$kssj[0];
                    $zsjl_cr_ac['month']=$kssj[1];
                
                    
                    M('resume_credent')->add($zsjl_cr_ac);
                    }
                    unset($zsjl_cr_ac);
                }
                D('Resume')->check_resume($setsqlarr['uid'],$resume,2,2);  
                unset($setsqlarr);      
            }
        }
        $this->success("导入成功！");  
    }
    function get_major($str)
    {
        global $db;
        $default=array("id"=>82,"cn"=>'其它分类');    
        if (empty($str))
        {
            return $default;
        }
        else
        {
            $where['parentid']=array('neq',0);
            $info=M('category_major')->where($where)->select();

            $return=$this->search_str($info,$str,"categoryname");
            if ($return)
            {
            return array("id"=>$return['id'],"cn"=>$return['categoryname']);     
            }
            else
            {
            return $default;
            }
        }
    }
    function get_district($str)
    {
        global $db;
        $default=array("s_provinces"=>0,"s_citys"=>0,"s_countys"=>0,"s_countys_cn"=>'未知');
        if (empty($str))
        {
            return $default;
        }
        else
        {
            $where['categoryname']=array('eq',$str);
            $return=M('category_district')->where($where)->find();
            
            if($return['parentid']==0){
                return array("district"=>$return['id'],"sdistrict"=>0,"tdistrict"=>0,"district_cn"=>$return['categoryname']);   
            }elseif($return['parentid']>0){
                $wheres['id']=array('eq',$return['parentid']);
                $top=M('category_district')->where($wheres)->find();
                if($top['parentid']==0){
                    return array("district"=>$return['parentid'],"sdistrict"=>$return['id'],"tdistrict"=>0,"district_cn"=>$return['categoryname']); 
                }else{
                    return array("district"=>$top['parentid'],"sdistrict"=>$return['parentid'],"tdistrict"=>$return['id'],"district_cn"=>$return['categoryname']);  
                }
            }else{
                return $default;
            }
        }
    }
    function get_education($str)
    {
        global $db;
        $default=array("id"=>69,"cn"=>'大专');    
        if (empty($str))
        {
            return $default;
        }
        else
        {
            $info=M('category')->where(array('c_alias'=>'QS_education'))->select();

            $return=$this->search_str($info,$str,"c_name");
            if ($return)
            {
            return array("id"=>$return['c_id'],"cn"=>$return['c_name']);     
            }
            else
            {
            return $default;
            }
        }
    }
    function get_language($str)
    {
        global $db;
        $default=array("id"=>213,"cn"=>'其它');    
        if (empty($str))
        {
            return $default;
        }
        else
        {
            $info=M('category')->where(array('c_alias'=>'QS_language'))->select();

            $return=$this->search_str($info,$str,"c_name");
            if ($return)
            {
            return array("id"=>$return['c_id'],"cn"=>$return['c_name']);     
            }
            else
            {
            return $default;
            }
        }
    }
    function get_level($str)
    {
        global $db;
        $default=array("id"=>291,"cn"=>'一般');    
        if (empty($str))
        {
            return $default;
        }
        else
        {
            $info=M('category')->where(array('c_alias'=>'QS_language_level'))->select();

            $return=$this->search_str($info,$str,"c_name");
            if ($return)
            {
            return array("id"=>$return['c_id'],"cn"=>$return['c_name']);     
            }
            else
            {
            return $default;
            }
        }
    }
    function get_wage($str)
    {
        global $db;
        $default=array("id"=>301,"cn"=>'面议');    
        if (empty($str))
        {
            return $default;
        }
        else
        {
            $info=M('category')->where(array('c_alias'=>'QS_wage'))->select();

            $return=$this->search_str($info,$str,"c_name");
            if ($return)
            {
            return array("id"=>$return['c_id'],"cn"=>$return['c_name']);     
            }
            else
            {
            return $default;
            }
        }
    }
    function get_jobs_cat($str)
    {
        global $db;
        $default=array("topclass"=>3,"category"=>545,"subclass"=>0,"category_cn"=>'不限职位');
        if (empty($str))
        {
            return $default;
        }
        else
        {
            $where['parentid']=array('neq',0);
            $where['categoryname']=array('eq',$str);
            $info=M('category_jobs')->where($where)->select();
            $return=$this->search_str_job($info,$str,"categoryname");
            
            if ($return)
            {
                $where_s['id']=$return['parentid'];
                $top=M('category_jobs')->where($where_s)->find();
                return array("topclass"=>$top['parentid'],"category"=>$return['parentid'],"subclass"=>$return['id'],"category_cn"=>$return['categoryname']);        
            }
            else
            {
                return $default; 
            }
        }
    }
    function get_experience($str)
    {
        global $db;
        $default=array("category"=>0,"subclass"=>0,"category_cn"=>'未知');
        if (empty($str))
        {
            return $default;
        }
        else
        {
            $info=M('category')->where(array('c_alias'=>'QS_experience'))->select();

            $return=$this->search_str($info,$str,"c_name");
            if ($return)
            {
            return array("id"=>$return['c_id'],"cn"=>$return['c_name']);     
            }
            else
            {
            return $default;
            }
        }
    }
    //模糊搜索
    function search_str_job($arr,$str,$arrinname,$n=30)
    {
        foreach ($arr as $key =>$list)
        {
            $a=similar_text($list[$arrinname],$str,$percent);
            
            if($a > 4){
            $od[$percent]=$key;
            }
            
        }
		krsort($od);
		foreach ($od as $key =>$li)
		{
			if ($key>=$n)
			{
			return $arr[$li];
			}
			else
			{
			return false;
			}
		}   
    }
    function search_str_dq($arr,$str,$arrinname,$n=30)
    {
        foreach ($arr as $key =>$list)
        {
            $a=similar_text($list[$arrinname],$str,$percent);
            
            if($a > 6){
            $od[$percent]=$key;
            }            
        }
		krsort($od);
		foreach ($od as $key =>$li)
		{
			if ($key>=$n)
			{
			return $arr[$li];
			}
			else
			{
			return false;
			}
		}   
    }
    function search_str($arr,$str,$arrinname,$n=30)
    {
        foreach ($arr as $key =>$list)
        {
            similar_text($list[$arrinname],$str,$percent);
        
            $od[$percent]=$key;
        }
		krsort($od);
		foreach ($od as $key =>$li)
		{
			if ($key>=$n)
			{
			return $arr[$li];
			}
			else
			{
			return false;
			}
		}   
    }
}
?>