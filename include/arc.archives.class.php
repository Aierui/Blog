<?php
if(!defined('DEDEINC')) exit("Request Error!");

require_once(DEDEINC."/typelink.class.php");
require_once(DEDEINC."/channelunit.class.php");
require_once(DEDEINC."/downmix.inc.php");
require_once(DEDEINC.'/ftp.class.php');

@set_time_limit(0);

class Archives
{
    var $TypeLink;
    var $ChannelUnit;
    var $dsql;
    var $Fields;
    var $dtp;
    var $ArcID;
    var $SplitPageField;
    var $SplitFields;
    var $NowPage;
    var $TotalPage;
    var $NameFirst;
    var $ShortName;
    var $FixedValues;
    var $TempSource;
    var $IsError;
    var $SplitTitles;
    var $PreNext;
    var $addTableRow;
    var $ftp;
    var $remoteDir;

    function __construct($aid)
    {
        global $dsql,$ftp;
        $this->IsError = FALSE;
        $this->ArcID = $aid;
        $this->PreNext = array();

        $this->dsql = $dsql;
        $query = "SELECT channel,typeid FROM `#@__arctiny` WHERE id='$aid' ";
        $arr = $this->dsql->GetOne($query);
        if(!is_array($arr))
        {
            $this->IsError = TRUE;
        }
        else
        {
            if($arr['channel']==0) $arr['channel']=1;
            $this->ChannelUnit = new ChannelUnit($arr['channel'], $aid);
            $this->TypeLink = new TypeLink($arr['typeid']);
            if($this->ChannelUnit->ChannelInfos['issystem']!=-1)
            {

                $query = "SELECT arc.*,tp.reid,tp.typedir,ch.addtable
                FROM `#@__archives` arc
                         LEFT JOIN #@__arctype tp on tp.id=arc.typeid
                          LEFT JOIN #@__channeltype as ch on arc.channel = ch.id
                          WHERE arc.id='$aid' ";
                $this->Fields = $this->dsql->GetOne($query);
            }
            else
            {
                $this->Fields['title'] = '';
                $this->Fields['money'] = $this->Fields['arcrank'] = 0;
                $this->Fields['senddate'] = $this->Fields['pubdate'] = $this->Fields['mid'] = $this->Fields['adminid'] = 0;
                $this->Fields['ismake'] = 1;
                $this->Fields['filename'] = '';
            }

            if($this->TypeLink->TypeInfos['corank'] > 0 && $this->Fields['arcrank']==0)
            {
                $this->Fields['arcrank'] = $this->TypeLink->TypeInfos['corank'];
            }

            $this->Fields['tags'] = GetTags($aid);
            $this->dtp = new DedeTagParse();
            $this->dtp->SetRefObj($this);
            $this->SplitPageField = $this->ChannelUnit->SplitPageField;
            $this->SplitFields = '';
            $this->TotalPage = 1;
            $this->NameFirst = '';
            $this->ShortName = 'html';
            $this->FixedValues = '';
            $this->TempSource = '';
            $this->ftp = &$ftp;
            $this->remoteDir = '';
            if(empty($GLOBALS['pageno']))
            {
                $this->NowPage = 1;
            }
            else
            {
                $this->NowPage = $GLOBALS['pageno'];
            }

            $this->Fields['aid'] = $aid;
            $this->Fields['id'] = $aid;
            $this->Fields['position'] = $this->TypeLink->GetPositionLink(TRUE);
            $this->Fields['typeid'] = $arr['typeid'];

            foreach($GLOBALS['PubFields'] as $k=>$v)
            {
                $this->Fields[$k] = $v;
            }

            if($this->ChannelUnit->ChannelInfos['addtable']!='')
            {
                $query = "SELECT * FROM `{$this->ChannelUnit->ChannelInfos['addtable']}` WHERE `aid` = '$aid'";
                $this->addTableRow = $this->dsql->GetOne($query);
            }

            if($this->ChannelUnit->ChannelInfos['addtable']!='' && $this->ChannelUnit->ChannelInfos['issystem']!=-1)
            {
                if(is_array($this->addTableRow))
                {
                    $this->Fields['redirecturl'] = $this->addTableRow['redirecturl'];
                    $this->Fields['templet'] = $this->addTableRow['templet'];
                    $this->Fields['userip'] = $this->addTableRow['userip'];
                }
                $this->Fields['templet'] = (empty($this->Fields['templet']) ? '' : trim($this->Fields['templet']));
                $this->Fields['redirecturl'] = (empty($this->Fields['redirecturl']) ? '' : trim($this->Fields['redirecturl']));
                $this->Fields['userip'] = (empty($this->Fields['userip']) ? '' : trim($this->Fields['userip']));
            }
            else
            {
                $this->Fields['templet'] = $this->Fields['redirecturl'] = '';
            }
        }
    }

    function Archives($aid)
    {
        $this->__construct($aid);
    }

