<?php

class mysqlDBA {

    var $db = null;

    //析构函数
    public function __construct() {
        global $_SGLOBAL;
        $this->db = $_SGLOBAL['db'];
    }

    //获取总记录数
    function getTotalSize() {
        $sSql = "SELECT FOUND_ROWS() total;";
        $sValue = $this->db->get_value($sSql);

        return $sValue;
    }

    function pageStart($iPage, $iPagesize) {
        if ($iPage > 0) {
            $iStart = ($iPage - 1) * $iPagesize;
        } else {
            $iStart = 0;
        }
        return " limit $iStart,$iPagesize";
    }

    function makeSql($arr, $type = 'insert', $tableName = '') {//传入数组，组合sql语句
        $filedsArr = array_keys($arr);
        $return = '';
        if ($type == 'insert') {
            $fileds = $values = '';
            foreach ($filedsArr as $v) {
                $fileds .= "`$v`,";
            }
            $fileds = rtrim($fileds, ',');
            foreach ($arr as $v) {
                $values .= "'$v',";
            }
            $values = rtrim($values, ',');
            $return = "($fileds) VALUE ($values)";
        } elseif ($type == 'update') {
            foreach ($filedsArr as $v) {
                $return .= "`$v`='{$arr[$v]}',";
            }
            $return = rtrim($return, ',');
        } elseif ($type == 'where') {
            foreach ($filedsArr as $v) {
                if ($tableName)
                    $return .= "`$tableName`.`$v`='{$arr[$v]}' AND ";
                else
                    $return .= "`$v`='{$arr[$v]}' AND ";
            }
            $return = rtrim($return, 'AND ');
        }
        return $return;
    }

    function testDB($sTable) {

        return "test.tt_" . $sTable;
    }
    

}

?>