<?php

function lib_tag(&$ctag,&$refObj)
{
    global $dsql,$envs,$cfg_cmsurl;

    $attlist="row|30,sort|new,getall|0,typeid|0";
    FillAttsDefault($ctag->CAttribute->Items,$attlist);
    extract($ctag->CAttribute->Items, EXTR_SKIP);

    $InnerText = $ctag->GetInnerText();
    if(trim($InnerText)=='') $InnerText = GetSysTemplets('tag_one.htm');
    $revalue = '';

    $ltype = $sort;
    $num = $row;

    $addsql = '';

    if($getall==0 && isset($refObj->Fields['tags']) && !empty($refObj->Fields['aid']))
    {
        $dsql->SetQuery("SELECT tid FROM `#@__taglist` WHERE aid = '{$refObj->Fields['aid']}' ");
        $dsql->Execute();
        $ids = '';
        while($row = $dsql->GetArray())
      {
          $ids .= ( $ids=='' ? $row['tid'] : ','.$row['tid'] );
        }
        if($ids != '')
        {
            $addsql = " WHERE id IN($ids) ";
        }
        if($addsql=='') return '';
    }
    else
    {
        if(!empty($typeid))
        {
            $addsql = " WHERE typeid='$typeid' ";
        }
  }
  
    if($ltype=='rand') $orderby = 'rand() ';
    else if($ltype=='week') $orderby=' weekcc DESC ';
    else if($ltype=='month') $orderby=' monthcc DESC ';
    else if($ltype=='hot') $orderby=' count DESC ';
    else if($ltype=='total') $orderby=' total DESC ';
    else $orderby = 'addtime DESC  ';

    $dsql->SetQuery("SELECT * FROM `#@__tagindex` $addsql ORDER BY $orderby LIMIT 0,$num");
    $dsql->Execute();

    $ctp = new DedeTagParse();
    $ctp->SetNameSpace('field','[',']');
    $ctp->LoadSource($InnerText);
    while($row = $dsql->GetArray())
    {
        $row['keyword'] = $row['tag'];
        $row['tag'] = htmlspecialchars($row['tag']);
        $row['link'] = $cfg_cmsurl."/tags.php?/".urlencode($row['keyword'])."/";
        $row['highlight'] = 0;
        if($row['monthcc']>1000 || $row['weekcc']>300 )
        {
            $row['highlight'] = mt_rand(3,4);
        }
        else if($row['count']>3000)
        {
            $row['highlight'] = mt_rand(5,6);
        }
        else
        {
            $row['highlight'] = mt_rand(1,2);
        }
        foreach($ctp->CTags as $tagid=>$ctag)
        {
            if(isset($row[$ctag->GetName()]))
            {
                $ctp->Assign($tagid,$row[$ctag->GetName()]);
            }
        }
        $revalue .= $ctp->GetResult();
    }
    return $revalue;
}