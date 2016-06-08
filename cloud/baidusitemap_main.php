<?php
set_time_limit(0);
require_once(dirname(__FILE__)."/config.php");
require_once(DEDEINC."/oxwindow.class.php");
require_once(DEDEINC."/channelunit.class.php");
require_once(DEDEINC."/baidusitemap.func.php");
require_once(DEDEINC."/baiduxml.class.php");

if(empty($dopost)) $dopost = '';
if(empty($action)) $action = '';

check_installed();

if($dopost=='auth'){
	$siteurl=$cfg_basehost;
    $sigurl="http://baidu.api.dedecms.com/index.php?siteurl=".urlencode($siteurl);
    $result = baidu_http_send($sigurl);
	//var_dump($result);exit();
    $data = json_decode($result, true); 
    baidu_set_setting('siteurl', $data['siteurl']);
    baidu_set_setting('checksign', $data['checksign']);
    if($data['status']==0){
        $checkurl=$siteurl."{$cfg_plus_dir}/baidusitemap.php?dopost=checkurl&checksign=".$data['checksign'];

        $authurl="http://zz.baidu.com/api/opensitemap/auth?siteurl=".$data ['siteurl']."&checkurl=".urlencode($checkurl)."&checksign=".$data['checksign'];
        $authdata = baidu_http_send($authurl);
        $output = json_decode($authdata, true);
        if($output['status']==0){
            baidu_set_setting('pingtoken', $output['token']);
            if($action=='resubmit')
            {
                $sign = md5($data['siteurl'].$output['token']);
                baidu_delsitemap($data['siteurl'],1,$sign);
                baidu_set_setting('bdpwd','');
            }
            $old_bdpwd = baidu_get_setting('bdpwd');
            if(empty($old_bdpwd))
            {
                $bdpwd = baidu_gen_sitemap_passwd();
                baidu_set_setting('bdpwd', $bdpwd);
                $sign = md5($data['siteurl'].$output['token']);
                //提交全量索引
                $allreturnjson = baidu_savesitemap('save',$data ['siteurl'], 1, $bdpwd, $sign);
                $allresult = json_decode($allreturnjson['json'], true);
                baidu_set_setting('lastuptime_all', time());
            } else {
                //提交增量索引
                $sign = md5($data['siteurl'].$output['token']);
                baidu_delsitemap($data['siteurl'],2,$sign);
                $allreturnjson = baidu_savesitemap('save',$data['siteurl'], 2, $old_bdpwd, $sign);
                $allresult = json_decode($allreturnjson['json'], true);
                baidu_set_setting('lastuptime_inc', time());
            }
            if(0==$allresult['status'])
            {
                ShowMsg("成功提交百度地图索引","baidusitemap_main.php",0,5000);
                exit();
            } else {
                ShowMsg("提交百度地图索引失败","baidusitemap_main.php",0,5000);
                exit();
            }
        } else {
            ShowMsg("提交百度地图索引失败，无法校验本地密钥！","baidusitemap_main.php");
            exit();
        }
    }
} elseif($dopost=='checkupdate')
{
    $get_latest_ver = baidu_http_send('http://baidu.api.dedecms.com/index.php?c=welcome&m=get_latest_ver');
    if(version_compare($get_latest_ver, PLUS_BAIDUSITEMAP_VER,'>'))
    {
        ShowMsg("检查到有新版本，请前去下载！<br /><a href='http://www.yunteng.cc' target='_blank' style='color:blue'>点击前去下载</a> <a href='baidusitemap_main.php' >返回</a>","javascript:;");
        exit();
    } else {
        ShowMsg("当前为最新版本，无须下载更新！","javascript:;");
        exit();
    }
} elseif($dopost=='viewsub')
{
    $query="SELECT * FROM `#@__plus_baidusitemap_list` ORDER BY sid DESC";
    $dsql->SetQuery($query);
    $dsql->Execute('dd');
    $liststr="";
    while($arr=$dsql->GetArray('dd'))
    {
        $typestr=$arr['type']==1?'[全量]':'[增量]';
        $liststr.="&nbsp;&nbsp;&nbsp;{$typestr} {$arr['url']}<br/>\r\n";
    }
    //返回成功信息
    $msg = "
    {$liststr}";
    $msg = "<div style=\"line-height:20px;\">    {$msg}</div>";

    $wintitle = '您已经提交的索引列表：';
    $wecome_info = '<a href=\'baidusitemap_main.php\'>索引操作</a> 》百度结构化数据地图::索引操作';
    $win = new OxWindow();
    $win->AddTitle($wintitle);
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow('hand', '&nbsp;', false);
    $win->Display();
} else {
    //返回成功信息
    $siteurl = baidu_get_setting('siteurl');
    $setupmaxaid = baidu_get_setting('setupmaxaid');
    $lastuptime_all = date('Y-m-d',baidu_get_setting('lastuptime_all'));
    $lastuptime_inc = date('Y-m-d',baidu_get_setting('lastuptime_inc'));
    $bdarcs = new BaiduArticleXml;
    $bdarcs->setSitemapType(1);
    $maxaid = $bdarcs->getMaxAid();
    $msg = "    　请选择您需要的操作：
    <a href='javascript:isGoUrl(\"baidusitemap_main.php?dopost=auth\",\"是否确定提交增量索引？\");'><u>提交增量索引</u></a>
    &nbsp;&nbsp;
    <a href='javascript:isGoUrl(\"baidusitemap_main.php?dopost=auth&action=resubmit\",\"是否确定重新提交全量索引？\");' ><u>重新提交全量索引</u></a>
    &nbsp;&nbsp;
    <a href='baidusitemap_main.php?dopost=viewsub'><u>查看提交索引</u></a>
    &nbsp;&nbsp;
<br />
<div style=\"padding:20px; color:#666\">
插件信息：<br />
站点地址：<span style='color:black'><b>{$siteurl}</b></span><br />
最后提交文档ID：<span style='color:black'><b>{$setupmaxaid}</b></span>，当前文档最新ID：<span style='color:black'><b>{$maxaid}</b></span><br />
增量索引最后提交：<span style='color:black'><b>{$lastuptime_all}</b></span><br />
全量索引最后提交：<span style='color:black'><b>{$lastuptime_inc}</b></span><br />
<hr/>
功能说明：<br />
【提交增量索引】
用于提交更新频率较频繁的索引，一般是全量索引提交完成后，每次更新少量内容后进行增量索引提交；<br />
【重新提交全量索引】
重新对全站的百度地图索引进行提交；<br />
<span style=\"color:red\">注：点击提交后可能等待时间较长，请耐心等待。</span><br />
</div>
  ";
    $msg = "<div style=\"line-height:36px;height:280px\">{$msg}</div><script type=\"text/javascript\">
function isGoUrl(url,msg)
{
	if(confirm(msg))
	{
		window.location.href=url;
	} else {
		return false;
	}
}
</script>";

    $wintitle = '提交百度结构化数据地图索引！';
    $wecome_info = '百度结构化数据地图::索引操作';
    $win = new OxWindow();
    $win->AddTitle($wintitle);
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow('hand', '&nbsp;', false);
    $win->Display();
}