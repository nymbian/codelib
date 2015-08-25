<?php

//数据库配置参数

$_dbhost = array(
    //默认数据库帐号
    'default' => array(
        'dbhost' => '192.168.2.246:3333', //服务器地址
        'dbuser' => 'sqladmin', //用户
        'dbpw' => 'cps@99', //密码
        'dbcharset' => 'utf8', //字符集
        'pconnect' => 0, //是否持续连接
        'dbname' => 'meepet', //数据库
        'tablepre' => 'mp_', //表名前缀
        'charset' => 'utf-8', //页面字符集
        'gzipcompress' => 0, //启用gzip
    ),
    //APP账号
    'test' => array(
        'dbhost' => '192.168.2.246:3333', //服务器地址
        'dbuser' => 'sqladmin', //用户
        'dbpw' => 'cps@99', //密码
        'dbcharset' => 'utf8', //字符集
        'pconnect' => 0, //是否持续连接
        'dbname' => 'meepet', //数据库
        'tablepre' => 'mp_', //表名前缀
        'charset' => 'utf-8', //页面字符集
        'gzipcompress' => 0, //启用gzip
    ),
);
?>