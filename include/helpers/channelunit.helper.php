<?php  if(!defined('DEDEINC')) exit('dedecms');

if ( ! function_exists('GetRankStar'))
{
    function GetRankStar($rank)
    {
        $nstar = "";
        for($i=1;$i<=$rank;$i++)
        {
            $nstar .= "★";
        }
        for($i;$i<=5;$i++)
        {
            $nstar .= "☆";
        }
        return $nstar;
    }
}

if ( ! function_exists('GetFileUrl'))
{
    function GetFileUrl($aid,$typeid,$timetag,$title,$ismake=0,$rank=0,$namerule='',$typedir='',
    $money=0, $filename='',$moresite=0,$siteurl='',$sitepath='')
    {
        $articleUrl = GetFileName($aid,$typeid,$timetag,$title,$ismake,$rank,$namerule,$typedir,$money,$filename);
        $sitepath = MfTypedir($sitepath);

        if($GLOBALS['cfg_multi_site']=='Y')
        {
            if($siteurl=='')
            {
                $siteurl = $GLOBALS['cfg_basehost'];
            }
            if($moresite==1)
            {
                $articleUrl = preg_replace("#^".$sitepath.'#', '', $articleUrl);
            }
            if(!preg_match("/http:/", $articleUrl))
            {
                $articleUrl = $siteurl.$articleUrl;
            }
        }

        return $articleUrl;
    }
}

if ( ! function_exists('GetFileNewName'))
{
     function GetFileNewName($aid,$typeid,$timetag,$title,$ismake=0,$rank=0,$namerule='',$typedir='',$money=0,$filename='')
    {
        global $cfg_arc_dirname;
        $articlename = GetFileName($aid,$typeid,$timetag,$title,$ismake,$rank,$namerule,$typedir,$money,$filename);
        
        if(preg_match("/\?/", $articlename))
        {
            return $articlename;
        }
        
        if($cfg_arc_dirname=='Y' && preg_match("/\/$/", $articlename))
        {
            $articlename = $articlename."index.html";
        }
        
        $slen = strlen($articlename)-1;
        for($i=$slen;$i>=0;$i--)
        {
            if($articlename[$i]=='/')
            {
                $subpos = $i;
                break;
            }
        }
        $okdir = substr($articlename,0,$subpos);
        CreateDir($okdir);
        return $articlename;
    }
}

if ( ! function_exists('GetFileName'))
{
    function GetFileName($aid,$typeid,$timetag,$title,$ismake=0,$rank=0,$namerule='',$typedir='',$money=0,$filename='')
    {
        global $cfg_rewrite, $cfg_cmspath, $cfg_arcdir, $cfg_special, $cfg_arc_dirname;

        if(empty($namerule)) {
            $namerule = $cfg_special.'/arc-{aid}.html';
            $typeid = -1;
        }
        if($rank!=0 || $ismake==-1 || $typeid==0 || $money>0)
        {

            if($cfg_rewrite == 'Y')
            {
                return $GLOBALS["cfg_plus_dir"]."/view-".$aid.'-1.html';
            }
            else
            {
                return $GLOBALS['cfg_phpurl']."/view.php?aid=$aid";
            }
        }
        else
        {
            $articleDir = MfTypedir($typedir);
            $articleRule = strtolower($namerule);
            if($articleRule=='')
            {
                $articleRule = strtolower($GLOBALS['cfg_df_namerule']);
            }
            if($typedir=='')
            {
                $articleDir  = $GLOBALS['cfg_cmspath'].$GLOBALS['cfg_arcdir'];
            }
            $dtime = GetDateMk($timetag);
            list($y, $m, $d) = explode('-', $dtime);
            $arr_rpsource = array('{typedir}','{y}','{m}','{d}','{timestamp}','{aid}','{cc}');
            $arr_rpvalues = array($articleDir,$y, $m, $d, $timetag, $aid, dd2char($m.$d.$aid.$y));
            if($filename != '')
            {
                $articleRule = dirname($articleRule).'/'.$filename.$GLOBALS['cfg_df_ext'];
            }
            $articleRule = str_replace($arr_rpsource,$arr_rpvalues,$articleRule);
            if(preg_match("/\{p/", $articleRule))
            {
                $articleRule = str_replace('{pinyin}',GetPinyin($title).'_'.$aid,$articleRule);
                $articleRule = str_replace('{py}',GetPinyin($title,1).'_'.$aid,$articleRule);
            }
            $articleUrl = '/'.preg_replace("/^\//", '', $articleRule);
            if(preg_match("/index\.html/", $articleUrl) && $cfg_arc_dirname=='Y')
            {
                $articleUrl = str_replace('index.html', '', $articleUrl);
            }
            return $articleUrl;
        }
    }
}

