<?php
$cfg_NotPrintHead='Y';
set_time_limit(0);
header("Content-Type: text/xml; charset=utf-8");
require_once(dirname(__FILE__)."/../include/common.inc.php");
require_once(DEDEINC."/channelunit.class.php");
require_once(DEDEINC."/baidusitemap.func.php");
require_once(DEDEINC."/baiduxml.class.php");

if(empty($dopost)) $dopost = '';
if(empty($action)) $action = '';

if ($dopost=='checkurl')
{
    $checksign = $_GET['checksign'];
    if (!$checksign || strlen($checksign) !== 32 ){
        exit();
    }
    $data = baidu_get_setting('checksign', true);
    if ($data && $data['svalue'] == $checksign && time()-$data['stime'] < 30) {
        echo $data['svalue'];
    }
} elseif ($dopost=='sitemap_index'){
    if (empty($_GET['pwd']) || $_GET['pwd'] != ($bdpwd = baidu_get_setting('bdpwd'))) {
        baidu_header_status(404);
        return 1;
    }
    $pagesize=empty($pagesize)? 0 : intval($pagesize);
    $sitemap_type=0;
    if($type=='indexall') $sitemap_type=1;
    elseif($type=='indexinc') $sitemap_type=2;
    $bdarcs = new BaiduArticleXml;
    $start=$pagesize*$bdarcs->Row;
    $bdarcs->setSitemapType($sitemap_type);
    $bdarcs->Start=$start;
    echo $bdarcs->toXml();
}