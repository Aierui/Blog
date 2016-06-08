<?php
if(!defined('DEDEINC'))
{
    exit("Request Error!");
}

function lib_softmsg(&$ctag,&$refObj)
{
    global $dsql;

    $revalue = '';
    $row = $dsql->GetOne(" SELECT * FROM `#@__softconfig` ");
    if(is_array($row)) $revalue = $row['downmsg'];
    return $revalue;
}