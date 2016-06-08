<?php

require_once(dirname(__FILE__)."/config.php");
CheckPurview('sys_MakeHtml');
require_once(DEDEINC."/arc.partview.class.php");

if($cfg_remote_site=='N') exit('Error:$cfg_remote_site is OFF,Check it!');

if(file_exists(DEDEDATA.'/config.file.inc.php'))
{
    require_once(DEDEDATA.'/config.file.inc.php');
}

if(empty($dopost)) $dopost = '';

$step = !isset($step)? 1 : $step;
$sta = !isset($sta)? 0 : $sta;
$totalnum = !isset($totalnum)? 0 : $totalnum;
$maketype = empty($maketype)? '' : $maketype;

function GetState($val)
{
    $color = ($val == 0)? 'red' : 'green';
    $signer = ($val == 0)? '未同步' : '已同步';
    return '<font color="'.$color.'">'.$signer.'</font>';
}

function addDir($filedir='', $description='', $dfserv=0, $state=0, $issystem=0)
{
    return array(
        'filedir' => $filedir,
        'description' => $description,
        'dfserv' => $dfserv,
        'state' => $state,
        'issystem' => $issystem
    );
}

function makeConfig($dirarray=array())
{
    $config_str = '';
    foreach($dirarray as $k => $val)
    {
        $config_str .= '$remotefile['.$k.'] = array('."\n";
        $config_str .= '  \'filedir\'=>\''.$val['filedir']."',\n";
        $config_str .= '  \'description\'=>\''.$val['description']."',\n";
        $config_str .= '  \'dfserv\'=>'.$val['dfserv'].",\n";
        $config_str .= '  \'state\'=>'.$val['state'].",\n";
        $config_str .= '  \'issystem\'=>'.$val['issystem']."\n";
        $config_str .= ");\n";
    }
    return ($config_str == '')? '' : $config_str;
}

function getDirs($directory,$exempt = array('.','..','.ds_store','.svn'),&$files = array()) 
{ 

    if(is_dir($directory) && !opendir($directory)) mkdir($directory,0777,TRUE);
    $handle = opendir($directory); 
  
    while(false !== ($resource = readdir($handle)))
    { 
        if(!in_array(strtolower($resource),$exempt)) 
        {

            if(is_dir($directory.$resource.'/'))
            { 
                array_merge($files, 
                getDirs($directory.$resource.'/',$exempt,$files)); 
            } else {
              //if(!is_file($directory.'/'.$resource))
              //{
              $files[] = $directory.'/'.$resource; 
              //}
            }
        } 
    }
    closedir($handle); 
    return $files; 
} 

function updateConfig($dirarray=array())
{
    $configfile = DEDEDATA.'/config.file.inc.php';
    $old_config = @file_get_contents($configfile);
    $config_str = makeConfig($dirarray);
    $new_config = preg_replace("/#<s_config>(.*)#<e_config>/s", "#<s_config>\n\n{$config_str}#<e_config>", $old_config);
    file_put_contents($configfile, $new_config);
}

if($dopost == '')
{

}
else if($dopost == 'updateremote')
{
    $dirbox = array();
    $query = "SELECT id,typedir,ispart FROM #@__arctype WHERE ispart <> '3'";
    $dsql->SetQuery($query);
    $dsql->Execute('al');
    $dirarray = array();

    $i = 0;
    while ($row = $dsql->GetArray("al"))
    {
        $darray = explode('/', preg_replace('/{cmspath}/', '', $row['typedir']));
        if(!in_array($darray[1], $dirbox))
        {
            $dirarray[$i] = addDir('/'.$darray[1], '文档HTML默认保存路', 0, 0, 1);
            $dirbox[] = $darray[1];
            $i++;
        }
    }

    $dirarray[$i++] = addDir($cfg_medias_dir, '图片/上传文件默认路径', 0, 0, 1);

    $dirarray[$i++] = addDir('/special', '专题目录', 0, 0, 1);

    $dirarray[$i++] = addDir('/data/js', '生成js目录', 0, 0, 1);

    foreach ($remotefile as $key => $value)
    {

        if($value['issystem'] == 0)
        {
            $dirarray[$i++] = addDir($value['filedir'], $value['description'],
                                     $value['dfserv'], $value['state'], $value['issystem']);
        }
    }

    updateConfig($dirarray);
    
    ShowMsg("成功更新同步目录,请重新对目录进行同步操作!","makeremote_all.php");
    exit;
}

