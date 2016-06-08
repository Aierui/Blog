<?php   if(!defined('DEDEINC')) exit("Request Error!");

require_once(DEDEINC."/dedetag.class.php");

class DedeVote
{
    var $VoteInfos;
    var $VoteNotes;
    var $VoteCount;
    var $VoteID;
    var $dsql;

    function __construct($aid)
    {
        $this->dsql = $GLOBALS['dsql'];
        $this->VoteInfos = $this->dsql->GetOne("SELECT * FROM `#@__vote` WHERE aid='$aid'");
        $this->VoteNotes = Array();
        $this->VoteCount = 0;
        $this->VoteID = $aid;
        if(!is_array($this->VoteInfos))
        {
            return;
        }
        $dtp = new DedeTagParse();
        $dtp->SetNameSpace("v", "<", ">");
        $dtp->LoadSource($this->VoteInfos['votenote']);
        if(is_array($dtp->CTags))
        {
            foreach($dtp->CTags as $ctag)
            {
                $this->VoteNotes[$ctag->GetAtt('id')]['count'] = $ctag->GetAtt('count');
                $this->VoteNotes[$ctag->GetAtt('id')]['name'] = trim($ctag->GetInnerText());
                $this->VoteCount++;
            }
        }
        $dtp->Clear();
    }

    function DedeVote($aid)
    {
        $this->__construct($aid);
    }
    
    function Close()
    {
    }

    function GetTotalCount()
    {
        if(!empty($this->VoteInfos["totalcount"]))
        {
            return $this->VoteInfos["totalcount"];
        }
        else
        {
            return 0;
        }
    }

    function AddVoteCount($aid)
    {
        if(isset($this->VoteNotes[$aid]))
        {
            $this->VoteNotes[$aid]['count']++;
        }
    }

    function GetVoteForm($lineheight=30,$tablewidth="100%",$titlebgcolor="#EDEDE2",$titlebackgroup="",$tablebg="#FFFFFF",$itembgcolor="#FFFFFF")
    {

        if($lineheight=="")
        {
            $lineheight=24;
        }
        if($tablewidth=="")
        {
            $tablewidth="100%";
        }
        if($titlebgcolor=="")
        {
            $titlebgcolor="#98C6EF";
        }
        if($titlebackgroup!="")
        {
            $titlebackgroup="background='$titlebackgroup'";
        }
        if($tablebg=="")
        {
            $tablebg="#FFFFFF";
        }
        if($itembgcolor=="")
        {
            $itembgcolor="#FFFFFF";
        }
        $items = "<table width='$tablewidth' border='0' cellspacing='1' cellpadding='1' id='voteitem'>\r\n";
        $items .= "<form name='voteform' method='post' action='".$GLOBALS['cfg_phpurl']."/vote.php' target='_blank'>\r\n";
        $items .= "<input type='hidden' name='dopost' value='send' />\r\n";
        $items .= "<input type='hidden' name='aid' value='".$this->VoteID."' />\r\n";
        $items .= "<input type='hidden' name='ismore' value='".$this->VoteInfos['ismore']."' />\r\n";
        $items.="<tr align='center'><td height='$lineheight' id='votetitle' style='border-bottom:1px dashed #999999;color:#3F7652' $titlebackgroup><strong>".$this->VoteInfos['votename']."</strong></td></tr>\r\n";
        if($this->VoteCount > 0)
        {

            foreach($this->VoteNotes as $k=>$arr)
            {
                if($this->VoteInfos['ismore']==0)
                {
                    $items.="<tr><td height=$lineheight bgcolor=$itembgcolor style='color:#666666'><input type='radio' name='voteitem' value='$k' />".$arr['name']."</td></tr>\r\n";
                }
                else
                {
                    $items.="<tr><td height=$lineheight bgcolor=$itembgcolor style='color:#666666'><input type=checkbox name='voteitem[]' value='$k' />".$arr['name']."</td></tr>\r\n";
                }
            }
            $items .= "<tr><td height='$lineheight'>\r\n";
            $items .= "<input type='submit' class='btn-1' name='vbt1' value='投票' />\r\n";
            $items .= "<input type='button' class='btn-1' name='vbt2' ";
            $items .= "value='查看结果' onClick=window.open('".$GLOBALS['cfg_phpurl']."/vote.php?dopost=view&aid=".$this->VoteID."'); /></td></tr>\r\n";
        }
        $items.="</form>\r\n</table>\r\n";
        return $items;
    }

