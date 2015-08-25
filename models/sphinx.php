<?php

class Cl_sphinx {

    protected $sphinx;
    protected $server;
    protected $port;
    protected $_dealer;
    public $total;
    public $ids;
    public $keywords;

    public function connect($host, $port) {
        $this->server = $host;
        $this->port = $port;
        $dir = dirname(__FILE__);

        file_exists(S_ROOT . '../lib/sphinxapi.php') && include (S_ROOT . '../lib/sphinxapi.php');
        $this->sphinx = new SphinxClient();
        $this->sphinx->SetServer($this->server, $this->port); //连接sphinx服务
        $this->sphinx->SetConnectTimeout(3); //设置超时时间
        $this->sphinx->SetMaxQueryTime(2000); //设定查询最大时间 ，单位为MS
    }

    private function setsearch($sortArr) {//设置配置为按权重排序
        $this->sphinx->SetConnectTimeout(3); //设置超时时间
        $this->sphinx->SetMaxQueryTime(2000); //设定查询最大时间 ，单位为MS
        //$this->sphinx->SetMatchMode ( SPH_MATCH_PHRASE);//匹配模式按顺序全词匹配
        $this->sphinx->SetMatchMode(SPH_MATCH_ALL); //匹配模式按顺序全词匹
        $this->sphinx->SetSortMode(SPH_SORT_EXTENDED, 'starsId DESC,@weight DESC'); //匹配模式按顺序全词匹配
    }

    public function search_car($key, $offset = 0, $limit = 10, $indexer = "car", $filter = array()) {
        if (empty($key))
            return false;
        $this->sphinx->SetConnectTimeout(3); //设置超时时间
        $this->sphinx->SetMaxQueryTime(2000); //设定查询最大时间 ，单位为MS
        $this->sphinx->SetMatchMode(SPH_MATCH_ANY); //查询模式
        $this->sphinx->SetSortMode(SPH_SORT_EXTENDED, '@weight DESC,hit DESC'); //匹配模式按顺序全词匹配
        $this->sphinx->SetLimits($offset, $limit);
        if ($filter)
            $this->sphinx->SetFilter($filter['att'], $filter['values'], $filter['exclude']);
        $list = $this->sphinx->Query($key, $indexer);
        if (empty($list['matches']))
            return $res;
        $ids = array_keys($list["matches"]);
        return $ids;
    }

    function _setFilter($att, $values, $exclude = false) {
        $this->sphinx->SetFilter($att, $values, $exclude);
    }

    function _resetFilter() {
        $this->sphinx->ResetFilters();
    }

    function _setGroupBy($att, $groupsort = "@group desc", $func = SPH_GROUPBY_ATTR) {
        $this->sphinx->SetGroupBy($att, $func, $groupsort);
    }

    function _resetGroupBy() {
        $this->sphinx->ResetGroupBy();
    }

    function _search($key, $offset = 0, $limit = 10, $indexer = "", $matchMode = SPH_MATCH_ANY, $sortAtt = '@weight DESC', $sortMode = SPH_SORT_EXTENDED) {
        $this->sphinx->SetMatchMode($matchMode); //查询模式
        $this->sphinx->SetSortMode($sortMode, $sortAtt); //匹配模式按顺序全词匹配
        if ($limit) {
            $this->sphinx->SetLimits($offset, $limit);
        }
        $arrList = $this->sphinx->Query($key, $indexer);
        return $arrList;
    }

}
