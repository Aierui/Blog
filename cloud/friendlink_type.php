<?php

require_once(dirname(__FILE__)."/config.php");
if(empty($dopost)) $dopost = '';

if($dopost=="save")
{
    $startID = 1;
    $endID = $idend;
    for(;$startID<=$endID;$startID++)
    {
        $query = '';
        $tid = ${'ID_'.$startID};
        $pname =   ${'pname_'.$startID};
        if(isset(${'check_'.$startID}))
        {
            if($pname!='')
            {
                $query = "UPDATE `#@__flinktype` SET typename='$pname' WHERE id='$tid' ";
                $dsql->ExecuteNoneQuery($query);
            }
        }
        else
        {
            $query = "DELETE FROM `#@__flinktype` WHERE id='$tid' ";
            $dsql->ExecuteNoneQuery($query);
        }
    }

    if(isset($check_new) && $pname_new!='')
    {
        $query = "INSERT INTO `#@__flinktype`(typename) VALUES('{$pname_new}');";
        $dsql->ExecuteNoneQuery($query);
    }
    header("Content-Type: text/html; charset={$cfg_soft_lang}");
    echo "<script> alert('成功更新友情链接网站分类表！'); </script>";
}

include DedeInclude('templets/friendlink_type.htm');