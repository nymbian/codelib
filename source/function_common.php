<?php

!defined('IN_CL') && exit('Forbidden');

//SQL ADDSLASHES
function saddslashes($string) {
    if (is_array($string)) {
        foreach ($string as $key => $val) {
            $string[$key] = saddslashes($val);
        }
    } else {
        $string = preg_replace('/(?!<[^>]*)"(?![^<]*>)/', '&quot;', $string);
        if (!MAGIC_QUOTES_GPC)
            $string = addslashes($string);
    }
    return $string;
}

//取消HTML代码
function shtmlspecialchars($string) {
    if (is_array($string)) {
        foreach ($string as $key => $val) {
            $string[$key] = shtmlspecialchars($val);
        }
    } else {
        $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4})|[a-zA-Z][a-z0-9]{2,5});)/', '&\\1', str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string));
    }
    return $string;
}

//字符串解密加密
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {

    $ckey_length = 4; // 随机密钥长度 取值 0-32;
    // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
    // 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
    // 当此值为 0 时，则不产生随机密钥

    $key = md5($key ? $key : UC_KEY);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);

    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);

    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if ($operation == 'DECODE') {
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc . str_replace('=', '', base64_encode($result));
    }
}

//数据库连接

function dbconnect($app) {
    global $_SGLOBAL;

    include_once(S_ROOT . '/source/class_mysql.php');
    include_once(S_ROOT . '/config/mysql.conf.php');


    if ($_dbhost['app']) {
        $_slave = $_dbhost['app'];
    } else {
        $_slave = $_dbhost['default'];
    }


    //随机实例化读数据库
    if (empty($_SGLOBAL['db'])) {
        $_SGLOBAL['db'] = new dbstuff;
        $_SGLOBAL['db']->charset = $_slave['dbcharset'];
        $_SGLOBAL['db']->connect($_slave['dbhost'], $_slave['dbuser'], $_slave['dbpw'], $_slave['dbname'], $_slave['pconnect'], true, 'ro');
    }
}

//站点链接
function getSiteUrl() {

    $uri = $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : ($_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME']);
    return shtmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . substr($uri, 0, strrpos($uri, '/') + 1));
}

//判断提交是否正确
function submitcheck($var) {

    if (!empty($_POST[$var]) && $_SERVER['REQUEST_METHOD'] == 'POST') {

        if ((empty($_SERVER['HTTP_REFERER']) || preg_replace("/https?:\/\/([^\:\/]+).*/i", "\\1", $_SERVER['HTTP_REFERER']) == preg_replace("/([^\:]+).*/", "\\1", $_SERVER['HTTP_HOST'])) && $_POST[$var] == formhash()) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

//获取字符串
function str_cut($string, $length, $dot = '...', $sSetChar = 'utf-8') {
    $strlen = strlen($string);

    if ($strlen <= $length)
        return $string;
    else
        $length = $length - 3;
    $string = str_replace(array('&nbsp;', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), array(' ', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), $string);

    //$string = preg_replace("/(\<[^\<]*\>|\r|\n|\s|\[.+?\])/is", ' ', $string);

    $strcut = '';
    if (strtolower($sSetChar) == 'utf-8') {

        $n = $tn = $noc = 0;
        while ($n < $strlen) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n++;
                $noc++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t < 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n++;
            }
            if ($noc >= $length)
                break;
        }
        if ($noc > $length)
            $n -= $tn;
        $strcut = substr($string, 0, $n);
    }
    else {
        $dotlen = strlen($dot);
        $maxi = $length - $dotlen - 1;
        for ($i = 0; $i < $maxi; $i++) {
            $strcut .= ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
        }
    }
    $strcut = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&#039;', '&lt;', '&gt;'), $strcut);
    return $strcut . $dot;
}

//获取指定字符串长度
function utf8Substr($str, $from, $len) {
    return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,' . $from . '}((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,' . $len . '}).*#s', '$1', $str);
}