    function ParAddTable()
    {
        if($this->ChannelUnit->ChannelInfos['addtable']!='')
        {
            $row = $this->addTableRow;
            if($this->ChannelUnit->ChannelInfos['issystem']==-1)
            {
                $this->Fields['title'] = $row['title'];
                $this->Fields['senddate'] = $this->Fields['pubdate'] = $row['senddate'];
                $this->Fields['mid'] = $this->Fields['adminid'] = $row['mid'];
                $this->Fields['ismake'] = 1;
                $this->Fields['arcrank'] = 0;
                $this->Fields['money']=0;
                $this->Fields['filename'] = '';
            }

            if(is_array($row))
            {
                foreach($row as $k=>$v) $row[strtolower($k)] = $v;
            }
            if(is_array($this->ChannelUnit->ChannelFields) && !empty($this->ChannelUnit->ChannelFields))
            {
                foreach($this->ChannelUnit->ChannelFields as $k=>$arr)
                {
                    if(isset($row[$k]))
                    {
                        if(!empty($arr['rename']))
                        {
                            $nk = $arr['rename'];
                        }
                        else
                        {
                            $nk = $k;
                        }
                        $cobj = $this->GetCurTag($k);
                        if(is_object($cobj))
                        {
                            foreach($this->dtp->CTags as $ctag)
                            {
                                if($ctag->GetTagName()=='field' && $ctag->GetAtt('name')==$k)
                                {

                                    if($ctag->GetAtt('noteid') != '') {
                                        $this->Fields[$k.'_'.$ctag->GetAtt('noteid')] = $this->ChannelUnit->MakeField($k, $row[$k], $ctag);
                                    }

                                    else if($ctag->GetAtt('type') != '') {
                                        $this->Fields[$k.'_'.$ctag->GetAtt('type')] = $this->ChannelUnit->MakeField($k, $row[$k], $ctag);
                                    }

                                    else {
                                        $this->Fields[$nk] = $this->ChannelUnit->MakeField($k, $row[$k], $ctag);
                                    }
                                }
                            }
                        }
                        else
                        {
                            $this->Fields[$nk] = $row[$k];
                        }
                        if($arr['type']=='htmltext' && $GLOBALS['cfg_keyword_replace']=='Y' && !empty($this->Fields['keywords']))
                        {
                            $this->Fields[$nk] = $this->ReplaceKeyword($this->Fields['keywords'],$this->Fields[$nk]);
                        }
                    }
                }
            }

            $this->Fields['typename'] = $this->TypeLink->TypeInfos['typename'];
            @SetSysEnv($this->Fields['typeid'],$this->Fields['typename'],$this->Fields['id'],$this->Fields['title'],'archives');
        }

        unset($row);

        $this->SplitTitles = Array();
        if($this->SplitPageField!='' && $GLOBALS['cfg_arcsptitle']='Y'
        && isset($this->Fields[$this->SplitPageField]))
        {
            $this->SplitFields = explode("#p#",$this->Fields[$this->SplitPageField]);
            $i = 1;
            foreach($this->SplitFields as $k=>$v)
            {
                $tmpv = cn_substr($v,50);
                $pos = strpos($tmpv,'#e#');
                if($pos>0)
                {
                    $st = trim(cn_substr($tmpv,$pos));
                    if($st==""||$st=="副标题"||$st=="分页标题")
                    {
                        $this->SplitFields[$k] = preg_replace("/^(.*)#e#/is","",$v);
                        continue;
                    }
                    else
                    {
                        $this->SplitFields[$k] = preg_replace("/^(.*)#e#/is","",$v);
                        $this->SplitTitles[$k] = $st;
                    }
                }
                else
                {
                    continue;
                }
                $i++;
            }
            $this->TotalPage = count($this->SplitFields);
            $this->Fields['totalpage'] = $this->TotalPage;
        }

        if (isset($this->Fields['litpic']))
        {
            if($this->Fields['litpic'] == '-' || $this->Fields['litpic'] == '')
            {
                $this->Fields['litpic'] = $GLOBALS['cfg_cmspath'].'/images/cloudcms_img.jpg';
            }
            if(!preg_match("#^http:\/\/#i", $this->Fields['litpic']) && $GLOBALS['cfg_multi_site'] == 'Y')
            {
                $this->Fields['litpic'] = $GLOBALS['cfg_mainsite'].$this->Fields['litpic'];
            }
            $this->Fields['picname'] = $this->Fields['litpic'];

            $this->Fields['image'] = (!preg_match('/jpg|gif|png/i', $this->Fields['picname']) ? '' : "<img src='{$this->Fields['picname']}' />");
        }

        if (isset($this->Fields['voteid']) && !empty($this->Fields['voteid']))
        {
            $this->Fields['vote'] = '';
            $voteid = $this->Fields['voteid'];
            $this->Fields['vote'] = "<script language='javascript' src='{$GLOBALS['cfg_cmspath']}/data/vote/vote_{$voteid}.js'></script>";
            if ($GLOBALS['cfg_multi_site'] == 'Y')
            {
                $this->Fields['vote'] = "<script language='javascript' src='{$GLOBALS['cfg_mainsite']}/data/vote/vote_{$voteid}.js'></script>";
            }
        }
        
        if (isset($this->Fields['goodpost']) && isset($this->Fields['badpost']))
        {
            if($this->Fields['goodpost'] + $this->Fields['badpost'] == 0)
            {
                $this->Fields['goodper'] = $this->Fields['badper'] = 0;
            }
            else
            {
                $this->Fields['goodper'] = number_format($this->Fields['goodpost']/($this->Fields['goodpost']+$this->Fields['badpost']), 3)*100;
                $this->Fields['badper'] = 100 - $this->Fields['goodper'];
            }
        }
    }

