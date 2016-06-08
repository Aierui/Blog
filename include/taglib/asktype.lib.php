<?php if(!defined('DEDEINC')) exit('Request Error!');

function lib_asktype(&$ctag,&$refObj)
{
    global $dsql, $envs, $cfg_dbprefix, $cfg_cmsurl,$cfg_ask_directory,$cfg_ask_isdomain,$cfg_ask_domain;

    $attlist="tid|0,reid|0,name|24";
    FillAttsDefault($ctag->CAttribute->Items,$attlist);
    extract($ctag->CAttribute->Items, EXTR_SKIP);
    
    if( !$dsql->IsTable("{$cfg_dbprefix}ask") ) return '没安装问答模块';

    if($cfg_ask_isdomain == 'Y')
    {
        $weburl = $cfg_ask_domain.'/';  
    }else{
        $weburl = $cfg_ask_directory.'/'; 
    }
    
    $innertext = $ctag->GetInnerText();
    if(trim($innertext)=='') $innertext = GetSysTemplets("asks.htm");
    
    if($tid > 0) $qtypeQuery = "WHERE reid=$tid ";
    else $qtypeQuery = '';
    if($reid > 0) $qtypeQuery = "WHERE reid > 0 ";

    $ctp = new DedeTagParse();
    $ctp->SetNameSpace('field', '[', ']');

    $solvingask = '';
    $query = "SELECT id,name,reid FROM `#@__asktype` $qtypeQuery";
    $dsql->Execute('me',$query);
    while($rs = $dsql->GetArray('me'))
    {
        $ctp->LoadSource($innertext);
        if($rs['reid'] != '')
            $rs['typeurl'] = $weburl."?ct=browser&tid2=".$rs['id'];
        else
            $rs['typeurl'] = $weburl."?ct=browser&tid=".$rs['id'];
        foreach($ctp->CTags as $tagid=>$ctag) {
            if(!empty($rs[strtolower($ctag->GetName())])) {
                $ctp->Assign($tagid,$rs[$ctag->GetName()]);
            }
        }
        $solvingask .= $ctp->GetResult();
    }
    return $solvingask;
}