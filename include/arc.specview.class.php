<?php   if(!defined('DEDEINC')) exit("Request Error!");

require_once(DEDEINC."/typelink.class.php");
require_once(DEDEINC."/channelunit.class.php");
require_once(DEDEINC.'/ftp.class.php');

@set_time_limit(0);

class SpecView
{
    var $dsql;
    var $dtp;
    var $dtp2;
    var $TypeID;
    var $TypeLink;
    var $PageNo;
    var $TotalPage;
    var $TotalResult;
    var $PageSize;
    var $ChannelUnit;
    var $ListType;
    var $TempInfos;
    var $Fields;
    var $StartTime;
    var $ftp;
    var $remoteDir;

    function __construct($starttime=0)
    {
        global $ftp;
        $this->TypeID = 0;
        $this->dsql = $GLOBALS['dsql'];
        $this->dtp = new DedeTagParse();
        $this->dtp->SetRefObj($this);
        $this->dtp->SetNameSpace("dede","{","}");
        $this->dtp2 = new DedeTagParse();
        $this->dtp2->SetNameSpace("field","[","]");
        $this->TypeLink = new TypeLink(0);
        $this->ChannelUnit = new ChannelUnit(-1);
        $this->ftp = &$ftp;
        $this->remoteDir = '';

        foreach($GLOBALS['PubFields'] as $k=>$v)
        {
            $this->Fields[$k] = $v;
        }
        if($starttime==0)
        {
            $this->StartTime = 0;
        }
        else
        {
            $this->StartTime = GetMkTime($starttime);
        }
        $this->CountRecord();
        $tempfile = $GLOBALS['cfg_basedir'].$GLOBALS['cfg_templets_dir']."/".$GLOBALS['cfg_df_style']."/list_spec.htm";
        if(!file_exists($tempfile)||!is_file($tempfile))
        {
            echo "模板文件不存在，无法解析文档！";
            exit();
        }
        $this->dtp->LoadTemplate($tempfile);
        $this->TempInfos['tags'] = $this->dtp->CTags;
        $this->TempInfos['source'] = $this->dtp->SourceString;
        $ctag = $this->dtp->GetTag("page");
        if(!is_object($ctag))
        {
            $this->PageSize = 20;
        }
        else
        {
            if($ctag->GetAtt("pagesize")!="")
            {
                $this->PageSize = $ctag->GetAtt("pagesize");
            }
            else
            {
                $this->PageSize = 20;
            }
        }
        $this->TotalPage = ceil($this->TotalResult/$this->PageSize);
    }

    function SpecView($starttime=0)
    {
        $this->__construct($starttime);
    }

    function Close()
    {
    }