//时间格式化
function sgmdate($dateformat, $timestamp = 0, $format = 0) {

    $timeoffset = 8;
    $result = "";
    if ($timestamp == 0) {
        return "--";
    }
    if ($format) {
        $time = time() - $timestamp;
        if ($time > 24 * 3600) {
            $result = gmdate($dateformat, $timestamp + $timeoffset * 3600);
        } elseif ($time > 3600) {
            $result = intval($time / 3600) . "小时前";
        } elseif ($time > 60) {
            $result = intval($time / 60) . "分钟前";
        } elseif ($time > 0) {
            $result = $time . "秒钟前";
        } else {
            $result = "现在";
        }
    } else {
        $result = gmdate($dateformat, $timestamp + $timeoffset * 3600);
    }
    return $result;
}

//字符串时间化
function sstrtotime($string) {
    global $_SGLOBAL, $_shopONFIG;
    $time = '';
    if ($string) {
        $time = strtotime($string);
        if (gmdate('H:i', $_SGLOBAL['timestamp'] + $_shopONFIG['timeoffset'] * 3600) != date('H:i', $_SGLOBAL['timestamp'])) {
            $time = $time - $_shopONFIG['timeoffset'] * 3600;
        }
    }
    return $time;
}

//模板调用
function template($name) {
    global $_SGLOBAL;

    if (strexists($name, '/')) {
        $tpl = $name;
    }
    $objfile = S_ROOT . './runtime/tpl_cache/' . str_replace('/', '_', $tpl) . '.php';
    if (defined('D_BUG')) {
        @unlink($objfile);
    }
    if (!file_exists($objfile) || @filemtime(S_ROOT . $tpl . '.htm') > @filemtime($objfile)) {
        @unlink($objfile);
        include_once(S_ROOT . './source/function_template.php');
        parse_template($tpl);
    }

    return $objfile;
}

//子模板更新检查
function subtplcheck($subfiles, $mktime, $tpl) {


    $subfiles = explode('|', $subfiles);
    foreach ($subfiles as $subfile) {
        $tplfile = S_ROOT . './' . $subfile . '.htm';
        @$submktime = filemtime($tplfile);
        if ($submktime > $mktime) {
            include_once(S_ROOT . './source/function_template.php');
            parse_template($tpl);
            break;
        }
    }
}

function Htm_cv($url, $tag) {
    echo urlRewrite($url);
    return stripslashes($tag) . urlRewrite($url) . '"';
}

function urlRewrite($url) {
    global $db_htmifopen;
    if (!$db_htmifopen)
        return $url;
    $tmppos = strpos($url, '#');
    $add = $tmppos !== false ? substr($url, $tmppos) : '';
    $turl = str_replace(array('?mod=', '=', '&amp;', '&', $add), array('mod-', '-', '-', '-', ''), $url);
    $turl = preg_replace('/controller-([^-]+)(-|)/', '/$1/', $turl);
    $turl = preg_replace('/action-([^-]+)(-|)/', '/$1/', $turl);
    $turl = str_replace(array('//'), array('/'), $turl);
    $turl = preg_replace('/^\/index\/([^\/]+)$/', '/$1', $turl);
    $turl != $url && $turl .= $db_ext;
    return $turl . $add;
}

//rewrite链接
function rewrite_url($pre, $para) {
    $para = str_replace(array('&', '='), array('-', '-'), $para);
    return '<a href="' . $pre . $para . '.html"';
}

//处理搜索关键字
function stripsearchkey($string) {
    $string = trim($string);
    $string = str_replace('*', '%', addcslashes($string, '%_'));
    $string = str_replace('_', '\_', $string);
    return $string;
}

//连接字符
function simplode($ids) {
    return "'" . implode("','", $ids) . "'";
}

//格式化大小函数
function formatsize($size) {
    $prec = 3;
    $size = round(abs($size));
    $units = array(0 => " B ", 1 => " KB", 2 => " MB", 3 => " GB", 4 => " TB");
    if ($size == 0)
        return str_repeat(" ", $prec) . "0$units[0]";
    $unit = min(4, floor(log($size) / log(2) / 10));
    $size = $size * pow(2, -10 * $unit);
    $digi = $prec - 1 - floor(log($size) / log(10));
    $size = round($size * pow(10, $digi)) * pow(10, -$digi);
    return $size . $units[$unit];
}

