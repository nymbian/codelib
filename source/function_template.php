<?php

!defined('IN_CL') && exit('Forbidden');

$_SGLOBAL['i'] = 0;
$_SGLOBAL['block_search'] = $_SGLOBAL['block_replace'] = array();

function parse_template($tpl) {
    global $_SGLOBAL;

    //包含模板
    $_SGLOBAL['sub_tpls'] = array($tpl);

    $tplfile = S_ROOT . './' . $tpl . '.htm';
    $objfile = S_ROOT . './runtime/tpl_cache/' . str_replace('/', '_', $tpl) . '.php';


    $template = sreadfile($tplfile);
    if (empty($template)) {
        exit("Template file : $tplfile Not found or have no access!");
    }

    //模板
    @$template = preg_replace("/\<\!\-\-\{template\s+([a-z0-9_\/]+)\}\-\-\>/ie", "readtemplate('\\1')", $template);
    //处理子页面中的代码
    @$template = preg_replace("/\<\!\-\-\{template\s+([a-z0-9_\/]+)\}\-\-\>/ie", "readtemplate('\\1')", $template);

    //PHP代码
    @$template = preg_replace("/\<\!\-\-\{eval\s+(.+?)\s*\}\-\-\>/ies", "evaltags('\\1')", $template);

    //开始处理
    //变量
    $var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
    @$template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
    @$template = preg_replace("/([\n\r]+)\t+/s", "\\1", $template);
    @$template = preg_replace("/(\\\$[a-zA-Z0-9_\[\]\'\"\$\x7f-\xff]+)\.([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/s", "\\1['\\2']", $template);
    @$template = preg_replace("/\{(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+)\}/s", "<?=\\1?>", $template);
    @$template = preg_replace("/$var_regexp/es", "addquote('<?=\\1?>')", $template);
    @$template = preg_replace("/\<\?\=\<\?\=$var_regexp\?\>\?\>/es", "addquote('<?php echo \\1?>')", $template);
    //逻辑
    @$template = preg_replace("/\{elseif\s+(.+?)\}/ies", "stripvtags('<?php } elseif(\\1) { ?>','')", $template);
    @$template = preg_replace("/\{else\}/is", "<?php } else { ?>", $template);
    //循环
    for ($i = 0; $i < 6; $i++) {
        @$template = preg_replace("/\{foreach\s+(\S+)\s+(\S+)\}(.+?)\{\/foreach\}/ies", "stripvtags('<?php if(is_array(\\1)) { foreach(\\1 as \\2) { ?>','\\3<?php } } ?>')", $template);
        @$template = preg_replace("/\{foreach\s+(\S+)\s+(\S+)\s+(\S+)\}(.+?)\{\/foreach\}/ies", "stripvtags('<?php if(is_array(\\1)) { foreach(\\1 as \\2 => \\3) { ?>','\\4<?php } } ?>')", $template);
        @$template = preg_replace("/\{if\s+(.+?)\}(.+?)\{\/if\}/ies", "stripvtags('<?php if(\\1) { ?>','\\2<?php } ?>')", $template);
    }
    //常量
    @$template = preg_replace("/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/s", "<?php echo \\1?>", $template);

    //替换
    if (!empty($_SGLOBAL['block_search'])) {
        @$template = str_replace($_SGLOBAL['block_search'], $_SGLOBAL['block_replace'], $template);
    }

    //换行
    @$template = preg_replace("/ \?\>[\n\r]*\<\? /s", " ", $template);

    //附加处理
    @$template = "<?php !defined('IN_CL') && exit('Forbidden');?><?php subtplcheck('" . implode('|', $_SGLOBAL['sub_tpls']) . "', '$_SGLOBAL[timestamp]', '$tpl');?>$template";

    //write
    if (!swritefile($objfile, $template, 'w')) {
        exit("File: $objfile can not be write!");
    }
}

function addquote($var) {
    return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var));
}

function striptagquotes($expr) {
    @$expr = preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr);
    @$expr = str_replace("\\\"", "\"", preg_replace("/\[\'([a-zA-Z0-9_\-\.\x7f-\xff]+)\'\]/s", "[\\1]", $expr));
    return $expr;
}

function evaltags($php) {
    global $_SGLOBAL;

    $_SGLOBAL['i'] ++;
    $search = "<!--EVAL_TAG_{$_SGLOBAL['i']}-->";
    $_SGLOBAL['block_search'][$_SGLOBAL['i']] = $search;
    $_SGLOBAL['block_replace'][$_SGLOBAL['i']] = "<?php " . stripvtags($php) . " ?>";

    return $search;
}

function stripvtags($expr, $statement = '') {
    @$expr = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
    $statement = str_replace("\\\"", "\"", $statement);
    return $expr . $statement;
}

function readtemplate($name) {
    global $_SGLOBAL;
    $tplfile = S_ROOT . './' . $name . '.htm';
    $_SGLOBAL['sub_tpls'][] = $name;

    $content = sreadfile($tplfile);
    return $content;
}

?>