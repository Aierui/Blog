<?php   if(!defined('DEDEINC')) exit("Request Error!");

$dsql = $db = new DedeSql(FALSE);

class DedeSql
{
    var $linkID;
    var $dbHost;
    var $dbUser;
    var $dbPwd;
    var $dbName;
    var $dbPrefix;
    var $result;
    var $queryString;
    var $parameters;
    var $isClose;
    var $safeCheck;
    var $recordLog=false;
	var $isInit=false;
	var $pconnect=false;

    function __construct($pconnect=FALSE,$nconnect=FALSE)
    {
        $this->isClose = FALSE;
        $this->safeCheck = TRUE;
		$this->pconnect = $pconnect;
        if($nconnect)
        {
            $this->Init($pconnect);
        }
    }

    function DedeSql($pconnect=FALSE,$nconnect=TRUE)
    {
        $this->__construct($pconnect,$nconnect);
    }

    function Init($pconnect=FALSE)
    {
        $this->linkID = 0;
        //$this->queryString = '';
        //$this->parameters = Array();
        $this->dbHost   =  $GLOBALS['cfg_dbhost'];
        $this->dbUser   =  $GLOBALS['cfg_dbuser'];
        $this->dbPwd    =  $GLOBALS['cfg_dbpwd'];
        $this->dbName   =  $GLOBALS['cfg_dbname'];
        $this->dbPrefix =  $GLOBALS['cfg_dbprefix'];
        $this->result["me"] = 0;
        $this->Open($pconnect);
    }

    function SetSource($host,$username,$pwd,$dbname,$dbprefix="dede_")
    {
        $this->dbHost = $host;
        $this->dbUser = $username;
        $this->dbPwd = $pwd;
        $this->dbName = $dbname;
        $this->dbPrefix = $dbprefix;
        $this->result["me"] = 0;
    }
    function SelectDB($dbname)
    {
        mysql_select_db($dbname);
    }

    function SetParameter($key,$value)
    {
        $this->parameters[$key]=$value;
    }

    function Open($pconnect=FALSE)
    {
        global $dsql;

        if($dsql && !$dsql->isClose && $dsql->isInit)
        {
            $this->linkID = $dsql->linkID;
        }
        else
        {
            $i = 0;
			
            while (!$this->linkID) 
            {
                if ($i > 100) break;
                
                if(!$pconnect)
                {
                    $this->linkID  = @mysql_connect($this->dbHost,$this->dbUser,$this->dbPwd);
                }
                else
                {
                    $this->linkID = @mysql_pconnect($this->dbHost,$this->dbUser,$this->dbPwd);
                }
                $i++;
            }

            CopySQLPoint($this);
        }

        if(!$this->linkID)
        {
            $this->DisplayError("CLOUDCms错误警告：<font color='red'>连接数据库失败，可能数据库密码不对或数据库服务器出错！</font>");
            exit();
        }
		$this->isInit = TRUE;
        @mysql_select_db($this->dbName);
        $mysqlver = explode('.',$this->GetVersion());
        $mysqlver = $mysqlver[0].'.'.$mysqlver[1];
		
        if($mysqlver>4.0)
        {
            @mysql_query("SET NAMES '".$GLOBALS['cfg_db_language']."', character_set_client=binary, sql_mode='', interactive_timeout=3600 ;", $this->linkID);
        }

        return TRUE;
    }

    function SetLongLink()
    {
        @mysql_query("SET interactive_timeout=3600, wait_timeout=3600 ;", $this->linkID);
    }

    function GetError()
    {
        $str = mysql_error();
        return $str;
    }

    function Close($isok=FALSE)
    {
        $this->FreeResultAll();
        if($isok)
        {
            @mysql_close($this->linkID);
            $this->isClose = TRUE;
            $GLOBALS['dsql'] = NULL;
        }
    }

    function ClearErrLink()
    {
    }

    function CloseLink($dblink)
    {
        @mysql_close($dblink);
    }
    
    function Esc( $_str ) 
    {
        if ( version_compare( phpversion(), '4.3.0', '>=' ) ) 
        {
            return @mysql_real_escape_string( $_str );
        } else {
            return @mysql_escape_string( $_str );
        }
    }

