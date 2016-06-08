<?php
require_once(dirname(__FILE__)."/../include/common.inc.php");
require_once(DEDEINC."/channelunit.class.php");
if(!isset($open)) $open = 0;

if($open==0)
{
    $aid = (isset($aid) && is_numeric($aid)) ? $aid : 0;
    if($aid==0) exit(' Request Error! ');

    $arcRow = GetOneArchive($aid);
    if($arcRow['aid']=='')
    {
        ShowMsg('无法获取未知文档的信息!','-1');
        exit();
    }
    extract($arcRow, EXTR_SKIP);

    $cu = new ChannelUnit($arcRow['channel'],$aid);
    if(!is_array($cu->ChannelFields))
    {
        ShowMsg('获取文档信息失败！','-1');
        exit();
    }

    $vname = '';
    foreach($cu->ChannelFields as $k=>$v)
    {
        if($v['type']=='softlinks'){ $vname=$k; break; }
    }
    $row = $dsql->GetOne("SELECT $vname FROM `".$cu->ChannelInfos['addtable']."` WHERE aid='$aid'");

    include_once(DEDEINC.'/taglib/channel/softlinks.lib.php');
    $ctag = '';
    $downlinks = ch_softlinks($row[$vname], $ctag, $cu, '', TRUE);

    require_once(DEDETEMPLATE.'/plus/download_links_templet.htm');
    exit();
}

else if($open==1)
{
    $id = isset($id) && is_numeric($id) ? $id : 0;
    $link = base64_decode(urldecode($link));
    $hash = md5($link);
    $rs = $dsql->ExecuteNoneQuery2("UPDATE `#@__downloads` SET downloads = downloads + 1 WHERE hash='$hash' ");
    if($rs <= 0)
    {
        $query = " INSERT INTO `#@__downloads`(`hash`,`id`,`downloads`) VALUES('$hash','$id',1); ";
        $dsql->ExecNoneQuery($query);
    }
    header("location:$link");
    exit();
}