    function GetCurTag($fieldname)
    {
        if(!isset($this->dtp->CTags))
        {
            return '';
        }
        foreach($this->dtp->CTags as $ctag)
        {
            if($ctag->GetTagName()=='field' && $ctag->GetAtt('name')==$fieldname)
            {
                return $ctag;
            }
            else
            {
                continue;
            }
        }
        return '';
    }
    function MakeHtml($isremote=0)
    {
        global $cfg_remote_site,$fileFirst;
        if($this->IsError)
        {
            return '';
        }
        $this->Fields["displaytype"] = "st";
        //预编译$th
        $this->LoadTemplet();
        $this->ParAddTable();
        $this->ParseTempletsFirst();
        $this->Fields['senddate'] = empty($this->Fields['senddate'])? '' : $this->Fields['senddate'];
        $this->Fields['title'] = empty($this->Fields['title'])? '' : $this->Fields['title'];
        $this->Fields['arcrank'] = empty($this->Fields['arcrank'])? 0 : $this->Fields['arcrank'];
        $this->Fields['ismake'] = empty($this->Fields['ismake'])? 0 : $this->Fields['ismake'];
        $this->Fields['money'] = empty($this->Fields['money'])? 0 : $this->Fields['money'];
        $this->Fields['filename'] = empty($this->Fields['filename'])? '' : $this->Fields['filename'];

        $filename = GetFileNewName(
            $this->ArcID,$this->Fields['typeid'],$this->Fields['senddate'],
            $this->Fields['title'],$this->Fields['ismake'],$this->Fields['arcrank'],
            $this->TypeLink->TypeInfos['namerule'],$this->TypeLink->TypeInfos['typedir'],$this->Fields['money'],$this->Fields['filename']
        );

        $filenames  = explode(".", $filename);
        $this->ShortName = $filenames[count($filenames)-1];
        if($this->ShortName=='') $this->ShortName = 'html';
        $fileFirst = preg_replace("/\.".$this->ShortName."$/i", "", $filename);
        $this->Fields['namehand'] = basename($fileFirst);
        $filenames  = explode("/", $filename);
        $this->NameFirst = preg_replace("/\.".$this->ShortName."$/i", "", $filenames[count($filenames)-1]);
        if($this->NameFirst=='')
        {
            $this->NameFirst = $this->arcID;
        }

        $filenameFull = GetFileUrl(
            $this->ArcID,$this->Fields['typeid'],$this->Fields["senddate"],
            $this->Fields["title"],$this->Fields["ismake"],
            $this->Fields["arcrank"],$this->TypeLink->TypeInfos['namerule'],$this->TypeLink->TypeInfos['typedir'],$this->Fields["money"],$this->Fields['filename'],
            $this->TypeLink->TypeInfos['moresite'],$this->TypeLink->TypeInfos['siteurl'],$this->TypeLink->TypeInfos['sitepath']
        );
        $this->Fields['arcurl'] = $this->Fields['fullname'] = $filenameFull;

        if($this->Fields['ismake']==-1 || $this->Fields['arcrank']!=0 || $this->Fields['money']>0 
           || ($this->Fields['typeid']==0 && $this->Fields['channel'] != -1) )
        {
            return $this->GetTrueUrl($filename);
        }

        else
        {
            for($i=1;$i<=$this->TotalPage;$i++)
            {
                if($this->TotalPage > 1) {
                    $this->Fields['tmptitle'] = (empty($this->Fields['tmptitle']) ? $this->Fields['title'] : $this->Fields['tmptitle']);
                    if($i>1) $this->Fields['title'] = $this->Fields['tmptitle']."($i)";
                }
                if($i>1)
                {
                    $TRUEfilename = $this->GetTruePath().$fileFirst."_".$i.".".$this->ShortName;
                }
                else
                {
                    $TRUEfilename = $this->GetTruePath().$filename;
                }
                $this->ParseDMFields($i,1);
                $this->dtp->SaveTo($TRUEfilename);

                if($cfg_remote_site=='Y' && $isremote == 1)
                {

                    $remotefile = str_replace(DEDEROOT, '', $TRUEfilename);
                    $localfile = '..'.$remotefile;

                    $remotedir = preg_replace("#[^\/]*\.html#", '', $remotefile);
                    $this->ftp->rmkdir($remotedir);
                    $this->ftp->upload($localfile, $remotefile, 'ascii');
                }
            }
        }
        $this->dsql->ExecuteNoneQuery("Update `#@__archives` SET ismake=1 WHERE id='".$this->ArcID."'");
        return $this->GetTrueUrl($filename);
    }
    function GetTrueUrl($nurl)
    {
        return GetFileUrl
        (
                $this->Fields['id'],
                $this->Fields['typeid'],
                $this->Fields['senddate'],
                $this->Fields['title'],
                $this->Fields['ismake'],
                $this->Fields['arcrank'],
                $this->TypeLink->TypeInfos['namerule'],
                $this->TypeLink->TypeInfos['typedir'],
                $this->Fields['money'],
                $this->Fields['filename'],
                $this->TypeLink->TypeInfos['moresite'],
                $this->TypeLink->TypeInfos['siteurl'],
                $this->TypeLink->TypeInfos['sitepath']
        );
    }
    function GetTruePath()
    {
        $TRUEpath = $GLOBALS["cfg_basedir"];
        return $TRUEpath;
    }
    function GetField($fname, $ctag)
    {

        if($fname=='array')
        {
            return $this->Fields;
        }

        else if($ctag->GetAtt('noteid') != '')
        {
            if( isset($this->Fields[$fname.'_'.$ctag->GetAtt('noteid')]) )
            {
                return $this->Fields[$fname.'_'.$ctag->GetAtt('noteid')];
            }
        }

        else if($ctag->GetAtt('type') != '')
        {
            if( isset($this->Fields[$fname.'_'.$ctag->GetAtt('type')]) )
            {
                return $this->Fields[$fname.'_'.$ctag->GetAtt('type')];
            }
        }
        else if( isset($this->Fields[$fname]) )
        {
            return $this->Fields[$fname];
        }
        return '';
    }
    function GetTempletFile()
    {
        global $cfg_basedir,$cfg_templets_dir,$cfg_df_style;
        $cid = $this->ChannelUnit->ChannelInfos['nid'];
        if(!empty($this->Fields['templet']))
        {
            $filetag = MfTemplet($this->Fields['templet']);
            if( !preg_match("#\/#", $filetag) ) $filetag = $GLOBALS['cfg_df_style'].'/'.$filetag;
        }
        else
        {
            $filetag = MfTemplet($this->TypeLink->TypeInfos["temparticle"]);
        }
        $tid = $this->Fields['typeid'];
        $filetag = str_replace('{cid}', $cid,$filetag);
        $filetag = str_replace('{tid}', $tid,$filetag);
        $tmpfile = $cfg_basedir.$cfg_templets_dir.'/'.$filetag;
        if($cid=='spec')
        {
            if( !empty($this->Fields['templet']) )
            {
                $tmpfile = $cfg_basedir.$cfg_templets_dir.'/'.$filetag;
            }
            else
            {
                $tmpfile = $cfg_basedir.$cfg_templets_dir."/{$cfg_df_style}/article_spec.htm";
            }
        }
        if(!file_exists($tmpfile))
        {
            $tmpfile = $cfg_basedir.$cfg_templets_dir."/{$cfg_df_style}/".($cid=='spec' ? 'article_spec.htm' : 'article_default.htm');
        }
        if (!preg_match("#.htm$#", $tmpfile)) return FALSE;
        return $tmpfile;
    }
    function display()
    {
        global $htmltype;
        if($this->IsError)
        {
            return '';
        }
        $this->Fields["displaytype"] = "dm";
        if($this->NowPage > 1) $this->Fields["title"] = $this->Fields["title"]."({$this->NowPage})";

        $this->LoadTemplet();
        $this->ParAddTable();

        $this->ParseTempletsFirst();

        $this->Fields['flag']=empty($this->Fields['flag'])? "" : $this->Fields['flag'];
        if(preg_match("#j#", $this->Fields['flag']) && $this->Fields['redirecturl'] != '')
        {
            if($GLOBALS['cfg_jump_once']=='N')
            {
                $pageHtml = "<html>\r\n<head>\r\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$GLOBALS['cfg_soft_lang']."\">\r\n<title>".$this->Fields['title']."</title>\r\n";
                $pageHtml .= "<meta http-equiv=\"refresh\" content=\"1;URL=".$this->Fields['redirecturl']."\">\r\n</head>\r\n<body>\r\n";
                $pageHtml .= "现在正在转向：".$this->Fields['title']."，请稍候...<br/><br/>\r\n转向内容简介:".$this->Fields['description']."\r\n</body>\r\n</html>\r\n";
                echo $pageHtml;
            }
            else
            {
                header("location:{$this->Fields['redirecturl']}");
            }
            exit();
        }
        $pageCount = $this->NowPage;
        $this->ParseDMFields($pageCount,0);
        $this->dtp->display();
    }
    function LoadTemplet()
    {
        if($this->TempSource=='')
        {
            $tempfile = $this->GetTempletFile();
            if(!file_exists($tempfile) || !is_file($tempfile))
            {
                echo "文档ID：{$this->Fields['id']} - {$this->TypeLink->TypeInfos['typename']} - {$this->Fields['title']}<br />";
                echo "模板文件不存在，无法解析文档。请联系云腾工作人员！";
                exit();
            }
            $this->dtp->LoadTemplate($tempfile);
            $this->TempSource = $this->dtp->SourceString;
        }
        else
        {
            $this->dtp->LoadSource($this->TempSource);
        }
    }

