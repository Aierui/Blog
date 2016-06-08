<?php   if(!defined('DEDEINC')) exit("Request Error!");

function MakePublicTag($atts=array(),$refObj='',$fields=array())
{
    $atts['tagname'] = preg_replace("/[0-9]{1,}$/", "", $atts['tagname']);
    $plusfile = DEDEINC.'/tpllib/plus_'.$atts['tagname'].'.php';
    if(!file_exists($plusfile))
    {
        if(isset($atts['rstype']) && $atts['rstype']=='string')
        {
            return '';
        }
        else
        {
            return array();
        }
    }
    else
    {
        include_once($plusfile);
        $func = 'plus_'.$atts['tagname'];
        return $func($atts, $refObj, $fields);
    }
}

function FillAtts(&$atts, $attlist)
{
    $attlists = explode(',', $attlist);
    foreach($attlists as $att)
    {
        list($k, $v)=explode('=', $att);
        if(!isset($atts[$k]))
        {
            $atts[$k] = $v;
        }
    }
}

function FillFields(&$atts, &$refObj, &$fields)
{
    global $_vars;
    foreach($atts as $k=>$v)
    {
        if(preg_match('/^field\./i',$v))
        {
            $key = preg_replace('/^field\./i', '', $v);
            if( isset($fields[$key]) )
            {
                $atts[$k] = $fields[$key];
            }
        }
        else if(preg_match('/^var\./i', $v))
        {
            $key = preg_replace('/^var\./i', '', $v);
            if( isset($_vars[$key]) )
            {
                $atts[$k] = $_vars[$key];
            }
        }
        else if(preg_match('/^global\./i', $v))
        {
            $key = preg_replace('/^global\./i', '', $v);
            if( isset($GLOBALS[$key]) )
            {
                $atts[$k] = $GLOBALS[$key];
            }
        }
    }
}

class Tag
{
    var $isCompiler=FALSE;
    var $tagName="";
    var $innerText="";
    var $startPos=0;
    var $endPos=0;
    var $cAtt="";
    var $tagValue="";
    var $tagID = 0;

    function GetName()
    {
        return strtolower($this->tagName);
    }

    function GetValue()
    {
        return $this->tagValue;
    }

    function IsAtt($str)
    {
        return $this->cAtt->IsAttribute($str);
    }

    function GetAtt($str)
    {
        return $this->cAtt->GetAtt($str);
    }

    function GetinnerText()
    {
        return $this->innerText;
    }
}

class DedeTemplate
{
    var $tagMaxLen = 64;
    var $charToLow = TRUE;
    var $isCache = TRUE;
    var $isParse = FALSE;
    var $isCompiler = TRUE;
    var $templateDir = '';
    var $tempMkTime = 0;
    var $cacheFile = '';
    var $configFile = '';
    var $buildFile = '';
    var $refDir = '';
    var $cacheDir = '';
    var $templateFile = '';
    var $sourceString = '';
    var $cTags = '';

    //var $definedVars = array();
    var $count = -1;
    var $loopNum = 0;
    var $refObj = '';
    var $makeLoop = 0;
    var $tagStartWord =  '{dede:';
    var $fullTagEndWord =  '{/dede:';
    var $sTagEndWord = '/}';
    var $tagEndWord = '}';
    var $tpCfgs = array();

    function __construct($templatedir='',$refDir='')
    {
        //$definedVars[] = 'var';
        if($templatedir=='')
        {
            $this->templateDir = DEDEROOT.'/templates';
        }
        else
        {
            $this->templateDir = $templatedir;
        }

        if($refDir=='')
        {
            if(isset($GLOBALS['cfg_df_style']))
            {
                $this->refDir = $this->templateDir.'/'.$GLOBALS['cfg_df_style'].'/';
            }
            else
            {
                $this->refDir = $this->templateDir;
            }
        }
        $this->cacheDir = DEDEROOT.$GLOBALS['cfg_tplcache_dir'];
    }

    function DedeTemplate($templatedir='',$refDir='')
    {
        $this->__construct($templatedir,$refDir);
    }

    function SetObject(&$refObj)
    {
        $this->refObj = $refObj;
    }

    function SetVar($k, $v)
    {
        $GLOBALS['_vars'][$k] = $v;
    }

    function Assign($k, $v)
    {
        $GLOBALS['_vars'][$k] = $v;
    }

    function SetArray($k, $v)
    {
        $GLOBALS[$k] = $v;
    }

