<?php

require_once(dirname(__FILE__)."/config.php");
CheckPurview('sys_ArcBatch');
if(empty($dopost)) $dopost = '';
if(empty($step)) $step = 1;

if($dopost=="ok")
{
    if(empty($uparc)) $uparc = 0;
    if($step == -1)
    {
        if($uparc == 0) sleep(1);
        ShowMsg("<span style='color:#333;font-family:微软雅黑; color:#ff0000;'>成功更新所有缓存！</span>","javascript:;");
        exit();
    }

    else if($step == 1)
    {
        UpDateCatCache();
        ClearOptCache();
        ShowMsg("<span style='color:#333;font-family:微软雅黑; '>成功更新栏目缓存，及后台栏目选项,准备更新枚举缓存...</span>","sys_cache_up.php?dopost=ok&step=2&uparc=$uparc");
        exit();
    }

    else if($step == 2)
    {
        include_once(DEDEINC."/enums.func.php");
        WriteEnumsCache();

        ShowMsg("<span style='color:#333;font-family:微软雅黑; '>成功更新枚举缓存，准备更新调用缓存...</span>", "sys_cache_up.php?dopost=ok&step=3&uparc=$uparc");
        exit();
    }

    else if($step == 3)
    {
        echo '<meta http-equiv="Content-Type" content="text/html; charset=gb2312'.$cfg_soft_lang.'">';
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__arccache`");
        echo "\n<span style='color:#333;font-family:微软雅黑; font-size:12px; '>成功更新arclist调用缓存，准备清理过期会员访问历史...</span><br /><br />";
        $oldtime = time() - (90 * 24 * 3600);
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__member_vhistory` WHERE vtime<'$oldtime' ");
        echo "<span style='color:#333;font-family:微软雅黑;font-size:12px; '>成功清理过期会员访问历史，准备清理过期短信...</span><br /><br />";
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__member_pms` WHERE sendtime<'$oldtime' ");
        echo "<span style='color:#333;font-family:微软雅黑; font-size:12px; '>成功清理过期短信，准备修正错误文档，这可能要占较长的时间...</span>";
        if($uparc == 1)
        {
            echo "<script language='javascript'>location='sys_cache_up.php?dopost=ok&step=9';</script>";
        }
        else
        {
            echo "<script language='javascript'>location='sys_cache_up.php?dopost=ok&step=-1&uparc=$uparc';</script>";
        }
        exit();
    }

    else if($step == 9)
    {
        ShowMsg('修正错误文档操作已经取消，请在&lt;系统-&gt;系统错误修复[S]&gt;中操作...','sys_cache_up.php?dopost=ok&step=-1&uparc=1',0,5000);
      exit();
    }
}
include DedeInclude('templets/sys_cache_up.htm');