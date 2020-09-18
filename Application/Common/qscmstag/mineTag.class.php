<?php
/**
 * 文本标签
 */
namespace Common\qscmstag;
defined('THINK_PATH') or exit();
class mineTag {
    protected $params = array();
    protected $map = array();
    function __construct($options) {
		echo 33;
        $array = array(
            '列表名'            =>  'listname',
            '类型'              =>  'type'
        );
        foreach ($options as $key => $value) {
            $this->params[$array[$key]] = $value;
        }
        $this->params['listname']=isset($this->params['listname'])?$this->params['listname']:"list";
    }
    public function run(){
        if(false === $cache = F('mine_list'))
        {
            $cache = D('Mine')->text_cache();
        }
        return $cache[$this->params['type']];
    }
}