    function SetTagStyle($ts='{dede:',$ftend='{/dede:',$stend='/}',$tend='}')
    {
        $this->tagStartWord =  $ts;
        $this->fullTagEndWord =  $ftend;
        $this->sTagEndWord = $stend;
        $this->tagEndWord = $tend;
    }

    function GetConfig($k)
    {
        return (isset($this->tpCfgs[$k]) ? $this->tpCfgs[$k] : '');
    }

    function LoadTemplate($tmpfile)
    {
        if(!file_exists($tmpfile))
        {
            echo " Template Not Found! ";
            exit();
        }
        $tmpfile = preg_replace("/[\\/]{1,}/", "/", $tmpfile);
        $tmpfiles = explode('/',$tmpfile);
        $tmpfileOnlyName = preg_replace("/(.*)\//", "", $tmpfile);
        $this->templateFile = $tmpfile;
        $this->refDir = '';
        for($i=0; $i < count($tmpfiles)-1; $i++)
        {
            $this->refDir .= $tmpfiles[$i].'/';
        }
        if(!is_dir($this->cacheDir))
        {
            $this->cacheDir = $this->refDir;
        }
        if($this->cacheDir!='')
        {
            $this->cacheDir = $this->cacheDir.'/';
        }
        if(isset($GLOBALS['_DEBUG_CACHE']))
        {
            $this->cacheDir = $this->refDir;
        }
        $this->cacheFile = $this->cacheDir.preg_replace("/\.(wml|html|htm|php)$/", "_".$this->GetEncodeStr($tmpfile).'.inc', $tmpfileOnlyName);
        $this->configFile = $this->cacheDir.preg_replace("/\.(wml|html|htm|php)$/", "_".$this->GetEncodeStr($tmpfile).'_config.inc', $tmpfileOnlyName);

        if($this->isCache==FALSE || !file_exists($this->cacheFile)
        || filemtime($this->templateFile) > filemtime($this->cacheFile))
        {
            $t1 = ExecTime();
            $fp = fopen($this->templateFile,'r');
            $this->sourceString = fread($fp,filesize($this->templateFile));
            fclose($fp);
            $this->ParseTemplate();
            //echo ExecTime() - $t1;
        }
        else
        {
            if(file_exists($this->configFile))
            {
                include($this->configFile);
            }
        }
    }

    function LoadString($str='')
    {
        $this->sourceString = $str;
        $hashcode = md5($this->sourceString);
        $this->cacheFile = $this->cacheDir."/string_".$hashcode.".inc";
        $this->configFile = $this->cacheDir."/string_".$hashcode."_config.inc";
        $this->ParseTemplate();
    }

    function CacheFile()
    {
        global $gtmpfile;
        $this->WriteCache();
        return $this->cacheFile;
    }

    function Display()
    {
        global $gtmpfile;
        extract($GLOBALS, EXTR_SKIP);
        $this->WriteCache();
        include $this->cacheFile;
    }

    function SaveTo($savefile)
    {
        extract($GLOBALS, EXTR_SKIP);
        $this->WriteCache();
        ob_start();
        include $this->cacheFile;
        $okstr = ob_get_contents();
        ob_end_clean();
        $fp = @fopen($savefile,"w") or die(" Tag Engine Create File FALSE! ");
        fwrite($fp,$okstr);
        fclose($fp);
    }

    function CheckDisabledFunctions($str,&$errmsg='')
    {
        global $cfg_disable_funs;
        $cfg_disable_funs = isset($cfg_disable_funs)? $cfg_disable_funs : 'phpinfo,eval,exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source,file_put_contents,fsockopen,fopen,fwrite';

        if (!defined('DEDEDISFUN')) {
            $tokens = token_get_all_nl($str);
            $disabled_functions = explode(',', $cfg_disable_funs);
            foreach ($tokens as $token)
            {
                if (is_array($token))
                {
                    if ($token[0] = '306' && in_array($token[1], $disabled_functions)) 
                    {
                       $errmsg = '';
                       return FALSE;
                    }
                }
            }
        }
        return TRUE;
    }

