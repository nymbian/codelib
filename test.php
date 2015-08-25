<?php

define('D_BUG', 1); //debug模式
define('SHOW_PAGE_TIME', 1); //输出页面执行时间
define('SHOW_SQL', 1); //打印SQL
define('REWRITE_URL', 1); //重写
//APP名字
define('APP', basename(__FILE__, '.php'));
include_once("./source/common.php");

$arrController = array('index',); //控制器列表
loadController($arrController, APP);
?>