//获取文件内容
function sreadfile($filename) {
    $content = '';
    if (function_exists('file_get_contents')) {
        @$content = file_get_contents($filename);
    } else {
        if (@$fp = fopen($filename, 'r')) {
            @$content = fread($fp, filesize($filename));
            @fclose($fp);
        }
    }
    return $content;
}

//写入文件
function swritefile($filename, $writetext, $openmod = 'a+') {

    if (@$fp = fopen($filename, $openmod)) {
        flock($fp, 2);
        fwrite($fp, $writetext);
        fclose($fp);
        return true;
    } else {
        runlog('error', "File: $filename write error.");
        return false;
    }
}

//判断字符串是否存在
function strexists($haystack, $needle) {
    return !(strpos($haystack, $needle) === FALSE);
}

//去掉slassh
function sstripslashes($string) {
    if (is_array($string)) {
        foreach ($string as $key => $val) {
            $string[$key] = sstripslashes($val);
        }
    } else {
        $string = stripslashes($string);
    }
    return $string;
}

//编码转换
function siconv($str, $out_charset, $in_charset = '') {
    global $_shop;

    $in_charset = empty($in_charset) ? strtoupper($_shop['charset']) : strtoupper($in_charset);
    $out_charset = strtoupper($out_charset);
    if ($in_charset != $out_charset) {
        if (function_exists('iconv') && (@$outstr = iconv("$in_charset//IGNORE", "$out_charset//IGNORE", $str))) {
            return $outstr;
        } elseif (function_exists('mb_convert_encoding') && (@$outstr = mb_convert_encoding($str, $out_charset, $in_charset))) {
            return $outstr;
        }
    }
    return $str; //转换失败
}

//检查start
function ckstart($start, $perpage) {
    global $_shopONFIG;

    $maxstart = $perpage * intval($_shopONFIG['maxpage']);
    if ($start < 0 || ($maxstart > 0 && $start >= $maxstart)) {
        showmessage('length_is_not_within_the_scope_of');
    }
}

//截取链接
function sub_url($url, $length) {
    if (strlen($url) > $length) {
        $url = str_replace(array('%3A', '%2F'), array(':', '/'), rawurlencode($url));
        $url = substr($url, 0, intval($length * 0.5)) . ' ... ' . substr($url, - intval($length * 0.3));
    }
    return $url;
}

//产生form防伪码
function formhash() {
    global $_SGLOBAL;

    if (empty($_SGLOBAL['formhash'])) {
        $hashadd = defined('IN_CL') ? 'Only For CODELIB' : '';
        $_SGLOBAL['formhash'] = substr(md5(substr($_SGLOBAL['timestamp'], 0, -7) . '|' . md5(getSiteUrl()) . '|' . $hashadd), 8, 8);
    }
    return $_SGLOBAL['formhash'];
}

//检查邮箱是否有效
function isemail($email) {
    return strlen($email) > 6 && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
}

//获取目录
function sreaddir($dir, $extarr = array()) {
    $dirs = array();
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            if (!empty($extarr) && is_array($extarr)) {
                if (in_array(strtolower(fileext($file)), $extarr)) {
                    $dirs[] = $file;
                }
            } else if ($file != '.' && $file != '..') {
                $dirs[] = $file;
            }
        }
        closedir($dh);
    }
    return $dirs;
}

//页面跳转
function TipsInfo($info = '', $sUrl = "", $sNewUrl = '') {
    if ($sUrl != '' and $sNewUrl != '') {
        echo "<script language='javascript'>if(confirm('" . $info . "')){location.href='" . $sUrl . "'}else{location.href='" . $sNewUrl . "'};</script>";
    } elseif ($sUrl != '' && $info != "") {
        echo "<script language='javascript'>alert('" . $info . "');location.href='" . $sUrl . "';</script>";
    } elseif ($sUrl != '' && $info == '') {
        echo "<script language='javascript'>location.href='" . $sUrl . "';</script>";
    } else {
        echo "<script language='javascript'>alert('" . $info . "');history.back();</script>";
    }
    exit;
}