    function WriteCache($ctype='all')
    {
        if(!file_exists($this->cacheFile) || $this->isCache==FALSE
        || ( file_exists($this->templateFile) && (filemtime($this->templateFile) > filemtime($this->cacheFile)) ) )
        {
                if(!$this->isParse)
                {
                    $this->ParseTemplate();
                }
                $fp = fopen($this->cacheFile,'w') or dir("Write Cache File Error! ");
                flock($fp,3);
                $result = trim($this->GetResult());
                $errmsg = '';
                //var_dump($result);exit();
                if (!$this->CheckDisabledFunctions($result, $errmsg)) 
                {
                    fclose($fp);
                    @unlink($this->cacheFile);
                    die($errmsg);
                }
                fwrite($fp,$result);
                fclose($fp);
                if(count($this->tpCfgs) > 0)
                {
                    $fp = fopen($this->configFile,'w') or dir("Write Config File Error! ");
                    flock($fp,3);
                    fwrite($fp,'<'.'?php'."\r\n");
                    foreach($this->tpCfgs as $k=>$v)
                    {
                        $v = str_replace("\"","\\\"",$v);
                        $v = str_replace("\$","\\\$",$v);
                        fwrite($fp,"\$this->tpCfgs['$k']=\"$v\";\r\n");
                    }
                    fwrite($fp,'?'.'>');
                    fclose($fp);
                }
        }
    }

    function GetEncodeStr($tmpfile)
    {
        //$tmpfiles = explode('/',$tmpfile);
        $encodeStr = substr(md5($tmpfile),0,24);
        return $encodeStr;
    }

    function ParseTemplate()
    {
        if($this->makeLoop > 5)
        {
            return ;
        }
        $this->count = -1;
        $this->cTags = array();
        $this->isParse = TRUE;
        $sPos = 0;
        $ePos = 0;
        $tagStartWord =  $this->tagStartWord;
        $fullTagEndWord =  $this->fullTagEndWord;
        $sTagEndWord = $this->sTagEndWord;
        $tagEndWord = $this->tagEndWord;
        $startWordLen = strlen($tagStartWord);
        $sourceLen = strlen($this->sourceString);
        if( $sourceLen <= ($startWordLen + 3) )
        {
            return;
        }
        $cAtt = new TagAttributeParse();
        $cAtt->CharToLow = TRUE;

        $t = 0;
        $preTag = '';
        $tswLen = strlen($tagStartWord);
        for($i=0; $i<$sourceLen; $i++)
        {
            $ttagName = '';

            if($i-1>=0)
            {
                $ss = $i-1;
            }
            else
            {
                $ss = 0;
            }
            $tagPos = strpos($this->sourceString,$tagStartWord,$ss);

            if($tagPos==0 && ($sourceLen-$i < $tswLen
            || substr($this->sourceString,$i,$tswLen)!=$tagStartWord ))
            {
                $tagPos = -1;
                break;
            }

            for($j = $tagPos+$startWordLen; $j < $tagPos+$startWordLen+$this->tagMaxLen; $j++)
            {
                if(preg_match("/[ >\/\r\n\t\}\.]/", $this->sourceString[$j]))
                {
                    break;
                }
                else
                {
                    $ttagName .= $this->sourceString[$j];
                }
            }
            if($ttagName!='')
            {
                $i = $tagPos + $startWordLen;
                $endPos = -1;

                $fullTagEndWordThis = $fullTagEndWord.$ttagName.$tagEndWord;
                $e1 = strpos($this->sourceString, $sTagEndWord, $i);
                $e2 = strpos($this->sourceString, $tagStartWord, $i);
                $e3 = strpos($this->sourceString, $fullTagEndWordThis, $i);
                $e1 = trim($e1); $e2 = trim($e2); $e3 = trim($e3);
                $e1 = ($e1=='' ? '-1' : $e1);
                $e2 = ($e2=='' ? '-1' : $e2);
                $e3 = ($e3=='' ? '-1' : $e3);
                if($e3==-1)
                {
                    //不存在'{/tag:标记'
                    $endPos = $e1;
                    $elen = $endPos + strlen($sTagEndWord);
                }
                else if($e1==-1)
                {
                    //不存在 '/}'
                    $endPos = $e3;
                    $elen = $endPos + strlen($fullTagEndWordThis);
                }

                //同时存在 '/}' 和 '{/tag:标记'
                else
                {
                    //如果 '/}' 比 '{tag:'、'{/tag:标记' 都要靠近，则认为结束标志是 '/}'，否则结束标志为 '{/tag:标记'
                    if($e1 < $e2 &&  $e1 < $e3 )
                    {
                        $endPos = $e1;
                        $elen = $endPos + strlen($sTagEndWord);
                    }
                    else
                    {
                        $endPos = $e3;
                        $elen = $endPos + strlen($fullTagEndWordThis);
                    }
                }

                if($endPos==-1)
                {
                    echo "Tpl Character postion $tagPos, '$ttagName' Error！<br />\r\n";
                    break;
                }
                $i = $elen;

                $attStr = '';
                $innerText = '';
                $startInner = 0;
                for($j = $tagPos+$startWordLen; $j < $endPos; $j++)
                {
                    if($startInner==0)
                    {
                        if($this->sourceString[$j]==$tagEndWord)
                        {
                            $startInner=1; continue;
                         }
                        else
                        {
                            $attStr .= $this->sourceString[$j];
                        }
                    }
                    else
                    {
                        $innerText .= $this->sourceString[$j];
                    }
                }
                $ttagName = strtolower($ttagName);

                if(preg_match("/^if[0-9]{0,}$/", $ttagName))
                {
                    $cAtt->cAttributes = new TagAttribute();
                    $cAtt->cAttributes->count = 2;
                    $cAtt->cAttributes->items['tagname'] = $ttagName;
                    $cAtt->cAttributes->items['condition'] = preg_replace("/^if[0-9]{0,}[\r\n\t ]/", "", $attStr);
                    $innerText = preg_replace("/\{else\}/i", '<'."?php\r\n}\r\nelse{\r\n".'?'.'>', $innerText);
                }
                else if($ttagName=='php')
                {
                    $cAtt->cAttributes = new TagAttribute();
                    $cAtt->cAttributes->count = 2;
                    $cAtt->cAttributes->items['tagname'] = $ttagName;
                    $cAtt->cAttributes->items['code'] = '<'."?php\r\n".trim(preg_replace("/^php[0-9]{0,}[\r\n\t ]/",
                                                          "",$attStr))."\r\n?".'>';
                }
                else
                {
                    $cAtt->SetSource($attStr);
                }
                $this->count++;
                $cTag = new Tag();
                $cTag->tagName = $ttagName;
                $cTag->startPos = $tagPos;
                $cTag->endPos = $i;
                $cTag->cAtt = $cAtt->cAttributes;
                $cTag->isCompiler = FALSE;
                $cTag->tagID = $this->count;
                $cTag->innerText = $innerText;
                $this->cTags[$this->count] = $cTag;
            }
            else
            {
                $i = $tagPos+$startWordLen;
                break;
            }
        }
        if( $this->count > -1 && $this->isCompiler )
        {
            $this->CompilerAll();
        }
    }