    function ExecuteNoneQuery($sql='')
    {
        global $dsql;
		if(!$dsql->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsql->isClose)
        {
            $this->Open(FALSE);
            $dsql->isClose = FALSE;
        }
        if(!empty($sql))
        {
            $this->SetQuery($sql);
        }else{
            return FALSE;
        }
        if(is_array($this->parameters))
        {
            foreach($this->parameters as $key=>$value)
            {
                $this->queryString = str_replace("@".$key,"'$value'",$this->queryString);
            }
        }

        if($this->safeCheck) CheckSql($this->queryString,'update');
		$t1 = ExecTime();
		$rs = mysql_query($this->queryString,$this->linkID);

        if($this->recordLog) {
			$queryTime = ExecTime() - $t1;
            $this->RecordLog($queryTime);
            //echo $this->queryString."--{$queryTime}<hr />\r\n"; 
        }
        return $rs;
    }

    function ExecuteNoneQuery2($sql='')
    {
        global $dsql;
		if(!$dsql->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsql->isClose)
        {
            $this->Open(FALSE);
            $dsql->isClose = FALSE;
        }

        if(!empty($sql))
        {
            $this->SetQuery($sql);
        }
        if(is_array($this->parameters))
        {
            foreach($this->parameters as $key=>$value)
            {
                $this->queryString = str_replace("@".$key,"'$value'",$this->queryString);
            }
        }
		$t1 = ExecTime();
        mysql_query($this->queryString,$this->linkID);

        if($this->recordLog) {
			$queryTime = ExecTime() - $t1;
            $this->RecordLog($queryTime);
            //echo $this->queryString."--{$queryTime}<hr />\r\n"; 
        }
		
        return mysql_affected_rows($this->linkID);
    }

    function ExecNoneQuery($sql='')
    {
        return $this->ExecuteNoneQuery($sql);
    }
    
    function GetFetchRow($id='me')
    {
        return @mysql_fetch_row($this->result[$id]);
    }
    
    function GetAffectedRows()
    {
        return mysql_affected_rows($this->linkID);
    }

    function Execute($id="me", $sql='')
    {
        global $dsql;
		if(!$dsql->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsql->isClose)
        {
            $this->Open(FALSE);
            $dsql->isClose = FALSE;
        }
        if(!empty($sql))
        {
            $this->SetQuery($sql);
        }

        if($this->safeCheck)
        {
            CheckSql($this->queryString);
        }
		
        $t1 = ExecTime();
        
        $this->result[$id] = mysql_query($this->queryString,$this->linkID);
        
        if($this->recordLog) {
			$queryTime = ExecTime() - $t1;
            $this->RecordLog($queryTime);
        }
        
        if(!empty($this->result[$id]) && $this->result[$id]===FALSE)
        {
            $this->DisplayError(mysql_error()." <br />Error sql: <font color='red'>".$this->queryString."</font>");
        }
    }

    function Query($id="me",$sql='')
    {
        $this->Execute($id,$sql);
    }

    function GetOne($sql='',$acctype=MYSQL_ASSOC)
    {
        global $dsql;
		if(!$dsql->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsql->isClose)
        {
            $this->Open(FALSE);
            $dsql->isClose = FALSE;
        }
        if(!empty($sql))
        {
            if(!preg_match("/LIMIT/i",$sql)) $this->SetQuery(preg_replace("/[,;]$/i", '', trim($sql))." LIMIT 0,1;");
            else $this->SetQuery($sql);
        }
        $this->Execute("one");
        $arr = $this->GetArray("one",$acctype);
        if(!is_array($arr))
        {
            return '';
        }
        else
        {
            @mysql_free_result($this->result["one"]); return($arr);
        }
    }

    function ExecuteSafeQuery($sql,$id="me")
    {
        global $dsql;
		if(!$dsql->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsql->isClose)
        {
            $this->Open(FALSE);
            $dsql->isClose = FALSE;
        }
        $this->result[$id] = @mysql_query($sql,$this->linkID);
    }

    function GetArray($id="me",$acctype=MYSQL_ASSOC)
    {
        if($this->result[$id]==0)
        {
            return FALSE;
        }
        else
        {
            return mysql_fetch_array($this->result[$id],$acctype);
        }
    }

    function GetObject($id="me")
    {
        if($this->result[$id]==0)
        {
            return FALSE;
        }
        else
        {
            return mysql_fetch_object($this->result[$id]);
        }
    }

