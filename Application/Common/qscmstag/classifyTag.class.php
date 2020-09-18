<?php
/**
 * 分类
 */
namespace Common\qscmstag;
defined('THINK_PATH') or exit();
class classifyTag {
    protected $params = array();
    protected $limit;
    function __construct($options) {
        $array = array(
            '列表名'            =>  'listname',
            '类型'              =>  'act',
            '显示数目'          =>  'row',
            '名称长度'          =>  'titlelen',
            '填补字符'          =>  'dot',
            'id'                =>  'id',
            '地区分类'          =>  'citycategory',
            '职位分类'          =>  'jobcategory'
        );
        foreach ($options as $key => $value) {
            $this->params[$array[$key]] = $value;
        }
        $this->params['id']=isset($this->params['id'])?intval($this->params['id']):"all";
        $this->params['listname']=isset($this->params['listname'])?$this->params['listname']:"list";
        $this->params['titlelen']=isset($this->params['titlelen'])?intval($this->params['titlelen']):18;
        $this->params['dot']=isset($this->params['dot'])?$this->params['dot']:'';
        $this->limit=isset($this->params['row'])?intval($this->params['row']):0;
        $this->params['act']=isset($this->params['act'])?trim($this->params['act']):'';
    }
    public function run(){
        switch($this->params['act']){
            case 'QS_jobs':
                return $this->_get_jobs_category();
                break;
            case 'QS_citycategory':
                return $this->_get_citycategory();
                break;
            case 'QS_jobcategory':
                return $this->_get_jobcategory();
                break;
            case 'QS_district':
                return $this->_get_district_category();
                break;
            case 'QS_major':
                return $this->_get_major_category();
                break;
            case 'QS_major_info':
                return $this->_get_major_category_info();
                break;
            case 'QS_goods_category':
                return $this->_get_goods_category();
                break;
            case 'QS_jobs_info':
                return $this->_get_jobs_category_info();
                break;
            case 'QS_city_info':
                return $this->_get_district_category_info();
                break;
            case 'QS_article':
                return $this->_get_article_category();
            case 'QS_help':
                return $this->_get_help_category();
            default:
                return $this->_get_category();
                break;
        }
    }
    protected function _get_goods_category(){
        $id=intval($this->params['id']);
        $result = D('Mall/MallCategory')->get_mall_cache($id);
        if($this->limit>0){
            $result = array_slice($result,0,$this->limit);
        }
        $list = array();
        foreach ($result as $key => $value) {
            $row['id'] = $key;
            $row['categoryname'] = $value;
            $row['categoryname']=cut_str($row['categoryname'],$this->params['titlelen'],0,$this->params['dot']);
            $list[] = $row;
        }
        return $list;
    }
    protected function _get_category(){
        if($this->params['act']){
            $category = D('Category')->get_category_cache($this->params['act']);
        }else{
            $category = D('Category')->get_category_cache();
        }
        if($this->limit>0){
            $result = array_slice($result,0,$this->limit);
        }
        /*foreach ($category as $key => $value) {
            $value = cut_str($value,$this->params['titlelen'],0,$this->params['dot']);
        }*/
        return $category;
    }

    protected function _get_jobs_category(){
        if(false === $result = F('jobs_custom_cate')) $result = D('CategoryJobs')->custom_jobs_cache();
        if ($this->params['id'] !='all'){
            $result = $result[intval($this->params['id'])];
        }
        if($this->limit>0){
            $result = array_slice($result,0,$this->limit);
        }
        return $result;
    }

    protected function _get_jobs_category_info(){
        if(false === $result = F('jobs_cate_list')) $result = D('CategoryJobs')->jobs_cate_cache();
        return $result;
    }

