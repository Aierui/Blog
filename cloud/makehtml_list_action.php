<?php

require_once(dirname(__FILE__)."/config.php");
CheckPurview('sys_MakeHtml');
require_once(DEDEDATA."/cache/inc_catalog_base.inc");
require_once(DEDEINC."/channelunit.func.php");

if(!isset($upnext)) $upnext = 1;
if(empty($gotype)) $gotype = '';
if(empty($pageno)) $pageno = 0;
if(empty($mkpage)) $mkpage = 1;
if(empty($typeid)) $typeid = 0;
if(!isset($uppage)) $uppage = 0;
if(empty($maxpagesize)) $maxpagesize = 50;
$adminID = $cuserLogin->getUserID();

$isremote = (empty($isremote)  ? 0 : $isremote);
$serviterm = empty($serviterm)? "" : $serviterm;

if($gotype=='' || $gotype=='mkallct')
{
    if($upnext==1 || $typeid==0)
    {
        if($typeid>0) 
        {
            $tidss = GetSonIds($typeid,0);
            $idArray = explode(',',$tidss);
        } else {
            foreach($cfg_Cs as $k=>$v) $idArray[] = $k;
        }
    } else {
        $idArray = array();
        $idArray[] = $typeid;
    }
}

else if($gotype=='mkall')
{
    $uppage = 1;
    $mkcachefile = DEDEDATA."/mkall_cache_{$adminID}.php";
    $idArray = array();
    if(file_exists($mkcachefile)) require_once($mkcachefile);
}

$totalpage=count($idArray);
if(isset($idArray[$pageno]))
{
    $tid = $idArray[$pageno];
}
else
{
    if($gotype=='')
    {
        ShowMsg("完成所有列表更新！","javascript:;");
        exit();
    }
    else if($gotype=='mkall' || $gotype=='mkallct')
    {
        ShowMsg("完成所有栏目列表更新，现在作最后数据优化！","makehtml_all.php?action=make&step=10");
        exit();
    }
}

if($pageno==0 && $mkpage==1)
{
    $dsql->ExecuteNoneQuery("Delete From `#@__arccache` ");
}

$reurl = '';

if(!empty($tid))
{
    if(!isset($cfg_Cs[$tid]))
    {
        showmsg('没有该栏目数据, 可能缓存文件(/data/cache/inc_catalog_base.inc)没有更新, 请检查是否有写入权限');
        exit();
    }
    if($cfg_Cs[$tid][1]>0)
    {
        require_once(DEDEINC."/arc.listview.class.php");
        $lv = new ListView($tid);
        $position= MfTypedir($lv->Fields['typedir']);
    }
    else
    {
        require_once(DEDEINC."/arc.sglistview.class.php");
        $lv = new SgListView($tid);        
    }
    if($lv->TypeLink->TypeInfos['ispart']==0 && $lv->TypeLink->TypeInfos['isdefault']!=-1) $ntotalpage = $lv->TotalPage;
    else $ntotalpage = 1;
    if($cfg_remote_site=='Y' && $isremote=="1")
    {
        if($serviterm!="")
        {
            list($servurl, $servuser, $servpwd) = explode(',',$serviterm);
            $config = array( 'hostname' => $servurl, 'username' => $servuser, 
                             'password' => $servpwd,'debug' => 'TRUE');
        } else {
            $config=array();
        }
        if(!$ftp->connect($config)) exit('Error:None FTP Connection!');
    }

    if($ntotalpage <= $maxpagesize || $lv->TypeLink->TypeInfos['ispart']!=0 || $lv->TypeLink->TypeInfos['isdefault']==-1)
    {
        $reurl = $lv->MakeHtml('', '', $isremote);
        $finishType = TRUE;
    }
    else
    {
        $reurl = $lv->MakeHtml($mkpage, $maxpagesize, $isremote);
        $finishType = FALSE;
        $mkpage = $mkpage + $maxpagesize;
        if( $mkpage >= ($ntotalpage+1) ) $finishType = TRUE;
    }
}

$nextpage = $pageno+1;
if($nextpage >= $totalpage && $finishType)
{
    if($gotype=='')
    {
        if(empty($reurl)) { $reurl = '../plus/list.php?tid='.$tid; }
        ShowMsg("<span style='color:#ff0000; font-family:微软雅黑; font-size:14px;'>完成所有栏目列表更新！</span><a href='$reurl' target='_blank' style='color:#111; font-family:微软雅黑; font-size:14px;'>浏览栏目</a>","javascript:;");
        exit();
    }
    else if($gotype=='mkall' || $gotype=='mkallct')
    {
        ShowMsg("完成所有栏目列表更新，现在作最后数据优化！","makehtml_all.php?action=make&step=10");
        exit();
    }
} else {
    if($finishType)
    {
        $gourl = "makehtml_list_action.php?gotype={$gotype}&uppage=$uppage&maxpagesize=$maxpagesize&typeid=$typeid&pageno=$nextpage&isremote={$isremote}&serviterm={$serviterm}";
        ShowMsg("<span style='color:#111; font-family:微软雅黑; font-size:14px;'>成功创建栏目：</span>".$tid."，继续进行操作！",$gourl,0,100);
        exit();
    } else {
        $gourl = "makehtml_list_action.php?gotype={$gotype}&uppage=$uppage&mkpage=$mkpage&maxpagesize=$maxpagesize&typeid=$typeid&pageno=$pageno&isremote={$isremote}&serviterm={$serviterm}";
        ShowMsg("栏目：".$tid."，继续进行操作...",$gourl,0,100);
        exit();
    }
}