else if($open==2)
{
    $id = intval($id);
    $row = $dsql->GetOne("SELECT ch.addtable,arc.mid FROM `#@__arctiny` arc LEFT JOIN `#@__channeltype` ch ON ch.id=arc.channel WHERE arc.id='$id' ");
    if(empty($row['addtable']))
    {
        ShowMsg('找不到所需要的软件资源！', 'javascript:;');
        exit();
    }
    $mid = $row['mid'];

    $row = $dsql->GetOne("SELECT softlinks,daccess,needmoney FROM `{$row['addtable']}` WHERE aid='$id' ");
    if(empty($row['softlinks']))
    {
        ShowMsg('找不到所需要的软件资源！', 'javascript:;');
        exit();
    }
    $softconfig = $dsql->GetOne("SELECT * FROM `#@__softconfig` ");
    $needRank = $softconfig['dfrank'];
    $needMoney = $softconfig['dfywboy'];
    if($softconfig['argrange']==0)
    {
        $needRank = $row['daccess'];
        $needMoney = $row['needmoney'];
    }

    require_once(DEDEINC.'/dedetag.class.php');
    $softUrl = '';
    $islocal = 0;
    $dtp = new DedeTagParse();
    $dtp->LoadSource($row['softlinks']);
    if( !is_array($dtp->CTags) )
    {
        $dtp->Clear();
        ShowMsg('找不到所需要的软件资源！', 'javascript:;');
        exit();
    }
    foreach($dtp->CTags as $ctag)
    {
        if($ctag->GetName()=='link')
        {
            $link = trim($ctag->GetInnerText());
            $islocal = $ctag->GetAtt('islocal');

            if(!isset($firstLink) && $islocal==1) $firstLink = $link;
            if($islocal==1 && $softconfig['islocal'] != 1) continue;

            if(!preg_match("#^http:\/\/|^thunder:\/\/|^ftp:\/\/|^flashget:\/\/#i", $link))
            {
                 $link = $cfg_mainsite.$link;
            }
            $dbhash = substr(md5($link), 0, 24);
            if($uhash==$dbhash) $softUrl = $link;
        }
    }
    $dtp->Clear();
    if($softUrl=='' && $softconfig['ismoresite']==1 
    && $softconfig['moresitedo']==1 && trim($softconfig['sites'])!='' && isset($firstLink))
    {
        $firstLink = preg_replace("#http:\/\/([^\/]*)\/#i", '/', $firstLink);
        $softconfig['sites'] = preg_replace("#[\r\n]{1,}#", "\n", $softconfig['sites']);
        $sites = explode("\n", trim($softconfig['sites']));
        foreach($sites as $site)
        {
            if(trim($site)=='') continue;
            list($link, $serverName) = explode('|', $site);
            $link = trim( preg_replace("#\/$#", "", $link) ).$firstLink;
            $dbhash = substr(md5($link), 0, 24);
            if($uhash == $dbhash) $softUrl = $link;
        }
    }
    if( $softUrl == '' )
    {
        ShowMsg('找不到所需要的软件资源！', 'javascript:;');
        exit();
    }

    $arcRow = GetOneArchive($id);
    if($arcRow['aid']=='')
    {
        ShowMsg('无法获取未知文档的信息!','-1');
        exit();
    }
    extract($arcRow, EXTR_SKIP);

    if($needRank>0 || $needMoney>0)
    {
        require_once(DEDEINC.'/memberlogin.class.php');
        $cfg_ml = new MemberLogin();
        $arclink = $arcurl;
        $arctitle = $title;
        $arcLinktitle = "<a href=\"{$arcurl}\"><u>".$arctitle."</u></a>";
        $pubdate = GetDateTimeMk($pubdate);

        if(($needRank>1 && $cfg_ml->M_Rank < $needRank && $mid != $cfg_ml->M_ID))
        {
            $dsql->Execute('me' , "SELECT * FROM `#@__arcrank` ");
            while($row = $dsql->GetObject('me'))
            {
                $memberTypes[$row->rank] = $row->membername;
            }
            $memberTypes[0] = "游客";
            $msgtitle = "你没有权限下载软件：{$arctitle}！";
            $moremsg = "这个软件需要 <font color='red'>".$memberTypes[$needRank]."</font> 才能下载，你目前是：<font color='red'>".$memberTypes[$cfg_ml->M_Rank]."</font> ！";
            include_once(DEDETEMPLATE.'/plus/view_msg.htm');
            exit();
        }

        if($needMoney > 0  && $mid != $cfg_ml->M_ID)
        {
            $sql = "SELECT aid,money FROM `#@__member_operation` WHERE buyid='ARCHIVE".$id."' AND mid='".$cfg_ml->M_ID."'";
            $row = $dsql->GetOne($sql);

            if( !is_array($row) )
            {

                if( $needMoney > $cfg_ml->M_Money || $cfg_ml->M_Money=='')
                {
                    $msgtitle = "你没有权限下载软件：{$arctitle}！";
                    $moremsg = "这个软件需要 <font color='red'>".$needMoney." 金币</font> 才能下载，你目前拥有金币：<font color='red'>".$cfg_ml->M_Money." 个</font> ！";
                    include_once(DEDETEMPLATE.'/plus/view_msg.htm');
                    exit(0);
                }

                $inquery = "INSERT INTO `#@__member_operation`(mid,oldinfo,money,mtime,buyid,product,pname,sta)
                  VALUES ('".$cfg_ml->M_ID."','$arctitle','$needMoney','".time()."', 'ARCHIVE".$id."', 'archive','下载软件', 2); ";

                if( !$dsql->ExecuteNoneQuery($inquery) )
                {
                    ShowMsg('记录定单失败, 请返回', '-1');
                    exit(0);
                }

                $dsql->ExecuteNoneQuery("UPDATE `#@__member` SET money = money - $needMoney WHERE mid='".$cfg_ml->M_ID."'");
            }
        }
    }

    $hash = md5($softUrl);
    $rs = $dsql->ExecuteNoneQuery2("UPDATE `#@__downloads` SET downloads = downloads+1 WHERE hash='$hash' ");
    if($rs <= 0)
    {
        $query = " INSERT INTO `#@__downloads`(`hash`, `id`, `downloads`) VALUES('$hash', '$id', 1); ";
        $dsql->ExecNoneQuery($query);
    }
    header("location:{$softUrl}");
    exit();
}