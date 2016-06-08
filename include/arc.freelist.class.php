<?php   if(!defined('DEDEINC')) exit("Request Error!");

require_once DEDEINC.'/arc.partview.class.php';
@set_time_limit(0);

class FreeList
{
    var $dsql;
    var $dtp;
    var $TypeID;
    var $TypeLink;
    var $PageNo;
    var $TotalPage;
    var $TotalResult;
    var $PageSize;
    var $ChannelUnit;
    var $Fields;
    var $PartView;
    var $FLInfos;
    var $ListObj;
    var $TempletsFile;
    var $maintable;

    function __construct($fid)
    {
        global $dsql;
        $this->FreeID = $fid;
        $this->TypeLink = new TypeLink(0);
        $this->dsql = $dsql;
        $this->maintable = '#@__archives';
        $this->TempletsFile = '';
        $this->FLInfos = $this->dsql->GetOne("SELECT * FROM `#@__freelist` WHERE aid='$fid' ");
        $liststr = $this->FLInfos['listtag'];
        $this->FLInfos['maxpage'] = (empty($this->FLInfos['maxpage']) ? 100 : $this->FLInfos['maxpage']);

        $ndtp = new DedeTagParse();
        $ndtp->SetNameSpace("dede","{","}");
        $ndtp->LoadString($liststr);
        $this->ListObj = $ndtp->GetTag('list');
        $this->PageSize = $this->ListObj->GetAtt('pagesize');
        if(empty($this->PageSize))
        {
            $this->PageSize = 30;
        }
        $channelid = $this->ListObj->GetAtt('channel');

        $channelid = intval($channelid);
        $this->maintable = '#@__archives';

        $this->dtp = new DedeTagParse();
        $this->dtp->SetNameSpace("dede","{","}");
        $this->dtp->SetRefObj($this);

        $this->Fields['aid'] = $this->FLInfos['aid'];
        $this->Fields['title'] = $this->FLInfos['title'];
        $this->Fields['position'] = $this->FLInfos['title'];
        $this->Fields['keywords'] = $this->FLInfos['keywords'];
        $this->Fields['description'] = $this->FLInfos['description'];
        $channelid = $this->ListObj->GetAtt('channel');
        if(!empty($channelid))
        {
            $this->Fields['channeltype'] = $channelid;
            $this->ChannelUnit = new ChannelUnit($channelid);
        }
        else
        {
            $this->Fields['channeltype'] = 0;
        }
        foreach($GLOBALS['PubFields'] as $k=>$v)
        {
            $this->Fields[$k] = $v;
        }
        $this->PartView = new PartView();
        $this->CountRecord();
    }

    function FreeList($fid)
    {
        $this->__construct($fid);
    }

    function Close()
    {
    }

    function CountRecord()
    {
        global $cfg_list_son,$cfg_needsontype;

        $this->TotalResult = -1;
        if(isset($GLOBALS['TotalResult']))
        {
            $this->TotalResult = $GLOBALS['TotalResult'];
        }
        if(isset($GLOBALS['PageNo']))
        {
            $this->PageNo = $GLOBALS['PageNo'];
        }
        else
        {
            $this->PageNo = 1;
        }

        if($this->TotalResult==-1)
        {
            $addSql  = " arcrank > -1 AND channel>-1 ";
            $typeid = $this->ListObj->GetAtt('typeid');
            $subday = $this->ListObj->GetAtt('subday');
            $listtype = $this->ListObj->GetAtt('type');
            $att = $this->ListObj->GetAtt('att');
            $channelid = $this->ListObj->GetAtt('channel');
            if(empty($channelid))
            {
                $channelid = 0;
            }

            if(!empty($typeid))
            {
                if($cfg_list_son=='N')
                {
                    $addSql .= " AND (typeid='$typeid') ";
                }
                else
                {
                    $addSql .= " AND typeid in (".GetSonIds($typeid,0,TRUE).") ";
                }
            }

            if($att!='') {
                $flags = explode(',',$att);
                for($i=0;isset($flags[$i]);$i++) $addSql .= " AND FIND_IN_SET('{$flags[$i]}',flag)>0 ";
            }

            if($channelid>0 && !preg_match("#spec#i", $listtype))
            {
                $addSql .= " AND channel = '$channelid' ";
            }

            if(preg_match("#commend#i",$listtype))
            {
                $addSql .= " AND FIND_IN_SET('c',flag) > 0  ";
            }
            if(preg_match("#image#i",$listtype))
            {
                $addSql .= " AND litpic <> ''  ";
            }
            if(preg_match("#spec#i",$listtype) || $channelid==-1)
            {
                $addSql .= " AND channel = -1  ";
            }
            if(!empty($subday))
            {
                $starttime = time() - $subday * 86400;
                $addSql .= " AND senddate > $starttime  ";
            }
            $keyword = $this->ListObj->GetAtt('keyword');
            if(!empty($keyword))
            {
                $addSql .= " AND CONCAT(title,keywords) REGEXP '$keyword' ";
            }
            $cquery = "SELECT COUNT(*) AS dd FROM `{$this->maintable}` WHERE $addSql";
            $row = $this->dsql->GetOne($cquery);
            if(is_array($row))
            {
                $this->TotalResult = $row['dd'];
            }
            else
            {
                $this->TotalResult = 0;
            }
        }
        $this->TotalPage = ceil($this->TotalResult/$this->PageSize);
        if($this->TotalPage > $this->FLInfos['maxpage'])
        {
            $this->TotalPage = $this->FLInfos['maxpage'];
            $this->TotalResult = $this->TotalPage * $this->PageSize;
        }
    }

