<?php
if(!defined('DEDEINC')) exit('Request Error!');
require_once(DEDEINC.'/channelunit.func.php');

function plus_channel(&$atts, &$refObj, &$fields)
{
    global $dsql,$_vars;

    $attlist = "typeid=0,reid=0,row=100,type=son,currentstyle=";
    FillAtts($atts,$attlist);
    FillFields($atts,$fields,$refObj);
    extract($atts, EXTR_OVERWRITE);

    $line = empty($row) ? 100 : $row;
    $reArray = array();
    $reid = 0;
    $topid = 0;

    if(empty($typeid))
    {
        if( isset($refObj->TypeLink->TypeInfos['id']) )
        {
            $typeid = $refObj->TypeLink->TypeInfos['id'];
            $reid = $refObj->TypeLink->TypeInfos['reid'];
            $topid = $refObj->TypeLink->TypeInfos['topid'];
        }
        else {
          $typeid = 0;
      }
    }

    else
    {
        $row2 = $dsql->GetOne("SELECT * FROM `#@__arctype` WHERE id='$typeid' ");
        $typeid = $row2['id'];
        $reid = $row2['reid'];
        $topid = $row2['topid'];
        $issetInfos = true;
    }
    
    if($type=='' || $type=='sun') $type='son';

    if($type=='top')
    {
        $sql = "SELECT id,typename,typedir,isdefault,ispart,defaultname,namerule2,moresite,siteurl,sitepath
          FROM `#@__arctype` WHERE reid=0 AND ishidden<>1 ORDER BY sortrank ASC LIMIT 0, $line ";
    }
    else if($type=='son')
    {
        if($typeid==0) return $reArray;
        $sql = "SELECT id,typename,typedir,isdefault,ispart,defaultname,namerule2,moresite,siteurl,sitepath
          FROM `#@__arctype` WHERE reid='$typeid' AND ishidden<>1 ORDER BY sortrank ASC LIMIT 0, $line ";
    }
    else if($type=='self')
    {
        if($reid==0) return $reArray;
        $sql = "SELECT id,typename,typedir,isdefault,ispart,defaultname,namerule2,moresite,siteurl,sitepath
            FROM `#@__arctype` WHERE reid='$reid' AND ishidden<>1 ORDER BY sortrank ASC LIMIT 0, $line ";
    }

    $needRel = true;
    
    if(empty($sql)) return $reArray;

    $dsql->Execute('me',$sql);
    $totalRow = $dsql->GetTotalRow('me');

    if($type=='son' && $reid!=0 && $totalRow==0)
    {
        $sql = "SELECT id,typename,typedir,isdefault,ispart,defaultname,namerule2,moresite,siteurl,sitepath
            FROM `#@__arctype` WHERE reid='$reid' AND ishidden<>1 ORDER BY sortrank ASC LIMIT 0, $line ";
        $dsql->Execute('me', $sql);
    }
    $GLOBALS['autoindex'] = 0;
    while($row=$dsql->GetArray())
    {
        $row['currentstyle'] = $row['sonids'] = $row['rel'] = '';
        if($needRel)
        {
            $row['sonids'] = GetSonIds($row['id'], 0, false);
            if($row['sonids']=='') $row['rel'] = '';
            else $row['rel'] = " rel='dropmenu{$row['id']}'";
        }

        if( ($row['id']==$typeid || ($topid==$row['id'] && $type=='top') ) && $currentstyle!='' )
        {
            $row['currentstyle'] = $currentstyle;
        }
        $row['typelink'] = $row['typeurl'] = GetOneTypeUrlA($row);
        $reArray[] = $row;
        $GLOBALS['autoindex']++;
    }

    $dsql->FreeResult();
    return $reArray;
}