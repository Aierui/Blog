<?php   if(!defined('DEDEINC')) exit('dedecms');

@set_time_limit(1000);
class FTP {
    var $hostname    = '';
    var $username    = '';
    var $password    = '';
    var $port        = 21;
    var $passive    = TRUE;
    var $debug        = FALSE;
    var $conn_id    = FALSE;

    function FTP($config = array())
    {
        if (count($config) > 0)
        {
            $this->initialize($config);
        }
    }

    function initialize($config = array())
    {
        foreach ($config as $key => $val)
        {
            if (isset($this->$key))
            {
                $this->$key = $val;
            }
        }

        $this->hostname = preg_replace('|.+?://|', '', $this->hostname);
    }

    function connect($config = array())
    {
        if (count($config) > 0)
        {
            $this->initialize($config);
        }

        if (FALSE === ($this->conn_id = @ftp_connect($this->hostname, $this->port)))
        {
            if ($this->debug == TRUE)
            {
                $this->_error('无法链接');
            }
            return FALSE;
        }

        if ( ! $this->_login())
        {
            if ($this->debug == TRUE)
            {
                $this->_error('无法登录');
            }
            return FALSE;
        }

        if ($this->passive == TRUE)
        {
            ftp_pasv($this->conn_id, TRUE);
        }

        return TRUE;
    }

    function _login()
    {
        return @ftp_login($this->conn_id, $this->username, $this->password);
    }

    function _is_conn()
    {
        if ( ! is_resource($this->conn_id))
        {
            if ($this->debug == TRUE)
            {
                $this->_error('无法链接');
            }
            return FALSE;
        }
        return TRUE;
    }

    function changedir($path = '', $supress_debug = FALSE)
    {
        if ($path == '' OR ! $this->_is_conn())
        {
            return FALSE;
        }

        $result = @ftp_chdir($this->conn_id, $path);

        if ($result === FALSE)
        {
            if ($this->debug == TRUE AND $supress_debug == FALSE)
            {
                $this->_error('无法更改目录');
            }
            return FALSE;
        }

        return TRUE;
    }

    function mkdir($path = '', $permissions = NULL)
    {
        if ($path == '' OR ! $this->_is_conn())
        {
            return FALSE;
        }

        $result = @ftp_mkdir($this->conn_id, $path);

        if ($result === FALSE)
        {
            if ($this->debug == TRUE)
            {
                $this->_error('无法创建文件夹');
            }
            return FALSE;
        }

        if ( ! is_null($permissions))
        {
            $this->chmod($path, (int)$permissions);
        }

        return TRUE;
    }

    function rmkdir($path = '', $pathsymbol = '/')
    {
        $pathArray = explode($pathsymbol,$path);
        $pathstr = $pathsymbol;
        foreach($pathArray as $val)
        {
            if(!empty($val))
            {
                $pathstr = $pathstr.$val.$pathsymbol;
                if (! $this->_is_conn())
                {
                    return FALSE;
                }
                $result = @ftp_chdir($this->conn_id, $pathstr);
                if($result === FALSE)
                {
                    if(!$this->mkdir($pathstr))
                    {
                        return FALSE;
                    }
                }
            }
        }
        return TRUE;
    }

    function upload($locpath, $rempath, $mode = 'auto', $permissions = NULL)
    {
        if (!$this->_is_conn())
        {
            return FALSE;
        }

        if (!file_exists($locpath))
        {
            $this->_error('不存在源文件');
            return FALSE;
        }

        if ($mode == 'auto')
        {
            $ext = $this->_getext($locpath);
            $mode = $this->_settype($ext);
        }

        $mode = ($mode == 'ascii') ? FTP_ASCII : FTP_BINARY;

        $result = @ftp_put($this->conn_id, $rempath, $locpath, $mode);

        if ($result === FALSE)
        {
            if ($this->debug == TRUE)
            {
                $this->_error('无法上传');
            }
            return FALSE;
        }

        if ( ! is_null($permissions))
        {
            $this->chmod($rempath, (int)$permissions);
        }

        return TRUE;
    }

    function rename($old_file, $new_file, $move = FALSE)
    {
        if ( ! $this->_is_conn())
        {
            return FALSE;
        }

        $result = @ftp_rename($this->conn_id, $old_file, $new_file);

        if ($result === FALSE)
        {
            if ($this->debug == TRUE)
            {
                $msg = ($move == FALSE) ? '无法重命名' : '无法移动';

                $this->_error($msg);
            }
            return FALSE;
        }

        return TRUE;
    }

    function move($old_file, $new_file)
    {
        return $this->rename($old_file, $new_file, TRUE);
    }

    function delete_file($filepath)
    {
        if ( ! $this->_is_conn())
        {
            return FALSE;
        }

        $result = @ftp_delete($this->conn_id, $filepath);

        if ($result === FALSE)
        {
            if ($this->debug == TRUE)
            {
                $this->_error('无法删除');
            }
            return FALSE;
        }

        return TRUE;
    }

