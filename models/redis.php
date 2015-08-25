<?php

!defined('CAR_BUY') && exit('Forbidden');

class CL_redis {

    var $redis;

    function __construct() {
        file_exists(S_ROOT . '/config/redis.conf.php') && include (S_ROOT . '/config/redis.conf.php');
        $this->redis = new Redis();
        $this->redis->connect($host, $port);
    }

    function _set($key, $val, $time = 0) {
        if (empty($val)) {
            if (is_array($val)) {
                $val = array('empty');
            } elseif ($val != 0) {
                $val = 'empty';
            }
        }
        if (is_array($val)) {
            $val = json_encode($val);
        }
        $return = $this->redis->set($key, $val);
        if ($time > 0) {
            $return = $this->_expire($key, $time);
        }
        $return = $this->_expire($key, 60);
        return $return;
    }

    function _get($key, $type = 'str') {
        $val = $this->redis->get($key);
        if (defined('SHOW_REDIS_KEY')) {
            echo $key . "<br>";
        }
        if (defined('CLEAR_REDIS')) {
            $this->_del($key);
            echo "clear " . $key . "<br>";
        }
        if ($type == 'json') {
            $val = json_decode($val, true);
        }
        return $val;
    }

    function _del($key) {
        $return = $this->redis->del($key);
        return $return;
    }

    function _zadd($key, $score, $val, $time = 0) {
        if (is_array($val)) {
            $val = json_encode($val);
        }
        $setExpire = false;
        if ((!$this->_exists($key) || $this->_ttl($key) == -1) && $time > 0) {
            $setExpire = true;
        }
        $return = $this->redis->zadd($key, $score, $val);
        if ($setExpire) {
            $this->_expire($key, $time);
        }

        return $return;
    }

    function _zrem($key, $val) {//删除
        $return = $this->redis->zrem($key, $val);

        return $return;
    }

    function _incrby($key, $num = 1) {//自增
        $return = $this->redis->incrby($key, $num);

        return $return;
    }

    function _decrby($key, $num = 1) {//自减
        $return = $this->redis->decrby($key, $num);

        return $return;
    }

    function _zincrby($key, $val, $num = 1) {//有序链表自增
        $return = $this->redis->zincrby($key, $num, $val);

        return $return;
    }

    function _zrange($key, $start = 0, $end = -1, $boolWithScore = false, $type = 'str') {//正序获取
        $return = $this->redis->zrange($key, $start, $end, $boolWithScore);
        if (defined('CLEAR_REDIS') && $return[0] != 'empty') {
            foreach ($return as $val) {
                $this->_zrem($key, $val);
                echo "clear " . $key . " " . $val . "<br>";
            }
        }

        if ($return && $type == 'json') {
            foreach ($return as $k => $v) {
                $return[$k] = json_decode($v, true);
            }
        }
        if (defined('SHOW_REDIS_KEY')) {
            echo $key . "<br>";
        }

        return $return;
    }

    function _zrevrange($key, $start = 0, $end = -1, $boolWithScore = false, $type = 'str') {//倒序获取
        $return = $this->redis->zrevrange($key, $start, $end, $boolWithScore);
        if (defined('CLEAR_REDIS') && $return[0] != 'empty') {
            foreach ($return as $val) {
                $this->_zrem($key, $val);
                echo "clear " . $key . " " . $val . "<br>";
            }
        }
        if ($return && $type == 'json') {
            foreach ($return as $k => $v) {
                $return[$k] = json_decode($v, true);
            }
        }
        if (defined('SHOW_REDIS_KEY')) {
            echo $key . "<br>";
        }
        return $return;
    }

    function _lpush($key, $val) {
        $return = $this->redis->lpush($key, $val);

        return $return;
    }

    function _rpush($key, $val) {
        $return = $this->redis->rpush($key, $val);

        return $return;
    }

    function _ltrim($key, $start, $end) {
        $return = $this->redis->ltrim($key, $start, $end);

        return $return;
    }

    function _lrange($key, $start, $end) {
        return $this->redis->lrange($key, $start, $end);
    }

    function _zcard($key) {//返回集合个数
        return $this->redis->zcard($key);
    }

    function _exists($key) {
        return $this->redis->exists($key);
    }

    function _ttl($key) {
        return $this->redis->ttl($key);
    }

    function _expire($key, $time) {//单位秒
        return $this->redis->expire($key, $time);
    }

}

?>