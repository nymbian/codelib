<?php

!defined('IN_CL') && exit('Forbidden');

class dbstuff {

    var $querynum = 0;
    var $link;
    var $charset;

    function connect($dbhost, $dbuser, $dbpw, $dbname = '', $pconnect = 0, $halt = TRUE) {
        if ($pconnect) {
            if (!$this->link = @mysql_pconnect($dbhost, $dbuser, $dbpw)) {
                $halt && $this->halt('Can not connect to MySQL server');
            }
        } else {
            if (!$this->link = @mysql_connect($dbhost, $dbuser, $dbpw, 1)) {
                $halt && $this->halt('Can not connect to MySQL server');
            }
        }

        if ($this->version() > '4.1') {
            if ($this->charset) {
                @mysql_query("SET character_set_connection=$this->charset, character_set_results=$this->charset, character_set_client=binary", $this->link);
            }
            if ($this->version() > '5.0.1') {
                @mysql_query("SET sql_mode=''", $this->link);
            }
        }
        if ($dbname) {
            @mysql_select_db($dbname, $this->link);
        }
    }

    function select_db($dbname) {
        return mysql_select_db($dbname, $this->link);
    }

    function fetch_array($query, $result_type = MYSQL_ASSOC) {
        return mysql_fetch_array($query, $result_type);
    }

    function query($sql, $type = '') {
        if (defined('SHOW_SQL')) {
            global $_SGLOBAL;
            $sqlstarttime = $sqlendttime = 0;
            $mtime = explode(' ', microtime());
            $sqlstarttime = number_format(($mtime[1] + $mtime[0] - $_SGLOBAL['supe_starttime']), 6) * 1000;
            echo $sql . '<br>';
        }
        $func = $type == 'UNBUFFERED' && @function_exists('mysql_unbuffered_query') ?
                'mysql_unbuffered_query' : 'mysql_query';
        if (!($query = $func($sql, $this->link)) && $type != 'SILENT') {
            $this->halt('MySQL Query Error', $sql);
        }
        if (defined('SHOW_SQL')) {
            $mtime = explode(' ', microtime());
            $sqlendttime = number_format(($mtime[1] + $mtime[0] - $_SGLOBAL['supe_starttime']), 6) * 1000;
            $sqltime = round(($sqlendttime - $sqlstarttime), 3);
            echo $sqltime . '<br>';
            $explain = array();
            $info = mysql_info();
            if ($query && preg_match("/^(select )/i", $sql)) {
                $explain = mysql_fetch_assoc(mysql_query('EXPLAIN ' . $sql, $this->link));
            }
            $_SGLOBAL['debug_query'][] = array('sql' => $sql, 'time' => $sqltime, 'info' => $info, 'explain' => $explain);
        }
        $this->querynum++;
        return $query;
    }

    //获取数组
    function select($sql, $keyfield = '') {

        $array = array();
        $result = $this->query($sql);
        while ($r = $this->fetch_array($result)) {
            if ($keyfield) {

                $key = $r["$keyfield"];

                $array[$key] = $r;
            } else {
                $array[] = $r;
            }
        }
        $this->free_result($result);
        return $array;
    }

    //获取全部
    function get_all($sql, $keyfield = '') {

        return $this->select($sql, $keyfield);
    }

    //获取一维数组
    function get_one($sql) {
        $array = array();
        $result = $this->query($sql);
        $array = $this->fetch_array($result);
        $this->free_result($result);

        return $array;
    }

    //获取结果
    function get_value($sql, $key = '', $type = '') {
        $query = $this->query($sql, $type);
        $result = $this->fetch_array($query, MYSQL_BOTH);
        return $result[$key] ? $result[$key] : $result[0];
    }

    /**
     * 方法：执行Sql命令，返回最后插入ID号
     * @sql -- Sql语句
     */
    function get_MaxID($sql) {
        $this->query($sql);
        return $this->insert_id();
    }

    /**
     * 方法：执行Sql命令，没有记录返回
     * @sql -- Sql语句
     */
    function update($sql) {
        $this->query($sql);
        if ($this->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    function affected_rows() {
        return mysql_affected_rows($this->link);
    }

    function error() {
        return (($this->link) ? mysql_error($this->link) : mysql_error());
    }

    function errno() {
        return intval(($this->link) ? mysql_errno($this->link) : mysql_errno());
    }

    function result($query, $row) {
        $query = @mysql_result($query, $row);
        return $query;
    }

    function num_rows($query) {
        $query = mysql_num_rows($query);
        return $query;
    }

    function num_fields($query) {
        return mysql_num_fields($query);
    }

    function free_result($query) {
        return mysql_free_result($query);
    }

    function insert_id() {
        return ($id = mysql_insert_id($this->link)) >= 0 ? $id : $this->result($this->query("SELECT last_insert_id()"), 0);
    }

    function fetch_row($query) {
        $query = mysql_fetch_row($query);
        return $query;
    }

    function fetch_fields($query) {
        return mysql_fetch_field($query);
    }

    function version() {
        return mysql_get_server_info($this->link);
    }

    function close() {
        return mysql_close($this->link);
    }

    function halt($message = '', $sql = '') {

        $dberror = $this->error();
        $dberrno = $this->errno();

        $strLog = 'Date:' . date("Y-m-d H:i:s") . "\r\n";
        $strLog .= 'REFERER:' . $_SERVER['HTTP_REFERER'] . "\r\n";
        $strLog .= 'URL:' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "\r\n";
        $strLog .= 'IP:' . $_SERVER['REMOTE_ADDR'] . "\r\n";
        $strLog .= 'HTTP_USER_AGENT:' . $_SERVER['HTTP_USER_AGENT'] . "\r\n";
        $strLog .= 'Message:' . $message . "\r\n";
        $strLog .= 'SQL:' . $sql . "\r\n";
        $strLog .= 'MYSQL Error:' . $dberror . "\r\n";
        $strLog .= 'MYSQL ErrorNo:' . $dberrno . "\r\n";
        $strLog .= "\r\n\r\n";
        @file_put_contents(S_ROOT . './runtime/log/mysql_error.txt', $strLog, FILE_APPEND);

        if (D_BUG) {
            echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body><div style="position:absolute;font-size:11px;font-family:verdana,arial;background:#EBEBEB;padding:0.5em;">
				<b>MySQL Error</b><br>
				<b>Message</b>: ' . $message . '<br>
				<b>SQL</b>: ' . $sql . '<br>
				<b>Error</b>: ' . $dberror . '<br>
				<b>Errno.</b>: ' . $dberrno . '<br>
				</div>
				</body>
				</html>';
        } else {


            header('HTTP/1.1 500 Internal Server Error');
            echo '服务器累的回火星了，请按F5刷新或稍后再试。';
        }

        exit();
    }

}

?>