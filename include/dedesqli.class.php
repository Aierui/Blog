<?php   if(!defined('DEDEINC')) exit("Request Error!");

@set_time_limit(0);

$dsql = $dsqli = $db = new DedeSqli(FALSE);

class DedeSqli
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
	var $showError=false;
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
        global $dsqli;
		
        if($dsqli && !$dsqli->isClose && $dsqli->isInit)
        {
            $this->linkID = $dsqli->linkID;
        }
        else
        {
            $i = 0;
            @list($dbhost, $dbport) = explode(':', $this->dbHost);
            !$dbport && $dbport = 3306;
            
            $this->linkID = mysqli_init();
            mysqli_real_connect($this->linkID, $dbhost, $this->dbUser, $this->dbPwd, false, $dbport);
            mysqli_errno($this->linkID) != 0 && $this->DisplayError('云腾互联Cms错误警告： 链接('.$this->pconnect.') 到MySQL发生错误');

            CopySQLiPoint($this);
        }

        if(!$this->linkID)
        {
            $this->DisplayError("云腾互联Cms错误警告：<font color='red'>连接数据库失败，可能数据库密码不对或数据库服务器出错！</font>");
            exit();
        }
		$this->isInit = TRUE;
        $serverinfo = mysqli_get_server_info($this->linkID);
        if ($serverinfo > '4.1' && $GLOBALS['cfg_db_language']) 
        {
            mysqli_query($this->linkID, "SET character_set_connection=" . $GLOBALS['cfg_db_language'] . ",character_set_results=" . $GLOBALS['cfg_db_language'] . ",character_set_client=binary");
        }
        if ($serverinfo > '5.0') {
            mysqli_query($this->linkID, "SET sql_mode=''");
        }
        if ($this->dbName && !@mysqli_select_db($this->linkID, $this->dbName)) {
            $this->DisplayError('无法使用数据库');
        }
        return TRUE;
    }

    function SetLongLink()
    {
        @mysqli_query("SET interactive_timeout=3600, wait_timeout=3600 ;", $this->linkID);
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
            @mysqli_close($this->linkID);
            $this->isClose = TRUE;
            $GLOBALS['dsql'] = NULL;
        }
    }

    function ClearErrLink()
    {
    }

    function CloseLink($dblink)
    {
        @mysqli_close($dblink);
    }
    
    function Esc( $_str ) 
    {
        if ( version_compare( phpversion(), '4.3.0', '>=' ) ) 
        {
            return @mysqli_real_escape_string($this->linkID, $_str );
        } else {
            return @mysqli_escape_string ($this->linkID, $_str );
        }
    }

    function ExecuteNoneQuery($sql='')
    {
        global $dsqli;
		if(!$dsqli->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsqli->isClose)
        {
            $this->Open(FALSE);
            $dsqli->isClose = FALSE;
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
        $rs = mysqli_query($this->linkID, $this->queryString);

        if($this->recordLog) {
			$queryTime = ExecTime() - $t1;
            $this->RecordLog($queryTime);
            //echo $this->queryString."--{$queryTime}<hr />\r\n"; 
        }
        return $rs;
    }

    function ExecuteNoneQuery2($sql='')
    {
        global $dsqli;
		if(!$dsqli->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsqli->isClose)
        {
            $this->Open(FALSE);
            $dsqli->isClose = FALSE;
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
        mysqli_query($this->linkID, $this->queryString);

        if($this->recordLog) {
			$queryTime = ExecTime() - $t1;
            $this->RecordLog($queryTime);
            //echo $this->queryString."--{$queryTime}<hr />\r\n"; 
        }
        
        return mysqli_affected_rows($this->linkID);
    }

    function ExecNoneQuery($sql='')
    {
        return $this->ExecuteNoneQuery($sql);
    }
    
    function GetFetchRow($id='me')
    {
        return @mysqli_fetch_row($this->result[$id]);
    }
    
    function GetAffectedRows()
    {
        return mysqli_affected_rows($this->linkID);
    }

    function Execute($id="me", $sql='')
    {
        global $dsqli;
		if(!$dsqli->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsqli->isClose)
        {
            $this->Open(FALSE);
            $dsqli->isClose = FALSE;
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
        //var_dump($this->queryString);
        $this->result[$id] = mysqli_query($this->linkID, $this->queryString);
		//var_dump(mysql_error());

        if($this->recordLog) {
			$queryTime = ExecTime() - $t1;
            $this->RecordLog($queryTime);
            //echo $this->queryString."--{$queryTime}<hr />\r\n"; 
        }
        
        if($this->result[$id]===FALSE)
        {
            $this->DisplayError(mysqli_error($this->linkID)." <br />Error sql: <font color='red'>".$this->queryString."</font>");
        }
    }

    function Query($id="me",$sql='')
    {
        $this->Execute($id,$sql);
    }

    function GetOne($sql='',$acctype=MYSQLI_ASSOC)
    {
        global $dsqli;
		if(!$dsqli->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsqli->isClose)
        {
            $this->Open(FALSE);
            $dsqli->isClose = FALSE;
        }
        if(!empty($sql))
        {
            if(!preg_match("/LIMIT/i",$sql)) $this->SetQuery(preg_replace("/[,;]$/i", '', trim($sql))." LIMIT 0,1;");
            else $this->SetQuery($sql);
        }
        $this->Execute("one");
        $arr = $this->GetArray("one", $acctype);
        if(!is_array($arr))
        {
            return '';
        }
        else
        {
            @mysqli_free_result($this->result["one"]); return($arr);
        }
    }

    function ExecuteSafeQuery($sql,$id="me")
    {
        global $dsqli;
		if(!$dsqli->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsqli->isClose)
        {
            $this->Open(FALSE);
            $dsqli->isClose = FALSE;
        }
        $this->result[$id] = @mysqli_query($sql,$this->linkID);
    }

    function GetArray($id="me",$acctype=MYSQLI_ASSOC)
    {
        // var_dump($this->result);
        if($this->result[$id]===0)
        {
            return FALSE;
        }
        else
        {
            return @mysqli_fetch_array($this->result[$id], $acctype);
        }
    }

    function GetObject($id="me")
    {
        if($this->result[$id]===0)
        {
            return FALSE;
        }
        else
        {
            return mysqli_fetch_object($this->result[$id]);
        }
    }

    function IsTable($tbname)
    {
        global $dsqli;
		if(!$dsqli->isInit)
		{
			$this->Init($this->pconnect);
		}
        $prefix="#@__";
        $tbname = str_replace($prefix, $GLOBALS['cfg_dbprefix'], $tbname);
        if( mysqli_num_rows( @mysqli_query($this->linkID, "SHOW TABLES LIKE '".$tbname."'")))
        {
            return TRUE;
        }
        return FALSE;
    }

    function GetVersion($isformat=TRUE)
    {
        global $dsqli;
		if(!$dsqli->isInit)
		{
			$this->Init($this->pconnect);
		}
        if($dsqli->isClose)
        {
            $this->Open(FALSE);
            $dsqli->isClose = FALSE;
        }
        $rs = mysqli_query($this->linkID, "SELECT VERSION();");
        $row = mysqli_fetch_array($rs);
        $mysql_version = $row[0];
        mysqli_free_result($rs);
        if($isformat)
        {
            $mysql_versions = explode(".",trim($mysql_version));
            $mysql_version = number_format($mysql_versions[0].".".$mysql_versions[1],2);
        }
        return $mysql_version;
    }

    function GetTableFields($tbname, $id="me")
    {
		global $dsqli;
		if(!$dsqli->isInit)
		{
			$this->Init($this->pconnect);
		}
        $prefix="#@__";
        $tbname = str_replace($prefix, $GLOBALS['cfg_dbprefix'], $tbname);
        $query = "SELECT * FROM {$tbname} LIMIT 0,1";
        $this->result[$id] = mysqli_query($this->linkID, $query);
    }

    function GetFieldObject($id="me")
    {
        return mysqli_fetch_field($this->result[$id]);
    }

    function GetTotalRow($id="me")
    {
        if($this->result[$id]===0)
        {
            return -1;
        }
        else
        {
            return @mysqli_num_rows($this->result[$id]);
        }
    }

    function GetLastID()
    {
        return mysqli_insert_id($this->linkID);
    }

    function FreeResult($id="me")
    {
        @mysqli_free_result($this->result[$id]);
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
                @mysqli_free_result($vv);
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
        $errorTrackFile = dirname(__FILE__).'/../data/mysqli_error_trace.inc';
        if( file_exists(dirname(__FILE__).'/../data/mysqli_error_trace.php') )
        {
            @unlink(dirname(__FILE__).'/../data/mysqli_error_trace.php');
        }
		if($this->showError)
		{
			$emsg = '';
			$emsg .= "<div><h3>cloudcms Error Warning!</h3>\r\n";
			$emsg .= "<div><a href='http://www.yunteng.cc' target='_blank' style='color:red'>Technical Support: http://www.yunteng.cc</a></div>";
			$emsg .= "<div style='line-helght:160%;font-size:14px;color:green'>\r\n";
			$emsg .= "<div style='color:blue'><br />Error page: <font color='red'>".$this->GetCurUrl()."</font></div>\r\n";
			$emsg .= "<div>Error infos: {$msg}</div>\r\n";
			$emsg .= "<br /></div></div>\r\n";
			
			echo $emsg;
		}
        
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

function CopySQLiPoint(&$ndsql)
{
    $GLOBALS['dsqli'] = $ndsql;
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

        if (strpos($clean, 'union') !== FALSE && preg_match('~(^|[^a-z])union($|[^[a-z])~s', $clean) != 0)
        {
            $fail = TRUE;
            $error="union detect";
        }

        elseif (strpos($clean, '/*') > 2 || strpos($clean, '--') !== FALSE || strpos($clean, '#') !== FALSE)
        {
            $fail = TRUE;
            $error="comment detect";
        }

        elseif (strpos($clean, 'sleep') !== FALSE && preg_match('~(^|[^a-z])sleep($|[^[a-z])~s', $clean) != 0)
        {
            $fail = TRUE;
            $error="slown down detect";
        }
        elseif (strpos($clean, 'benchmark') !== FALSE && preg_match('~(^|[^a-z])benchmark($|[^[a-z])~s', $clean) != 0)
        {
            $fail = TRUE;
            $error="slown down detect";
        }
        elseif (strpos($clean, 'load_file') !== FALSE && preg_match('~(^|[^a-z])load_file($|[^[a-z])~s', $clean) != 0)
        {
            $fail = TRUE;
            $error="file fun detect";
        }
        elseif (strpos($clean, 'into outfile') !== FALSE && preg_match('~(^|[^a-z])into\s+outfile($|[^[a-z])~s', $clean) != 0)
        {
            $fail = TRUE;
            $error="file fun detect";
        }

        elseif (preg_match('~\([^)]*?select~s', $clean) != 0)
        {
            $fail = TRUE;
            $error="sub select detect";
        }
        if (!empty($fail))
        {
            fputs(fopen($log_file,'a+'),"$userIP||$getUrl||$db_string||$error\r\n");
            exit("<font size='5' color='red'>Safe Alert: Request Error step 2!</font>");
        }
        else
        {
            return $db_string;
        }
    }
}