    function CompilerAll()
    {
        $this->loopNum++;
        if($this->loopNum > 10)
        {
            return;
        }
        $ResultString = '';
        $nextTagEnd = 0;
        for($i=0; isset($this->cTags[$i]); $i++)
        {
            $ResultString .= substr($this->sourceString, $nextTagEnd, $this->cTags[$i]->startPos - $nextTagEnd);
            $ResultString .= $this->CompilerOneTag($this->cTags[$i]);
            $nextTagEnd = $this->cTags[$i]->endPos;
        }
        $slen = strlen($this->sourceString);
        if($slen > $nextTagEnd)
        {
            $ResultString .= substr($this->sourceString,$nextTagEnd,$slen-$nextTagEnd);
        }
        $this->sourceString = $ResultString;
        $this->ParseTemplate();
    }

    function GetResult()
    {
        if(!$this->isParse)
        {
            $this->ParseTemplate();
        }
        $addset = '';
        $addset .= '<'.'?php'."\r\n".'if(!isset($GLOBALS[\'_vars\'])) $GLOBALS[\'_vars\'] = array(); '."\r\n".'$fields = array();'."\r\n".'?'.'>';
        return preg_replace("/\?".">[ \r\n\t]{0,}<"."\?php/", "", $addset.$this->sourceString);
    }

