<?php
if(!isset($GLOBALS['_vars'])) $GLOBALS['_vars'] = array(); 
$fields = array();
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $cfg_soft_lang; ?>">
<title>采集内容管理</title>
<link href="css/base.css" rel="stylesheet" type="text/css">
<script language="javascript">
//获得选中文件的文件名
function getCheckboxItem()
{
	var allSel="";
	if(document.form1.aids.value) return document.form1.aids.value;
	for(i=0;i<document.form1.aids.length;i++)
	{
		if(document.form1.aids[i].checked)
		{
			if(allSel=="")
				allSel=document.form1.aids[i].value;
			else
				allSel=allSel+","+document.form1.aids[i].value;
		}
	}
	return allSel;	
}
function ReSel()
{
	for(i=0;i<document.form1.aids.length;i++)
	{
		if(document.form1.aids[i].checked) document.form1.aids[i].checked = false;
		else document.form1.aids[i].checked = true;
	}
}
function DelSel()
{
	var nid = getCheckboxItem();
	location.href = "co_do.php?dopost=clear&ids="+nid;
}
function DelSel2()
{
	var nid = getCheckboxItem();
	location.href = "co_do.php?dopost=clear&clshash=true&ids="+nid;
}
function ClearCt()
{
	var nid = getCheckboxItem();
	location.href = "co_do.php?dopost=clearct&ids="+nid;
}
</script>
</head>
<body background='images/allbg.gif' leftmargin='8' topmargin='8'>
<table width="98%" border="0" cellpadding="2" cellspacing="1" bgcolor="#D6D6D6" align="center">
  <tr> 
    <td height="28" colspan="8" background='images/tbg.gif'>
    	<table width="96%" border="0" cellpadding="0" cellspacing="0">
        <tr> 
          <td width="20%" height="18" style="padding-left:10px;"><strong>临时内容管理：</strong></td>
          <td width="80%" align="right">
          	<?php echo $exportbt; ?>
          	<input type="button" name="ba2" value="节点管理" class="coolbg np" style="width:80px" onClick="location.href='co_main.php';" />
          	<input type="button" name="ba1" value="清空下载内容" class="coolbg np" style="width:100px" onClick="location.href='co_do.php?dopost=clearall';" />
          	</td>
        </tr>
      </table>
     </td>
  </tr>
  <tr align="center" height="26" bgcolor="#FBFCE2"> 
    <td width="6%">选择</td>
    <td width="30%">内容标题</td>
    <td width="15%">所属节点</td>
    <td width="10%">获取日期</td>
    <td width="8%">下载</td>
    <td width="8%">导出</td>
    <td width="10%">预设栏目</td>
    <td width="8%">来源网页</td>
  </tr>
  <form name="form1">
  <?php
$atts = array();
$atts['tagname'] = 'datalist';
$blockValue = $this->refObj->GetArcList($atts,$this->refObj,$fields); 
if(is_array($blockValue)){
foreach( $blockValue as $key=>$fields )
{
?>
  <tr align="center" bgcolor="#FFFFFF" height="26" align="center" onMouseMove="javascript:this.bgColor='#FCFDEE';" onMouseOut="javascript:this.bgColor='#FFFFFF';"> 
    <td><input type='checkbox' name='aids' value='<?php echo $fields['aid']; ?>' class='np'></td>
    <td><a href='co_view.php?aid=<?php echo $fields['aid']; ?>'><?php echo $fields['title']; ?></a></td>
    <td><a href='co_edit.php?nid=<?php echo $fields['nid']; ?>'><?php echo $fields['notename']; ?></a></td>
    <td><?php echo GetDateMk($fields['dtime']); ?></td>
    <td><?php echo IsDownLoad($fields['isdown']); ?></td>
    <td><?php echo IsExData($fields['isex']); ?></td>
    <td><?php echo $fields['typename']; ?></td>
    <td><a href='<?php echo $fields['url']; ?>' target='_blank'>[源网址]</a></td>
  </tr>
 <?php
}
}?>
</form>
  <tr> 
    <td height="30" colspan="8" bgcolor="#FCFDF7">&nbsp;
	  <input type="button" name="b11" value="反选" class="coolbg np" style="width:40px" onClick="ReSel();" /> 　
	  <input type="button" name="b12" value="仅删除网址" class="coolbg np" style="width:80px" onClick="DelSel();" />
	  <input type="button" name="b13" value="仅清空内容" class="coolbg np" style="width:80px" onClick="ClearCt();" />
	  <input type="button" name="b14" value="删除网址及历史记录" class="coolbg np" style="width:120px" onClick="DelSel2();" />
    </td>
  </tr>
  <tr> 
    <td height="36" colspan="8" bgcolor="#F9FCEF" align="center">
    <?php
$atts = array();
$atts['tagname'] = 'pagelist';
$atts['listsize'] = '5';
 echo $this->refObj->GetPageList($atts,$this->refObj,$fields); 
?>

    </td>
  </tr>
</table>
</body>
</html>