    function LoadTemplet()
    {
        $tmpdir = $GLOBALS['cfg_basedir'].$GLOBALS['cfg_templets_dir'];
        $tempfile = str_replace("{style}",$GLOBALS['cfg_df_style'],$this->FLInfos['templet']);
        $tempfile = $tmpdir."/".$tempfile;
        if(!file_exists($tempfile))
        {
            $tempfile = $tmpdir."/".$GLOBALS['cfg_df_style']."/list_free.htm";
        }
        $this->dtp->LoadTemplate($tempfile);
        $this->TempletsFile = preg_replace("#^".$GLOBALS['cfg_basedir']."#", '', $tempfile);
    }

    function MakeHtml($startpage=1, $makepagesize=0)
    {
        $this->LoadTemplet();
        $murl = "";
        if(empty($startpage))
        {
            $startpage = 1;
        }
        $this->ParseTempletsFirst();
        $totalpage = ceil($this->TotalResult/$this->PageSize);
        if($totalpage==0)
        {
            $totalpage = 1;
        }
        if($makepagesize>0)
        {
            $endpage = $startpage+$makepagesize;
        }
        else
        {
            $endpage = ($totalpage+1);
        }
        if($endpage>($totalpage+1))
        {
            $endpage = $totalpage;
        }
        $firstFile = '';
        for($this->PageNo=$startpage;$this->PageNo<$endpage;$this->PageNo++)
        {
            $this->ParseDMFields($this->PageNo,1);

            $makeFile = $this->GetMakeFileRule();
            if(!preg_match("#^\/#", $makeFile))
            {
                $makeFile = "/".$makeFile;
            }
            $makeFile = str_replace('{page}',$this->PageNo,$makeFile);
            $murl = $makeFile;
            $makeFile = $GLOBALS['cfg_basedir'].$makeFile;
            $makeFile = preg_replace("#\/{1,}#", "/", $makeFile);
            if($this->PageNo==1)
            {
                $firstFile = $makeFile;
            }

            $this->dtp->SaveTo($makeFile);
            echo "成功创建：<a href='".preg_replace("#\/{1,}#", "/", $murl)."' target='_blank'>".preg_replace("#\/{1,}#", "/", $murl)."</a><br/>";
        }
        if($this->FLInfos['nodefault']==0)
        {
            $murl = '/'.str_replace('{cmspath}',$GLOBALS['cfg_cmspath'],$this->FLInfos['listdir']);
            $murl .= '/'.$this->FLInfos['defaultpage'];
            $indexfile = $GLOBALS['cfg_basedir'].$murl;
            $murl = preg_replace("#\/{1,}#", "/", $murl);
            echo "复制：$firstFile 为 ".$this->FLInfos['defaultpage']." <br/>";
            copy($firstFile,$indexfile);
        }
        $this->Close();
        return $murl;
    }

    function Display()
    {
        $this->LoadTemplet();
        $this->ParseTempletsFirst();
        $this->ParseDMFields($this->PageNo,0);
        $this->dtp->Display();
    }