    function SaveVote($voteitem)
    {
        global $ENV_GOBACK_URL,$file,$memberID,$row,$content;
        if(empty($voteitem))
        {
            return '你没选中任何项目！';
        }
        $items = '';

        $nowtime = time();
        if($nowtime > $this->VoteInfos['endtime'])
        {

            ShowMsg('投票已经过期！',$ENV_GOBACK_URL);
            exit();
        }
        if($nowtime < $this->VoteInfos['starttime'])
        {
            ShowMsg('投票还没有开始！',$ENV_GOBACK_URL);
            exit();
        }

        if(isset($_COOKIE['VOTE_MEMBER_IP']))
        {
            if($_COOKIE['VOTE_MEMBER_IP'] == $_SERVER['REMOTE_ADDR'])
            {
                ShowMsg('您已投过票',$ENV_GOBACK_URL);
                exit();
            } else {
                setcookie('VOTE_MEMBER_IP',$_SERVER['REMOTE_ADDR'],time()*$row['spec']*3600,'/');
            }
        } else {
            setcookie('VOTE_MEMBER_IP',$_SERVER['REMOTE_ADDR'],time()*$row['spec']*3600,'/');
        }

        $nowtime = time();
        $VoteMem = $this->dsql->GetOne("SELECT * FROM #@__vote_member WHERE voteid = '$this->VoteID' and userid='$memberID'");
        if(!empty($memberID))
        {
            if(isset($VoteMem['id']))
            {
                $voteday = date("Y-m-d",$VoteMem['uptime']);
                $day = strtotime("-".$row['spec']." day");
                $day = date("Y-m-d",$day);
                if($day < $voteday)
                {
                    ShowMsg('在'.$row['spec'].'天内不能重复投票',$ENV_GOBACK_URL);
                    exit();
                }else{
                    $query = "UPDATE #@__vote_member SET uptime='$nowtime' WHERE voteid='$this->VoteID' AND userid='$memberID'";
                    if($this->dsql->ExecuteNoneQuery($query) == false)
                    {
                        ShowMsg('插入数据过程中出现错误',$ENV_GOBACK_URL);
                        exit();
                    }
                }
            }else{
                $query = "INSERT INTO #@__vote_member(id,voteid,userid,uptime) VALUES('','$this->VoteID','$memberID','$nowtime')";
                if($this->dsql->ExecuteNoneQuery($query) == false)
                {
                    ShowMsg('插入数据过程中出现错误',$ENV_GOBACK_URL);
                    exit();
                }
            }
        }

        if($this->VoteCount > 0)
        {
            foreach($this->VoteNotes as $k=>$v)
            {
                if($this->VoteInfos['ismore']==0)
                {

                    if($voteitem == $k)
                    {
                        $this->VoteNotes[$k]['count']++; break;
                    }
                }
                else
                {

                    if(is_array($voteitem) && in_array($k,$voteitem))
                    {
                        $this->VoteNotes[$k]['count']++;
                    }
                }
            }
            foreach($this->VoteNotes as $k=>$arr)
            {
                $items .= "<v:note id='$k' count='".$arr['count']."'>".$arr['name']."</v:note>\r\n";
            }
        }
        $this->dsql->ExecuteNoneQuery("UPDATE `#@__vote` SET totalcount='".($this->VoteInfos['totalcount']+1)."',votenote='".addslashes($items)."' WHERE aid='".$this->VoteID."'");
        return "投票成功！";
    }

    function GetVoteResult($tablewidth="600", $lineheight="24", $tablesplit="40%")
    {
        $totalcount = $this->VoteInfos['totalcount'];
        if($totalcount==0)
        {
            $totalcount=1;
        }
        $res = "<table width='$tablewidth' border='0' cellspacing='1' cellpadding='1'>\r\n";
        $res .= "<tr height='8'><td width='$tablesplit'></td><td></td></tr>\r\n";
        $i=1;
        foreach($this->VoteNotes as $k=>$arr)
        {
            $res .= "<tr height='$lineheight'><td style='border-bottom:1px solid'>".$i."、".$arr['name']."</td>";
            $c = $arr['count'];
            $res .= "<td style='border-bottom:1px solid'>
            <table border='0' cellspacing='0' cellpadding='2' width='".(($c/$totalcount)*100)."%'><tr><td height='16' background='img/votebg.gif' style='border:1px solid #666666;font-size:9pt;line-height:110%'>".$arr['count']."</td></tr></table>
            </td></tr>\r\n";
            $i++;
        }
        $res .= "<tr><td></td><td></td></tr>\r\n";
        $res .= "</table>\r\n";
        return $res;
    }
}