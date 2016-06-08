<?php
require_once(dirname(__FILE__)."/config.php");
CheckPurview('c_New');
require_once(DEDEINC."/dedetag.class.php");
require_once(dirname(__FILE__)."/inc/inc_admin_channel.php");

if(empty($action)) $action = '';
$id = isset($id) && is_numeric($id) ? $id : 0;
$mysql_version = $dsql->GetVersion();

$row = $dsql->GetOne("SELECT fieldset,'' as maintable,addtable,issystem FROM `#@__channeltype` WHERE id='$id'");
$fieldset = $row['fieldset'];
$trueTable = $row['addtable'];

$dtp = new DedeTagParse();
$dtp->SetNameSpace("field", "<", ">");
$dtp->LoadSource($fieldset);
foreach($dtp->CTags as $ctag)
{
    if(strtolower($ctag->GetName())==strtolower($fname)) break;
}

$ds = file(dirname(__FILE__)."/inc/fieldtype.txt");
foreach($ds as $d)
{
    $dds = explode(',', trim($d));
    $fieldtypes[$dds[0]] = $dds[1];
}

if($action=='save')
{
    if(!isset($fieldtypes[$dtype]))
    {
        ShowMsg("你修改的是系统专用类型的数据，禁止操作！","-1");
        exit();
    }
    
    $dfvalue = $vdefault;
    if(preg_match("#^(select|radio|checkbox)#", $dtype))
    {
        if(!preg_match("#,#", $dfvalue))
        {
            ShowMsg("你设定了字段为 {$dtype} 类型，必须在默认值中指定元素列表，如：'a,b,c' ","-1");
            exit();
        }
    }

    if($dtype=='stepselect')
    {
        $arr = $dsql->GetOne("SELECT * FROM `#@__stepselect` WHERE egroup='$fname' ");
        if(!is_array($arr))
        {
            ShowMsg("你设定了字段为联动类型，但系统中没找到与你定义的字段名相同的联动组名!","-1");
            exit();
        }
    }

    $tabsql = "CREATE TABLE IF NOT EXISTS  `{$row['addtable']}`( `aid` int(11) NOT NULL default '0',\r\n `typeid` int(11) NOT NULL default '0',\r\n ";
    if($mysql_version < 4.1)
    {
        $tabsql .= " PRIMARY KEY  (`aid`), KEY `".$trueTable."_index` (`typeid`)\r\n) TYPE=MyISAM; ";
    }
    else
    {
        $tabsql .= " PRIMARY KEY  (`aid`), KEY `".$trueTable."_index` (`typeid`)\r\n) ENGINE=MyISAM DEFAULT CHARSET=".$cfg_db_language."; ";
    }
    $dsql->ExecuteNoneQuery($tabsql);

    $fields = array();
    $rs = $dsql->SetQuery("SHOW fields FROM `{$row['addtable']}`");
    $dsql->Execute('a');
    while($nrow = $dsql->GetArray('a',MYSQL_ASSOC))
    {
        $fields[ strtolower($nrow['Field']) ] = $nrow['Type'];
    }

    $isnull = ($isnull==1 ? "true" : "false");
    $mxlen = $maxlength;
    $fieldname = strtolower($fname);

    $fieldinfos = GetFieldMake($dtype,$fieldname,$dfvalue,$mxlen);
    $ntabsql = $fieldinfos[0];
    $buideType = $fieldinfos[1];
    $tabsql  = '';

    foreach($dtp->CTags as $tagid=>$ctag)
    {
        if($fieldname==strtolower($ctag->GetName()))
        {
            if(isset($fields[$fieldname]) && $fields[$fieldname] != $buideType)
            {
                $tabsql = "ALTER TABLE `$trueTable` CHANGE `$fieldname` ".$ntabsql;
                $dsql->ExecuteNoneQuery($tabsql);
            }else if(!isset($fields[$fieldname]))
            {
                $tabsql = "ALTER TABLE `$trueTable` ADD ".$ntabsql;
                $dsql->ExecuteNoneQuery($tabsql);
            }else
            {
                $tabsql = '';
            }
            $dtp->Assign($tagid,stripslashes($fieldstring),false);
            break;
        }
    }
    $oksetting = $dtp->GetResultNP();

    $addlist = GetAddFieldList($dtp,$oksetting);
    $oksetting = addslashes($oksetting);
    $dsql->ExecuteNoneQuery("UPDATE `#@__channeltype` SET fieldset='$oksetting',listfields='$addlist' WHERE id='$id' ");

    ShowMsg("成功更改一个字段的配置！","mychannel_edit.php?id={$id}&dopost=edit&openfield=1");
    exit();
}

else if($action=="delete")
{
    if($row['issystem']==1)
    {
        ShowMsg("对不起，系统模型的字段不允许删除！","-1");
        exit();
    }

    foreach($dtp->CTags as $tagid=>$ctag)
    {
        if(strtolower($ctag->GetName()) == strtolower($fname))
        {
            $dtp->Assign($tagid, "#@Delete@#");
        }
    }
    
    $oksetting = addslashes($dtp->GetResultNP());
    $dsql->ExecuteNoneQuery("UPDATE `#@__channeltype` SET fieldset='$oksetting' WHERE id='$id' ");
    $dsql->ExecuteNoneQuery("ALTER TABLE `$trueTable` DROP `$fname` ");
    ShowMsg("成功删除一个字段！","mychannel_edit.php?id={$id}&dopost=edit&openfield=1");
    exit();
}

require_once(DEDEADMIN."/templets/mychannel_field_edit.htm");