    function DisplayPartTemplets()
    {
        $nmfa = 0;
        $tmpdir = $GLOBALS['cfg_basedir'].$GLOBALS['cfg_templets_dir'];
        if($this->Fields['ispart']==1)
        {
            $tempfile = str_replace("{tid}",$this->FreeID,$this->Fields['tempindex']);
            $tempfile = str_replace("{cid}",$this->ChannelUnit->ChannelInfos['nid'],$tempfile);
            $tempfile = $tmpdir."/".$tempfile;
            if(!file_exists($tempfile))
            {
                $tempfile = $tmpdir."/".$GLOBALS['cfg_df_style']."/index_default.htm";
            }
            $this->PartView->SetTemplet($tempfile);
        }
        else if($this->Fields['ispart']==2)
        {
            $tempfile = str_replace("{tid}",$this->FreeID,$this->Fields['tempone']);
            $tempfile = str_replace("{cid}",$this->ChannelUnit->ChannelInfos['nid'],$tempfile);
            if(is_file($tmpdir."/".$tempfile))
            {
                $this->PartView->SetTemplet($tmpdir."/".$tempfile);
            }
            else
            {
                $this->PartView->SetTemplet("这是没有使用模板的单独页！","string"); $nmfa = 1;
            }
        }
        CreateDir($this->Fields['typedir']);
        $makeUrl = $this->GetMakeFileRule($this->Fields['id'],"index",$this->Fields['typedir'],$this->Fields['defaultname'],$this->Fields['namerule2']);
        $makeFile = $this->GetTruePath().$makeUrl;
        if($nmfa==0)
        {
            $this->PartView->Display();
        }
        else{
            if(!file_exists($makeFile))
            {
                $this->PartView->Display();
            }
            else
            {
                include($makeFile);
            }
        }
    }

    function ParseTempletsFirst()
    {
        MakeOneTag($this->dtp,$this);
    }

    function ParseDMFields($PageNo,$ismake=1)
    {
        foreach($this->dtp->CTags as $tagid=>$ctag)
        {
            if($ctag->GetName()=="freelist")
            {
                $limitstart = ($this->PageNo-1) * $this->PageSize;
                if($this->PageNo > $this->FLInfos['maxpage']) $this->dtp->Assign($tagid, '已经超过了最大允许列出的页面！');
                else $this->dtp->Assign($tagid,$this->GetList($limitstart,$ismake));
            }
            else if($ctag->GetName()=="pagelist")
            {
                $list_len = trim($ctag->GetAtt("listsize"));
                $ctag->GetAtt("listitem")=="" ? $listitem="info,index,pre,pageno,next,end,option" : $listitem=$ctag->GetAtt("listitem");
                if($list_len=="")
                {
                    $list_len = 3;
                }
                if($ismake==0)
                {
                    $this->dtp->Assign($tagid,$this->GetPageListDM($list_len,$listitem));
                }
                else
                {
                    $this->dtp->Assign($tagid,$this->GetPageListST($list_len,$listitem));
                }
            }
            else if($ctag->GetName()=="pageno")
            {
                $this->dtp->Assign($tagid,$PageNo);
            }
        }
    }

    function GetMakeFileRule()
    {
        $okfile = '';
        $namerule = $this->FLInfos['namerule'];
        $listdir = $this->FLInfos['listdir'];
        $listdir = str_replace('{cmspath}',$GLOBALS['cfg_cmspath'],$listdir);
        $okfile = str_replace('{listid}',$this->FLInfos['aid'],$namerule);
        $okfile = str_replace('{listdir}',$listdir,$okfile);
        $okfile = str_replace("\\","/",$okfile);
        $mdir = preg_replace("#/([^/]*)$#", "", $okfile);
        if(!preg_match("#\/#", $mdir) && preg_match("#\.#", $mdir))
        {
            return $okfile;
        }
        else
        {
            CreateDir($mdir,'','');
            return $okfile;
        }
    }

