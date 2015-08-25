<?php

!defined('IN_CL') && exit('Forbidden');

class L {

    /**
     * 类文件的加载入口
     *
     * @param string $className 类的名称
     * @param string $dir 目录：末尾不需要'/'
     * @param boolean $isGetInstance 是否实例化
     * @return mixed
     */
    function loadClass($className, $dir = '', $isGetInstance = true) {
        return self::_loadClass($className, 'models/' . self::_formatDir($dir), $isGetInstance);
    }

    function _loadClass($className, $dir = '', $isGetInstance = true, $classPrefix = 'CL_') {
        static $classes = array();
        $dir = self::_formatDir($dir);
        $classToken = $isGetInstance ? $className : $dir . $className; //避免重名
        if (isset($classes[$classToken]))
            return $classes[$classToken]; //避免重复初始化

        $classes[$classToken] = true; //默认值
        $fileDir = S_ROOT . $dir . strtolower($className) . '.php';

        if (!$isGetInstance)
            return (require_once self::escapePath($fileDir)); //未实例化的直接返回

        $class = $classPrefix . $className;
        if (!class_exists($class)) {
            if (file_exists($fileDir))
                require_once self::escapePath($fileDir);
            if (!class_exists($class)) { //再次验证是否存在class
                $GLOBALS['className'] = $class;
                echo 'load_class_error';
                exit;
            }
        }
        $classes[$classToken] = new $class(); //实例化
        return $classes[$classToken];
    }

    function _formatDir($dir) {
        $dir = trim($dir);
        if ($dir)
            $dir = trim($dir, "\\/") . '/';
        return $dir;
    }

    /**
     * 路径转换
     * @param $fileName
     * @param $ifCheck
     * @return string
     */
    function escapePath($fileName, $ifCheck = true) {
        if (!self::_escapePath($fileName, $ifCheck)) {
            exit('Path Forbidden');
        }
        return $fileName;
    }

    /**
     * 私用路径转换
     * @param $fileName
     * @param $ifCheck
     * @return boolean
     */
    function _escapePath($fileName, $ifCheck = true) {
        $tmpname = strtolower($fileName);
        $tmparray = array('://', "\0");
        $ifCheck && $tmparray[] = '..';
        if (str_replace($tmparray, '', $tmpname) != $tmpname) {
            return false;
        }
        return true;
    }

}

?>