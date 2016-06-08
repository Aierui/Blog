<?php  if(!defined('DEDEINC')) exit('dedecms');

$g_ftpLink = false;

if ( ! function_exists('FtpMkdir'))
{
    function FtpMkdir($truepath,$mmode,$isMkdir=true)
    {
        global $cfg_basedir,$cfg_ftp_root,$g_ftpLink;
        OpenFtp();
        $ftproot = preg_replace('/'.$cfg_ftp_root.'$/', '', $cfg_basedir);
        $mdir = preg_replace('/^'.$ftproot.'/', '', $truepath);
        if($isMkdir)
        {
            ftp_mkdir($g_ftpLink, $mdir);
        }
        return ftp_site($g_ftpLink, "chmod $mmode $mdir");
    }
}

if ( ! function_exists('FtpChmod'))
{
    function FtpChmod($truepath, $mmode)
    {
        return FtpMkdir($truepath, $mmode, false);
    }
}

if ( ! function_exists('OpenFtp'))
{
    function OpenFtp()
    {
        global $cfg_basedir,$cfg_ftp_host,$cfg_ftp_port, $cfg_ftp_user,$cfg_ftp_pwd,$cfg_ftp_root,$g_ftpLink;
        if(!$g_ftpLink)
        {
            if($cfg_ftp_host=='')
            {
                echo "由于你的站点的PHP配置存在限制，程序尝试用FTP进行目录操作，你必须在后台指定FTP相关的变量！";
                exit();
            }
            $g_ftpLink = ftp_connect($cfg_ftp_host,$cfg_ftp_port);
            if(!$g_ftpLink)
            {
                echo "连接FTP失败！";
                exit();
            }
            if(!ftp_login($g_ftpLink,$cfg_ftp_user,$cfg_ftp_pwd))
            {
                echo "登陆FTP失败！";
                exit();
            }
        }
    }
}

if ( ! function_exists('CloseFtp'))
{
    function CloseFtp()
    {
        global $g_ftpLink;
        if($g_ftpLink)
        {
            @ftp_quit($g_ftpLink);
        }
    }
}

if ( ! function_exists('MkdirAll'))
{
    function MkdirAll($truepath,$mmode)
    {
        global $cfg_ftp_mkdir,$isSafeMode,$cfg_dir_purview;
        if( $isSafeMode || $cfg_ftp_mkdir=='Y' )
        {
            return FtpMkdir($truepath, $mmode);
        }
        else
        {
            if(!file_exists($truepath))
            {
                mkdir($truepath, $cfg_dir_purview);
                chmod($truepath, $cfg_dir_purview);
                return true;
            }
            else
            {
                return true;
            }
        }
    }
}

if ( ! function_exists('ChmodAll'))
{
    function ChmodAll($truepath,$mmode)
    {
        global $cfg_ftp_mkdir,$isSafeMode;
        if( $isSafeMode || $cfg_ftp_mkdir=='Y' )
        {
            return FtpChmod($truepath, $mmode);
        }
        else
        {
            return chmod($truepath, '0'.$mmode);
        }
    }
}

if ( ! function_exists('CreateDir'))
{
    function CreateDir($spath)
    {
        if(!function_exists('SpCreateDir'))
        {
            require_once(DEDEINC.'/inc/inc_fun_funAdmin.php');
        }
        return SpCreateDir($spath);
    }
}

if ( ! function_exists('PutFile'))
{
    function PutFile($file, $content, $flag = 0)
    {
        $pathinfo = pathinfo ( $file );
        if (! empty ( $pathinfo ['dirname'] ))
        {
            if (file_exists ( $pathinfo ['dirname'] ) === FALSE)
            {
                if (@mkdir ( $pathinfo ['dirname'], 0777, TRUE ) === FALSE)
                {
                    return FALSE;
                }
            }
        }
        if ($flag === FILE_APPEND)
        {
            return @file_put_contents ( $file, $content, FILE_APPEND );
        }
        else
        {
            return @file_put_contents ( $file, $content, LOCK_EX );
        }
    }
}

if ( ! function_exists('RmRecurse'))
{
    function RmRecurse($file)
    {
        if (is_dir($file) && !is_link($file))
        {
            foreach(glob($file . '/*') as $sf)
            {
                if (!RmRecurse($sf))
                {
                    return false;
                }
            }
            return @rmdir($file);
        } else {
            return @unlink($file);
        }
    }
}