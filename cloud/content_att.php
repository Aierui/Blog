<?php

require_once(dirname(__FILE__)."/config.php");
CheckPurview('sys_Att');
if(empty($dopost)) $dopost = '';

if($dopost=="save")
{
    $startID = 1;
    $endID = $idend;
    for(; $startID<=$endID; $startID++)
    {
        $att = ${'att_'.$startID};
        $attname = ${'attname_'.$startID};
        $sortid = ${'sortid_'.$startID};
        $query = "UPDATE `#@__arcatt` SET `attname`='$attname',`sortid`='$sortid' WHERE att='$att' ";
        $dsql->ExecuteNoneQuery($query);
    }
    echo "<script> alert('成功更新自定文档义属性表！'); </script>";
}

include DedeInclude('templets/content_att.htm');