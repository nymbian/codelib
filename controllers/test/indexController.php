<?php

!defined('IN_CL') && exit('Forbidden');

//define('SHOW_SQL', 1);

class indexController {


    function actionIndex() {
		$obj = L::loadClss('test');
		$province = $obj->getRegion(1);
        include_once template('views/test/index');
    }

}

?>