    function CompilerOneTag(&$cTag)
    {
        $cTag->isCompiler = TRUE;
        $tagname = $cTag->tagName;
        $varname = $cTag->GetAtt('name');
        $rsvalue = "";

        if( $tagname == 'config' )
        {
            $this->tpCfgs[$varname] = $cTag->GetAtt('value');
        }
        else if( $tagname == 'global' )
        {
            $cTag->tagValue = $this->CompilerArrayVar('global',$varname);
            if( $cTag->GetAtt('function') != '' )
            {
                $cTag->tagValue = $this->CompilerFunction($cTag->GetAtt('function'), $cTag->tagValue);
            }
            $cTag->tagValue = '<'.'?php echo '.$cTag->tagValue.'; ?'.'>';
        }
        else if( $tagname == 'cfg' )
        {
            $cTag->tagValue = '$GLOBALS[\'cfg_'.$varname.'\']';
            if( $cTag->GetAtt('function')!='' )
            {
                $cTag->tagValue = $this->CompilerFunction($cTag->GetAtt('function'), $cTag->tagValue);
            }
            $cTag->tagValue = '<'.'?php echo '.$cTag->tagValue.'; ?'.'>';
        }
        else if( $tagname == 'name' )
        {
            $cTag->tagValue = '$'.$varname;
            if( $cTag->GetAtt('function')!='' )
            {
                $cTag->tagValue = $this->CompilerFunction($cTag->GetAtt('function'), $cTag->tagValue);
            }
            $cTag->tagValue = '<'.'?php echo '.$cTag->tagValue.'; ?'.'>';
        }
        else if( $tagname == 'object' )
        {
            list($_obs,$_em) = explode('->',$varname);
            $cTag->tagValue = "\$GLOBALS['{$_obs}']->{$_em}";
            if( $cTag->GetAtt('function')!='' )
            {
                $cTag->tagValue = $this->CompilerFunction($cTag->GetAtt('function'), $cTag->tagValue);
            }
            $cTag->tagValue = '<'.'?php echo '.$cTag->tagValue.'; ?'.'>';
        }
        else if($tagname == 'var')
        {
            $cTag->tagValue = $this->CompilerArrayVar('var', $varname);

            if( $cTag->GetAtt('function')!='' )
            {
                $cTag->tagValue = $this->CompilerFunction($cTag->GetAtt('function'), $cTag->tagValue);
            }

            if ($cTag->GetAtt('default')!='')
            {
                $cTag->tagValue = '<'.'?php echo empty('.$cTag->tagValue.')? \''.addslashes($cTag->GetAtt('default')).'\':'.$cTag->tagValue.'; ?'.'>';
            } else {
                $cTag->tagValue = '<'.'?php echo '.$cTag->tagValue.'; ?'.'>';
            }
        }
        else if($tagname == 'field')
        {
            $cTag->tagValue = '$fields[\''.$varname.'\']';
            if( $cTag->GetAtt('function')!='' )
            {
                $cTag->tagValue = $this->CompilerFunction($cTag->GetAtt('function'), $cTag->tagValue);
            }
            $cTag->tagValue = '<'.'?php echo '.$cTag->tagValue.'; ?'.'>';
        }
        else if( preg_match("/^key[0-9]{0,}/", $tagname) || preg_match("/^value[0-9]{0,}/", $tagname))
        {
            if( preg_match("/^value[0-9]{0,}/", $tagname) && $varname!='' )
            {
                $cTag->tagValue = '<'.'?php echo '.$this->CompilerArrayVar($tagname,$varname).'; ?'.'>';
            }
            else
            {
                $cTag->tagValue = '<'.'?php echo $'.$tagname.'; ?'.'>';
            }
        }
        else if( preg_match("/^if[0-9]{0,}$/", $tagname) )
        {
            $cTag->tagValue = $this->CompilerIf($cTag);
        }
        else if( $tagname=='echo' )
        {
            if(trim($cTag->GetInnerText())=='') $cTag->tagValue = $cTag->GetAtt('code');
            else
            {
                $cTag->tagValue =  '<'."?php echo $".trim($cTag->GetInnerText())." ;?".'>';
            }
        }
        else if( $tagname=='php' )
        {
            if(trim($cTag->GetInnerText())=='') $cTag->tagValue = $cTag->GetAtt('code');
            else
            {
                $cTag->tagValue =  '<'."?php\r\n".trim($cTag->GetInnerText())."\r\n?".'>';
            }
        }
        else if( preg_match("/^array[0-9]{0,}/",$tagname) )
        {
            $kk = '$key';
            $vv = '$value';
            if($cTag->GetAtt('key')!='')
            {
                $kk = '$key'.$cTag->GetAtt('key');
            }
            if($cTag->GetAtt('value')!='')
            {
                $vv = '$value'.$cTag->GetAtt('value');
            }
            $addvar = '';
            if(!preg_match("/\(/",$varname))
            {
                $varname = '$GLOBALS[\''.$varname.'\']';
            }
            else
            {
                $addvar = "\r\n".'$myarrs = $pageClass->'.$varname.";\r\n";
                $varname = ' $myarrs ';
            }
            $rsvalue = '<'.'?php '.$addvar.' foreach('.$varname.' as '.$kk.'=>'.$vv.'){ ?'.">";
            $rsvalue .= $cTag->GetInnerText();
            $rsvalue .= '<'.'?php  }    ?'.">\r\n";
            $cTag->tagValue = $rsvalue;
        }

        else if($tagname == 'include')
        {
            $filename = $cTag->GetAtt('file');
            if($filename=='')
            {
                $filename = $cTag->GetAtt('filename');
            }
            $cTag->tagValue = $this->CompilerInclude($filename, FALSE);
            if($cTag->tagValue==0) $cTag->tagValue = '';
            $cTag->tagValue = '<'.'?php include $this->CompilerInclude("'.$filename.'");'."\r\n".' ?'.'>';
        }
        else if( $tagname=='label' )
        {
            $bindFunc = $cTag->GetAtt('bind');
            $rsvalue = 'echo '.$bindFunc.";\r\n";
            $rsvalue = '<'.'?php  '.$rsvalue.'  ?'.">\r\n";
            $cTag->tagValue = $rsvalue;
        }
        else if( $tagname=='datalist' )
        {

            foreach($cTag->cAtt->items as $k=>$v)
            {
                $v = $this->TrimAtts($v);
                $rsvalue .= '$atts[\''.$k.'\'] = \''.str_replace("'","\\'",$v)."';\r\n";
            }
            $rsvalue = '<'.'?php'."\r\n".'$atts = array();'."\r\n".$rsvalue;
            $rsvalue .= '$blockValue = $this->refObj->GetArcList($atts,$this->refObj,$fields); '."\r\n";
            $rsvalue .= 'if(is_array($blockValue)){'."\r\n";
            $rsvalue .= 'foreach( $blockValue as $key=>$fields )'."\r\n{\r\n".'?'.">";
            $rsvalue .= $cTag->GetInnerText();
            $rsvalue .= '<'.'?php'."\r\n}\r\n}".'?'.'>';
            $cTag->tagValue = $rsvalue;
        }
        else if( $tagname=='pagelist' )
        {

            foreach($cTag->cAtt->items as $k=>$v)
            {
                $v = $this->TrimAtts($v);
                $rsvalue .= '$atts[\''.$k.'\'] = \''.str_replace("'","\\'",$v)."';\r\n";
            }
            $rsvalue = '<'.'?php'."\r\n".'$atts = array();'."\r\n".$rsvalue;
            $rsvalue .= ' echo $this->refObj->GetPageList($atts,$this->refObj,$fields); '."\r\n".'?'.">\r\n";
            $cTag->tagValue = $rsvalue;
        }
        else
        {
            $bindFunc = $cTag->GetAtt('bind');
            $bindType = $cTag->GetAtt('bindtype');
            $rstype =  ($cTag->GetAtt('resulttype')=='' ? $cTag->GetAtt('rstype') : $cTag->GetAtt('resulttype') );
            $rstype = strtolower($rstype);

            foreach($cTag->cAtt->items as $k=>$v)
            {
                if(preg_match("/(bind|bindtype)/i",$k))
                {
                    continue;
                }
                $v = $this->TrimAtts($v);
                $rsvalue .= '$atts[\''.$k.'\'] = \''.str_replace("'","\\'",$v)."';\r\n";
            }
            $rsvalue = '<'.'?php'."\r\n".'$atts = array();'."\r\n".$rsvalue;

            if($bindFunc=='')
            {
                $rsvalue .= '$blockValue = MakePublicTag($atts,$this->refObj,$fields); '."\r\n";
            }
            else
            {
                if($bindType=='') $rsvalue .= '$blockValue = $this->refObj->'.$bindFunc.'($atts,$this->refObj,$fields); '."\r\n";
                else $rsvalue .= '$blockValue = '.$bindFunc.'($atts,$this->refObj,$fields); '."\r\n";
            }

            if($rstype=='string')
            {
                $rsvalue .= 'echo $blockValue;'."\r\n".'?'.">";
            }
            else
            {
                $rsvalue .= 'if(is_array($blockValue) && count($blockValue) > 0){'."\r\n";
                $rsvalue .= 'foreach( $blockValue as $key=>$fields )'."\r\n{\r\n".'?'.">";
                $rsvalue .= $cTag->GetInnerText();
                $rsvalue .= '<'.'?php'."\r\n}\r\n}\r\n".'?'.'>';
            }
            $cTag->tagValue = $rsvalue;
        }
        return $cTag->tagValue;
    }

