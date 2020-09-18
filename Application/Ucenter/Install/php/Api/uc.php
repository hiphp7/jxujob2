<?php
// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');
ob_start();
define('APP_DEBUG',false);// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('BIND_MODULE','Ucenter');

//网站根目录
define('WEB_ROOT_PATH', rtrim(dirname(dirname(__FILE__)), '/\\') . DIRECTORY_SEPARATOR);
//API根目录
define('API_PATH', realpath(WEB_ROOT_PATH . 'Api') . DIRECTORY_SEPARATOR);
//项目根目录
define('APP_PATH', realpath(WEB_ROOT_PATH . 'Application') . DIRECTORY_SEPARATOR);
//数据目录
define('UCENTER_DATA_PATH', realpath(WEB_ROOT_PATH . 'data') . DIRECTORY_SEPARATOR);
//项目安全文件
define('DIR_SECURE_FILENAME', 'index.html');
/*项目自定义类库*/
define('UCENTER_LIB_PATH', APP_PATH . 'Ucenter' . DIRECTORY_SEPARATOR . 'qscmslib' . DIRECTORY_SEPARATOR);
//运行时文件目录
define('RUNTIME_PATH', UCENTER_DATA_PATH . 'Runtime' . DIRECTORY_SEPARATOR);
//应用静态目录
define('HTML_PATH', WEB_ROOT_PATH . 'html' . DIRECTORY_SEPARATOR);
//session存放路径
define('SESSION_PATH', realpath(WEB_ROOT_PATH . 'data' . DIRECTORY_SEPARATOR . 'session'));
// 引入ThinkPHP入口文件
require WEB_ROOT_PATH . 'ThinkPHP/ThinkPHP.php';