    function ParseTempletsFirst()
    {
        if(empty($this->Fields['keywords']))
        {
            $this->Fields['keywords'] = '';
        }

        if(empty($this->Fields['reid']))
        {
            $this->Fields['reid'] = 0;
        }

        $GLOBALS['envs']['tags'] = $this->Fields['tags'];

        if(isset($this->TypeLink->TypeInfos['reid']))
        {
            $GLOBALS['envs']['reid'] = $this->TypeLink->TypeInfos['reid'];
        }

        $GLOBALS['envs']['keyword'] = $this->Fields['keywords'];

        $GLOBALS['envs']['typeid'] = $this->Fields['typeid'];

        $GLOBALS['envs']['topid'] = GetTopid($this->Fields['typeid']);

        $GLOBALS['envs']['aid'] = $GLOBALS['envs']['id'] = $this->Fields['id'];

        $GLOBALS['envs']['adminid'] = $GLOBALS['envs']['mid'] = isset($this->Fields['mid'])? $this->Fields['mid'] : 1;

        $GLOBALS['envs']['channelid'] = $this->TypeLink->TypeInfos['channeltype'];

        if($this->Fields['reid']>0)
        {
            $GLOBALS['envs']['typeid'] = $this->Fields['reid'];
        }

        MakeOneTag($this->dtp, $this, 'N');
    }