if ( ! function_exists('GetTypeUrl'))
{
    function GetTypeUrl($typeid,$typedir,$isdefault,$defaultname,$ispart,$namerule2,$moresite=0,$siteurl='',$sitepath='')
    {
        global $cfg_typedir_df;
        $typedir = MfTypedir($typedir);
        $sitepath = MfTypedir($sitepath);
        if($isdefault==-1)
        {

            $reurl = $GLOBALS['cfg_phpurl']."/list.php?tid=".$typeid;
        }
        else if($ispart==2)
        {

            $reurl = $typedir;
            return $reurl;
        }
        else
        {
            if($isdefault==0 && $ispart==0)
            {
                $reurl = str_replace("{page}","1",$namerule2);
                $reurl = str_replace("{tid}",$typeid,$reurl);
                $reurl = str_replace("{typedir}",$typedir,$reurl);
            }
            else
            {
                if($cfg_typedir_df=='N' || $isdefault==0) $reurl = $typedir.'/'.$defaultname;
                else $reurl = $typedir.'/';
            }
        }

        if( !preg_match("/^http:\/\//",$reurl) ) {
            $reurl = preg_replace("/\/{1,}/i", '/', $reurl);
        }
        
        if($GLOBALS['cfg_multi_site']=='Y')
        {
            if($siteurl=='') {
                $siteurl = $GLOBALS['cfg_basehost'];
            }
            if($moresite==1 ) {
                $reurl = preg_replace("#^".$sitepath."#", '', $reurl);
            }
            if( !preg_match("/^http:\/\//", $reurl) ) {
                $reurl = $siteurl.$reurl;
            }
        }
        return $reurl;
    }
}

if ( ! function_exists('MagicVar'))
{
    function MagicVar($v1,$v2)
    {
        return $GLOBALS['autoindex']%2==0 ? $v1 : $v2;
    }
}

if ( ! function_exists('GetTopids'))
{
    function GetTopids($tid)
    {
        $arr = GetParentIds($tid);
        return join(',',$arr);
    }
}

if ( ! function_exists('GetParentIds'))
{
    function GetParentIds($tid)
    {
        global $cfg_Cs;
        $GLOBALS['pTypeArrays'][] = $tid;
        if(!is_array($cfg_Cs))
        {
            require_once(DEDEDATA."/cache/inc_catalog_base.inc");
        }
        if(!isset($cfg_Cs[$tid]) || $cfg_Cs[$tid][0]==0)
        {
            return $GLOBALS['pTypeArrays'];
        }
        else
        {
            return GetParentIds($cfg_Cs[$tid][0]);
        }
    }
}

if ( ! function_exists('IsParent'))
{
    function IsParent($sid, $pid)
    {
        $pTypeArrays = GetParentIds($sid);
        return in_array($pid, $pTypeArrays);
    }
}

if ( ! function_exists('GetTopid'))
{
    function GetTopid($tid)
    {
        global $cfg_Cs;
        if(!is_array($cfg_Cs))
        {
            require_once(DEDEDATA."/cache/inc_catalog_base.inc");
        }
        if(!isset($cfg_Cs[$tid][0]) || $cfg_Cs[$tid][0]==0)
        {
            return $tid;
        }
        else
        {
            return GetTopid($cfg_Cs[$tid][0]);
        }
    }
}

function GetSonIds($id,$channel=0,$addthis=true)
{
    global $cfg_Cs;
    $GLOBALS['idArray'] = array();
    if( !is_array($cfg_Cs) )
    {
        require_once(DEDEDATA."/cache/inc_catalog_base.inc");
    }
    GetSonIdsLogic($id,$cfg_Cs,$channel,$addthis);
    $rquery = join(',',$GLOBALS['idArray']);
    $rquery = preg_replace("/,$/", '', $rquery); 
    return $rquery;
}

function GetSonIdsLogic($id,$sArr,$channel=0,$addthis=false)
{
    if($id!=0 && $addthis)
    {
        $GLOBALS['idArray'][$id] = $id;
    }
    if(is_array($sArr))
    {
        foreach($sArr as $k=>$v)
        {
            if( $v[0]==$id && ($channel==0 || $v[1]==$channel ))
            {
                GetSonIdsLogic($k,$sArr,$channel,true);
            }
        }
    }
}