    function GetList($limitstart, $ismake=1)
    {
        global $cfg_list_son,$cfg_needsontype;
        $col = $this->ListObj->GetAtt('col');
        if(empty($col))
        {
            $col = 1;
        }
        $titlelen = $this->ListObj->GetAtt('titlelen');
        $infolen = $this->ListObj->GetAtt('infolen');
        $imgwidth = $this->ListObj->GetAtt('imgwidth');
        $imgheight = $this->ListObj->GetAtt('imgheight');
        $titlelen = AttDef($titlelen,60);
        $infolen = AttDef($infolen,250);
        $imgwidth = AttDef($imgwidth,80);
        $imgheight = AttDef($imgheight,80);
        $innertext = trim($this->ListObj->GetInnerText());
        if(empty($innertext)) $innertext = GetSysTemplets("list_fulllist.htm");

        $tablewidth = 100;
        if($col=="") $col=1;
        $colWidth = ceil(100 / $col);
        $tablewidth = $tablewidth."%";
        $colWidth = $colWidth."%";

        $orwhere = " arc.arcrank > -1 AND channel>-1 ";
        $typeid = $this->ListObj->GetAtt('typeid');
        $subday = $this->ListObj->GetAtt('subday');
        $listtype = $this->ListObj->GetAtt('type');
        $att = $this->ListObj->GetAtt('att');
        $channelid = $this->ListObj->GetAtt('channel');
        if(empty($channelid)) $channelid = 0;

        if(!empty($typeid))
        {
            if($cfg_list_son=='N')
            {
                $orwhere .= " AND (arc.typeid='$typeid') ";
            }
            else
            {
                $orwhere .= " AND arc.typeid IN (".GetSonIds($typeid, 0, TRUE).") ";
            }
        }

        if($att!='') {
            $flags = explode(',', $att);
            for($i=0; isset($flags[$i]); $i++) $orwhere .= " AND FIND_IN_SET('{$flags[$i]}',flag)>0 ";
        }

        if($channelid>0 && !preg_match("#spec#i", $listtype))
        {
            $orwhere .= " AND arc.channel = '$channelid' ";
        }

        if(preg_match("#commend#i",$listtype))
        {
            $orwhere .= " AND FIND_IN_SET('c',flag) > 0  ";
        }
        if(preg_match("#image#i",$listtype))
        {
            $orwhere .= " AND arc.litpic <> ''  ";
        }
        if(preg_match("#spec#i",$listtype) || $channelid==-1)
        {
            $orwhere .= " AND arc.channel = -1  ";
        }
        if(!empty($subday))
        {
            $starttime = time() - $subday*86400;
            $orwhere .= " AND arc.senddate > $starttime  ";
        }
        $keyword = $this->ListObj->GetAtt('keyword');
        if(!empty($keyword))
        {
            $orwhere .= " AND CONCAT(arc.title,arc.keywords) REGEXP '$keyword' ";
        }
        $orderby = $this->ListObj->GetAtt('orderby');
        $orderWay = $this->ListObj->GetAtt('orderway');

        $ordersql = "";
        if($orderby=="senddate")
        {
            $ordersql=" ORDER BY arc.senddate $orderWay";
        }
        else if($orderby=="pubdate")
        {
            $ordersql=" ORDER BY arc.pubdate $orderWay";
        }
        else if($orderby=="id")
        {
            $ordersql="  ORDER BY arc.id $orderWay";
        }
        else if($orderby=="hot"||$orderby=="click")
        {
            $ordersql = " ORDER BY arc.click $orderWay";
        }
        else if($orderby=="lastpost")
        {
            $ordersql = "  ORDER BY arc.lastpost $orderWay";
        }
        else if($orderby=="scores")
        {
            $ordersql = "  ORDER BY arc.scores $orderWay";
        }
        else if($orderby=="rand")
        {
            $ordersql = "  ORDER BY rand()";
        }
        else
        {
            $ordersql=" ORDER BY arc.sortrank $orderWay";
        }

        $addField = "";
        $addJoin = "";
        if(is_object($this->ChannelUnit))
        {
            $addtable  = $this->ChannelUnit->ChannelInfos['addtable'];
            if($addtable!="")
            {
                $addJoin = " LEFT JOIN $addtable ON arc.id = ".$addtable.".aid ";
                $addField = "";
                $fields = explode(",",$this->ChannelUnit->ChannelInfos['listfields']);
                foreach($fields as $k=>$v)
                {
                    $nfields[$v] = $k;
                }
                foreach($this->ChannelUnit->ChannelFields as $k=>$arr)
                {
                    if(isset($nfields[$k]))
                    {
                        if(!empty($arr['rename']))
                        {
                            $addField .= ",".$addtable.".".$k." as ".$arr['rename'];
                        }
                        else
                        {
                            $addField .= ",".$addtable.".".$k;
                        }
                    }
                }
            }
        }

        $query = "SELECT arc.*,tp.typedir,tp.typename,tp.isdefault,tp.defaultname,
        tp.namerule,tp.namerule2,tp.ispart,tp.moresite,tp.siteurl,tp.sitepath
        $addField
        FROM {$this->maintable} arc
        LEFT JOIN #@__arctype tp ON arc.typeid=tp.id
        $addJoin
        WHERE $orwhere $ordersql LIMIT $limitstart,".$this->PageSize;
        $this->dsql->SetQuery($query);
        $this->dsql->Execute("al");
        $artlist = "";
        if($col>1)
        {
            $artlist = "<table width='$tablewidth' border='0' cellspacing='0' cellpadding='0'>\r\n";
        }
        $indtp = new DedeTagParse();
        $indtp->SetNameSpace("field","[","]");
        $indtp->LoadSource($innertext);
        $GLOBALS['autoindex'] = 0;
        for($i=0;$i<$this->PageSize;$i++)
        {
            if($col>1)
            {
                $artlist .= "<tr>\r\n";
            }
            for($j=0;$j<$col;$j++)
            {
                if($col>1)
                {
                    $artlist .= "<td width='$colWidth'>\r\n";
                }
                if($row = $this->dsql->GetArray("al"))
                {
                    $GLOBALS['autoindex']++;

                    $row['id'] =  $row['id'];
                    $row['arcurl'] = $this->GetArcUrl($row['id'],$row['typeid'],$row['senddate'],
                    $row['title'],$row['ismake'],$row['arcrank'],$row['namerule'],$row['typedir'],$row['money'],
                    $row['filename'],$row['moresite'],$row['siteurl'],$row['sitepath']);
                    $row['typeurl'] = GetTypeUrl($row['typeid'],$row['typedir'],$row['isdefault'],$row['defaultname'],
                    $row['ispart'],$row['namerule2'],$row['siteurl'],$row['sitepath']);
                    if($ismake==0 && $GLOBALS['cfg_multi_site']=='Y')
                    {
                        if($row["siteurl"]=="")
                        {
                            $row["siteurl"] = $GLOBALS['cfg_mainsite'];
                        }
                    }

                    $row['description'] = cn_substr($row['description'],$infolen);

                    if($row['litpic'] == '-' || $row['litpic'] == '')
                    {
                        $row['litpic'] = $GLOBALS['cfg_cmspath'].'/images/cloudcms_img.jpg';
                    }
                    if(!preg_match("#^http:\/\/#i", $row['litpic']) && $GLOBALS['cfg_multi_site'] == 'Y')
                    {
                        $row['litpic'] = $GLOBALS['cfg_mainsite'].$row['litpic'];
                    }
                    $row['picname'] = $row['litpic'];
                    $row['info'] = $row['description'];
                    $row['filename'] = $row['arcurl'];
                    $row['stime'] = GetDateMK($row['pubdate']);
                    $row['textlink'] = "<a href='".$row['filename']."' title='".str_replace("'","",$row['title'])."'>".$row['title']."</a>";
                    $row['typelink'] = "<a href='".$row['typeurl']."'>[".$row['typename']."]</a>";
                    $row['imglink'] = "<a href='".$row['filename']."'><img src='".$row['picname']."' border='0' width='$imgwidth' height='$imgheight' alt='".str_replace("'","",$row['title'])."'></a>";
                    $row['image'] = "<img src='".$row['picname']."' border='0' width='$imgwidth' height='$imgheight' alt='".str_replace("'","",$row['title'])."'>";
                    $row['plusurl'] = $row['phpurl'] = $GLOBALS['cfg_phpurl'];
                    $row['memberurl'] = $GLOBALS['cfg_memberurl'];
                    $row['templeturl'] = $GLOBALS['cfg_templeturl'];
                    $row['title'] = cn_substr($row['title'],$titlelen);
                    if($row['color']!="")
                    {
                        $row['title'] = "<font color='".$row['color']."'>".$row['title']."</font>";
                    }
                    if(preg_match("#c#", $row['flag']))
                    {
                        $row['title'] = "<b>".$row['title']."</b>";
                    }

                    if(is_object($this->ChannelUnit))
                    {
                        foreach($row as $k=>$v)
                        {
                            if(preg_match("#[A-Z]#", $k))
                            {
                                $row[strtolower($k)] = $v;
                            }
                        }
                        foreach($this->ChannelUnit->ChannelFields as $k=>$arr)
                        {
                            if(isset($row[$k]))
                            {
                                $row[$k] = $this->ChannelUnit->MakeField($k,$row[$k]);
                            }
                        }
                    }

                    if(is_array($indtp->CTags))
                    {
                        foreach($indtp->CTags as $k=>$ctag)
                        {
                            $_f = $ctag->GetName();
                            if($_f=='array')
                            {
                                $indtp->Assign($k,$row);
                            }
                            else
                            {
                                if(isset($row[$_f]))
                                {
                                    $indtp->Assign($k,$row[$_f]);
                                }
                                else
                                {
                                    $indtp->Assign($k,"");
                                }
                            }
                        }
                    }
                    $artlist .= $indtp->GetResult();
                }

                else
                {
                    $artlist .= "";
                }
                if($col>1)
                {
                    $artlist .= "    </td>\r\n";
                }
            }

            if($col>1){
                $i += $col - 1;
            }
            if($col>1)
            {
                $artlist .= "    </tr>\r\n";
            }
        }

        if($col>1)
        {
            $artlist .= "</table>\r\n";
        }
        $this->dsql->FreeResult("al");
        return $artlist;
    }