    function ParseDMFields($pageNo, $ismake=1)
    {
        $this->NowPage = $pageNo;
        $this->Fields['nowpage'] = $this->NowPage;
        if($this->SplitPageField!='' && isset($this->Fields[$this->SplitPageField]))
        {
            $this->Fields[$this->SplitPageField] = $this->SplitFields[$pageNo - 1];
            if($pageNo>1) $this->Fields['description'] = trim(preg_replace("/[\r\n\t]/", ' ', cn_substr(html2text($this->Fields[$this->SplitPageField]), 200)));
        }

        if(is_array($this->dtp->CTags))
        {
            foreach($this->dtp->CTags as $i=>$ctag)
            {
                if($ctag->GetName()=='field')
                {
                    $this->dtp->Assign($i, $this->GetField($ctag->GetAtt('name'), $ctag) );
                }
                else if($ctag->GetName()=='pagebreak')
                {
                    if($ismake==0)
                    {
                        $this->dtp->Assign($i,$this->GetPagebreakDM($this->TotalPage,$this->NowPage,$this->ArcID));
                    }
                    else
                    {
                        $this->dtp->Assign($i,$this->GetPagebreak($this->TotalPage,$this->NowPage,$this->ArcID));
                    }
                }
                else if($ctag->GetName()=='pagetitle')
                {
                    if($ismake==0)
                    {
                        $this->dtp->Assign($i,$this->GetPageTitlesDM($ctag->GetAtt("style"),$pageNo));
                    }
                    else
                    {
                        $this->dtp->Assign($i,$this->GetPageTitlesST($ctag->GetAtt("style"),$pageNo));
                    }
                }
                else if($ctag->GetName()=='prenext')
                {
                    $this->dtp->Assign($i,$this->GetPreNext($ctag->GetAtt('get')));
                }
                else if($ctag->GetName()=='fieldlist')
                {
                    $innertext = trim($ctag->GetInnerText());
                    if($innertext=='') $innertext = GetSysTemplets('tag_fieldlist.htm');
                    $dtp2 = new DedeTagParse();
                    $dtp2->SetNameSpace('field','[',']');
                    $dtp2->LoadSource($innertext);
                    $oldSource = $dtp2->SourceString;
                    $oldCtags = $dtp2->CTags;
                    $res = '';
                    if(is_array($this->ChannelUnit->ChannelFields) && is_array($dtp2->CTags))
                    {
                        foreach($this->ChannelUnit->ChannelFields as $k=>$v)
                        {
                            if(isset($v['autofield']) && empty($v['autofield'])) {
                                continue;
                            }
                            $dtp2->SourceString = $oldSource;
                            $dtp2->CTags = $oldCtags;
                            $fname = $v['itemname'];
                            foreach($dtp2->CTags as $tid=>$ctag2)
                            {
                                if($ctag2->GetName()=='name')
                                {
                                    $dtp2->Assign($tid,$fname);
                                }
                                else if($ctag2->GetName()=='tagname')
                                {
                                    $dtp2->Assign($tid,$k);
                                }
                                else if($ctag2->GetName()=='value')
                                {
                                    $this->Fields[$k] = $this->ChannelUnit->MakeField($k,$this->Fields[$k],$ctag2);
                                    @$dtp2->Assign($tid,$this->Fields[$k]);
                                }
                            }
                            $res .= $dtp2->GetResult();
                        }
                    }
                    $this->dtp->Assign($i,$res);
                }

            }

        }
    }