//时间段提示
function topUserMsg() {
    $iHours = intval(gmdate("H", time() + 8 * 3600));
    if ($iHours >= 0 && $iHours < 6) {
        $sMsg = "凌晨了，请注意休息";
    }
    if ($iHours >= 6 && $iHours < 12) {
        $sMsg = "上午好";
    }
    if ($iHours >= 12 && $iHours < 14) {
        $sMsg = "中午好";
    }
    if ($iHours >= 14 && $iHours < 18) {
        $sMsg = "下午好";
    }
    if ($iHours >= 18 && $iHours < 24) {
        $sMsg = "晚上好";
    }
    return $sMsg;
}

//转向
function jump($sUrl) {
    header("Location: " . $sUrl);
    exit;
}

/**
 * 获得用户的真实IP地址
 *
 * @access  public
 * @return  string
 */
function real_ip() {
    static $realip = NULL;

    if ($realip !== NULL) {
        return $realip;
    }

    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
            foreach ($arr AS $ip) {
                $ip = trim($ip);

                if ($ip != 'unknown') {
                    $realip = $ip;

                    break;
                }
            }
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $realip = $_SERVER['REMOTE_ADDR'];
            } else {
                $realip = '0.0.0.0';
            }
        }
    } else {
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $realip = getenv('HTTP_CLIENT_IP');
        } else {
            $realip = getenv('REMOTE_ADDR');
        }
    }

    preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
    $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';

    return $realip;
}

function postcurl($data) {
    $ch = curl_init();
    // 设置curl允许执行的最长秒数
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    // 获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // 从证书中检查SSL加密算法是否存在
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
    if ($data['type'] == "post") {
        //发送一个常规的POST请求，类型为：application/x-www-form-urlencoded，就像表单提交的一样。
        foreach ($data['fileds'] as $key => $value) {
            $fields_string.=$key . '=' . $value . '&';
        }
        $fields_string = rtrim($fields_string, '&');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $data['url']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    } else {
        curl_setopt($ch, CURLOPT_URL, $data['url']);
    }
    $res = curl_exec($ch);
    return $res;
}

/**
 * 生成分页HTML的函数
 * $numofpage 总页数
 * $page 当前页
 * $url 页面链接
  × $total_num 总记录数
 * $max 最大显示页数
 */
function numofpage($page, $numofpage, $url = '', $bookmark = '', $total_num = '', $max = '', $ajaxurl = '') {
    global $rewriteHandler;
    if (!empty($max)) {
        $max = (int) $max;
        $numofpage > $max && $numofpage = $max;
    }
    $total = $numofpage;
    if ($numofpage <= 1 || !is_numeric($page)) {
        return '';
    } else {
        $prev = $page - 1 > 0 ? $page - 1 : 1;
        $next = $page + 1 < $total ? $page + 1 : $total;
        $prev_text = '上一页';
        $next_text = '下一页';
        $page_text = '页数：';
        if ($page > 1)
            $pages = "<a class=\"f12\" href=\"" . $url . $prev . ".html\">{$prev_text}</a>";
        if ($page > 5)
            $pages .= " <a href=\"" . $url . "1.html\">1</a> ... ";
        for ($i = $page - 3; $i <= $page - 1; $i++) {
            if ($i < 1)
                continue;
            $pages .= " <a href=\"" . $url . $i . ".html\">$i</a>";
        }
        $pages .= " <b>$page</b>";
        if ($page < $numofpage) {
            $flag = 0;
            for ($i = $page + 1; $i <= $numofpage; $i++) {
                $pages .= " <a href=\"" . $url . $i . ".html\">$i</a>";
                $flag++;
                if ($flag == 4)
                    break;
            }
        }
        if ($i < $total) {
            $pages .= " ... <a href=\"" . $url . $total . ".html\">{$total}</a> ";
        }
        if ($page < $total)
            $pages .= " <a class=\"f12\" href=\"" . $url . $next . ".html\">{$next_text}</a>";
        //else $pages .= " <a class=\"na\">{$next_text}</a>";
        return $pages;
    }
}

