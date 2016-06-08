<?php

function lib_mynews(&$ctag,&$refObj)
{
    global $dsql,$envs;

    $attlist="row|1,titlelen|24";
    FillAttsDefault($ctag->CAttribute->Items,$attlist);
    extract($ctag->CAttribute->Items, EXTR_SKIP);

    $innertext = trim($ctag->GetInnerText());
    if(empty($row)) $row=1;
    if(empty($titlelen)) $titlelen=30;
    if(empty($innertext)) $innertext = GetSysTemplets('mynews.htm');

    $idsql = '';
    if($envs['typeid'] > 0) $idsql = " WHERE typeid='".GetTopid($this->TypeID)."' ";
    $dsql->SetQuery("SELECT * FROM #@__mynews $idsql ORDER BY senddate DESC LIMIT 0,$row");
    $dsql->Execute();
    $ctp = new DedeTagParse();
    $ctp->SetNameSpace('field','[',']');
    $ctp->LoadSource($innertext);
    $revalue = '';
    while($row = $dsql->GetArray())
    {
        foreach($ctp->CTags as $tagid=>$ctag){
            @$ctp->Assign($tagid,$row[$ctag->GetName()]);
        }
        $revalue .= $ctp->GetResult();
    }
    return $revalue;
}