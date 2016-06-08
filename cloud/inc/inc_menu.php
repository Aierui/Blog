<?php
require_once(dirname(__FILE__)."/../config.php");

$addset = '';

if($cfg_admin_channel = 'array' && count($admin_catalogs) > 0)
{
    $admin_catalog = join(',', $admin_catalogs);
    $dsql->SetQuery(" SELECT channeltype FROM `#@__arctype` WHERE id IN({$admin_catalog}) GROUP BY channeltype ");
}
else
{
    $dsql->SetQuery(" SELECT channeltype FROM `#@__arctype` GROUP BY channeltype ");
}
$dsql->Execute();
$candoChannel = '';
while($row = $dsql->GetObject())
{
    $candoChannel .= ($candoChannel=='' ? $row->channeltype : ','.$row->channeltype);
}
if(empty($candoChannel)) $candoChannel = 1;
$dsql->SetQuery("SELECT id,typename,addcon,mancon FROM `#@__channeltype` WHERE id IN({$candoChannel}) AND id<>-1 AND isshow=1 ORDER BY id ASC");
$dsql->Execute();
while($row = $dsql->GetObject())
{
    $addset .= "";
}

$adminMenu1 = $adminMenu2 = '';
if($cuserLogin->getUserType() >= 10)
{
$adminMenu1 = "";
$adminMenu2 = "<m:top item='7_' name='模板管理' display='none' rank='temp_One,temp_Other,temp_MyTag,temp_test,temp_All'>
  <m:item name='默认模板管理' link='templets_main.php' rank='temp_All' target='main'/>
</m:top>
<m:top item='7_' name='采集管理' display='none' rank='co_NewRule,co_ListNote,co_ViewNote,co_Switch,co_GetOut'>
  <m:item name='采集节点管理' link='co_main.php' rank='co_ListNote' target='main' />
  <m:item name='临时内容管理' link='co_url.php' rank='co_ViewNote' target='main' />
  <m:item name='导入采集规则' link='co_get_corule.php' rank='co_GetOut' target='main'/>
  <m:item name='监控采集模式' link='co_gather_start.php' rank='co_GetOut' target='main'/>
  <m:item name='采集未下载内容' link='co_do.php?dopost=coall' rank='co_GetOut' target='main'/>
</m:top>
<m:top item='7_' name='附件管理' display='none' rank='sys_Upload,sys_MyUpload,plus_文件管理器'>
  <m:item name='图片管理' link='media_main.php?dopost=filemanager' rank='plus_文件管理器' target='main' />
  <m:item name='上传新文件' link='media_add.php' rank='' target='main' />
  <m:item name='附件数据管理' link='media_main.php' rank='sys_Upload,sys_MyUpload' target='main' />
</m:top>
<m:top item='7_' name='百度工具' display='none' rank='temp_One,temp_Other,temp_MyTag,temp_test,temp_All'>
  <m:item name='提交百度索引' link='baidusitemap_main.php' rank='sys_ArcBatch' target='main' />
</m:top>
<m:top item='10_' name='系统设置' display='none' rank='sys_User,sys_Group,sys_Edit,sys_Log,sys_Data'>
  <m:item name='系统基本参数' link='sys_info.php' rank='sys_Edit' target='main' />
  <m:item name='文件式管理器' link='file_manage_main.php' rank='sys_Edit' target='main' />
  <m:item name='友情链接管理' link='friendlink_main.php' rank='sys_Edit' target='main' />
  <m:item name='图片水印设置' link='sys_info_mark.php' rank='sys_Edit' target='main' />
  <m:item name='自定义表单' link='diy_main.php' rank='c_List' target='main' />
</m:top>
<m:top item='10_' name='其他设置' display='none' rank='sys_User,sys_Group,sys_Edit,sys_Log,sys_Data'>
  <m:item name='系统用户管理' link='sys_admin_user.php' rank='sys_User' target='main' />
  <m:item name='用户分组设定' link='sys_group.php' rank='sys_Group' target='main' />
  <m:item name='内容模型管理' link='mychannel_main.php' rank='c_List' target='main' />
  <m:item name='系统日志管理' link='log_list.php' rank='sys_Log' target='main' />
</m:top>
";
}
$remoteMenu = ($cfg_remote_site=='Y')? "" : "";
$menusMain = "
-----------------------------------------------

<m:top item='1_' name='栏目操作' display='block'>
  <m:item name='网站栏目' link='catalog_main.php' ischannel='1' addalt='创建栏目' linkadd='catalog_add.php?listtype=all' rank='t_List,t_AccList' target='main' />
  <m:item name='重复检测' link='article_test_same.php' rank='sys_ArcBatch' target='main' />
  <m:item name='自动摘要' link='article_description_main.php' rank='sys_Keyword' target='main' />
  <m:item name='回收站' link='recycling.php' ischannel='1' addalt='清空回收站' addico='images/gtk-del.gif' linkadd='archives_do.php?dopost=clear&aid=no&recycle=1' rank='a_List,a_AccList,a_MyList' target='main' />
</m:top>
<m:top item='1_' name='更新生成' display='none' rank='temp_One,temp_Other,temp_MyTag,temp_test,temp_All'>
  <m:item name='更新系统缓存' link='sys_cache_up.php' rank='sys_ArcBatch' target='main' />
  <m:item name='更新主页HTML' link='makehtml_homepage.php' rank='sys_MakeHtml' target='main' />
  <m:item name='更新栏目HTML' link='makehtml_list.php' rank='sys_MakeHtml' target='main' />
  <m:item name='更新文档HTML' link='makehtml_archives.php' rank='sys_MakeHtml' target='main' />
</m:top>

$adminMenu1

$adminMenu2

<m:top item='1_7_10_' name='友情链接' display='none'>
  <m:item name='网站建设' link='http://www.yunteng.cc' rank='' target='_blank' />
  <m:item name='域名主机' link='http://idc.yunteng.cc' rank='' target='_blank' />
  <m:item name='腾讯云论坛' link='http://bbs.qcloud.com' rank='' target='_blank' />
  <m:item name='联系我们' link='http://wpa.qq.com/msgrd?v=3&uin=503827438&site=qq&menu=yes' rank='' target='_blank' />
</m:top>

-----------------------------------------------
";