function csubstrs($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
    if (empty($str))
        return false;
    $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    /* if(function_exists("mb_substr")){
      $str=mb_substr($str, $start, $length, $charset);
      if(count($match[0])>$length) return $str.'...';
      else return $str;
      } */
    //$slice = join("",array_slice($match[0], $start, $length));
    $tooLong = false;
    $i = 0;
    $j = 0;
    $temp = '';
    $str = $match[0] ? $match[0] : $match;
    $strCount = count($str);
    do {
        if ($strCount <= $j)
            break;
        if (preg_match("/[\x80-\xff]/", $str[$j]))
            $i+=2;
        else
            $i++;
        if ($i > $length) {
            $tooLong = true;
            break;
        }
        $temp .= $str[$j++];
    } while ($i <= $length);
    if ($suffix && $tooLong)
        return $temp . '...';
    return $temp;
}

function _queryString($queryString) {
    global $db_htmifopen;
    if (empty($db_htmifopen))
        return $queryString;
    $return = '';
    $self_array = false !== strpos($queryString, '&') ? array() : explode('-', $queryString);

    for ($i = 0, $s_count = count($self_array); $i < $s_count - 1; $i++) {
        $_key = $self_array[$i];
        $_value = rawurlencode($self_array[++$i]);
        $return .= "&$_key=$_value";
    }
    if ($i < $s_count)
        $return .= "&action={$self_array[$i]}";
    $return = ltrim($return, '&');
    return $return;
}

function parseRewriteQueryString($queryString) {
    $_NGET = array();
    $self_array = false !== strpos($queryString, '&') ? array() : explode('-', $queryString);

    for ($i = 0, $s_count = count($self_array); $i < $s_count - 1; $i++) {
        $_key = $self_array[$i];
        $_value = rawurldecode($self_array[++$i]);
        $_NGET[$_key] = addslashes($_value);
    }
    if ($i < $s_count)
        $_NGET['extra'] = addslashes(rawurldecode($self_array[$i]));
    return $_NGET;
}

function getTimePeriod($time) {//获取早上，中午，下午，晚上
    $time = intval($time);
    $return = '';
    if ($time < 12)
        $return = '上午';
    elseif ($time < 19)
        $return = '下午';
    elseif ($time <= 23)
        $return = '晚上';
    elseif ($time < 7)
        $return = '凌晨';
    return $return;
}

function getMonday($dIn) {
    if ($dIn == 0) {
        $getMonday = date("d") + (-6);
    } else {
        $getMonday = date("d") + (($dIn - 1) * -1);
    }
    return $getMonday;
}


function Cookie($ck_Var, $ck_Value, $ck_Time = '', $db_ckpath = '/') {
    global $cookiepre;
    !$ck_Time && $ck_Time = time() + 3600 * 24;
    setcookie($cookiepre . $ck_Var, $ck_Value, $ck_Time, $db_ckpath, COOKIE_DOMAIN);
}

/*
  获取cookie
 */

function GetCookie($Var) {
    global $cookiepre;
    return $_COOKIE[$cookiepre . $Var];
}

/*
  删除cookie
 */

function ShiftCookie($ck_Var, $db_ckpath = '/') {
    global $cookiepre;
    $ck_Time = time() - 1;
    setcookie($cookiepre . $ck_Var, '', $ck_Time, $db_ckpath, COOKIE_DOMAIN);
}

function resizeImage($url, $width, $height = 0, $boolYuanCheng = false) {
    $url = preg_replace('/^(.*?)\.(jpg|gif|png|bmp)[\S]+/is', "$1.$2", $url);
    if (strstr($url, "16888.com")) {
        $extension = pathinfo($url, PATHINFO_EXTENSION);
        if ($width > 0 && $height > 0) {
            return $url . "_" . $width . "x" . $height . "." . $extension;
        } elseif ($width > 0) {
            return $url . "_" . $width . "." . $extension;
        }
        return $url . "_0x" . $height . "." . $extension;
    } elseif ($boolYuanCheng) {
        return $height ? "http://www.16888.com/lib/cutimg.php?w=$width&h=$height&url=" . $url : "http://www.16888.com/lib/cutimg.php?w=$width&url=" . $url;
    } else {
        return $url;
    }
}

function testTime() {
    global $sqlstarttime, $_SGLOBAL;
    if (defined('SHOW_PAGE_TIME')) {
        //页面速度测试
        $mtime = explode(' ', microtime());
        $sqlendttime = number_format(($mtime[1] + $mtime[0] - $_SGLOBAL['supe_starttime']), 6) * 1000;
        $totaltime = round(($sqlendttime - $sqlstarttime), 3);
        echo '<!--' . $sqlendttime . '-->';
    }
}