    function CompilerArrayVar($vartype, $varname)
    {
        $okvalue = '';

        if(!preg_match("/\[/", $varname))
        {
            if(preg_match("/^value/",$vartype))
            {
                $varname = $vartype.'.'.$varname;
            }
            $varnames = explode('.',$varname);
            if(isset($varnames[1]))
            {
                $varname = $varnames[0];
                for($i=1; isset($varnames[$i]); $i++)
                {
                    $varname .= "['".$varnames[$i]."']";
                }
            }
        }

        if(preg_match("/\[/", $varname))
        {
            $varnames = explode('[', $varname);
            $arrend = '';
            for($i=1;isset($varnames[$i]);$i++)
            {
                $arrend .= '['.$varnames[$i];
            }
            if(!preg_match("/[\"']/", $arrend)) {
                $arrend = str_replace('[', '', $arrend);
                $arrend = str_replace(']', '', $arrend);
                $arrend = "['{$arrend}']";
            }
            if($vartype=='var')
            {
                $okvalue = '$GLOBALS[\'_vars\'][\''.$varnames[0].'\']'.$arrend;
            }
            else if( preg_match("/^value/", $vartype) )
            {
                $okvalue = '$'.$varnames[0].$arrend;
            }
            else if($vartype=='field')
            {
                $okvalue = '$fields[\''.$varnames[0].'\']'.$arrend;
            }
            else
            {
                $okvalue = '$GLOBALS[\''.$varnames[0].'\']'.$arrend;
            }
        }
        else
        {
            if($vartype=='var')
            {
                $okvalue = '$GLOBALS[\'_vars\'][\''.$varname.'\']';
            }
            else if( preg_match("/^value/",$vartype) )
            {
                $okvalue = '$'.$vartype;
            }
            else if($vartype=='field')
            {
                $okvalue = '$'.str_replace($varname);
            }
            else
            {
                $okvalue = '$GLOBALS[\''.$varname.'\']';
            }
        }
        return $okvalue;
    }