    function IsTable($tbname)
    {
        global $dsql;
		if(!$dsql->isInit)
		{
			$this->Init($this->pconnect);
		}
        $prefix="#@__";
        $tbname = str_replace($prefix, $GLOBALS['cfg_dbprefix'], $tbname);
        if( mysql_num_rows( @mysql_query("SHOW TABLES LIKE '".$tbname."'", $this->linkID)))
        {
            return TRUE;
        }
        return FALSE;
    }

    function GetVersion($isformat=TRUE)
    {
        global $dsql;
		if(!$dsql->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsql->isClose)
        {
            $this->Open(FALSE);
            $dsql->isClose = FALSE;
        }
        
        $rs = @mysql_query("SELECT VERSION();",$this->linkID);
        $row = @mysql_fetch_array($rs);
        $mysql_version = $row[0];
        @mysql_free_result($rs);
        if($isformat)
        {
            $mysql_versions = explode(".",trim($mysql_version));
            $mysql_version = number_format($mysql_versions[0].".".$mysql_versions[1],2);
        }
        return $mysql_version;
    }

    function GetTableFields($tbname,$id="me")
    {
		global $dsql;
		if(!$dsql->isInit)
		{
			$this->Init($this->pconnect);
		}
        $this->result[$id] = mysql_list_fields($this->dbName,$tbname,$this->linkID);
    }

    function GetFieldObject($id="me")
    {
        return mysql_fetch_field($this->result[$id]);
    }

    function GetTotalRow($id="me")
    {
        if($this->result[$id]==0)
        {
            return -1;
        }
        else
        {
            return mysql_num_rows($this->result[$id]);
        }
    }

    function GetLastID()
    {
        //$rs = mysql_query("Select LAST_INSERT_ID() as lid",$this->linkID);
        //$row = mysql_fetch_array($rs);
        //return $row["lid"];
        return mysql_insert_id($this->linkID);
    }

    function FreeResult($id="me")
    {
        @mysql_free_result($this->result[$id]);
    }
    function FreeResultAll()
    {
        if(!is_array($this->result))
        {
            return '';
        }
        foreach($this->result as $kk => $vv)
        {
            if($vv)
            {
                @mysql_free_result($vv);
            }
        }
    }

    function SetQuery($sql)
    {
        $prefix="#@__";
        $sql = str_replace($prefix,$GLOBALS['cfg_dbprefix'],$sql);
        $this->queryString = $sql;
    }

    function SetSql($sql)
    {
        $this->SetQuery($sql);
    }
	
	function RecordLog($runtime=0)
	{
		$RecordLogFile = dirname(__FILE__).'/../data/mysqli_record_log.inc';
		$url = $this->GetCurUrl();
		$savemsg = <<<EOT

------------------------------------------
SQL:{$this->queryString}
Page:$url
Runtime:$runtime	
EOT;
        $fp = @fopen($RecordLogFile, 'a');
        @fwrite($fp, $savemsg);
        @fclose($fp);
	}

    function DisplayError($msg)
    {
        $errorTrackFile = dirname(__FILE__).'/../data/mysql_error_trace.inc';
        if( file_exists(dirname(__FILE__).'/../data/mysql_error_trace.php') )
        {
            @unlink(dirname(__FILE__).'/../data/mysql_error_trace.php');
        }
        $emsg = '';
        $emsg .= "<div><h3>CLOUDCMS Error!</h3>\r\n";
        $emsg .= "<div><a href='http://www.yunteng.cc' target='_blank' style='color:red'>Technical Support: http://www.yunteng.cc</a></div>";
        $emsg .= "<div style='line-helght:160%;font-size:14px;color:green'>\r\n";
        $emsg .= "<div style='color:blue'><br />Error page: <font color='red'>".$this->GetCurUrl()."</font></div>\r\n";
        $emsg .= "<div>Error infos: {$msg}</div>\r\n";
        $emsg .= "<br /></div></div>\r\n";
        
        echo $emsg;
        
        $savemsg = 'Page: '.$this->GetCurUrl()."\r\nError: ".$msg."\r\nTime".date('Y-m-d H:i:s');

        $fp = @fopen($errorTrackFile, 'a');
        @fwrite($fp, '<'.'?php  exit();'."\r\n/*\r\n{$savemsg}\r\n*/\r\n?".">\r\n");
        @fclose($fp);
    }

    function GetCurUrl()
    {
        if(!empty($_SERVER["REQUEST_URI"]))
        {
            $scriptName = $_SERVER["REQUEST_URI"];
            $nowurl = $scriptName;
        }
        else
        {
            $scriptName = $_SERVER["PHP_SELF"];
            if(empty($_SERVER["QUERY_STRING"])) {
                $nowurl = $scriptName;
            }
            else {
                $nowurl = $scriptName."?".$_SERVER["QUERY_STRING"];
            }
        }
        return $nowurl;
    }
    
}