function MfTypedir($typedir)
{
    if(preg_match("/^http:|^ftp:/i", $typedir)) return $typedir;
    $typedir = str_replace("{cmspath}",$GLOBALS['cfg_cmspath'],$typedir);
    $typedir = preg_replace("/\/{1,}/", "/", $typedir);
    return $typedir;
}

function MfTemplet($tmpdir)
{
    $tmpdir = str_replace("{style}", $GLOBALS['cfg_df_style'], $tmpdir);
    $tmpdir = preg_replace("/\/{1,}/", "/", $tmpdir);
    return $tmpdir;
}

function FormatScript($atme)
{
    return $atme=='&nbsp;' ? '' : $atme;
}

function FillAttsDefault(&$atts, $attlist)
{
    $attlists = explode(',', $attlist);
    for($i=0; isset($attlists[$i]); $i++)
    {
        list($k, $v) = explode('|', $attlists[$i]);
        if(!isset($atts[$k]))
        {
            $atts[$k] = $v;
        }
    }
}

function MakeOneTag(&$dtp, &$refObj, $parfield='Y')
{
    global $cfg_disable_tags;
    $cfg_disable_tags = isset($cfg_disable_tags)? $cfg_disable_tags : 'php';
    $disable_tags = explode(',', $cfg_disable_tags);
    $alltags = array();
    $dtp->setRefObj($refObj);

    $dh = dir(DEDEINC.'/taglib');
    while($filename = $dh->read())
    {
        if(preg_match("/\.lib\./", $filename))
        {
            $alltags[] = str_replace('.lib.php','',$filename);
        }
    }
    $dh->Close();

    if(!is_array($dtp->CTags))
    {
        return '';
    }
    foreach($dtp->CTags as $tagid=>$ctag)
    {
        $tagname = $ctag->GetName();
        if($tagname=='field' && $parfield=='Y')
        {
            $vname = $ctag->GetAtt('name');
            if( $vname=='array' && isset($refObj->Fields) )
            {
                $dtp->Assign($tagid,$refObj->Fields);
            }
            else if(isset($refObj->Fields[$vname]))
            {
                $dtp->Assign($tagid,$refObj->Fields[$vname]);
            }
            else if($ctag->GetAtt('noteid') != '')
            {
                if( isset($refObj->Fields[$vname.'_'.$ctag->GetAtt('noteid')]) )
                {
                    $dtp->Assign($tagid, $refObj->Fields[$vname.'_'.$ctag->GetAtt('noteid')]);
                }
            }
            continue;
        }

        if(preg_match("/^(artlist|likeart|hotart|imglist|imginfolist|coolart|specart|autolist)$/", $tagname))
        {
            $tagname='arclist';
        }
        if($tagname=='friendlink')
        {
            $tagname='flink';
        }
        if(in_array($tagname,$alltags))
        {
            if(in_array($tagname, $disable_tags))
            {
                echo 'cloudcms Error:Tag disabled:"'.$tagname.'" <a href="http://www.yunteng.cc" target="_blank">more...</a>!';
                return FALSE;
            }
            if (DEBUG_LEVEL==TRUE) {
                $ttt1 = ExecTime();
            }
            $filename = DEDEINC.'/taglib/'.$tagname.'.lib.php';
            include_once($filename);
            $funcname = 'lib_'.$tagname;
            $dtp->Assign($tagid,$funcname($ctag,$refObj));
            if (DEBUG_LEVEL==TRUE) {
                $queryTime = ExecTime() - $ttt1;
                echo '标签：'.$tagname.'载入花费时间：'.$queryTime."<br />\r\n";
            }
        }
    }
}

function GetOneTypeUrlA($typeinfos)
{
    return GetTypeUrl($typeinfos['id'],MfTypedir($typeinfos['typedir']),$typeinfos['isdefault'],$typeinfos['defaultname'],
    $typeinfos['ispart'],$typeinfos['namerule2'],$typeinfos['moresite'],$typeinfos['siteurl'],$typeinfos['sitepath']);
}

function SetSysEnv($typeid=0,$typename='',$aid=0,$title='',$curfile='')
{
    global $_sys_globals;
    if(empty($_sys_globals['curfile']))
    {
        $_sys_globals['curfile'] = $curfile;
    }
    if(empty($_sys_globals['typeid']))
    {
        $_sys_globals['typeid'] = $typeid;
    }
    if(empty($_sys_globals['typename']))
    {
        $_sys_globals['typename'] = $typename;
    }
    if(empty($_sys_globals['aid']))
    {
        $_sys_globals['aid'] = $aid;
    }
}