    function CompilerIf($cTag)
    {
        $condition = trim($cTag->GetAtt('condition'));
        if($condition =='')
        {
            $cTag->tagValue=''; return '';
        }
        $condition = preg_replace("/((var\.|field\.|cfg\.|global\.|key[0-9]{0,}\.|value[0-9]{0,}\.)[\._a-z0-9]+)/ies", "private_rt('\\1')", $condition);
        $rsvalue = '<'.'?php if('.$condition.'){ ?'.'>';
        $rsvalue .= $cTag->GetInnerText();
        $rsvalue .= '<'.'?php } ?'.'>';
        return $rsvalue;
    }

    function TrimAtts($v)
    {
        $v = str_replace('<'.'?','&lt;?',$v);
        $v = str_replace('?'.'>','?&gt;',$v);
        return  $v;
    }

    function CompilerFunction($funcstr, $nvalue)
    {
        $funcstr = str_replace('@quote', '"', $funcstr);
        $funcstr = str_replace('@me', $nvalue, $funcstr);
        return $funcstr;
    }

    function CompilerInclude($filename, $isload=TRUE)
    {
        $okfile = '';
        if( @file_exists($filename) )
        {
            $okfile = $filename;
        }
        else if( @file_exists($this->refDir.$filename) )
        {
            $okfile = $this->refDir.$filename;
        }
        else if( @file_exists($this->refDir."../".$filename) )
        {
            $okfile = $this->refDir."../".$filename;
        }
        if($okfile=='') return 0;
        if( !$isload ) return 1;
        $itpl = new DedeTemplate($this->templateDir);
        $itpl->isCache = $this->isCache;
        $itpl->SetObject($this->refObj);
        $itpl->LoadTemplate($okfile);
        return $itpl->CacheFile();
    }
}

class TagAttribute
{
    var $count = -1;
    var $items = "";

    function GetAtt($str)
    {
        if($str=="")
        {
            return "";
        }
        if(isset($this->items[$str]))
        {
            return $this->items[$str];
        }
        else
        {
            return "";
        }
    }

    function GetAttribute($str)
    {
        return $this->GetAtt($str);
    }

    function IsAttribute($str)
    {
        if(isset($this->items[$str])) return TRUE;
        else return FALSE;
    }

    function GettagName()
    {
        return $this->GetAtt("tagname");
    }

    function Getcount()
    {
        return $this->count+1;
    }
}
class TagAttributeParse
{
    var $sourceString = "";
    var $sourceMaxSize = 1024;
    var $cAttributes = "";
    var $charToLow = TRUE;
    function SetSource($str="")
    {
        $this->cAttributes = new TagAttribute();
        $strLen = 0;
        $this->sourceString = trim(preg_replace("/[ \r\n\t\f]{1,}/"," ",$str));
        $strLen = strlen($this->sourceString);
        if($strLen>0 && $strLen <= $this->sourceMaxSize)
        {
            $this->ParseAttribute();
        }
    }

