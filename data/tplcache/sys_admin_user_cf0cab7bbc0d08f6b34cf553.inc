<?php
if(!isset($GLOBALS['_vars'])) $GLOBALS['_vars'] = array(); 
$fields = array();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gb2312 echo $cfg_soft_lang; ?>">
<title>管理员帐号</title>
<link href="css/base.css" rel="stylesheet" type="text/css">
</head>
<body background='images/allbg.gif' leftmargin='8' topmargin='8'>
<table width="98%" border="0" align="center" cellpadding="2" cellspacing="0" bgcolor="#f2f2f2">
  <tr>
    <td height="27" colspan="7"  bgcolor="#ffffff">
    	<table width="96%" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="24%" style="padding-left:10px; font-size:16px; font-weight:bold; color:#467A00;">管理员帐号</td>
          <td width="76%" align="right">
          	<a href="sys_admin_user_add.php" style="background:url(images/inputbg3.gif); color:#fff; display:block; height:25px; width:90px; text-align:center; line-height:25px; float:right; margin-right:3px; margin-left:3px;">增加管理员</a>
          	<a href="sys_group.php" style="background:url(images/inputbg3.gif); color:#fff; display:block; height:25px; width:90px; text-align:center; line-height:25px; float:right;">用户组管理</a>
          	<div style=" clear:both;"></div>
          </td>
        </tr>
      </table>
     </td>
  </tr>
  <tr bgcolor="#FFFFFF">
    <td height="34" colspan="7" style="background:#FFFCED; border:1px solid #FFBE7A;">　§为了防止错误操作，系统不允许在管理界面删除超级管理员，如果确实要删除，请自行在数据表yt_admin中删除。</td>
  </tr>
  

    <tr bgcolor="#EEF3F7" >
        <td align="center">登录ID</td>
        <td align="center">笔名</td>
        <td align="center">级别</td>
        <td align="center">频道</td>
        
        <td align="center">登录时间</td>
        <td align="center">登录IP</td>
		<td align="center">管理项</td>
    </tr>
	 <?php
$atts = array();
$atts['tagname'] = 'datalist';
$blockValue = $this->refObj->GetArcList($atts,$this->refObj,$fields); 
if(is_array($blockValue)){
foreach( $blockValue as $key=>$fields )
{
?>
	<tr height="39" >
        <td class="bline" align="center"><?php echo $fields['userid']; ?></td>
        <td class="bline" align="center"><?php echo $fields['uname']; ?></td>
        <td class="bline" align="center"><?php echo GetUserType($fields['usertype']); ?></td>
        <td class="bline" align="center"><?php echo GetChannel($fields['typename']); ?></td>
        
        <td class="bline" align="center"><?php echo GetDateTimeMk($fields['logintime']); ?></td>
        <td class="bline" align="center"><?php echo $fields['loginip']; ?></td>
		<td class="bline" align="center"><a href='sys_admin_user_edit.php?id=<?php echo $fields['id']; ?>&dopost=edit'><u>更改</u></a> |
		 <a href='sys_admin_user_edit.php?id=<?php echo $fields['id']; ?>&userid=<?php echo $fields['userid']; ?>&dopost=delete'><u>删除</u></a> |
		 <a href='content_list.php?mid=<?php echo $fields['id']; ?>'><u>文档</u></a>　</td>
    </tr>
	 <?php
}
}?>

  
  
  <tr bgcolor="#F9FCEF">
    <td height="24" colspan="7" align="center" valign="top">
    	<?php
$atts = array();
$atts['tagname'] = 'pagelist';
$atts['listsize'] = '7';
 echo $this->refObj->GetPageList($atts,$this->refObj,$fields); 
?>

    </td>
  </tr>
</table>
</body>
</html>