    function CountRecord()
    {
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
            if($this->StartTime>0)
            {
                $timesql = " AND #@__archives.senddate>'".$this->StartTime."'";
            }
            else
            {
                $timesql = "";
            }
            $row = $this->dsql->GetOne("SELECT count(*) AS dd FROM #@__archives WHERE #@__archives.arcrank > -1 AND channel=-1 $timesql");
            if(is_array($row))
            {
                $this->TotalResult = $row['dd'];
            }
            else
            {
                $this->TotalResult = 0;
            }
        }
    }

    function Display()
    {
        if($this->TypeLink->TypeInfos['ispart']==1
        ||$this->TypeLink->TypeInfos['ispart']==2)
        {
            $this->DisplayPartTemplets();
        }
        $this->ParseTempletsFirst();
        foreach($this->dtp->CTags as $tagid=>$ctag)
        {
            if($ctag->GetName()=="list")
            {
                $limitstart = ($this->PageNo-1) * $this->PageSize;
                $row = $this->PageSize;
                if(trim($ctag->GetInnerText())=="")
                {
                    $InnerText = GetSysTemplets("list_fulllist.htm");
                }
                else
                {
                    $InnerText = trim($ctag->GetInnerText());
                }
                $this->dtp->Assign($tagid,
                $this->GetArcList($limitstart,$row,
                $ctag->GetAtt("col"),
                $ctag->GetAtt("titlelen"),
                $ctag->GetAtt("infolen"),
                $ctag->GetAtt("imgwidth"),
                $ctag->GetAtt("imgheight"),
                $ctag->GetAtt("listtype"),
                $ctag->GetAtt("orderby"),
                $InnerText,
                $ctag->GetAtt("tablewidth"))
                );
            }
            else if($ctag->GetName()=="pagelist")
            {
                $list_len = trim($ctag->GetAtt("listsize"));
                if($list_len=="")
                {
                    $list_len = 3;
                }
                $this->dtp->Assign($tagid,$this->GetPageListDM($list_len));
            }
        }
        $this->dtp->Display();
    }

    function MakeHtml($isremote=0)
    {
        global $cfg_remote_site;

        $this->ParseTempletsFirst();
        $totalpage = ceil($this->TotalResult / $this->PageSize);
        if($totalpage==0)
        {
            $totalpage = 1;
        }
        CreateDir($GLOBALS['cfg_special']);
        $murl = "";
        for($this->PageNo=1;$this->PageNo<=$totalpage;$this->PageNo++)
        {
            foreach($this->dtp->CTags as $tagid=>$ctag)
            {
                if($ctag->GetName()=="list")
                {
                    $limitstart = ($this->PageNo-1) * $this->PageSize;
                    $row = $this->PageSize;
                    if(trim($ctag->GetInnerText())=="")
                    {
                        $InnerText = GetSysTemplets("spec_list.htm");
                    }
                    else
                    {
                        $InnerText = trim($ctag->GetInnerText());
                    }
                    $this->dtp->Assign($tagid,
                    $this->GetArcList($limitstart,$row,
                    $ctag->GetAtt("col"),
                    $ctag->GetAtt("titlelen"),
                    $ctag->GetAtt("infolen"),
                    $ctag->GetAtt("imgwidth"),
                    $ctag->GetAtt("imgheight"),
                    "spec",
                    $ctag->GetAtt("orderby"),
                    $InnerText,
                    $ctag->GetAtt("tablewidth"))
                    );
                }
                else if($ctag->GetName()=="pagelist")
                {
                    $list_len = trim($ctag->GetAtt("listsize"));
                    if($list_len=="")
                    {
                        $list_len = 3;
                    }
                    $this->dtp->Assign($tagid,$this->GetPageListST($list_len));
                }
            }

            $makeFile = $GLOBALS['cfg_special']."/spec_".$this->PageNo.$GLOBALS['art_shortname'];
            $murl = $makeFile;
            $makeFile = $GLOBALS['cfg_basedir'].$makeFile;
            $this->dtp->SaveTo($makeFile);

            if($cfg_remote_site=='Y'&& $isremote == 1)
            {

                $remotefile = str_replace(DEDEROOT, '', $makeFile);
                $localfile = '..'.$remotefile;
                $remotedir = preg_replace('/[^\/]*\.html/', '',$remotefile);

                $this->ftp->rmkdir($remotedir);
                $this->ftp->upload($localfile, $remotefile, 'acii');
            }
            echo "成功创建：$murl<br/>";
        }
        copy($GLOBALS['cfg_basedir'].$GLOBALS['cfg_special']."/spec_1".$GLOBALS['art_shortname'],$GLOBALS['cfg_basedir'].$GLOBALS['cfg_special']."/index.html");
        $murl = $GLOBALS['cfg_special']."/index.html";
        return $murl;
    }

    function ParseTempletsFirst()
    {
        MakeOneTag($this->dtp,$this);
    }

    function GetArcList($limitstart=0,$row=10,$col=1,$titlelen=30,$infolen=250,
    $imgwidth=120,$imgheight=90,$listtype="all",$orderby="default",$innertext="",$tablewidth="100")
    {
        $typeid=$this->TypeID;
        if($row=="")
        {
            $row = 10;
        }
        if($limitstart=="")
        {
            $limitstart = 0;
        }
        if($titlelen=="")
        {
            $titlelen = 30;
        }
        if($infolen=="")
        {
            $infolen = 250;
        }
        if($imgwidth=="")
        {
            $imgwidth = 120;
        }
        if($imgheight=="")
        {
            $imgheight = 120;
        }
        if($listtype=="")
        {
            $listtype = "all";
        }
        if($orderby=="")
        {
            $orderby="default";
        }
        else
        {
            $orderby=strtolower($orderby);
        }
        $tablewidth = str_replace("%", "", $tablewidth);
        if($tablewidth=="")
        {
            $tablewidth=100;
        }
        if($col=="")
        {
            $col=1;
        }
        $colWidth = ceil(100/$col);
        $tablewidth = $tablewidth."%";
        $colWidth = $colWidth."%";
        $innertext = trim($innertext);
        if($innertext=="")
        {
            $innertext = GetSysTemplets("spec_list.htm");
        }

        $orwhere = " arc.arcrank > -1 AND arc.channel = -1 ";
        if($this->StartTime>0)
        {
            $orwhere .= " AND arc.senddate>'".$this->StartTime."'";
        }

        $ordersql = '';
        if($orderby=='senddate')
        {
            $ordersql=" ORDER BY arc.senddate desc";
        }
        else if($orderby=='pubdate')
        {
            $ordersql=" ORDER BY arc.pubdate desc";
        }
        else if($orderby=='id')
        {
            $ordersql="  ORDER BY arc.id desc";
        }
        else
        {
            $ordersql=" ORDER BY arc.sortrank desc";
        }
        $query = "SELECT arc.*,tp.typedir,tp.typename,tp.isdefault,arc.money,
            tp.defaultname,tp.namerule,tp.namerule2,tp.ispart,tp.moresite,tp.siteurl,tp.sitepath
            FROM `#@__archives` arc LEFT JOIN `#@__arctype` tp ON arc.typeid=tp.id
            WHERE $orwhere $ordersql LIMIT $limitstart,$row ";
        $this->dsql->SetQuery($query);
        $this->dsql->Execute('al');
        $artlist = '';
        if($col>1)
        {
            $artlist = "<table width='$tablewidth' border='0' cellspacing='0' cellpadding='0'>\r\n";
        }
        $this->dtp2->LoadSource($innertext);
        for($i=0;$i<$row;$i++)
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

                    $row["description"] = cn_substr($row["description"],$infolen);
                    $row["title"] = cn_substr($row["title"],$titlelen);
                    $row["id"] =  $row["id"];
                    if($row['litpic'] == '-' || $row['litpic'] == '')
                    {
                        $row['litpic'] = $GLOBALS['cfg_cmspath'].'/images/cloudcms_img.jpg';
                    }
                    if(!preg_match("/^http:\/\//", $row['litpic']) && $GLOBALS['cfg_multi_site'] == 'Y')
                    {
                        $row['litpic'] = $GLOBALS['cfg_mainsite'].$row['litpic'];
                    }
                    $row['picname'] = $row['litpic'];
                    $row["arcurl"] = GetFileUrl($row["id"],$row["typeid"],$row["senddate"],$row["title"],
                    $row["ismake"],$row["arcrank"],$row["namerule"],$row["typedir"],$row["money"],$row['filename'],$row["moresite"],$row["siteurl"],$row["sitepath"]);
                    $row["typeurl"] = GetTypeUrl($row["typeid"],$row["typedir"],$row["isdefault"],$row["defaultname"],$row["ispart"],$row["namerule2"],$row["moresite"],$row["siteurl"],$row["sitepath"]);
                    $row["info"] = $row["description"];
                    $row["filename"] = $row["arcurl"];
                    $row["stime"] = GetDateMK($row["pubdate"]);
                    $row["textlink"] = "<a href='".$row["filename"]."'>".$row["title"]."</a>";
                    $row["typelink"] = "[<a href='".$row["typeurl"]."'>".$row["typename"]."</a>]";
                    $row["imglink"] = "<a href='".$row["filename"]."'><img src='".$row["picname"]."' border='0' width='$imgwidth' height='$imgheight'></a>";
                    $row["image"] = "<img src='".$row["picname"]."' border='0' width='$imgwidth' height='$imgheight'>";
                    $row['plusurl'] = $row['phpurl'] = $GLOBALS['cfg_phpurl'];
                    $row['memberurl'] = $GLOBALS['cfg_memberurl'];
                    $row['templeturl'] = $GLOBALS['cfg_templeturl'];

                    foreach($this->ChannelUnit->ChannelFields as $k=>$arr)
                    {
                        if(isset($row[$k]))
                        {
                            $row[$k] = $this->ChannelUnit->MakeField($k,$row[$k]);
                        }
                    }
                    if(is_array($this->dtp2->CTags))
                    {
                        foreach($this->dtp2->CTags as $k=>$ctag)
                        {
                            if($ctag->GetName()=='array')
                            {

                                $this->dtp2->Assign($k,$row);
                            }
                            else
                            {
                                if(isset($row[$ctag->GetName()]))
                                {
                                    $this->dtp2->Assign($k,$row[$ctag->GetName()]);
                                }
                                else
                                {
                                    $this->dtp2->Assign($k,'');
                                }
                            }
                        }
                    }
                    $artlist .= $this->dtp2->GetResult();
                }

                else
                {
                    $artlist .= "";
                }
                if($col>1)
                {
                    $artlist .= "</td>\r\n";
                }
            }

            if($col>1)
            {
                $artlist .= "</tr>\r\n";
            }
        }

        if($col>1)
        {
            $artlist .= "</table>\r\n";
        }
        $this->dsql->FreeResult("al");
        return $artlist;
    }

    function GetPageListST($list_len)
    {
        $prepage="";
        $nextpage="";
        $prepagenum = $this->PageNo - 1;
        $nextpagenum = $this->PageNo + 1;
        if($list_len=="" || preg_match("/[^0-9]/", $list_len))
        {
            $list_len=3;
        }
        $totalpage = ceil($this->TotalResult / $this->PageSize);
        if($totalpage <= 1 && $this->TotalResult > 0)
        {

            return "<span class=\"pageinfo\">共 <strong>1</strong>页<strong>".$this->TotalResult."</strong>条记录</span>";
        }
        if($this->TotalResult == 0)
        {
            return "<span class=\"pageinfo\">共 <strong>0</strong>页<strong>".$this->TotalResult."</strong>条记录</span>";
        }
        $purl = $this->GetCurUrl();
        $tnamerule = "spec_";

        if($this->PageNo != 1)
        {
            $prepage.="<li><a href='".$tnamerule."$prepagenum".$GLOBALS['art_shortname']."'>上一页</a></li>\r\n";
            $indexpage="<li><a href='".$tnamerule."1".$GLOBALS['art_shortname']."'>首页</a></li>\r\n";
        }
        else
        {
            $indexpage="<li><a>首页</a></li>\r\n";
        }
        if($this->PageNo!=$totalpage && $totalpage>1)
        {
            $nextpage.="<li><a href='".$tnamerule."$nextpagenum".$GLOBALS['art_shortname']."'>下一页</a></li>\r\n";
            $endpage="<li><a href='".$tnamerule."$totalpage".$GLOBALS['art_shortname']."'>末页</a></li>\r\n";
        }
        else
        {
            $endpage="<li><a>末页</a></li>\r\n";
        }

        $listdd="";
        $total_list = $list_len * 2 + 1;
        if($this->PageNo >= $total_list)
        {
            $j = $this->PageNo - $list_len;
            $total_list = $this->PageNo + $list_len;
            if($total_list > $totalpage)
            {
                $total_list = $totalpage;
            }
        }
        else
        {
            $j=1;
            if($total_list > $totalpage)
            {
                $total_list = $totalpage;
            }
        }
        for($j; $j<=$total_list; $j++)
        {
            if($j == $this->PageNo)
            {
                $listdd.= "<li class=\"thisclass\"><a>$j</a></li>\r\n";
            }
            else
            {
                $listdd.="<li><a href='".$tnamerule."$j".$GLOBALS['art_shortname']."'>".$j."</a></li>\r\n";
            }
        }
        $plist = $indexpage.$prepage.$listdd.$nextpage.$endpage;
        return $plist;
    }

    function GetPageListDM($list_len)
    {
        $prepage="";
        $nextpage="";
        $prepagenum = $this->PageNo - 1;
        $nextpagenum = $this->PageNo + 1;
        if($list_len=="" || preg_match("/[^0-9]/", $list_len))
        {
            $list_len=3;
        }
        $totalpage = ceil($this->TotalResult / $this->PageSize);
        if($totalpage <= 1 && $this->TotalResult > 0)
        {
            return "<span class=\"pageinfo\">共1页/".$this->TotalResult."条记录</span>";
        }
        if($this->TotalResult == 0)
        {
            return "<span class=\"pageinfo\">共0页/".$this->TotalResult."条记录</span>";
        }

        $purl = $this->GetCurUrl();
        $geturl = "typeid=".$this->TypeID."&TotalResult=".$this->TotalResult."&";
        $hidenform = "<input type='hidden' name='typeid' value='".$this->TypeID."'>\r\n";
        $hidenform .= "<input type='hidden' name='TotalResult' value='".$this->TotalResult."'>\r\n";
        $purl .= "?".$geturl;

        if($this->PageNo != 1)
        {
            $prepage.="<li><a href='".$purl."PageNo=$prepagenum'>上一页</a></li>\r\n";
            $indexpage="<li><a href='".$purl."PageNo=1'>首页</a></li>\r\n";
        }
        else
        {
            $indexpage="<li><a>首页</a></li>\r\n";
        }
        if($this->PageNo != $totalpage && $totalpage>1)
        {
            $nextpage.="<li><a href='".$purl."PageNo=$nextpagenum'>下一页</a></li>\r\n";
            $endpage="<li><a href='".$purl."PageNo=$totalpage'>末页</a></li>\r\n";
        }
        else
        {
            $endpage="<li><a>末页</a></li>";
        }

        $listdd = "";
        $total_list = $list_len * 2 + 1;
        if($this->PageNo >= $total_list)
        {
            $j = $this->PageNo - $list_len;
            $total_list = $this->PageNo + $list_len;
            if($total_list > $totalpage)
            {
                $total_list = $totalpage;
            }
        }
        else
        {
            $j=1;
            if($total_list > $totalpage)
            {
                $total_list = $totalpage;
            }
        }
        for($j; $j<=$total_list; $j++)
        {
            if($j == $this->PageNo)
            {
                $listdd.= "<li class=\"thisclass\"><a>$j</a></li>\r\n";
            }
            else
            {
                $listdd.="<li><a href='".$purl."PageNo=$j'>".$j."</a></li>\r\n";
            }
        }

        $plist = $indexpage.$prepage.$listdd.$nextpage.$endpage;
        return $plist;
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