function redisInit() {
    static $obj;
    if (!isset($obj)) {
        include S_ROOT . '/models/redis.class.php';
        $obj = new CL_redis;
    }
    return $obj;
}

function sphinxInit($serverId = 0) {
    static $obj;
    if (!isset($obj[$serverId])) {
        include_once(S_ROOT . '/models/sphinx.class.php');
        include_once(S_ROOT . '/config/sphinx.conf.php');
        if (!isset($arrSphinxHost[$serverId])) {
            echo 'can\'t find this sphinx server';
            exit;
        }
        $obj[$serverId] = new CL_sphinx;
        $obj[$serverId]->connect($arrSphinxHost[$serverId]['host'], $arrSphinxHost[$serverId]['port']);
    }
    return $obj[$serverId];
}

function makedir($strFilePath, $arrDir = array(), $num = 0) {//传入文件路径
    $arrDir[$num] = dirname($strFilePath);
    if (file_exists($arrDir[$num])) {
        for ($i = $num - 1; $i >= 0; $i--) {
            !file_exists($arrDir[$i]) && @mkdir($arrDir[$i], 0755);
        }
        return true;
    }
    return makedir($arrDir[$num], $arrDir, ++$num);
}

/**
 * 写文件
 *
 * @param string $fileName 文件绝对路径
 * @param string $data 数据
 * @param string $method 读写模式
 * @param bool $ifLock 是否锁文件
 * @param bool $ifCheckPath 是否检查文件名中的“..”
 * @param bool $ifChmod 是否将文件属性改为可读写
 * @return bool 是否写入成功   :注意rb+创建新文件均返回的false,请用wb+
 */
function writeover($data, $fileName, $method = 'rb+', $ifLock = true, $ifCheckPath = true, $ifChmod = true) {
    $filePath = dirname($fileName);
    !file_exists($filePath) && @mkdir($filePath, 0755);
    touch($fileName);
    $handle = fopen($fileName, $method);
    $ifLock && flock($handle, LOCK_EX);
    $writeCheck = fwrite($handle, $data);
    $method == 'rb+' && ftruncate($handle, strlen($data));
    fclose($handle);
    $ifChmod && @chmod($fileName, 0755);
    return $writeCheck;
}

function readover($fileName, $method = 'rb') {
    $data = '';
    if ($handle = @fopen($fileName, $method)) {
        flock($handle, LOCK_SH);
        $data = @fread($handle, filesize($fileName));
        fclose($handle);
    }
    return $data;
}

function timeSubtraction($time1, $time2) {//计算时间差值，返回N年N月
    $time1 = date("Y-m", $time1);
    $time2 = date("Y-m", $time2);
    list($year1, $month1) = explode('-', $time1);
    list($year2, $month2) = explode('-', $time2);
    $year = intval($year2) - intval($year1);
    $month = abs(intval($month2) - intval($month1));
    $return = '';
    !empty($year) && $return = $year . '年';
    !empty($month) && $return .= $month . '月';
    return $return;
}

function verifyBrowser($strUserAgent = '') {
    $strUserAgent = $strUserAgent ? $strUserAgent : $_SERVER['HTTP_USER_AGENT'];
    $strBrowser = '其他';
    if (strstr($strUserAgent, 'MSIE 6.0')) {
        $strBrowser = 'IE6';
    } elseif (strstr($strUserAgent, 'MSIE 7.0')) {
        $strBrowser = 'IE7';
    } elseif (strstr($strUserAgent, 'MSIE 8.0')) {
        $strBrowser = 'IE8';
    } elseif (strstr($strUserAgent, 'MSIE 9.0')) {
        $strBrowser = 'IE9';
    } elseif (strstr($strUserAgent, 'MSIE 10.0')) {
        $strBrowser = 'IE10';
    } elseif (strstr($strUserAgent, 'Firefox')) {
        $strBrowser = 'Firefox';
    } elseif (strstr($strUserAgent, 'Chrome')) {
        $strBrowser = 'Google';
    } elseif (strstr($strUserAgent, '360')) {
        $strBrowser = '360安全浏览器';
    }
    $arrBrowser = getBrowser();
    foreach ($arrBrowser as $v) {
        if ($v['browser'] == $strBrowser) {
            $intBrowser = $v['id'];
            break;
        }
    }
    return array('name' => $strBrowser, 'id' => $intBrowser);
}

