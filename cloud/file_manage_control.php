<?php

require(dirname(__FILE__)."/config.php");
CheckPurview('plus_文件管理器');
require(DEDEINC."/oxwindow.class.php");
require_once(DEDEADMIN.'/file_class.php');
$activepath = str_replace("..", "", $activepath);
$activepath = preg_replace("#^\/{1,}#", "/", $activepath);
if($activepath == "/") $activepath = "";
if($activepath == "") $inpath = $cfg_basedir;
else $inpath = $cfg_basedir.$activepath;

$fmm = new FileManagement();
$fmm->Init();

if($fmdo=="rename")
{
    $fmm->RenameFile($oldfilename,$newfilename);
}

else if($fmdo=="newdir")
{
    $fmm->NewDir($newpath);
}

else if($fmdo=="move")
{
    $fmm->MoveFile($filename,$newpath);
}

else if($fmdo=="del")
{
    $fmm->DeleteFile($filename);
}

else if($fmdo=="edit")
{
    $filename = str_replace("..", "", $filename);
    $file = "$cfg_basedir$activepath/$filename";
    $str = stripslashes($str);
    $fp = fopen($file, "w");
    fputs($fp, $str);
    fclose($fp);
    if(empty($backurl))
    {
        ShowMsg("成功保存一个文件！","file_manage_main.php?activepath=$activepath");
    }
    else
    {
        ShowMsg("成功保存文件！",$backurl);
    }
    exit();
}

else if($fmdo=="upload")
{
    $j=0;
    for($i=1; $i<=50; $i++)
    {
        $upfile = "upfile".$i;
        $upfile_name = "upfile".$i."_name";
        if(!isset(${$upfile}) || !isset(${$upfile_name}))
        {
            continue;
        }
        $upfile = ${$upfile};
        $upfile_name = ${$upfile_name};
        if(is_uploaded_file($upfile))
        {
            if(!file_exists($cfg_basedir.$activepath."/".$upfile_name))
            {
                move_uploaded_file($upfile, $cfg_basedir.$activepath."/".$upfile_name);
            }
            @unlink($upfile);
            $j++;
        }
    }
    ShowMsg("成功上传 $j 个文件到: $activepath","file_manage_main.php?activepath=$activepath");
    exit();
}
else if($fmdo=="space")
{
    if($activepath=="")
    {
        $ecpath = "所有目录";
    }
    else
    {
        $ecpath = $activepath;
    }
    $titleinfo = "目录 <a href='file_manage_main.php?activepath=$activepath'><b><u>$ecpath</u></b></a> 空间使用状况：<br/>";
    $wintitle = "文件管理";
    $wecome_info = "文件管理::空间大小检查 [<a href='file_manage_main.php?activepath=$activepath'>文件浏览器</a>]</a>";
    $activepath=$cfg_basedir.$activepath;
    $space = new SpaceUse;
    $space->checksize($activepath);
    $total=$space->totalsize;
    $totalkb=$space->setkb($total);
    $totalmb=$space->setmb($total);
    $win = new OxWindow();
    $win->Init("","js/blank.js","POST");
    $win->AddTitle($titleinfo);
    $win->AddMsgItem("　　$totalmb M<br/>　　$totalkb KB<br/>　　$total 字节");
    $winform = $win->GetWindow("");
    $win->Display();
}