function GetBookUrl($bid,$title,$gdir=0)
{
    global $cfg_cmspath;
    $bookurl = $gdir==1 ?
    "{$cfg_cmspath}/book/".DedeID2Dir($bid) : "{$cfg_cmspath}/book/".DedeID2Dir($bid).'/'.GetPinyin($title).'-'.$bid.'.html';
    return $bookurl;
}

function DedeID2Dir($aid)
{
    $n = ceil($aid / 1000);
    return $n;
}

function GetFreeListUrl($lid,$namerule,$listdir,$defaultpage,$nodefault){
    $listdir = str_replace('{cmspath}',$GLOBALS['cfg_cmspath'],$listdir);
    if($nodefault==1)
    {
        $okfile = str_replace('{page}','1',$namerule);
        $okfile = str_replace('{listid}',$lid,$okfile);
        $okfile = str_replace('{listdir}',$listdir,$okfile);
    }
    else
    {
        $okfile = $GLOBALS['cfg_phpurl']."/freelist.php?lid=$lid";
        return $okfile;
    }
    $okfile = str_replace("\\","/",$okfile);
    $okfile = str_replace("//","/",$okfile);
    $trueFile = $GLOBALS['cfg_basedir'].$okfile;
    if(!@file_exists($trueFile))
    {
        $okfile = $GLOBALS['cfg_phpurl']."/freelist.php?lid=$lid";
    }
    return $okfile;
}

function GetHotKeywords(&$dsql,$num=8,$nday=365,$klen=16,$orderby='count')
{
    global $cfg_phpurl,$cfg_cmspath;
    $nowtime = time();
    $num = @intval($num);
    $nday = @intval($nday);
    $klen = @intval($klen);
    if(empty($nday))
    {
        $nday = 365;
    }
    if(empty($num))
    {
        $num = 6;
    }
    if(empty($klen))
    {
        $klen = 16;
    }
    $klen = $klen+1;
    $mintime = $nowtime - ($nday * 24 * 3600);
    if(empty($orderby))
    {
        $orderby = 'count';
    }
    $dsql->SetQuery("SELECT keyword FROM #@__search_keywords WHERE lasttime>$mintime AND length(keyword)<$klen ORDER BY $orderby DESC LIMIT 0,$num");
    $dsql->Execute('hw');
    $hotword = "";
    while($row=$dsql->GetArray('hw'))
    {
        $hotword .= "　<a href='".$cfg_phpurl."/search.php?keyword=".urlencode($row['keyword'])."&searchtype=titlekeyword'>".$row['keyword']."</a> ";
    }
    return $hotword;
}

function Gmapurl($gurl)
{
    return preg_replace("/http:\/\//i", $gurl) ? $gurl : $GLOBALS['cfg_basehost'].$gurl;
}

function Quote_replace($quote)
{
    $quote = str_replace('{quote}','<div class="decmt-box">',$quote);
    $quote = str_replace('{title}','<div class="decmt-title"><span class="username">',$quote);
    $quote = str_replace('{/title}','</span></div>',$quote);
    $quote = str_replace('&lt;br/&gt;','<br>',$quote);
    $quote = str_replace('&lt;', '<', $quote);
    $quote = str_replace('&gt;', '>', $quote);
    $quote = str_replace('{content}','<div class="decmt-content">',$quote);
    $quote = str_replace('{/content}','</div>',$quote);
    $quote = str_replace('{/quote}','</div>',$quote);
    return $quote;
}

function GetCacheBlock($cacheid)
{
    global $cfg_puccache_time;
    $cachefile = DEDEDATA.'/cache/'.$cacheid.'.inc';
    if(!file_exists($cachefile) || filesize($cachefile)==0 || 
      $cfg_puccache_time==0 || time() - filemtime($cachefile) > $cfg_puccache_time)
    {
        return '';
    }
    $fp = fopen($cachefile, 'r');
    $str = @fread($fp, filesize($cachefile));
    fclose($fp);
    return $str;
}

function WriteCacheBlock($cacheid, $str)
{
    $cachefile = DEDEDATA.'/cache/'.$cacheid.'.inc';
    $fp = fopen($cachefile, 'w');
    $str = fwrite($fp, $str);
    fclose($fp);
}