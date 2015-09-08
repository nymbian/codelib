<?php

!defined('IN_CL') && exit('Forbidden');

//Code By Safe3 
function customError($errno, $errstr, $errfile, $errline) {
    echo "<b>Error number:</b> [$errno],error on line $errline in $errfile<br />";
    die();
}

set_error_handler("customError", E_ERROR);
$getfilter = "'|(and|or)\\b.+?(>|<|=|in|like)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
$postfilter = "\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
$cookiefilter = "\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";

function StopAttack($StrFiltKey, $StrFiltValue, $ArrFiltReq) {

    $StrFiltValue = arr_foreach($StrFiltValue);
    if (preg_match("/" . $ArrFiltReq . "/is", $StrFiltValue) == 1) {
        slog("<br><br>操作IP: " . $_SERVER["REMOTE_ADDR"] . "<br>操作时间: " . strftime("%Y-%m-%d %H:%M:%S") . "<br>操作页面:" . $_SERVER["PHP_SELF"] . "<br>提交方式: " . $_SERVER["REQUEST_METHOD"] . "<br>来源: " . $_SERVER['HTTP_REFERER'] . "<br>提交参数: " . $StrFiltKey . "<br>提交数据: " . $StrFiltValue);
        header('HTTP/1.1 400 Bad Request');
        header('status: 400 Bad Request');
        print "<div style=\"position:fixed;top:0px;width:100%;height:100%;background-color:white;color:green;font-weight:bold;border-bottom:5px solid #999;\"><br>您的提交带有不合法参数,谢谢合作!<br><br>了解更多请点击:<a href=\"http://www.16888.com\">http://www.16888.com</a></div>";
        exit();
    }
    if (preg_match("/" . $ArrFiltReq . "/is", $StrFiltKey) == 1) {
        slog("<br><br>操作IP: " . $_SERVER["REMOTE_ADDR"] . "<br>操作时间: " . strftime("%Y-%m-%d %H:%M:%S") . "<br>操作页面:" . $_SERVER["PHP_SELF"] . "<br>提交方式: " . $_SERVER["REQUEST_METHOD"] . "<br>来源: " . $_SERVER['HTTP_REFERER'] . "<br>提交参数: " . $StrFiltKey . "<br>提交数据: " . $StrFiltValue);
        header('HTTP/1.1 400 Bad Request');
        header('status: 400 Bad Request');
        print "<div style=\"position:fixed;top:0px;width:100%;height:100%;background-color:white;color:green;font-weight:bold;border-bottom:5px solid #999;\"><br>您的提交带有不合法参数,谢谢合作!<br><br>了解更多请点击:<a href=\"http://www.16888.com\">http://www.16888.com</a></div>";
        exit();
    }
}

//$ArrPGC=array_merge($_GET,$_POST,$_COOKIE);
foreach ($_GET as $key => $value) {
    StopAttack($key, $value, $getfilter);
}
foreach ($_POST as $key => $value) {
    StopAttack($key, $value, $postfilter);
}
foreach ($_COOKIE as $key => $value) {
    StopAttack($key, $value, $cookiefilter);
}
if (file_exists('update360.php')) {
    echo "请重命名文件update360.php，防止黑客利用<br/>";
    die();
}

function slog($logs) {
    $toppath = $_SERVER["DOCUMENT_ROOT"] . "/runtime/log/logs_error.log";
    $Ts = fopen($toppath, "a+");
    fputs($Ts, $logs . "\r\n");
    fclose($Ts);
}

function arr_foreach($arr) {
    static $str;
    if (!is_array($arr)) {
        return $arr;
    }
    foreach ($arr as $key => $val) {

        if (is_array($val)) {

            arr_foreach($val);
        } else {

            $str[] = $val;
        }
    }
    return implode($str);
}

?>