function sysSortArray($ArrayData, $KeyName1, $SortOrder1 = "SORT_ASC", $SortType1 = "SORT_REGULAR") {
    if (!is_array($ArrayData)) {
        return $ArrayData;
    }

    // Get args number.
    $ArgCount = func_num_args();

    // Get keys to sort by and put them to SortRule array.
    for ($I = 1; $I < $ArgCount; $I ++) {
        $Arg = func_get_arg($I);
        if (!preg_match("/SORT/", $Arg)) {
            $KeyNameList[] = $Arg;
            $SortRule[] = '$' . $Arg;
        } else {
            $SortRule[] = $Arg;
        }
    }

    // Get the values according to the keys and put them to array.
    foreach ($ArrayData AS $Key => $Info) {
        foreach ($KeyNameList AS $KeyName) {
            ${$KeyName}[$Key] = $Info[$KeyName];
        }
    }

    // Create the eval string and eval it.
    $EvalString = 'array_multisort(' . join(",", $SortRule) . ',$ArrayData);';
    eval($EvalString);
    return $ArrayData;
}

function filterBlackLink($str) {
    $arrFilter = array(
        0 => '\.autohome\.com\.cn',
        1 => 'auto\.sina\.com\.cn',
        2 => '\.xcar\.com\.cn',
        3 => 'auto\.sohu\.com',
        4 => '\.cheshi\.com',
        5 => '\.pcauto\.com\.cn',
        6 => '\.bitauto\.com',
        7 => 'auto\.qq\.com',
        8 => '\.chinacars\.com',
        9 => 'auto\.163\.com',
        10 => '\.autofan\.com\.cn',
        11 => 'auto\.ifeng\.com',
        12 => '\.xgo\.com\.cn',
        13 => '\.webcars\.com\.cn',
        14 => '\.chexun\.com',
        15 => '\.chetx\.com',
        16 => '\.xincheping\.com',
    );
    foreach ($arrFilter as $v) {
        $str = preg_replace("/<a([^>]+{$v}[^>]+)>/is", '<a>', $str);
    }
    return $str;
}

function stripTags($str) {//过滤HTML
    $str = trim(strip_tags($str));
    $str = str_replace('&nbsp;', '', $str);
    //$str=str_replace(' ','',$str);
    $str = preg_replace('/(\t|\r\n|\n)/', '', $str);
    return trim($str);
}

function myDateFormat($time) {
    $now = time();
    $t = $now - $time;
    if ($t > 24 * 3600) {
        $time = intval($t / (3600 * 24)) . "天前";
    } elseif ($t > 3600) {
        $time = intval($t / 3600) . "小时前";
    } elseif ($t < 3600 && $t >= 60) {
        $time = intval($t / 60) . "分钟前";
    } else {
        $time = "刚刚";
    }
    return $time;
}

function getImageShape($image_file) {
    @$image_size = getimagesize($image_file);
    if ($image_size[1] > 0 && $image_size[0] / $image_size[1] > 1) {
        return 1;
    } else {
        return 0;
    }
}

function loadController($arrController, $app) {
    $controller = saddslashes($_GET['controller']);

    $controller = $controller ? $controller : 'index';
    if (in_array($controller, $arrController)) {
        define('CONTROLLER', $controller);
        $action = !empty($_GET['action']) ? $_GET['action'] : (!empty($_POST['action']) ? $_POST['action'] : 'index');
        define('ACTION', $action);
    } else {
        header('location: /');
        exit;
    }

    require(S_ROOT . 'controllers/' . $app . '/' . $controller . 'Controller.php');

    $controllerClassName = $controller . 'Controller';
    $controllerClassObj = new $controllerClassName;
    if (method_exists($controllerClassObj, 'action' . $action)) {
        $actionName = 'action' . $action;
        $controllerClassObj->$actionName();
    }
    testTime();
}

?>
