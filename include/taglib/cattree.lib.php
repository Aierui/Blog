<?php   if(!defined('DEDEINC')) exit('Request Error!');

function lib_cattree(&$ctag, &$refObj)
{
    global $dsql;
    $attlist="showall|,catid|0";
    FillAttsDefault($ctag->CAttribute->Items,$attlist);
    extract($ctag->CAttribute->Items, EXTR_SKIP);
    $revalue = '';

    if(empty($typeid))
    {
        if( isset($refObj->TypeLink->TypeInfos['id']) ) {
            $typeid = $refObj->TypeLink->TypeInfos['id'];
            $reid = $refObj->TypeLink->TypeInfos['reid'];
            $topid = $refObj->TypeLink->TypeInfos['topid'];
            $channeltype = $refObj->TypeLink->TypeInfos['channeltype'];
            $ispart = $refObj->TypeLink->TypeInfos['ispart'];
            if($reid==0) $topid = $typeid;
        } else {
          $typeid = $reid = $topid = $channeltype = $ispart = 0;
        }
    }
    else
    {
        $row = $dsql->GetOne("SELECT reid,topid,channeltype,ispart FROM `#@__arctype` WHERE id='$typeid' ");
        if(!is_array($row))
        {
            $typeid = $reid = $topid = $channeltype = $ispart = 0;
        } else {
            $reid = $row['reid'];
            $topid = $row['topid'];
            $channeltype = $row['channeltype'];
            $ispart = $row['ispart'];
        }
    }
    if( !empty($catid) )
    {
        $topQuery = "SELECT id,typename,typedir,isdefault,ispart,defaultname,namerule2,moresite,siteurl,sitepath FROM `#@__arctype` WHERE reid='$catid' And ishidden<>1 ";
    }
    else
    {
        if($showall == "yes" )
        {
            $topQuery = "SELECT id,typename,typedir,isdefault,ispart,defaultname,namerule2,moresite,siteurl,sitepath FROM `#@__arctype` WHERE reid='$topid' ";
        }
        else
        {
           if($showall=='')
           {
                   if( $ispart < 2 && !empty($channeltype) ) $showall = $channeltype;
                   else $showall = 6;
           }
           $topQuery = "SELECT id,typename,typedir,isdefault,ispart,defaultname,namerule2,moresite,siteurl,sitepath FROM `#@__arctype` WHERE reid='{$topid}' And channeltype='{$showall}' And ispart<2 And ishidden<>1 ";
        }
    }
  $dsql->Execute('t', $topQuery);
  while($row = $dsql->GetArray('t'))
  {
      $row['typelink'] = GetOneTypeUrlA($row);
    $revalue .= "<dl class='cattree'>\n";
    $revalue .= "<dt><a href='{$row['typelink']}'>{$row['typename']}</a></dt>\n";
    cattreeListSon($row['id'], $revalue);
    $revalue .= "</dl>\n";
  }
    return $revalue;
}

function cattreeListSon($id, &$revalue)
{
    global $dsql;
    $query = "SELECT id,typename,typedir,isdefault,ispart,defaultname,namerule2,moresite,siteurl,sitepath FROM `#@__arctype` WHERE reid='{$id}' And ishidden<>1 ";
    $dsql->Execute($id, $query);
    $thisv = '';
  while($row = $dsql->GetArray($id))
  {
      $row['typelink'] = GetOneTypeUrlA($row);
    $thisv .= "    <dl class='cattree'>\n";
    $thisv .= "    <dt><a href='{$row['typelink']}'>{$row['typename']}</a></dt>\n";
    cattreeListSon($row['id'], $thisv);
    $thisv .= "    </dl>\n";
  }
  if($thisv!='') $revalue .= "    <dd>\n$thisv    </dd>\n";
}