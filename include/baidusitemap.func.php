<?php
if(!defined('DEDEINC')) exit('Request Error!');
define('PLUS_BAIDUSITEMAP_VER','0.0.1');
function baidu_http_send($url, $limit=0, $post='', $cookie='', $timeout=15)
{
    $return = '';
    $matches = parse_url($url);
    $scheme = $matches['scheme'];
    $host = $matches['host'];
    $path = $matches['path'] ? $matches['path'].(@$matches['query'] ? '?'.$matches['query'] : '') : '/';
    $port = !empty($matches['port']) ? $matches['port'] : 80;

    if (function_exists('curl_init') && function_exists('curl_exec')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $scheme.'://'.$host.':'.$port.$path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            $content = is_array($port) ? http_build_query($post) : $post;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        }
        if ($cookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $data = curl_exec($ch);
        $status = curl_getinfo($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        if ($errno || $status['http_code'] != 200) {
            return;
        } else {
            return !$limit ? $data : substr($data, 0, $limit);
        }
    }

    if ($post) {
        $content = is_array($port) ? http_build_query($post) : $post;
        $out = "POST $path HTTP/1.0\r\n";
        $header = "Accept: */*\r\n";
        $header .= "Accept-Language: zh-cn\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "User-Agent: ".@$_SERVER['HTTP_USER_AGENT']."\r\n";
        $header .= "Host: $host:$port\r\n";
        $header .= 'Content-Length: '.strlen($content)."\r\n";
        $header .= "Connection: Close\r\n";
        $header .= "Cache-Control: no-cache\r\n";
        $header .= "Cookie: $cookie\r\n\r\n";
        $out .= $header.$content;
    } else {
        $out = "GET $path HTTP/1.0\r\n";
        $header = "Accept: */*\r\n";
        $header .= "Accept-Language: zh-cn\r\n";
        $header .= "User-Agent: ".@$_SERVER['HTTP_USER_AGENT']."\r\n";
        $header .= "Host: $host:$port\r\n";
        $header .= "Connection: Close\r\n";
        $header .= "Cookie: $cookie\r\n\r\n";
        $out .= $header;
    }

    $fpflag = 0;
    $fp = false;
    if (function_exists('fsocketopen')) {
        $fp = fsocketopen($host, $port, $errno, $errstr, $timeout);
    }
    if (!$fp) {
        $context = stream_context_create(array(
            'http' => array(
                'method' => $post ? 'POST' : 'GET',
                'header' => $header,
                'content' => $content,
                'timeout' => $timeout,
            ),
        ));
        $fp = @fopen($scheme.'://'.$host.':'.$port.$path, 'b', false, $context);
        $fpflag = 1;
    }

    if (!$fp) {
        return '';
    } else {
        stream_set_blocking($fp, true);
        stream_set_timeout($fp, $timeout);
        @fwrite($fp, $out);
        $status = stream_get_meta_data($fp);
        if (!$status['timed_out']) {
            while (!feof($fp) && !$fpflag) {
                if (($header = @fgets($fp)) && ($header == "\r\n" ||  $header == "\n")) {
                    break;
                }
            }
            if ($limit) {
                $return = stream_get_contents($fp, $limit);
            } else {
                $return = stream_get_contents($fp);
            }
        }
        @fclose($fp);
        return $return;
    }
}

function check_installed()
{
    global $dsql,$cfg_db_language;
    $is_installed = $dsql->IsTable("#@__plus_baidusitemap_setting");
    
    if(!$is_installed)
    {
        $install_sql=<<<EOT
CREATE TABLE IF NOT EXISTS `#@__plus_baidusitemap_list` (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL DEFAULT '',
  `type` tinyint(4) NOT NULL,
  `create_time` int(10) NOT NULL DEFAULT '0',
  `pagesize` int(11) DEFAULT '0',
  PRIMARY KEY (`sid`),
  KEY `pagesize` (`pagesize`)
) TYPE=MyISAM;
CREATE TABLE IF NOT EXISTS `#@__plus_baidusitemap_setting` (
  `skey` varchar(255) NOT NULL DEFAULT '',
  `svalue` text NOT NULL,
  `stime` int(10) NOT NULL,
  PRIMARY KEY (`skey`)
) TYPE=MyISAM;
INSERT INTO `#@__plus_baidusitemap_setting` (`skey`, `svalue`, `stime`) VALUES
	('siteurl', '', 0),
	('checksign', '', 0),
	('pingtoken', '', 0),
	('bdpwd', '', 0),
	('setupmaxaid', '', 0),
	('lastuptime_all', '', 0),
	('lastuptime_inc', '', 0);
EOT;
        $sqlquery = str_replace("\r","",$install_sql);
        $sqls = preg_split("#;[ \t]{0,}\n#",$sqlquery);
        $nerrCode = ""; $i=0;
        foreach($sqls as $q)
        {
            $q = trim($q);
            if($q=="")
            {
                continue;
            }
            if ($dsql->GetVersion() >= 4.1) {
                if(preg_match('#CREATE#i', $q))
                {
                    $q = preg_replace("#TYPE=MyISAM#i","ENGINE=MyISAM DEFAULT CHARSET=".$cfg_db_language,$q);
                }
            }
            $dsql->ExecuteNoneQuery($q);
            $errCode = trim($dsql->GetError());
            if($errCode=="")
            {
                $i++;
            }
            else
            {
                $nerrCode .= "执行： <font color='blue'>$q</font> 出错，错误提示：<font color='red'>".$errCode."</font><br>";
            }
        }
        ShowMsg("成功安装数据库！首次安装进行全量索引提交，此过程可能比较长，请耐心等待……".$nerrCode,"baidusitemap_main.php?dopost=auth&action=resubmit",0,5000);
        exit();
    } else {
        return True;
    }
}

function baidu_get_setting($skey, $time=false, $real=false)
{
    global $dsql;
    static $setting = array();
    $skey=addslashes($skey);
    if (empty($setting[$skey]) || $real) {
        $row = $dsql->GetOne("SELECT * FROM `#@__plus_baidusitemap_setting` WHERE skey='{$skey}'");
        $setting[$skey]['svalue']=$row['svalue'];
        $setting[$skey]['stime']=$row['stime'];
    }
    if (!isset($setting[$skey])) return $time ? array() : null;
    return $time ? $setting[$skey] : $setting[$skey]['svalue'];
}

function baidu_set_setting($skey, $svalue)
{
    global $dsql;
    $stime=time();
    $skey=addslashes($skey);
    $svalue=addslashes($svalue);
    $sql="UPDATE `#@__plus_baidusitemap_setting` SET svalue='{$svalue}',stime='{$stime}' WHERE skey='{$skey}' ";
    $dsql->ExecuteNoneQuery($sql);
}
function baidu_strip_invalid_xml($value)
{
    $ret = '';
    if (empty($value)) {
        return $ret;
    }

    $length = strlen($value);
    for ($i=0; $i < $length; $i++) {
        $current = ord($value[$i]);
        if ($current == 0x9 || $current == 0xA || $current == 0xD ||
        ($current >= 0x20 && $current <= 0xD7FF) ||
        ($current >= 0xE000 && $current <= 0xFFFD) ||
        ($current >= 0x10000 && $current <= 0x10FFFF)) {
            $ret .= chr($current);
        } else {
            $ret .= ' ';
        }
    }
    return $ret;
}

function baidu_savesitemap($action, $site, $type, $bdpwd, $sign)
{
    global $dsql,$cfg_plus_dir,$cfg_basehost;
    //$siteurl = $cfg_basehost;
    $siteurl = $site;
    $zzaction = '';
    $bdpwd=addslashes($bdpwd);
    if (0 == strncasecmp('save', $action, 3)) {
        $zzaction = 'savesitemap';
    } else {
        return false;
    }
    $script = '';
    $stype = '';
    $pagesize=0;
    if (1 == $type) {
        $script = 'indexall';
        $stype = 'all';
    } else if (2 == $type) {
        $script = 'indexinc';
        $stype = 'inc';
    } else {
        return false;
    }
    $resource_name='RDF_Other_Webpage';
    $bdarcs = new BaiduArticleXml;
    $bdarcs->setSitemapType($type);
    $arctotal = $bdarcs->getTotal();
    if($arctotal==0) return false;
    $pagesize=ceil($arctotal/$bdarcs->Row);
    if($pagesize>0)
    {
        for($i=0;$i<$pagesize;$i++)
        {
            $cfg_plus_dir = str_replace("/", '', $cfg_plus_dir );
            $indexurl = $siteurl."{$cfg_plus_dir}/baidusitemap.php?dopost=sitemap_index&type={$script}&pwd={$bdpwd}&pagesize={$i}";
            $time=time();
            $inQuery = "INSERT INTO `#@__plus_baidusitemap_list` (`url`, `type`, `create_time`, `pagesize`) VALUES ('{$indexurl}', {$type}, {$time}, {$i});";
            
            $rs = $dsql->ExecuteNoneQuery($inQuery);
            $submiturl="http://zz.baidu.com/api/opensitemap/{$zzaction}?siteurl=".urlencode($siteurl)."&indexurl=".urlencode($indexurl)."&tokensign=".urlencode($sign)."&type=".$stype."&resource_name=".$resource_name;
            $ret = baidu_http_send($submiturl);
        }
    }
    $bdarcs->setSetupMaxAid();
    return array(
        'json' => $ret,
        'url'  => $submiturl,
    );
}

function baidu_delsitemap($site, $type=0, $sign)
{
    global $dsql;
    $siteurl = $site;
    $type=intval($type);
    $addWhere="";
    if($type>0) $addWhere .= "WHERE type={$type}";
    $query = "SELECT * FROM `#@__plus_baidusitemap_list` {$addWhere}";
    $dsql->SetQuery($query);
    $dsql->Execute('dd');
    while ($row=$dsql->GetArray('dd')) {
        $indexurl=$row['url'];
        $submiturl="http://zz.baidu.com/api/opensitemap/deletesitemap?siteurl=".urlencode($siteurl)."&indexurl=".urlencode($indexurl)."&tokensign=".urlencode($sign);
        //var_dump($submiturl);
        $ret = baidu_http_send($submiturl);
        
        $delresult = json_decode($ret, true);
        if($delresult['status']==0)
        {
            $delQuery="DELETE FROM `#@__plus_baidusitemap_list` WHERE `sid`={$row['sid']};";
            $dsql->ExecuteNoneQuery($delQuery);
            $dsql->ExecuteNoneQuery("TRUNCATE `#@__plus_baidusitemap_list`;");
        }
        //var_dump($delresult);
    }
    return true;
}

function baidu_gen_sitemap_passwd()
{
    return substr(md5(mt_rand(10000000, 99999999).microtime()), 0, 18);
}

function baidu_header_status($status)
{
   // 'cgi', 'cgi-fcgi'
   header('Status: '.$status, TRUE);
   header($_SERVER['SERVER_PROTOCOL'].' '.$status);
}
?>