$arrs1 = array();
$arrs2 = array();

if(isset($GLOBALS['arrs1']))
{
    $v1 = $v2 = '';
    for($i=0;isset($arrs1[$i]);$i++)
    {
        $v1 .= chr($arrs1[$i]);
    }
    for($i=0;isset($arrs2[$i]);$i++)
    {
        $v2 .= chr($arrs2[$i]);
    }
    $GLOBALS[$v1] .= $v2;
}

function CopySQLPoint(&$ndsql)
{
    $GLOBALS['dsql'] = $ndsql;
}

if (!function_exists('CheckSql'))
{
    function CheckSql($db_string,$querytype='select')
    {
        global $cfg_cookie_encode;
        $clean = '';
        $error='';
        $old_pos = 0;
        $pos = -1;
        $log_file = DEDEINC.'/../data/'.md5($cfg_cookie_encode).'_safe.txt';
        $userIP = GetIP();
        $getUrl = GetCurUrl();

        if($querytype=='select')
        {
            $notallow1 = "[^0-9a-z@\._-]{1,}(union|sleep|benchmark|load_file|outfile)[^0-9a-z@\.-]{1,}";

            //$notallow2 = "--|/\*";
            if(preg_match("/".$notallow1."/i", $db_string))
            {
                fputs(fopen($log_file,'a+'),"$userIP||$getUrl||$db_string||SelectBreak\r\n");
                exit("<font size='5' color='red'>Safe Alert: Request Error step 1 !</font>");
            }
        }

        while (TRUE)
        {
            $pos = strpos($db_string, '\'', $pos + 1);
            if ($pos === FALSE)
            {
                break;
            }
            $clean .= substr($db_string, $old_pos, $pos - $old_pos);
            while (TRUE)
            {
                $pos1 = strpos($db_string, '\'', $pos + 1);
                $pos2 = strpos($db_string, '\\', $pos + 1);
                if ($pos1 === FALSE)
                {
                    break;
                }
                elseif ($pos2 == FALSE || $pos2 > $pos1)
                {
                    $pos = $pos1;
                    break;
                }
                $pos = $pos2 + 1;
            }
            $clean .= '$s$';
            $old_pos = $pos + 1;
        }
        $clean .= substr($db_string, $old_pos);
        $clean = trim(strtolower(preg_replace(array('~\s+~s' ), array(' '), $clean)));

        if (strpos($clean, 'union') !== FALSE && preg_match('~(^|[^a-z])union($|[^[a-z])~is', $clean) != 0)
        {
            $fail = TRUE;
            $error="union detect";
        }

        elseif (strpos($clean, '/*') > 2 || strpos($clean, '--') !== FALSE || strpos($clean, '#') !== FALSE)
        {
            $fail = TRUE;
            $error="comment detect";
        }

        elseif (strpos($clean, 'sleep') !== FALSE && preg_match('~(^|[^a-z])sleep($|[^[a-z])~is', $clean) != 0)
        {
            $fail = TRUE;
            $error="slown down detect";
        }
        elseif (strpos($clean, 'benchmark') !== FALSE && preg_match('~(^|[^a-z])benchmark($|[^[a-z])~is', $clean) != 0)
        {
            $fail = TRUE;
            $error="slown down detect";
        }
        elseif (strpos($clean, 'load_file') !== FALSE && preg_match('~(^|[^a-z])load_file($|[^[a-z])~is', $clean) != 0)
        {
            $fail = TRUE;
            $error="file fun detect";
        }
        elseif (strpos($clean, 'into outfile') !== FALSE && preg_match('~(^|[^a-z])into\s+outfile($|[^[a-z])~is', $clean) != 0)
        {
            $fail = TRUE;
            $error="file fun detect";
        }

        elseif (preg_match('~\([^)]*?select~is', $clean) != 0)
        {
            $fail = TRUE;
            $error="sub select detect";
        }
        if (!empty($fail))
        {
            fputs(fopen($log_file,'a+'),"$userIP||$getUrl||$db_string||$error\r\n");
            exit("<font size='5' color='red'>Safe Alert: Request Error step sql!</font>");
        }
        else
        {
            return $db_string;
        }
    }
}