    function GetPageListST($list_len, $listitem="info,index,end,pre,next,pageno")
    {
        $prepage="";
        $nextpage="";
        $prepagenum = $this->PageNo-1;
        $nextpagenum = $this->PageNo+1;
        if($list_len=="" || preg_match("#[^0-9]#", $list_len))
        {
            $list_len=3;
        }
        $totalpage = ceil($this->TotalResult/$this->PageSize);
        if($totalpage <= 1 && $this->TotalResult > 0)
        {
            return "共1页/".$this->TotalResult."条记录";
        }
        if($this->TotalResult == 0)
        {
            return "共0页/".$this->TotalResult."条记录";
        }
        $maininfo = " 共{$totalpage}页/".$this->TotalResult."条记录 ";
        $purl = $this->GetCurUrl();
        $tnamerule = $this->GetMakeFileRule();
        $tnamerule = preg_replace("#^(.*)\/#", '', $tnamerule);

        if($this->PageNo != 1)
        {
            $prepage.="<a href='".str_replace("{page}", $prepagenum, $tnamerule)."'>上一页</a>\r\n";
            $indexpage="<a href='".str_replace("{page}", 1, $tnamerule)."'>首页</a>\r\n";
        }
        else
        {
            $indexpage="<a href='#'>首页</a>\r\n";
        }

        if($this->PageNo!=$totalpage && $totalpage>1)
        {
            $nextpage.="<a href='".str_replace("{page}",$nextpagenum,$tnamerule)."'>下一页</a>\r\n";
            $endpage="<a href='".str_replace("{page}",$totalpage,$tnamerule)."'>末页</a>\r\n";
        }
        else
        {
            $endpage="<a href='#'>末页</a>\r\n";
        }

        $optionlen = strlen($totalpage);
        $optionlen = $optionlen*12 + 18;
        if($optionlen < 36) $optionlen = 36;
        if($optionlen > 100) $optionlen = 100;
        $optionlist = "<select name='sldd' style='width:$optionlen' onchange='location.href=this.options[this.selectedIndex].value;'>\r\n";
        for($fl=1; $fl<=$totalpage; $fl++)
        {
            if($fl==$this->PageNo)
            {
                $optionlist .= "<option value='" . str_replace("{page}",$fl,$tnamerule) . "' selected>$fl</option>\r\n";
            } else {
                $optionlist .= "<option value='" . str_replace("{page}",$fl,$tnamerule)."'>$fl</option>\r\n";
            }
        }
        $optionlist .= "</select>";

        $listdd="";
        $total_list = $list_len * 2 + 1;
        if($this->PageNo >= $total_list)
        {
            $j = $this->PageNo-$list_len;
            $total_list = $this->PageNo+$list_len;
            if($total_list > $totalpage)
            {
                $total_list = $totalpage;
            }
        }
        else
        {
            $j = 1;
            if($total_list > $totalpage)
            {
                $total_list = $totalpage;
            }
        }
        
        for($j; $j<=$total_list; $j++)
        {
            if($j==$this->PageNo)
            {
                $listdd.= "<strong>{$j}</strong>\r\n";
            }
            else
            {
                $listdd.="<a href='".str_replace("{page}", $j, $tnamerule)."'>".$j."</a>\r\n";
            }
        }
        $plist = "";
        if(preg_match('#info#i', $listitem))
        {
            $plist .= $maininfo.' ';
        }
        if(preg_match('#index#i',$listitem))
        {
            $plist .= $indexpage.' ';
        }
        if(preg_match('#pre#i', $listitem))
        {
            $plist .= $prepage.' ';
        }
        if(preg_match('#pageno#i', $listitem))
        {
            $plist .= $listdd.' ';
        }
        if(preg_match('#next#i', $listitem))
        {
            $plist .= $nextpage.' ';
        }
        if(preg_match('#end#i', $listitem))
        {
            $plist .= $endpage.' ';
        }
        if(preg_match('#option#i', $listitem))
        {
            $plist .= $optionlist;
        }
        return $plist;
    }