    function ParseAttribute()
    {
        $d = '';
        $tmpatt = '';
        $tmpvalue = '';
        $startdd = -1;
        $ddtag = '';
        $hasAttribute=FALSE;
        $strLen = strlen($this->sourceString);

        for($i=0; $i<$strLen; $i++)
        {
            if($this->sourceString[$i]==' ')
            {
                $this->cAttributes->count++;
                $tmpvalues = explode('.', $tmpvalue);
                $this->cAttributes->items['tagname'] = ($this->charToLow ? strtolower($tmpvalues[0]) : $tmpvalues[0]);
                if( isset($tmpvalues[2]) )
                {
                    $okname = $tmpvalues[1];
                    for($j=2;isset($tmpvalues[$j]);$j++)
                    {
                        $okname .= "['".$tmpvalues[$j]."']";
                    }
                    $this->cAttributes->items['name'] = $okname;
                }
                else if(isset($tmpvalues[1]) && $tmpvalues[1]!='')
                {
                    $this->cAttributes->items['name'] = $tmpvalues[1];
                }
                $tmpvalue = '';
                $hasAttribute = TRUE;
                break;
            }
            else
            {
                $tmpvalue .= $this->sourceString[$i];
            }
        }

        if(!$hasAttribute)
        {
            $this->cAttributes->count++;
            $tmpvalues = explode('.', $tmpvalue);
            $this->cAttributes->items['tagname'] = ($this->charToLow ? strtolower($tmpvalues[0]) : $tmpvalues[0]);
            if( isset($tmpvalues[2]) )
            {
                $okname = $tmpvalues[1];
                for($i=2;isset($tmpvalues[$i]);$i++)
                {
                    $okname .= "['".$tmpvalues[$i]."']";
                 }
                $this->cAttributes->items['name'] = $okname;
            }
            else if(isset($tmpvalues[1]) && $tmpvalues[1]!='')
            {
                $this->cAttributes->items['name'] = $tmpvalues[1];
            }
            return ;
        }
        $tmpvalue = '';

        for($i; $i<$strLen; $i++)
        {
            $d = $this->sourceString[$i];

            if($startdd==-1)
            {
                if($d != '=')
                {
                    $tmpatt .= $d;
                }
                else
                {
                    if($this->charToLow)
                    {
                        $tmpatt = strtolower(trim($tmpatt));
                    }
                    else
                    {
                        $tmpatt = trim($tmpatt);
                    }
                    $startdd=0;
                }
            }

            else if($startdd==0)
            {
                switch($d)
                {
                    case ' ':
                        break;
                    case '\'':
                        $ddtag = '\'';
                        $startdd = 1;
                        break;
                    case '"':
                        $ddtag = '"';
                        $startdd = 1;
                        break;
                    default:
                        $tmpvalue .= $d;
                        $ddtag = ' ';
                        $startdd = 1;
                        break;
                }
            }
            else if($startdd==1)
            {
                if($d==$ddtag && ( isset($this->sourceString[$i-1]) && $this->sourceString[$i-1]!="\\") )
                {
                    $this->cAttributes->count++;
                    $this->cAttributes->items[$tmpatt] = trim($tmpvalue);
                    $tmpatt = '';
                    $tmpvalue = '';
                    $startdd = -1;
                }
                else
                {
                    $tmpvalue .= $d;
                }
            }
        }

        if($tmpatt != '')
        {
            $this->cAttributes->count++;
            $this->cAttributes->items[$tmpatt] = trim($tmpvalue);
        }

    }

}
function private_rt($str)
{
    $arr = explode('.', $str);

    $rs = '$GLOBALS[\'';
    if($arr[0] == 'cfg')
    {
        return $rs.'cfg_'.$arr[1]."']";
    }
    elseif($arr[0] == 'var')
    {
        $arr[0] = '_vars';
        $rs .= implode('\'][\'', $arr);
        $rs .= "']";
        return $rs;
    }
    elseif($arr[0] == 'global')
    {
        unset($arr[0]);
        $rs .= implode('\'][\'', $arr);
        $rs .= "']";
        return $rs;
    }
    else
    {
        if($arr[0] == 'field') $arr[0] = 'fields';
        $rs = '$'.$arr[0]."['";
        unset($arr[0]);
        $rs .= implode('\'][\'', $arr);
        $rs .= "']";
        return $rs;
    }
}