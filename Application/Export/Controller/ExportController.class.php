<?php
namespace Export\Controller;
use Common\Controller\BackendController;
class ExportController extends BackendController{
	public function _initialize() {
        parent::_initialize();
    }
    /**
     * 职位导出
     */
    public function export_jobs(){
        $id = I('request.id');
        if(!$id){
            $this->error('请选择职位');
        }
        $sqlin=implode(",",$id);
        $where = array('id'=>array('in',$sqlin));
        $data=M('jobs')->where($where)->getField('id,jobs_name,companyname,nature_cn,sex_cn,education_cn,experience_cn,amount,age,tag_cn,department,category_cn,trade_cn,scale_cn,district_cn,minwage,maxwage,contents');
        foreach ($data as $key => $value) {
            $contact=M('jobs_contact')->where(array('pid'=>$value['id']))->find();
            $data[$key]['contact']=$contact['contact'];
            $data[$key]['qq']=$contact['qq'];
            $data[$key]['telephone']=$contact['telephone'];
            $data[$key]['landline_tel']=$contact['landline_tel'];
            $data[$key]['address']=$contact['address'];
            $data[$key]['email']=$contact['email'];
        }
        $title=array('职位ID','职位名称','企业名称','招聘类型','性别','学历要求','工作经验要求','招聘人数','年龄','职位亮点','所属部门','职位类别','公司性质','公司人数','工作地区','最低薪资','最高薪资','职位介绍','联系人','QQ','联系电话','座机','联系地址','联系邮箱');
        $this->exportexcel($data,$title,'职位下载'.date("Y-m-d"));
    }
    public function export_jobs_s(){
        $data=M('jobs')->getField('id,jobs_name,companyname,nature_cn,sex_cn,education_cn,experience_cn,amount,age,tag_cn,department,category_cn,trade_cn,scale_cn,district_cn,minwage,maxwage,contents');
        foreach ($data as $key => $value) {
            $contact=M('jobs_contact')->where(array('pid'=>$value['id']))->find();
            $data[$key]['contact']=$contact['contact'];
            $data[$key]['qq']=$contact['qq'];
            $data[$key]['telephone']=$contact['telephone'];
            $data[$key]['landline_tel']=$contact['landline_tel'];
            $data[$key]['address']=$contact['address'];
            $data[$key]['email']=$contact['email'];
        }
        $title=array('职位ID','职位名称','企业名称','招聘类型','性别','学历要求','工作经验要求','招聘人数','年龄','职位亮点','所属部门','职位类别','公司性质','公司人数','工作地区','最低薪资','最高薪资','职位介绍','联系人','QQ','联系电话','座机','联系地址','联系邮箱');
        $this->exportexcel($data,$title,'职位下载'.date("Y-m-d"));
    }
    /**
     * 企业导出
     */
    public function export_company(){
        $id = I('request.y_id');
        if(!$id){
            $this->error('请选择企业');
        }
        $sqlin=implode(",",$id);
        $where = array('id'=>array('in',$sqlin));
        $data=M('company_profile')->where($where)->getField('companyname,nature_cn,trade_cn,scale_cn,district_cn,registered,currency,website,tag,contents,contact,telephone,landline_tel,email,qq,address');
        $title=array('企业名称','企业性质','所属行业','企业规模','所在地区','注册资金','币种','企业网址','企业福利','企业简介','联系人','联系电话','座机','联系邮箱','QQ','联系地址');
        $this->exportexcel($data,$title,'企业下载'.date("Y-m-d"));
    }
    public function export_company_s(){
        $data=M('company_profile')->getField('companyname,nature_cn,trade_cn,scale_cn,district_cn,registered,currency,website,tag,contents,contact,telephone,landline_tel,email,qq,address');
        $title=array('企业名称','企业性质','所属行业','企业规模','所在地区','注册资金','币种','企业网址','企业福利','企业简介','联系人','联系电话','座机','联系邮箱','QQ','联系地址');
        $this->exportexcel($data,$title,'企业下载'.date("Y-m-d"));
    }
    /**
     * 简历导出
     */
    public function export_personal(){
        $id = I('request.id');
        if(!$id) $this->error('请选择简历');
        $sqlin=implode(",",$id);
        $where = array('id'=>array('in',$sqlin));
        $data=M('resume')->where($where)->getField('id,title,fullname,sex_cn,birthdate,residence,education_cn,experience_cn,telephone,email,major_cn,height,householdaddress,marriage_cn,qq,weixin,current_cn,nature_cn,trade_cn,intention_jobs,district_cn,wage_cn,specialty');
        foreach ($data as $key => $value) {
            $education=M('resume_education')->where(array('pid'=>$value['id']))->select();
            foreach ($education as $key_education => $value_education) {
                $education_s.=$value_education['startyear'].'/'.$value_education['startmonth'].'-';
                if($value_education['todate']==1){
                    $education_s.='至今';
                }else{
                    $education_s.=$value_education['endyear'].'/'.$value_education['endmonth'];
                }
                $education_s.=',学校名称：'.$value_education['school'].',专业名称：'.$value_education['speciality'].',获取学历：'.$value_education['education_cn'];
                $education_s.=' | ';
            }
            $data[$key]['education']=$education_s;
            
            $work=M('resume_work')->where(array('pid'=>$value['id']))->select();
            foreach ($work as $key_work => $value_work) {
                $work_s.=$value_work['startyear'].'/'.$value_work['startmonth'].'-';
                if($value_work['todate']==1){
                    $work_s.='至今';
                }else{
                    $work_s.=$value_work['endyear'].'/'.$value_work['endmonth'];
                }
                $work_s.=',公司名称：'.$value_work['companyname'].',职位名称：'.$value_work['jobs'].',工作职责：'.$value_work['achievements'];
                $work_s.=' | ';
            }
            $data[$key]['work']=$work_s;

            $training=M('resume_training')->where(array('pid'=>$value['id']))->select();
            foreach ($training as $key_training => $value_training) {
                $training_s.=$value_training['startyear'].'/'.$value_training['startmonth'].'-';
                if($value_training['todate']==1){
                    $training_s.='至今';
                }else{
                    $training_s.=$value_training['endyear'].'/'.$value_training['endmonth'];
                }
                $training_s.=',培训机构：'.$value_training['agency'].',培训课程：'.$value_training['course'].',培训内容：'.$value_training['description'];
                $training_s.=' | ';
            }
            $data[$key]['training']=$training_s;

            $credent=M('resume_credent')->where(array('pid'=>$value['id']))->select();
            foreach ($credent as $key_credent => $value_credent) {
                $credent_s.=$value_credent['year'].'/'.$value_credent['month'].'获取证书：'.$value_credent['name'];
                $credent_s.=' | ';
            }
            $data[$key]['credent']=$credent_s;

            $language=M('resume_language')->where(array('pid'=>$value['id']))->select();
            foreach ($language as $key_language => $value_language) {
                $language_s.=$value_language['language_cn'].'/'.$value_language['level_cn'];
                $language_s.=' | ';
            }
            $data[$key]['language']=$language_s;
            
        }
        $title=array('简历ID','标题','姓名','性别','出生年份','现居住地','最高学历','工作经验','手机','邮箱','所学专业','身高','籍贯','婚姻状况','QQ','微信号','目前状态','工作性质','期望行业','期望职位','工作地区','期望薪资','自我描述','教育经历','工作经历','培训经历','获得证书','语言能力');
        $this->exportexcel($data,$title,'简历下载'.date("Y-m-d"));
    }
    public function export_personal_s(){
        $data=M('resume')->getField('id,title,fullname,sex_cn,birthdate,residence,education_cn,experience_cn,telephone,email,major_cn,height,householdaddress,marriage_cn,qq,weixin,current_cn,nature_cn,trade_cn,intention_jobs,district_cn,wage_cn,specialty');
        foreach ($data as $key => $value) {
            $education=M('resume_education')->where(array('pid'=>$value['id']))->select();
            foreach ($education as $key_education => $value_education) {
                $education_s.=$value_education['startyear'].'/'.$value_education['startmonth'].'-';
                if($value_education['todate']==1){
                    $education_s.='至今';
                }else{
                    $education_s.=$value_education['endyear'].'/'.$value_education['endmonth'];
                }
                $education_s.=',学校名称：'.$value_education['school'].',专业名称：'.$value_education['speciality'].',获取学历：'.$value_education['education_cn'];
                $education_s.=' | ';
            }
            $data[$key]['education']=$education_s;
            
            $work=M('resume_work')->where(array('pid'=>$value['id']))->select();
            foreach ($work as $key_work => $value_work) {
                $work_s.=$value_work['startyear'].'/'.$value_work['startmonth'].'-';
                if($value_work['todate']==1){
                    $work_s.='至今';
                }else{
                    $work_s.=$value_work['endyear'].'/'.$value_work['endmonth'];
                }
                $work_s.=',公司名称：'.$value_work['companyname'].',职位名称：'.$value_work['jobs'].',工作职责：'.$value_work['achievements'];
                $work_s.=' | ';
            }
            $data[$key]['work']=$work_s;

            $training=M('resume_training')->where(array('pid'=>$value['id']))->select();
            foreach ($training as $key_training => $value_training) {
                $training_s.=$value_training['startyear'].'/'.$value_training['startmonth'].'-';
                if($value_training['todate']==1){
                    $training_s.='至今';
                }else{
                    $training_s.=$value_training['endyear'].'/'.$value_training['endmonth'];
                }
                $training_s.=',培训机构：'.$value_training['agency'].',培训课程：'.$value_training['course'].',培训内容：'.$value_training['description'];
                $training_s.=' | ';
            }
            $data[$key]['training']=$training_s;

            $credent=M('resume_credent')->where(array('pid'=>$value['id']))->select();
            foreach ($credent as $key_credent => $value_credent) {
                $credent_s.=$value_credent['year'].'/'.$value_credent['month'].'获取证书：'.$value_credent['name'];
                $credent_s.=' | ';
            }
            $data[$key]['credent']=$credent_s;

            $language=M('resume_language')->where(array('pid'=>$value['id']))->select();
            foreach ($language as $key_language => $value_language) {
                $language_s.=$value_language['language_cn'].'/'.$value_language['level_cn'];
                $language_s.=' | ';
            }
            $data[$key]['language']=$language_s;
            
        }
        $title=array('简历ID','标题','姓名','性别','出生年份','现居住地','最高学历','工作经验','手机','邮箱','所学专业','身高','籍贯','婚姻状况','QQ','微信号','目前状态','工作性质','期望行业','期望职位','工作地区','期望薪资','自我描述','教育经历','工作经历','培训经历','获得证书','语言能力');
        $this->exportexcel($data,$title,'简历下载'.date("Y-m-d"));
    }
    function exportexcel($data=array(),$title=array(),$filename){
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-excel");  
        header("Content-Disposition:attachment;filename=".$filename.".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        //导出xls 开始
        if (!empty($title)){
            foreach ($title as $k => $v) {
                $title[$k]=iconv("UTF-8", "GB2312",$v);
            }
            $title= implode("\t", $title);
            echo "$title\n";
        }
        if (!empty($data)){
            foreach($data as $key=>$val){
                foreach ($val as $ck => $cv) {
                    $data[$key][$ck]=iconv("UTF-8", "GB2312", $cv);
                }
                $data[$key]=implode("\t", $data[$key]);
                
            }
            echo implode("\n",$data);
        }
    }
}
?>