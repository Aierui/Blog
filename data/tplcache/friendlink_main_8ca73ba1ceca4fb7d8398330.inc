<?php
if(!isset($GLOBALS['_vars'])) $GLOBALS['_vars'] = array(); 
$fields = array();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gb2312 echo $cfg_soft_lang; ?>">
<title>友情链接管理</title>
<link href="css/base.css" rel="stylesheet" type='text/css'>
<script language='javascript'>
function Sel(stype)
{
	  for(var i=0;i < document.getElementsByName('aids').length;i++)
	  {
		  if(stype=='all') document.getElementsByName('aids')[i].checked=true;
		  else document.getElementsByName('aids')[i].checked=false;
	  }
}
function checkSubmit()
{
	var str = '';
	for(var i=0;i < document.getElementsByName('aids').length;i++)
	{
		  if(document.getElementsByName('aids')[i].checked){
		  	if(str=='') str += document.getElementsByName('aids')[i].value;
		  	else str += ',' + document.getElementsByName('aids')[i].value;
		  }
	}
	if(str=='')
	{
		alert('你没选择任何内容！');
		return false;
	}
	else
	{
		location="friendlink_edit.php?aids="+str+"&dopost=delall";
	}
}
</script>
</head>
<body background='images/allbg.gif' leftmargin='8' topmargin='8'>

<table width="98%" border="0" align="center" cellpadding="3" cellspacing="0" bgcolor="#D6D6D6">
  <tr>
    <td height="28" bgcolor="#ffffff"colspan="8" style="padding-left:1px;">
  <div style="float:left; font-size:16px; color:#4B8200; font-weight:bold;">
  	友情链接管理
	</div>
	<div style="float:right;padding-right:6px;">
		<a href="friendlink_add.php" style="width:90px; height:27px; background:url(images/inputbg3.gif) no-repeat; border:none; color:#fff; display:block; text-align:center; float:right; margin-right:10px;">增加链接</a>
 	 	<a href="friendlink_type.php" style="width:90px; height:27px; background:url(images/inputbg3.gif) no-repeat; border:none; color:#fff; display:block; text-align:center; float:right; margin-right:10px;">网站类型管理</a>
  </div>
  </td>
</tr>
<tr>
   <td height="30" align="left" bgcolor="#FFFCED"  colspan="8" style="border:1px solid #FFBE7A; padding-left:15px;"> 
   <form action="friendlink_main.php" name="form1" method="get">
	  <table border="0" cellspacing="0" cellpadding="3" >
      <tr>
          <td>关键字：</td>
          <td><input name="keyword" type="text" id="keyword" size="12" style="width:250px" value="<?php echo $keyword; ?>" /></td>
          <td>
          	<select name="ischeck" id="ischeck">
        			<?php
        			foreach($selCheckArr as $k=>$v)
        			{
        				if($k==$ischeck) echo "<option value='$k' selected>$v</option>\r\n";
        				else echo "<option value='$k'>$v</option>\r\n";
        			}
        			?>
						</select>
          </td>
          <td>
           <input name="imageField" class="np" type="image" src="images/button_search.gif" width="60" height="22" border="0" />
          </td>
        </tr>
      </table>
	  </form>
    </td>
</tr>
<form name='form1' method='post' action='friendlink_edit.php' onSubmit='return checkSubmit();'>
<input type='hidden' name='dopost' value='delall'/>
<input type='hidden' name='allid' value=''/>
<tr align="center" bgcolor="#EEF3F7" height="26">
          <td width="6%">选择</td>
          <td width="20%">网站名称</td>
          <td width="20%">网站Logo</td>
          <td width="15%">站长Email</td>
          <td width="15%">时间</td>
          <td width="8%">状态</td>
          <td width="3%">顺序</td>
          <td width="15%">管理</td>
        </tr>
        <?php
$atts = array();
$atts['tagname'] = 'datalist';
$blockValue = $this->refObj->GetArcList($atts,$this->refObj,$fields); 
if(is_array($blockValue)){
foreach( $blockValue as $key=>$fields )
{
?>
        <tr align="center" bgcolor="#FFFFFF" height="26" onMouseMove="javascript:this.bgColor='#FCFDEE';"
        	 onMouseOut="javascript:this.bgColor='#FFFFFF';" >
          <td class="bline"><input type='checkbox' name='aids' value='<?php echo $fields['id']; ?>' class='np'></td>
          <td class="bline"><a href="<?php echo $fields['url']; ?>" target='_blank'><?php echo htmlspecialchars($fields['webname']); ?></a></td>
          <td class="bline"><a href="<?php echo $fields['url']; ?>" target='_blank'><?php echo GetPic($fields['logo']); ?></a></td>
          <td class="bline"><?php echo $fields['email']; ?></td>
          <td class="bline"><?php echo MyDate('Y-m-d',$fields['dtime']); ?></td>
          <td class="bline"><?php echo GetSta($fields['ischeck']); ?></td>
          <td class="bline"><?php echo $fields['sortrank']; ?></td>
          <td class="bline">
          	<a href='friendlink_edit.php?id=<?php echo $fields['id']; ?>&dopost=getedit'>[更改]</a>
		        <a href='friendlink_edit.php?id=<?php echo $fields['id']; ?>&dopost=delete'>[删除]</a>
		      </td>
        </tr>
		<?php
}
}?>
    </form>
     <tr bgcolor="#ffffff" height="28">
      <td colspan="8">
      	　
        <a href='#' onclick='Sel("all")' class='np coolbg'>[全选]</a>
      	<a href='#' onclick='Sel("none")' class='np coolbg'>[取消]</a>
      	<a href='#' onclick='checkSubmit()' class='np coolbg'>批量删除</a>

      </td>
     </tr>
     <tr align="center" bgcolor="#EEF3F7" height="28">
      <td colspan="8">
      	<?php
$atts = array();
$atts['tagname'] = 'pagelist';
$atts['listsize'] = '6';
 echo $this->refObj->GetPageList($atts,$this->refObj,$fields); 
?>

      </td>
     </tr>
   </table>
  </td>
</tr>
</table>
</body>
</html>