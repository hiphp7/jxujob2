<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace Common\Builder;

use Think\Controller;
use Think\Exception;

/**
 * 构建器
 * @package app\common\builder
 * @author 蔡伟明 <314013107@qq.com>
 */
class ZBuilder extends Common
{
    /**
     * @var array 构建器数组
     * @author 蔡伟明 <314013107@qq.com>
     */
    protected static $builder = [];

    /**
     * @var array 模板参数变量
     */
    protected static $vars = [];

    /**
     * @var string 动作
     */
    protected static $action = '';

    /**
     * 初始化
     * @author 蔡伟明 <314013107@qq.com>
     */
    public function _initialize()
    {
    }

    /**
     * 创建各种builder的入口
     * @param string $type 构建器名称，'Form', 'Table', 'View' 或其他自定义构建器
     * @param string $action 动作
     * @author 蔡伟明 <314013107@qq.com>
     * @return table\Builder|form\Builder|aside\Builder
     * @throws Exception
     */
    public static function make($type = '', $action = '')
    {
        if ($type == '') {
            throw new Exception('未指定构建器名称', 8001);
        } else {
            $type = strtolower($type);
        }

        // 构造器类路径
        $class = '\\Common\\Builder\\' . $type . '\\Builder';
        if (!class_exists($class)) {
            throw new Exception($type . '构建器不存在', 8002);
        }

        if ($action != '') {
            static::$action = $action;
        } else {
            static::$action = '';
        }

        return new $class;
    }

    /**
     * 加载模板输出
     * @param string $template 模板文件名
     * @param array  $vars     模板输出变量
     * @param array  $config   模板参数
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function fetch($template = '', $vars = [], $config = [])
    {
        $vars = array_merge($vars, self::$vars);
        // return parent::fetch($template, $vars, $config);

        C([ // Think模板引擎标签库相关设定
            // 'TAGLIB_BEGIN' => '{', // 标签库标签开始标记
            // 'TAGLIB_END'   => '}', // 标签库标签结束标记
        ]);
        $this->assign('_admin_base_layout', C('admin_base_layout'));
        parent::assign($vars);
        return parent::display($template);
    }

    public static function result($code = '', $msg = '', $url = '', $data = [], $wait = 3)
    {
        echo json_encode(['code' => $code, 'msg' => $msg, 'url' => $url, 'data' => $data, 'wait' => $wait]);
        die;
    }
}

/**
 * 项目公共控制器
 * @package app\common\controller
 */
class Common extends Controller
{
    /**
     * 初始化
     * @author 蔡伟明 <314013107@qq.com>
     */
    protected function initialize()
    {
        // 后台公共模板
        $this->assign('_admin_base_layout', config('admin_base_layout'));
        // 当前配色方案
        $this->assign('system_color', config('system_color'));
        // 输出弹出层参数
        $this->assign('_pop', $this->request->param('_pop'));
    }

    /**
     * 获取筛选条件
     * @author 蔡伟明 <314013107@qq.com>
     * @alter 小乌 <82950492@qq.com>
     * @return array
     */
    final public function getMap()
    {
        $search_field     = I('param.search_field', '', 'trim');
        $keyword          = I('param.keyword', '', 'trim');
        $filter           = I('param._filter', '', 'trim');
        $filter_content   = I('param._filter_content', '', 'trim');
        $filter_time      = I('param._filter_time', '', 'trim');
        $filter_time_from = I('param._filter_time_from', '', 'trim');
        $filter_time_to   = I('param._filter_time_to', '', 'trim');
        $select_field     = I('param._select_field', '', 'trim');
        $select_value     = I('param._select_value', '', 'trim');
        $search_area      = I('param._s', '', 'trim');
        $search_area_op   = I('param._o', '', 'trim');

        $map = [];

        // 搜索框搜索
        if ($search_field != '' && $keyword !== '') {
            $map[] = [$search_field, 'like', "%$keyword%"];
        }

        // 下拉筛选
        if ($select_field != '') {
            $select_field = array_filter(explode('|', $select_field), 'strlen');
            $select_value = array_filter(explode('|', $select_value), 'strlen');
            foreach ($select_field as $key => $item) {
                if ($select_value[$key] != '_all') {
                    $map[] = [$item, 'eq', $select_value[$key]];
                }
            }
        }

        // 时间段搜索
        if ($filter_time != '' && $filter_time_from != '' && $filter_time_to != '') {
            $map[] = [$filter_time, 'between', [strtotime($filter_time_from . ' 00:00:00'), strtotime($filter_time_to . ' 23:59:59')]];
        }

        // 表头筛选
        if ($filter != '') {
            $filter         = array_filter(explode('|', $filter), 'strlen');
            $filter_content = array_filter(explode('|', $filter_content), 'strlen');
            foreach ($filter as $key => $item) {
                if (isset($filter_content[$key])) {
                    $map[] = [$item, 'in', $filter_content[$key]];
                }
            }
        }

        // 搜索区域
        if ($search_area != '') {
            $search_area    = explode('|', $search_area);
            $search_area_op = explode('|', $search_area_op);
            foreach ($search_area as $key => $item) {
                list($field, $value) = explode('=', $item);
                $value               = trim($value);
                $op                  = explode('=', $search_area_op[$key]);
                if ($value != '') {
                    switch ($op[1]) {
                        case 'like':
                            $map[] = [$field, 'like', "%$value%"];
                            break;
                        case 'between time':
                        case 'not between time':
                            $value = explode(' - ', $value);
                            if ($value[0] == $value[1]) {
                                $value[0] = date('Y-m-d', strtotime($value[0])) . ' 00:00:00';
                                $value[1] = date('Y-m-d', strtotime($value[1])) . ' 23:59:59';
                            }
                        default:
                            $map[] = [$field, $op[1], $value];
                    }
                }
            }
        }
        $tp3map = [];
        foreach ($map as $k => $v) {
            $tp3map[$v[0]] = [$v[1], $v[2]];
        }
        return $tp3map;
    }

    /**
     * 获取字段排序
     * @param string $extra_order 额外的排序字段
     * @param bool $before 额外排序字段是否前置
     * @author 蔡伟明 <314013107@qq.com>
     * @return string
     */
    final public function getOrder($extra_order = '', $before = false)
    {
        $order = I('param._order', '');
        $by    = I('param._by', '');
        if ($order == '' || $by == '') {
            return $extra_order;
        }
        if ($extra_order == '') {
            return $order . ' ' . $by;
        }
        if ($before) {
            return $extra_order . ',' . $order . ' ' . $by;
        } else {
            return $order . ' ' . $by . ',' . $extra_order;
        }
    }

    /**
     * 渲染插件模板
     * @param string $template 模板名称
     * @param string $suffix 模板后缀
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    /**
     * 渲染插件模板
     * @param string $template 模板文件名
     * @param string $suffix 模板后缀
     * @param array $vars 模板输出变量
     * @param array $config 模板参数
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    final protected function pluginView($template = '', $suffix = '', $vars = [], $config = [])
    {
        $plugin_name = I('param.plugin_name');

        if ($plugin_name != '') {
            $plugin = $plugin_name;
            $action = 'index';
        } else {
            $plugin = I('param._plugin');
            $action = I('param._action');
        }
        $suffix        = $suffix == '' ? 'html' : $suffix;
        $template      = $template == '' ? $action : $template;
        $template_path = config('plugin_path') . "{$plugin}/view/{$template}.{$suffix}";
        return parent::fetch($template_path, $vars, $config);
    }
}