    function delete_dir($filepath)
    {
        if ( ! $this->_is_conn())
        {
            return FALSE;
        }

        $filepath = preg_replace("/(.+?)\/*$/", "\\1/",  $filepath);

        $list = $this->list_files($filepath);

        if ($list !== FALSE AND count($list) > 0)
        {
            foreach ($list as $item)
            {
                if ( ! @ftp_delete($this->conn_id, $item))
                {
                    $this->delete_dir($item);
                }
            }
        }

        $result = @ftp_rmdir($this->conn_id, $filepath);

        if ($result === FALSE)
        {
            if ($this->debug == TRUE)
            {
                $this->_error('无法删除');
            }
            return FALSE;
        }

        return TRUE;
    }

    function chmod($path, $perm)
    {
        if ( ! $this->_is_conn())
        {
            return FALSE;
        }

        if ( ! function_exists('ftp_chmod'))
        {
            if ($this->debug == TRUE)
            {
                $this->_error('无法更改权限');
            }
            return FALSE;
        }

        $result = @ftp_chmod($this->conn_id, $perm, $path);

        if ($result === FALSE)
        {
            if ($this->debug == TRUE)
            {
                $this->_error('无法更改权限');
            }
            return FALSE;
        }

        return TRUE;
    }

    function list_files($path = '.')
    {
        if ( ! $this->_is_conn())
        {
            return FALSE;
        }

        return ftp_nlist($this->conn_id, $path);
    }

    function list_rawfiles($path = '.', $type='dir')
    {
        if ( ! $this->_is_conn())
        {
            return FALSE;
        }
        
        $ftp_rawlist = ftp_rawlist($this->conn_id, $path, TRUE);
      foreach ($ftp_rawlist as $v) {
        $info = array();
        $vinfo = preg_split("/[\s]+/", $v, 9);
        if ($vinfo[0] !== "total") {
          $info['chmod'] = $vinfo[0];
          $info['num'] = $vinfo[1];
          $info['owner'] = $vinfo[2];
          $info['group'] = $vinfo[3];
          $info['size'] = $vinfo[4];
          $info['month'] = $vinfo[5];
          $info['day'] = $vinfo[6];
          $info['time'] = $vinfo[7];
          $info['name'] = $vinfo[8];
          $rawlist[$info['name']] = $info;
        }
      }
      
      $dir = array();
      $file = array();
      foreach ($rawlist as $k => $v) {
        if ($v['chmod']{0} == "d") {
          $dir[$k] = $v;
        } elseif ($v['chmod']{0} == "-") {
          $file[$k] = $v;
        }
      }

      return ($type == 'dir')? $dir : $file;
    }

    function mirror($locpath, $rempath)
    {
        if ( ! $this->_is_conn())
        {
            return FALSE;
        }

        if ($fp = @opendir($locpath))
        {

            if ( ! $this->changedir($rempath, TRUE))
            {

                if ( ! $this->rmkdir($rempath) OR ! $this->changedir($rempath))
                {
                    return FALSE;
                }
            }

            while (FALSE !== ($file = readdir($fp)))
            {
                if (@is_dir($locpath.$file) && substr($file, 0, 1) != '.')
                {
                    $this->mirror($locpath.$file."/", $rempath.$file."/");
                }
                elseif (substr($file, 0, 1) != ".")
                {

                    $ext = $this->_getext($file);
                    $mode = $this->_settype($ext);

                    $this->upload($locpath.$file, $rempath.$file, $mode);
                }
            }
            return TRUE;
        }

        return FALSE;
    }

    function _getext($filename)
    {
        if (FALSE === strpos($filename, '.'))
        {
            return 'txt';
        }

        $x = explode('.', $filename);
        return end($x);
    }

    function _settype($ext)
    {
        $text_types = array(
                            'txt',
                            'text',
                            'php',
                            'phps',
                            'php4',
                            'js',
                            'css',
                            'htm',
                            'html',
                            'phtml',
                            'shtml',
                            'log',
                            'xml'
                            );


        return (in_array($ext, $text_types)) ? 'ascii' : 'binary';
    }

    function close()
    {
        if ( ! $this->_is_conn())
        {
            return FALSE;
        }

        @ftp_close($this->conn_id);
    }

    function _error($msg)
    {
        $errorTrackFile = dirname(__FILE__).'/../data/ftp_error_trace.inc';
        $emsg = '';
        $emsg .= "<div><h3>cloudcms Error Warning!</h3>\r\n";
        $emsg .= "<div><a href='http://www.yunteng.cc' target='_blank' style='color:red'>Technical Support: http://www.yunteng.cc</a></div>";
        $emsg .= "<div style='line-helght:160%;font-size:14px;color:green'>\r\n";
        $emsg .= "<div style='color:blue'><br />Error page: <font color='red'>".$this->GetCurUrl()."</font></div>\r\n";
        $emsg .= "<div>Error infos: {$msg}</div>\r\n";
        $emsg .= "<br /></div></div>\r\n";
        
        echo $emsg;
        
        $savemsg = 'Page: '.$this->GetCurUrl()."\r\nError: ".$msg;

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