    function Close()
    {
        $this->FixedValues = '';
        $this->Fields = '';
    }

    function GetPreNext($gtype='')
    {
        $rs = '';
        if(count($this->PreNext)<2)
        {
            $aid = $this->ArcID;
            $preR =  $this->dsql->GetOne("Select id From `#@__arctiny` where id<$aid And arcrank>-1 And typeid='{$this->Fields['typeid']}' order by id desc");
            $nextR = $this->dsql->GetOne("Select id From `#@__arctiny` where id>$aid And arcrank>-1 And typeid='{$this->Fields['typeid']}' order by id asc");
            $next = (is_array($nextR) ? " where arc.id={$nextR['id']} " : ' where 1>2 ');
            $pre = (is_array($preR) ? " where arc.id={$preR['id']} " : ' where 1>2 ');
            $query = "Select arc.id,arc.title,arc.shorttitle,arc.typeid,arc.ismake,arc.senddate,arc.arcrank,arc.money,arc.filename,arc.litpic,
                        t.typedir,t.typename,t.namerule,t.namerule2,t.ispart,t.moresite,t.siteurl,t.sitepath
                        from `#@__archives` arc left join #@__arctype t on arc.typeid=t.id  ";
            $nextRow = $this->dsql->GetOne($query.$next);
            $preRow = $this->dsql->GetOne($query.$pre);
            if(is_array($preRow))
            {
                $mlink = GetFileUrl($preRow['id'],$preRow['typeid'],$preRow['senddate'],$preRow['title'],$preRow['ismake'],$preRow['arcrank'],
                $preRow['namerule'],$preRow['typedir'],$preRow['money'],$preRow['filename'],$preRow['moresite'],$preRow['siteurl'],$preRow['sitepath']);
                $this->PreNext['pre'] = "上一篇：<a href='$mlink' title='{$nextRow['title']}'>{$preRow['title']}</a> ";
                $this->PreNext['preimg'] = "<a href='$mlink'><img src=\"{$preRow['litpic']}\" alt=\"{$preRow['title']}\"/></a> "; 
            }
            else
            {
                $this->PreNext['pre'] = "上一篇：<a href='index.html'>前面已经没有了，返回列表吧！</a> ";
                $this->PreNext['preimg'] ="<img src=\"/images/cloudcms_nophoto.jpg\" alt=\"对不起，没有上一图集了！\"/>";
            }
            if(is_array($nextRow))
            {
                $mlink = GetFileUrl($nextRow['id'],$nextRow['typeid'],$nextRow['senddate'],$nextRow['title'],$nextRow['ismake'],$nextRow['arcrank'],
                $nextRow['namerule'],$nextRow['typedir'],$nextRow['money'],$nextRow['filename'],$nextRow['moresite'],$nextRow['siteurl'],$nextRow['sitepath']);
                $this->PreNext['next'] = "下一篇：<a href='$mlink' title='{$nextRow['title']}'>{$nextRow['title']}</a> ";
                $this->PreNext['nextimg'] = "<a href='$mlink'><img src=\"{$nextRow['litpic']}\" alt=\"{$nextRow['title']}\"/></a> ";
            }
            else
            {
                $this->PreNext['next'] = "下一篇：<a href='index.html'>已经到最后了哦，返回列表吧！</a> ";
                $this->PreNext['nextimg'] ="<a href='javascript:void(0)' alt=\"\"><img src=\"/images/cloudcms_nophoto.jpg\" alt=\"对不起，没有下一图集了！\"/></a>";
            }
        }
        if($gtype=='pre')
        {
            $rs =  $this->PreNext['pre'];
        }
        else if($gtype=='preimg'){
            
            $rs =  $this->PreNext['preimg'];
        }
        else if($gtype=='next')
        {
            $rs =  $this->PreNext['next'];
        }
        else if($gtype=='nextimg'){
            
            $rs =  $this->PreNext['nextimg'];
        }
        else
        {
            $rs =  $this->PreNext['pre']." &nbsp; ".$this->PreNext['next'];
        }
        return $rs;
    }

