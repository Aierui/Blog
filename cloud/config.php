<?php

define('DEDEADMIN', str_replace("\\", '/', dirname(__FILE__) ) );
require_once(DEDEADMIN.'/../include/common.inc.php');
require_once(DEDEINC.'/userlogin.class.php');
header('Cache-Control:private');
$dsql->safeCheck = FALSE;
$dsql->SetLongLink();
$cfg_admin_skin = 1;

if(file_exists(DEDEDATA.'/admin/skin.txt'))
{
	$skin = file_get_contents(DEDEDATA.'/admin/skin.txt');
	$cfg_admin_skin = !in_array($skin, array(1,2,3,4))? 1 : $skin;
}

$dedeNowurl = $s_scriptName = '';
$isUrlOpen = @ini_get('allow_url_fopen');
$dedeNowurl = GetCurUrl();
$dedeNowurls = explode('?', $dedeNowurl);
$s_scriptName = $dedeNowurls[0];
$cfg_remote_site = empty($cfg_remote_site)? 'N' : $cfg_remote_site;

$cuserLogin = new userLogin();
if($cuserLogin->getUserID()==-1)
{
    header("location:login.php?gotopage=".urlencode($dedeNowurl));
    exit();
}

if($cfg_dede_log=='Y')
{
    $s_nologfile = '_main|_list';
    $s_needlogfile = 'sys_|file_';
    $s_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
    $s_query = isset($dedeNowurls[1]) ? $dedeNowurls[1] : '';
    $s_scriptNames = explode('/', $s_scriptName);
    $s_scriptNames = $s_scriptNames[count($s_scriptNames)-1];
    $s_userip = GetIP();
    if( $s_method=='POST' || (!preg_match("#".$s_nologfile."#i", $s_scriptNames) && $s_query!='') || preg_match("#".$s_needlogfile."#i",$s_scriptNames) )
    {
        $inquery = "INSERT INTO `#@__log`(adminid,filename,method,query,cip,dtime)
             VALUES ('".$cuserLogin->getUserID()."','{$s_scriptNames}','{$s_method}','".addslashes($s_query)."','{$s_userip}','".time()."');";
        $dsql->ExecuteNoneQuery($inquery);
    }
}

if($cfg_remote_site=='Y')
{
    require_once(DEDEINC.'/ftp.class.php');
    if(file_exists(DEDEDATA."/cache/inc_remote_config.php"))
    {
        require_once DEDEDATA."/cache/inc_remote_config.php";
    }
    if(empty($remoteuploads)) $remoteuploads = 0;
    if(empty($remoteupUrl)) $remoteupUrl = '';
    $config = array(
      'hostname' => $GLOBALS['cfg_ftp_host'],
      'username' => $GLOBALS['cfg_ftp_user'],
      'password' => $GLOBALS['cfg_ftp_pwd'],
      'debug' => 'TRUE'
    );
    $ftp = new FTP($config); 

    if($remoteuploads==1){
        $ftpconfig = array(
            'hostname'=>$rmhost, 
            'port'=>$rmport,
            'username'=>$rmname,
            'password'=>$rmpwd
        );
    }
}

$cache1 = DEDEDATA.'/cache/inc_catalog_base.inc';
if(!file_exists($cache1)) UpDateCatCache();
$cacheFile = DEDEDATA.'/cache/admincat_'.$cuserLogin->userID.'.inc';
if(file_exists($cacheFile)) require_once($cacheFile);

require_once (DEDEDATA.'/admin/config_update.php');

function UpDateCatCache()
{
    global $dsql, $cfg_multi_site, $cache1, $cacheFile, $cuserLogin;
    $cache2 = DEDEDATA.'/cache/channelsonlist.inc';
    $cache3 = DEDEDATA.'/cache/channeltoplist.inc';
    $dsql->SetQuery("SELECT id,reid,channeltype,issend,typename FROM `#@__arctype`");
    $dsql->Execute();
    $fp1 = fopen($cache1,'w');
    $phph = '?';
    $fp1Header = "<{$phph}php\r\nglobal \$cfg_Cs;\r\n\$cfg_Cs=array();\r\n";
    fwrite($fp1,$fp1Header);
    while($row=$dsql->GetObject())
    {
        $row->typename = base64_encode($row->typename);
        fwrite($fp1,"\$cfg_Cs[{$row->id}]=array({$row->reid},{$row->channeltype},{$row->issend},'{$row->typename}');\r\n");
    }
    fwrite($fp1, "{$phph}>");
    fclose($fp1);
    $cuserLogin->ReWriteAdminChannel();
    @unlink($cache2);
    @unlink($cache3);
}

function ClearOptCache()
{
    $tplCache = DEDEDATA.'/tplcache/';
    $fileArray = glob($tplCache."inc_option_*.inc");
    if (count($fileArray) > 1)
    {
        foreach ($fileArray as $key => $value)
        {
            if (file_exists($value)) unlink($value);
            else continue;
        }
        return TRUE;
    }
    return FALSE;
}

function UpDateMemberModCache()
{
    global $dsql;
    $cachefile = DEDEDATA.'/cache/member_model.inc';

    $dsql->SetQuery("SELECT * FROM `#@__member_model` WHERE state='1'");
    $dsql->Execute();
    $fp1 = fopen($cachefile,'w');
    $phph = '?';
    $fp1Header = "<{$phph}php\r\nglobal \$_MemberMod;\r\n\$_MemberMod=array();\r\n";
    fwrite($fp1,$fp1Header);
    while($row=$dsql->GetObject())
    {
        fwrite($fp1,"\$_MemberMod[{$row->id}]=array('{$row->name}','{$row->table}');\r\n");
    }
    fwrite($fp1,"{$phph}>");
    fclose($fp1);
}

function DedeInclude($filename, $isabs=FALSE)
{
    return $isabs ? $filename : DEDEADMIN.'/'.$filename;
}

function GetFtp($current='', $formname='')
{
    global $dsql;
    $formname = empty($formname)? 'serviterm' : $formname;
    $cuserLogin = new userLogin();
    $row=$dsql->GetOne("SELECT servinfo FROM `#@__multiserv_config`");
    $row['servinfo']=trim($row['servinfo']);
    if(!empty($row['servinfo'])){
        $servinfos = explode("\n", $row['servinfo']);
        $select="";
        echo '<select name="'.$formname.'" size="1" id="serviterm">';
        $i=0;
        foreach($servinfos as $servinfo){
            $servinfo = trim($servinfo);
            list($servname,$servurl,$servport,$servuser,$servpwd,$userlist) = explode('|',$servinfo);
            $servname = trim($servname);
            $servurl = trim($servurl);
            $servport = trim($servport);
            $servuser = trim($servuser);
            $servpwd = trim($servpwd);
            $userlist = trim($userlist);   
            $checked = ($current == $i)? '  selected="selected"' : '';
            if(strstr($userlist,$cuserLogin->getUserName()))
            {
                $select.="<option value='".$servurl.",".$servuser.",".$servpwd."'{$checked}>".$servname."</option>";  
            }
            $i++;
        }
        echo  $select."</select>";
    }
}
helper('cache');

if(!function_exists('GetMemberName')){
	function GetMemberName($mid=0)
	{
		global $dsql;
		$rs = GetCache('memberlogin', $mid);
		if( empty($rs) )
		{
			$rs = $dsql->GetOne("SELECT * FROM `#@__member` WHERE mid='{$mid}' ");
			SetCache('memberlogin', $mid, $rs, 1800);
		}
		return $rs['uname'];
	}
}