else if($dopost == 'make')
{
    if($step == 1)
    {
        if($maketype == 'makeall')
        {

            foreach($remotefile as $key => $val)
            {
                $Iterm[] = $val['filedir'];
            }
        } else {

            $Iterm = !isset($Iterm)? array(): $Iterm;
        }

        $serviterm = !isset($serviterm)? array(): $serviterm;
        $cacheMakeFile = DEDEDATA.'/cache/filelist.inc.php';
        $dirlist = $alllist = $updir = array();
        $dirindex = 0;
        if(count($Iterm) > 0)
        {

            foreach($Iterm as $key => $val)
            {
                $config = $serviterm[$key];
                if(is_array($dirlist = getDirs(DEDEROOT.$val)))
                {
                    foreach($dirlist as $k => $v)
                    {
                        $alllist[] = $v.'|'.$config;
                        if(!in_array($val, array_values($updir))) $updir[] = $val;
                    }
                }
            }

            $cachestr = "<?php \n  global \$dirlist,\$upremote;\n  \$dirlist=array();\n";
            foreach($alllist as $key => $val)
            {
                list($filename,$fileconfig) = explode('|', $val); 
                if(is_dir($filename))
                {
                    $deepDir = getDirs($filename);
                    $dd = 0;

                    foreach($deepDir as $k => $v)
                    {
                        if(is_dir($v)) $dd++;
                    }
                    if($dd > 3)
                    {

                        foreach($deepDir as $k => $v)
                        {
                            $v .= '|'.$fileconfig;
                            $cachestr .= "  \$dirlist['$dirindex']='$v';\n";
                            $dirindex++;
                        }
                    }else{
                        $cachestr .= "  \$dirlist['$dirindex']='$val';\n";
                        $dirindex++;
                    }
                }
            }
            
            foreach($updir as $key => $val)
            {
                $cachestr .= "  \$upremote['$key']='$val';\n";
            }
            $cachestr .= "?>";
            file_put_contents($cacheMakeFile, $cachestr);
            $tnum = count($alllist);
            ShowMsg("成功获取远程列表,下面进行文件远程发布!","makeremote_all.php?dopost=make&step=2&sta=1&totalnum=$tnum");
            exit;
        } else {
            echo '您没有选择,请先选择再点击更新!';
        }
        exit;    
    } elseif ($step == 2)
    {
        if(file_exists(DEDEDATA.'/cache/filelist.inc.php'))
        {
            require_once(DEDEDATA.'/cache/filelist.inc.php');
        }
        if(is_array($dirlist))
        {
            if($sta > 0 && $sta < $totalnum)
            {
                list($dirname, $ftpconfig) = explode('|', $dirlist[$sta-1]); 
                list($servurl, $servuser, $servpwd) = explode(',', $ftpconfig);
                $config=array( 'hostname' => $servurl, 'username' => $servuser,
                               'password' => $servpwd,'debug' => 'TRUE');
                if($ftp->connect($config))
                {

                    if(is_dir($dirname))
                    {

                        $remotedir = str_replace(DEDEROOT, '', $dirname).'/';
                        $localdir = '..'.$remotedir.'/';
                        $ftp->rmkdir($remotedir);
                        if( $ftp->mirror($localdir, $remotedir))
                        {
                            $sta++;
                            ShowMsg("成功同步文件夹$remotedir,进入下一个任务","makeremote_all.php?dopost=make&step=2&sta={$sta}&totalnum=$totalnum");
                            exit;
                        }
                    } else {
                        $remotefile = str_replace(DEDEROOT, '', $dirname);
                        $localfile = '..'.$remotefile;

                        $remotedir = preg_replace('/[^\/]*\.(\w){0,}/', '', $remotefile);

                        $remotebox = array();
                        $ftp->rmkdir($remotedir);
                        foreach($dirlist as $key => $val)
                        {
                            list($filename,$fileconfig) = explode('|', $val); 
                            if(preg_replace('/[^\/]*\.(\w){0,}/', '', str_replace(DEDEROOT, '', $filename)) == $remotedir)
                            {
                                $remotebox[] = $key;
                            }
                        }
                        //print_r($remotebox);
                        //if(count($remotebox) > 1 && count($remotebox) < 20)
                        if(count($remotebox) > 1)
                        {
                            //如果大于1,则说明有多条记录在同一文件夹内
                            $localdir = '..'.$remotedir;
                            if( $ftp->mirror($localdir, $remotedir))
                            {
                                $sta = end($remotebox) + 1;
                                ShowMsg("成功同步文件夹$remotedir,进入下一个任务","makeremote_all.php?dopost=make&step=2&sta={$sta}&totalnum=$totalnum");
                                exit;
                            }
                        } else {
                            if( $ftp->upload($localfile, $remotefile) )
                            {
                                $sta++;
                                ShowMsg("成功同步文件$remotefile,进入下一个任务","makeremote_all.php?dopost=make&step=2&sta={$sta}&totalnum=$totalnum");
                                exit;
                            }
                        }
                    }
                }
            } else {

                foreach($remotefile as $key => $val)
                {
                    if(in_array($val['filedir'],array_values($upremote)))
                    {
                        $remotefile[$key]['state'] = 1;
                    }
                }
                updateConfig($remotefile);
                @unlink(DEDEDATA.'/cache/filelist.inc.php');
                echo '全部同步完毕!';exit;
            }
        } else {
            exit('Error:None remote cache file exist!');
        }
        exit;
    }
}
include DedeInclude('templets/makeremote_all.htm');