    protected function _get_citycategory(){
        if ($this->params['citycategory']){
            if(false === $city_cate = F('city_cate_list')) $city_cate = D('CategoryDistrict')->city_cate_cache();
            if(false === $city_search = F('city_search_cate')) $city_search = D('CategoryDistrict')->city_search_cache();
            if(!strpos($this->params['citycategory'],".")){
                if(fieldRegex($this->params['citycategory'],'in')){
                    $result['select'] = $city_cate['id'][$this->params['citycategory']];
                }else{
                    $result['select'] = $city_cate['spell'][$this->params['citycategory']];
                }
                $citycategory = $city_search[$result['select']['id']];
                $temp = $result['select']['id'];
                $city = explode('_',$citycategory);
            }else{
                $citycategory = $this->params['citycategory'];
                $city = explode('.',$this->params['citycategory']);
                if($city[2]){
                    $result['select'] = $city_cate['id'][$city[2]];
                    $temp = $city[2];
                }elseif($city[1]){
                    $result['select'] = $city_cate['id'][$city[1]];
                    $temp = $city[1];
                }else{
                    $result['select'] = $city_cate['id'][$city[0]];
                    $temp = $city[0];
                }
            }
            $result['select']['citycategory'] = APP_SPELL ? $city_cate['id'][$result['select']['id']]['spell'] : (C('SUBSITE_VAL.s_id') ? $temp : $citycategory);
            if(!C('SUBSITE_VAL.s_id') && $city[1]){
                $result['list'] = D('CategoryDistrict')->get_district_cache($city[1]);
                foreach ($result['list'] as $key => $val) {
                    $result['list'][$key] = array('id'=>$key,'categoryname'=>$val,'citycategory'=>APP_SPELL ? $city_cate['id'][$key]['spell'] : $city[0].'.'.$city[1].'.'.$key);
                }
                $result['parent'] = $city_cate['id'][$city[1]];
                $result['parent']['citycategory'] = APP_SPELL ? $city_cate['id'][$result['parent']['id']]['spell'] : $city[0].'.'.$city[1].'.0';
            }
            return $result;
        }
        return false;
    }
    protected function _get_jobcategory(){
        if ($this->params['jobcategory']){
            if(false === $jobs_cate = F('jobs_cate_list')) $jobs_cate = D('CategoryJobs')->jobs_cate_cache();
            if(false === $jobs_search = F('jobs_search_cate')) $jobs_search = D('CategoryJobs')->jobs_search_cache();
            if(!strpos($this->params['jobcategory'],".")){
                if(fieldRegex($this->params['jobcategory'],'in')){
                    $result['select'] = $jobs_cate['id'][$this->params['jobcategory']];
                }else{
                    $result['select'] = $jobs_cate['spell'][$this->params['jobcategory']];
                }
                $jobcategory = $jobs_search[$result['select']['id']];
                $jobs = explode('_',$jobcategory);
            }else{
                $jobcategory = $this->params['jobcategory'];
                $jobs = explode('.',$this->params['jobcategory']);
                if($jobs[2]){
                    $result['select'] = $jobs_cate['id'][$jobs[2]];
                }elseif($jobs[1]){
                    $result['select'] = $jobs_cate['id'][$jobs[1]];
                }else{
                    $result['select'] = $jobs_cate['id'][$jobs[0]];
                }
            }
            $result['select']['jobcategory'] = APP_SPELL ? $jobs_cate['id'][$result['select']['id']]['spell'] : $jobcategory;
            if($jobs[1]){
                $result['list'] = D('CategoryDistrict')->get_district_cache($jobs[1]);
                foreach ($result['list'] as $key => $val) {
                    $result['list'][$key] = array('id'=>$key,'categoryname'=>$val,'jobcategory'=>APP_SPELL ? $jobs_cate['id'][$key]['spell'] : $jobs[0].'.'.$jobs[1].'.'.$key);
                }
                $result['parent'] = $jobs_cate['id'][$jobs[1]];
                $result['parent']['jobcategory'] = APP_SPELL ? $jobs_cate['id'][$result['parent']['id']]['spell'] : $jobs[0].'.'.$jobs[1].'.0';
            }
            return $result;
        }
        return false;
    }
    protected function _get_district_category(){
        if(false === $result = F('district_custom_cate')) $result = D('CategoryDistrict')->custom_district_cache();
        if ($this->params['id'] !='all'){
            $result = $result[intval($this->params['id'])];
        }
        if($this->limit>0){
            $result = array_slice($result,0,$this->limit);
        }
        return $result;
    }

    protected function _get_district_category_info(){
        if(false === $result = F('city_cate_list')) $result = D('CategoryDistrict')->city_cate_cache();
        return $result;
    }

    protected function _get_major_category(){
        if ($this->params['id']=='all')
        {
            $result = D('CategoryMajor')->get_major_cache('all');
        }else{
            $result = D('CategoryMajor')->get_major_cache(intval($this->params['id']));
        }
        /*if($this->limit>0){
            $result = array_slice($result,0,$this->limit);
        }*/
        return $result;
    }

    protected function _get_major_category_info(){
        if(false === $result = F('major_data_list')) $result = D('CategoryMajor')->get_major_list();
        if ($this->params['id'] !='all'){
            $result = $result[intval($this->params['id'])];
        }
        return $result;
    }

    protected function _get_article_category(){
        $result = D('ArticleCategory')->get_article_category_cache($this->params['id']);
        if($this->limit>0){
            foreach ($result as $key => $val) {
                $data[$key] = $val;
                $i++;
                if($i >= $this->limit) break;
            }
            $result = $data;
        }
        return $result;
    }

    protected function _get_help_category(){
        if(false === $help_category = F('help_category_list')) $help_category = D('HelpCategory')->help_category_list();
        return $help_category;
    }
}