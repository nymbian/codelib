<?php

class CL_test extends mysqlDBA {

    var $intTotal = 0;
    var $intTotal2 = 0;

    function getRegion($region_type = '', $parent_id = '') {//获取
        if ($region_type) {
            $strWhere = " AND region_type ='$region_type' ";
        }

        if ($parent_id) {
            $strWhere = " AND parent_id ='$parent_id' ";
        }

        $sSql = "SELECT * FROM " . $this->testDB('region') . " WHERE 1=1 $strWhere ";
        return $this->db->select($sSql, 'region_id');
    }

}

?>