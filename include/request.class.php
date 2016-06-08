<?php

define('DEDEREQUEST', TRUE);

function Request($key, $df='')
{
    $GLOBALS['request'] = isset($GLOBALS['request'])? $GLOBALS['request'] : new Request;
    if (!$GLOBALS['request']->isinit)
    {
        $GLOBALS['request']->Init();
    }
    return $GLOBALS['request']->Item($key, $df);
}
class Request
{

    var $isinit = false;
	
    var $cookies = array();

    var $forms = array();

    var $gets = array();

    var $posts = array();

    var $request_type = 'GET';

    var $files = array();

    var $filter_filename = '/\.(php|pl|sh|js)$/i';

    function Init()
    {
        global $_POST,$_GET;

        $formarr = array('p' => $_POST, 'g' => $_GET);
        foreach($formarr as $_k => $_r)
        {
            if( count($_r) > 0 )
            {
                foreach($_r as $k=>$v)
                {
                    if( preg_match('/^cfg_(.*?)/i', $k) )
                    {
                        continue;
                    }
                    $this->forms[$k] = $v;
                    if( $_k=='p' )
                    {
                        $this->posts[$k] = $v;
                    } else {
                        $this->gets[$k] = $v;
                    }
                }
            }
        }
        unset($_POST);
        unset($_GET);
        unset($_REQUEST);

        if( count($_COOKIE) > 0 )
        {
            foreach($_COOKIE as $k=>$v)
            {
                if( preg_match('/^config/i', $k) )
                {
                    continue;
                }
                $this->cookies[$k] = $v;
            }
        }

        if( isset($_FILES) && count($_FILES) > 0 )
        {
            $this->FilterFiles($_FILES);
        }
        $this->isinit = TRUE;

    }

    function MyEval( $phpcode )
    {
        return eval( $phpcode );
    }

    function Item( $formname, $defaultvalue = '' )
    {
        return isset($this->forms[$formname]) ? $this->forms[$formname] :  $defaultvalue;
    }

    function Upfile( $formname, $defaultvalue = '' )
    {
        return isset($this->files[$formname]['tmp_name']) ? $this->files[$formname]['tmp_name'] :  $defaultvalue;
    }

    function FilterFiles( &$files )
    {
        foreach($files as $k=>$v)
        {
            $this->files[$k] = $v;
        }
        unset($_FILES);
    }

    function MoveUploadFile( $formname, $filename, $filetype = '' )
    {
        if( $this->IsUploadFile( $formname ) )
        {
            if( preg_match($this->filter_filename, $filename) )
            {
                return FALSE;
            }
            else
            {
                return move_uploaded_file($this->files[$formname]['tmp_name'], $filename);
            }
        }
    }

    function GetShortname( $formname )
    {
        $filetype = strtolower(isset($this->files[$formname]['type']) ? $this->files[$formname]['type'] : '');
        $shortname = '';
        switch($filetype)
        {
            case 'image/jpeg':
                $shortname = 'jpg';
                break;
            case 'image/pjpeg':
                $shortname = 'jpg';
                break;
            case 'image/gif':
                $shortname = 'gif';
                break;
            case 'image/png':
                $shortname = 'png';
                break;
            case 'image/xpng':
                $shortname = 'png';
                break;
            case 'image/wbmp':
                $shortname = 'bmp';
                break;
            default:
                $filename = isset($this->files[$formname]['name']) ? $this->files[$formname]['name'] : '';
                if( preg_match("/\./", $filename) )
                {
                    $fs = explode('.', $filename);
                    $shortname = strtolower($fs[ count($fs)-1 ]);
                }
                break;
        }
        return $shortname;
    }

    function GetFileInfo( $formname, $item = '' )
    {
        if( !isset( $this->files[$formname]['tmp_name'] ) )
        {
            return FALSE;
        }
        else
        {
            if($item=='')
            {
                return $this->files[$formname];
            }
            else
            {
                return (isset($this->files[$formname][$item]) ? $this->files[$formname][$item] : '');
            }
        }
    }

    function IsUploadFile( $formname )
    {
        if( !isset( $this->files[$formname]['tmp_name'] ) )
        {
            return FALSE;
        }
        else
        {
            return is_uploaded_file( $this->files[$formname]['tmp_name'] );
        }
    }

     function CheckSubfix($formname, $subfix = 'csv')
    {
        if( $this->GetShortname( $formname ) != $subfix)
        {
            return FALSE;
        }
        return TRUE;
    }
}