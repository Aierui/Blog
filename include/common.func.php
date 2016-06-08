<?php
if(!defined('DEDEINC')) exit('dedecms');

$_helpers = array();
function helper($helpers)
{
    if (is_array($helpers))
    {
        foreach($helpers as $dede)
        {
            helper($dede);
        }
        return;
    }

    if (isset($_helpers[$helpers]))
    {
        continue;
    }
    if (file_exists(DEDEINC.'/helpers/'.$helpers.'.helper.php'))
    { 
        include_once(DEDEINC.'/helpers/'.$helpers.'.helper.php');
        $_helpers[$helpers] = TRUE;
    }
    if ( ! isset($_helpers[$helpers]))
    {
        exit('Unable to load the requested file: helpers/'.$helpers.'.helper.php');                
    }
}

function RunApp($ct, $ac = '',$directory = '')
{
    
    $ct = preg_replace("/[^0-9a-z_]/i", '', $ct);
    $ac = preg_replace("/[^0-9a-z_]/i", '', $ac);
        
    $ac = empty ( $ac ) ? $ac = 'index' : $ac;
	if(!empty($directory)) $path = DEDECONTROL.'/'.$directory. '/' . $ct . '.php';
	else $path = DEDECONTROL . '/' . $ct . '.php';
        
	if (file_exists ( $path ))
	{
		require $path;
	} else {
		 if (DEBUG_LEVEL === TRUE)
        {
            trigger_error("Load Controller false!");
        }
        else
        {
            header ( "location:/404.html" );
            die ();
        }
	}
	$action = 'ac_'.$ac;
    $loaderr = FALSE;
    $instance = new $ct ( );
    if (method_exists ( $instance, $action ) === TRUE)
    {
        $instance->$action();
        unset($instance);
    } else $loaderr = TRUE;
        
    if ($loaderr)
    {
        if (DEBUG_LEVEL === TRUE)
        {
            trigger_error("Load Method false!");
        }
        else
        {
            header ( "location:/404.html" );
            die ();
        }
    }
}

function helpers($helpers)
{
    helper($helpers);
}

if(!function_exists('file_put_contents'))
{
    function file_put_contents($n, $d)
    {
        $f=@fopen($n, "w");
        if (!$f)
        {
            return FALSE;
        }
        else
        {
            fwrite($f, $d);
            fclose($f);
            return TRUE;
        }
    }
}

function UpdateStat()
{
    include_once(DEDEINC."/inc/inc_stat.php");
    return SpUpdateStat();
}

function ShowMsg($msg, $gourl, $onlymsg=0, $limittime=0)
{
    if(empty($GLOBALS['cfg_plus_dir'])) $GLOBALS['cfg_plus_dir'] = '..';
    
    $htmlhead  = "<html>\r\n<head>\r\n<title>提示信息_石家庄云腾网络科技_www.yunteng.cc</title>\r\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=gb2312\" />\r\n";
    $htmlhead .= "<base target='_self'/>\r\n<style>
    .ts_div{width:500px;overflow:hidden;margin:0 auto;margin-top:5%;border:1px solid #E9E9E9;border-radius:3px;}
.ts_border{border:7px solid #efefef;}
.ts_b2{background:#fff;border:1px solid #E9E9E9;padding:10px 20px 10px 20px;}
.ts_div h2{text-align:left;color:#666;border-bottom:1px dotted #ccc;padding-bottom:10px;font-size:12px;}
.ts_div p{line-height:70px;margin:10px auto;font-size:14px;text-align:left;text-indent:10px;}
.ts_tz{margin:10px auto;text-align:right;width:500px;color:#666;font-size:12px;}
.ts_tz a{color:#f30; font-size:12px; text-decoration:none;}a.history{text-indent:-9999px;float:left;margin:-27px 0 0 115px;margin-top:-29px\9;display:inline;width:13px;height:15px;overflow:hidden;}a:hover.history{background-position:-15px -120px;}
.wts{text-align:left;line-height:28px;padding:10px 0 0px 60px;font-size:12px;color:#060}.wts a{color:#333;}ul#history em{display:block;margin-top:8px;color:#333;font-style:normal;text-indent:5px;}</style></head>\r\n<body leftmargin='0' topmargin='0' bgcolor='#f7f7f7'>".(isset($GLOBALS['ucsynlogin']) ? $GLOBALS['ucsynlogin'] : '')."\r\n<center>\r\n<script>\r\n";
    $htmlfoot  = "</script>\r\n</center>\r\n</body>\r\n</html>\r\n";
    
    $litime = ($limittime==0 ? 1000 : $limittime);
    $func = '';
    
    if($gourl=='-1')
    {
        if($limittime==0) $litime = 1000;
        $gourl = "javascript:history.go(-1);";
    }
    
    if($gourl=='' || $onlymsg==1)
    {
        $msg = "<script>alert(\"".str_replace("\"","“",$msg)."\");</script>";
    }
    else
    {
        if(preg_match('/close::/',$gourl))
        {
            $tgobj = trim(preg_replace('/close::/', '', $gourl));
            $gourl = 'javascript:;';
            $func .= "window.parent.document.getElementById('{$tgobj}').style.display='none';\r\n";
        }
           
        $func .= "      var pgo=0;
      function JumpUrl(){
        if(pgo==0){ location='$gourl'; pgo=1; }
      }\r\n";
        $rmsg = $func;
        $rmsg .= "document.write(\"<div class='ts_div'>  <div class='ts_border'>";
        $rmsg .= "<div class='ts_b2'><h2>石家庄云腾网络科技 提示信息：</h2>\");\r\n";
        $rmsg .= "document.write(\"<p>\");\r\n";
        $rmsg .= "document.write(\"".str_replace("\"","“",$msg)."\");\r\n";
        $rmsg .= "document.write(\"";
           
        if($onlymsg==0)
        {
            if( $gourl != 'javascript:;' && $gourl != '')
            {
                $rmsg .= "</p></div>  </div></div><div class='ts_tz'>如果你的浏览器没反应，<a href='{$gourl}'>请点击这里...</a>";
                $rmsg .= "</div>\");\r\n";
                $rmsg .= "setTimeout('JumpUrl()',$litime);";
            }
             else
            {
                $rmsg .= "</div>\");\r\n";
            }           
        }
        else
        {
            $rmsg .= "</div>\");\r\n";
        }
        $msg  = $htmlhead.$rmsg.$htmlfoot;
    }
    echo $msg;
}

function GetCkVdValue()
{
	@session_id($_COOKIE['PHPSESSID']);
    @session_start();
    return isset($_SESSION['securimage_code_value']) ? $_SESSION['securimage_code_value'] : '';
}

function ResetVdValue()
{
    @session_start();
    $_SESSION['securimage_code_value'] = '';
}

if( file_exists(DEDEINC.'/extend.func.php') )
{
    require_once(DEDEINC.'/extend.func.php');
}

function pasterTempletDiy($path) {
	require_once(DEDEINC."/arc.partview.class.php");
	global $cfg_basedir,$cfg_templets_dir;
	$tmpfile = $cfg_basedir.$cfg_templets_dir."/".$path;
	$dtp = new PartView();
	$dtp->SetTemplet($tmpfile);
$dtp->Display(); }