    function GetPagebreakDM($totalPage, $nowPage, $aid)
    {
        global $cfg_rewrite;
        if($totalPage==1)
        {
            return "";
        }
        $PageList = "<li><a>共".$totalPage."页: </a></li>";
        $nPage = $nowPage-1;
        $lPage = $nowPage+1;
        if($nowPage==1)
        {
            $PageList.="<li><a href='#'>上一页</a></li>";
        }
        else
        {
            if($nPage==1)
            {
                $PageList.="<li><a href='view.php?aid=$aid'>上一页</a></li>";
                if($cfg_rewrite == 'Y')
                {
                    $PageList = preg_replace("#.php\?aid=(\d+)#i", '-\\1-1.html', $PageList);
                }
            }
            else
            {
                $PageList.="<li><a href='view.php?aid=$aid&pageno=$nPage'>上一页</a></li>";
                if($cfg_rewrite == 'Y')
                {
                    $PageList = str_replace(".php?aid=", "-", $PageList);
                    $PageList =  preg_replace("#&pageno=(\d+)#i", '-\\1.html', $PageList);
                }
            }
        }
        for($i=1;$i<=$totalPage;$i++)
        {
            if($i==1)
            {
                if($nowPage!=1)
                {
                    $PageList.="<li><a href='view.php?aid=$aid'>1</a></li>";
                    if($cfg_rewrite == 'Y')
                    {
                        $PageList = preg_replace("#.php\?aid=(\d+)#i", '-\\1-1.html', $PageList);
                    }
                }
                else
                {
                    $PageList.="<li class=\"thisclass\"><a>1</a></li>";
                }
            }
            else
            {
                $n = $i;
                if($nowPage!=$i)
                {
                    $PageList.="<li><a href='view.php?aid=$aid&pageno=$i'>".$n."</a></li>";
                    if($cfg_rewrite == 'Y')
                    {
                        $PageList = str_replace(".php?aid=", "-", $PageList);
                        $PageList =  preg_replace("#&pageno=(\d+)#i", '-\\1.html', $PageList);
                    }
                }
                else
                {
                    $PageList.="<li class=\"thisclass\"><a href='#'>{$n}</a></li>";
                }
            }
        }
        if($lPage <= $totalPage)
        {
            $PageList.="<li><a href='view.php?aid=$aid&pageno=$lPage'>下一页</a></li>";
            if($cfg_rewrite == 'Y')
            {
                $PageList = str_replace(".php?aid=", "-", $PageList);
                $PageList =  preg_replace("#&pageno=(\d+)#i", '-\\1.html', $PageList);
            }
        }
        else
        {
            $PageList.= "<li><a href='#'>下一页</a></li>";
        }
        return $PageList;
    }

    function GetPagebreak($totalPage, $nowPage, $aid)
    {
        if($totalPage==1)
        {
            return "";
        }
        $PageList = "<li><a>共".$totalPage."页: </a></li>";
        $nPage = $nowPage-1;
        $lPage = $nowPage+1;
        if($nowPage==1)
        {
            $PageList.="<li><a href='#'>上一页</a></li>";
        }
        else
        {
            if($nPage==1)
            {
                $PageList.="<li><a href='".$this->NameFirst.".".$this->ShortName."'>上一页</a></li>";
            }
            else
            {
                $PageList.="<li><a href='".$this->NameFirst."_".$nPage.".".$this->ShortName."'>上一页</a></li>";
            }
        }
        for($i=1;$i<=$totalPage;$i++)
        {
            if($i==1)
            {
                if($nowPage!=1)
                {
                    $PageList.="<li><a href='".$this->NameFirst.".".$this->ShortName."'>1</a></li>";
                }
                else
                {
                    $PageList.="<li class=\"thisclass\"><a href='#'>1</a></li>";
                }
            }
            else
            {
                $n = $i;
                if($nowPage!=$i)
                {
                    $PageList.="<li><a href='".$this->NameFirst."_".$i.".".$this->ShortName."'>".$n."</a></li>";
                }
                else
                {
                    $PageList.="<li class=\"thisclass\"><a href='#'>{$n}</a></li>";
                }
            }
        }
        if($lPage <= $totalPage)
        {
            $PageList.="<li><a href='".$this->NameFirst."_".$lPage.".".$this->ShortName."'>下一页</a></li>";
        }
        else
        {
            $PageList.= "<li><a href='#'>下一页</a></li>";
        }
        return $PageList;
    }

