<?php   if(!defined('DEDEINC')) exit("Request Error!");

class Model
{
    var $dsql;
    var $db;

    function Model()
    {
        global $dsql;
        if ($GLOBALS['cfg_mysql_type'] == 'mysqli')
        {
            $this->dsql = $this->db = isset($dsql)? $dsql : new DedeSqli(FALSE);
        } else {
            $this->dsql = $this->db = isset($dsql)? $dsql : new DedeSql(FALSE);
        }
            
    }

    function __destruct() 
    {
        $this->dsql->Close(TRUE);
    }
}