    function GetPageListDM($list_len,$listitem="index,end,pre,next,pageno")
    {
        $prepage="";
        $nextpage="";
        $prepagenum = $this->PageNo-1;
        $nextpagenum = $this->PageNo+1;
        if($list_len==""||preg_match("/[^0-9]/", $list_len))
        {
            $list_len=3;
        }
        $totalpage = ceil($this->TotalResult/$this->PageSize);
        if($totalpage<=1 && $this->TotalResult>0)
        {
            return "共1页/".$this->TotalResult."条记录";
        }
        if($this->TotalResult == 0)
        {
            return "共0页/".$this->TotalResult."条记录";
        }
        $maininfo = "共{$totalpage}页/".$this->TotalResult."条记录";
        $purl = $this->GetCurUrl();
        $geturl = "lid=".$this->FreeID."&TotalResult=".$this->TotalResult."&";
        $hidenform = "<input type='hidden' name='lid' value='".$this->FreeID."' />\r\n";
        $hidenform .= "<input type='hidden' name='TotalResult' value='".$this->TotalResult."' />\r\n";
        $purl .= "?".$geturl;

        if($this->PageNo != 1)
        {
            $prepage.="<a href='".$purl."PageNo=$prepagenum'>上一页</a>\r\n";
            $indexpage="<a href='".$purl."PageNo=1'>首页</a>\r\n";
        }
        else
        {
            $indexpage="<a href='#'>首页</a>\r\n";
        }
        if($this->PageNo!=$totalpage && $totalpage>1)
        {
            $nextpage.="<a href='".$purl."PageNo=$nextpagenum'>下一页</a>\r\n";
            $endpage="<a href='".$purl."PageNo=$totalpage'>末页</a>\r\n";
        }
        else
        {
            $endpage="<a href='#'>末页</a>\r\n";
        }

        $listdd="";
        $total_list = $list_len * 2 + 1;
        if($this->PageNo >= $total_list)
        {
            $j = $this->PageNo-$list_len;
            $total_list = $this->PageNo+$list_len;
            if($total_list>$totalpage) $total_list=$totalpage;
        }
        else
        {
            $j=1;
            if($total_list>$totalpage) $total_list=$totalpage;
        }
        for($j;$j<=$total_list;$j++)
        {
            if($j==$this->PageNo)
            {
                $listdd.= "<a href='#'>.$j.</a>\r\n";
            }
            else
            {
                $listdd.="<a href='".$purl."PageNo=$j'>".$j."</a>\r\n";
            }
        }
        $plist  = "<form name='pagelist' action='".$this->GetCurUrl()."'>$hidenform";
        $plist .= $maininfo.$indexpage.$prepage.$listdd.$nextpage.$endpage;
        if($totalpage>$total_list)
        {
            $plist.="<input type='text' name='PageNo'  value='".$this->PageNo."' style='width:30px' />\r\n";
            $plist.="<input type='submit' name='plistgo' value='GO' />\r\n";
        }
        $plist .= "</form>\r\n";
        return $plist;
    }

    function GetArcUrl($aid, $typeid, $timetag, $title, $ismake=0, $rank=0, $namerule='', $artdir='',
    $money=0, $filename='', $moresite='', $siteurl='', $sitepath='')
    {
        return GetFileUrl($aid, $typeid, $timetag, $title, $ismake, $rank, $namerule, $artdir,
        $money, $filename, $moresite, $siteurl, $sitepath);
    }

    function GetCurUrl()
    {
        if(!empty($_SERVER["REQUEST_URI"]))
        {
            $nowurl = $_SERVER["REQUEST_URI"];
            $nowurls = explode("?",$nowurl);
            $nowurl = $nowurls[0];
        }
        else
        {
            $nowurl = $_SERVER["PHP_SELF"];
        }
        return $nowurl;
    }
}