    function GetPageTitlesDM($styleName, $pageNo)
    {
        if($this->TotalPage==1)
        {
            return "";
        }
        if(count($this->SplitTitles)==0)
        {
            return "";
        }
        $i=1;
        $aid = $this->ArcID;
        if($styleName=='link')
        {
            $revalue = "";
            foreach($this->SplitTitles as $k=>$v)
            {
                if($i==1)
                {
                    $revalue .= "<a href='view.php?aid=$aid&pageno=$i'>$v</a> \r\n";
                }
                else
                {
                    if($pageNo==$i)
                    {
                        $revalue .= " $v \r\n";
                    }
                    else
                    {
                        $revalue .= "<a href='view.php?aid=$aid&pageno=$i'>$v</a> \r\n";
                    }
                }
                $i++;
            }
        }
        else
        {
            $revalue = "<select id='dedepagetitles' onchange='location.href=this.options[this.selectedIndex].value;'>\r\n";
            foreach($this->SplitTitles as $k=>$v)
            {
                if($i==1)
                {
                    $revalue .= "<option value='".$this->Fields['phpurl']."/view.php?aid=$aid&pageno=$i'>{$i}、{$v}</option>\r\n";
                }
                else
                {
                    if($pageNo==$i)
                    {
                        $revalue .= "<option value='".$this->Fields['phpurl']."/view.php?aid=$aid&pageno=$i' selected>{$i}、{$v}</option>\r\n";
                    }
                    else
                    {
                        $revalue .= "<option value='".$this->Fields['phpurl']."/view.php?aid=$aid&pageno=$i'>{$i}、{$v}</option>\r\n";
                    }
                }
                $i++;
            }
            $revalue .= "</select>\r\n";
        }
        return $revalue;
    }

    function GetPageTitlesST($styleName, $pageNo)
    {
        if($this->TotalPage==1)
        {
            return "";
        }
        if(count($this->SplitTitles)==0)
        {
            return "";
        }
        $i=1;
        if($styleName=='link')
        {
            $revalue = "";
            foreach($this->SplitTitles as $k=>$v)
            {
                if($i==1)
                {
                    $revalue .= "<a href='".$this->NameFirst.".".$this->ShortName."'>$v</a> \r\n";
                }
                else
                {
                    if($pageNo==$i)
                    {
                        $revalue .= " $v \r\n";
                    }
                    else
                    {
                        $revalue .= "<a href='".$this->NameFirst."_".$i.".".$this->ShortName."'>$v</a> \r\n";
                    }
                }
                $i++;
            }
        }
        else
        {
            $revalue = "<select id='dedepagetitles' onchange='location.href=this.options[this.selectedIndex].value;'>\r\n";
            foreach($this->SplitTitles as $k=>$v)
            {
                if($i==1)
                {
                    $revalue .= "<option value='".$this->NameFirst.".".$this->ShortName."'>{$i}、{$v}</option>\r\n";
                }
                else
                {
                    if($pageNo==$i)
                    {
                        $revalue .= "<option value='".$this->NameFirst."_".$i.".".$this->ShortName."' selected>{$i}、{$v}</option>\r\n";
                    }
                    else
                    {
                        $revalue .= "<option value='".$this->NameFirst."_".$i.".".$this->ShortName."'>{$i}、{$v}</option>\r\n";
                    }
                }
                $i++;
            }
            $revalue .= "</select>\r\n";
        }
        return $revalue;
    }

    function ReplaceKeyword($kw,&$body)
    {
        global $cfg_cmspath;
        $maxkey = 5;
        $kws = explode(",",trim($kw));
        $i=0;
        $karr = $kaarr = $GLOBALS['replaced'] = array();

        $body = preg_replace("#(<a(.*))(>)(.*)(<)(\/a>)#isU", '\\1-]-\\4-[-\\6', $body);

        $query = "SELECT * FROM #@__keywords WHERE rpurl<>'' ORDER BY rank DESC"; 
        $this->dsql->SetQuery($query);
        $this->dsql->Execute();
        while($row = $this->dsql->GetArray())
        {
            $key = trim($row['keyword']);
            $key_url=trim($row['rpurl']);
            $karr[] = $key;
            $kaarr[] = "<a href='$key_url' target='_blank'><u>$key</u></a>";
        }

        $body = @preg_replace("#(^|>)([^<]+)(?=<|$)#sUe", "_highlight('\\2', \$karr, \$kaarr, '\\1')", $body);

        $body = preg_replace("#(<a(.*))-\]-(.*)-\[-(\/a>)#isU", '\\1>\\3<\\4', $body);
        return $body;
    }


}

function _highlight($string, $words, $result, $pre)
{
    global $cfg_replace_num;
    $string = str_replace('\"', '"', $string);
    if($cfg_replace_num > 0)
    {
        foreach ($words as $key => $word)
        {
            if($GLOBALS['replaced'][$word] == 1)
            {
                continue;
            }
            $string = preg_replace("#".preg_quote($word)."#", $result[$key], $string, $cfg_replace_num);
            if(strpos($string, $word) !== FALSE)
            {
                $GLOBALS['replaced'][$word] = 1;
            }
        }
    }
    else
    {
        $string = str_replace($words, $result, $string);
    }
    return $pre.$string;
}