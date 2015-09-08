<?php

!defined('IN_CL') && exit('Forbidden');
define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
D_BUG ? error_reporting(E_ALL ^ E_NOTICE) : error_reporting(0);
header("Content-type: text/html; charset=utf-8");
$_SGLOBAL = array();
//程序目录
define('S_ROOT', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

include_once(S_ROOT . './source/360_safe3.php');
include_once(S_ROOT . './source/class_load.php');
include_once(S_ROOT . './source/function_common.php');
include_once(S_ROOT . './source/function_user.php');
include_once(S_ROOT . './models/mysqlDBA.php');

//时间

$mtime = explode(' ', microtime());
$_SGLOBAL['timestamp'] = $mtime[1];
$_SGLOBAL['supe_starttime'] = $_SGLOBAL['timestamp'] + $mtime[0];
if (defined('SHOW_PAGE_TIME')) {
    //页面速度测试
    $mtime = explode(' ', microtime());
    $sqlstarttime = number_format(($mtime[1] + $mtime[0] - $_SGLOBAL['supe_starttime']), 6) * 1000;
}

//初始化
$_SGLOBAL['query_string'] = $_SERVER['QUERY_STRING'];
$_SGLOBAL['inajax'] = empty($_GET['inajax']) ? 0 : intval($_GET['inajax']);
$_SGLOBAL['mobile'] = empty($_GET['mobile']) ? 0 : trim($_GET['mobile']);
$_SGLOBAL['refer'] = empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];
require_once(S_ROOT . "./config/global.conf.php"); //全局数组
if (file_exists(S_ROOT . "./config/app/" . APP . ".conf.php")) {
    require_once(S_ROOT . "./config/app/" . APP . ".conf.php"); //APP全局数组
}
if (REWRITE_URL) {
    $_NGET = parseRewriteQueryString($_SGLOBAL['query_string']);
    !empty($_NGET['controller']) && $_GET = $_NGET;
    $_SGLOBAL['query_string'] = _queryString($_SGLOBAL['query_string']);
}
dbconnect(APP); //连接数据库
loadController($arrController, APP);
$_SGLOBAL['memory'] = memory_get_usage();
?>