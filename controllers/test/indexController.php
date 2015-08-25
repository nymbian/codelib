<?php

!defined('IN_CL') && exit('Forbidden');

//define('SHOW_SQL', 1);

class indexController {


    function actionIndex() {
        global $_SGLOBAL;
        var_export($_SGLOBAL);
    }

}

?>