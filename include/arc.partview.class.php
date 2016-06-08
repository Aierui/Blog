<?php   if(!defined('DEDEINC')) exit('Request Error!');

require_once(DEDEINC.'/channelunit.class.php');
require_once(DEDEINC.'/typelink.class.php');
require_once(DEDEINC.'/ftp.class.php');

class PartView
{
    var $dsql;
    var $dtp;
    var $TypeID;
    var $Fields;
    var $TypeLink;
    var $pvCopy;
    var $refObj;
    var $ftp;
    var $remoteDir;

    function __construct($typeid=0,$needtypelink=TRUE)
    {
        global $_sys_globals,$ftp;
        $this->TypeID = $typeid;
        $this->dsql = $GLOBALS['dsql'];
        $this->dtp = new DedeTagParse();
        $this->dtp->SetNameSpace("dede","{","}");
        $this->dtp->SetRefObj($this);
        $this->ftp = &$ftp;
        $this->remoteDir = '';

        if($needtypelink)
        {
            $this->TypeLink = new TypeLink($typeid);
            if(is_array($this->TypeLink->TypeInfos))
            {
                foreach($this->TypeLink->TypeInfos as $k=>$v)
                {
                    if(preg_match("/[^0-9]/", $k))
                    {
                        $this->Fields[$k] = $v;
                    }
                }
            }
            $_sys_globals['curfile'] = 'partview';
            $_sys_globals['typename'] = $this->Fields['typename'];

            SetSysEnv($this->TypeID,$this->Fields['typename'],0,'','partview');
        }
        SetSysEnv($this->TypeID,'',0,'','partview');
        $this->Fields['typeid'] = $this->TypeID;

        foreach($GLOBALS['PubFields'] as $k=>$v)
        {
            $this->Fields[$k] = $v;
        }
    }

    function PartView($typeid=0,$needtypelink=TRUE)
    {
        $this->__construct($typeid,$needtypelink);
    }

    function SetRefObj(&$refObj)
    {
        $this->dtp->SetRefObj($refObj);
        if(isset($refObj->TypeID))
        {
            $this->__construct($refObj->TypeID);
        }
    }

    function SetTypeLink(&$typelink)
    {
        $this->TypeLink = $typelink;
        if(is_array($this->TypeLink->TypeInfos))
        {
            foreach($this->TypeLink->TypeInfos as $k=>$v)
            {
                if(preg_match("/[^0-9]/", $k))
                {
                    $this->Fields[$k] = $v;
                }
            }
        }
    }

    function SetTemplet($temp,$stype="file")
    {
        if($stype=="string")
        {
            $this->dtp->LoadSource($temp);
        }
        else
        {
            $this->dtp->LoadTemplet($temp);
        }
        if($this->TypeID > 0)
        {
            $this->Fields['position'] = $this->TypeLink->GetPositionLink(TRUE);
            $this->Fields['title'] = $this->TypeLink->GetPositionLink(false);
        }
        $this->ParseTemplet();
    }

    function Display()
    {
        $this->dtp->Display();
    }

    function GetResult()
    {
        return $this->dtp->GetResult();
    }

    function SaveToHtml($filename,$isremote=0)
    {
        global $cfg_remote_site;

        if($cfg_remote_site=='Y' && $isremote == 1)
        {

            $remotefile = str_replace(DEDEROOT, '', $filename);
            $localfile = '..'.$remotefile;

            $remotedir = preg_replace('/[^\/]*\.js/', '', $remotefile);
            $this->ftp->rmkdir($remotedir);
            $this->ftp->upload($localfile, $remotefile, 'ascii');
        }
        $this->dtp->SaveTo($filename);
    }

    function ParseTemplet()
    {
        $GLOBALS['envs']['typeid'] = $this->TypeID;
        if($this->TypeID>0)
        {
            $GLOBALS['envs']['topid'] = GetTopid($this->TypeID);
        }
        else 
        {
            $GLOBALS['envs']['topid'] = 0;
        }
        if(isset($this->TypeLink->TypeInfos['reid']))
        {
            $GLOBALS['envs']['reid'] = $this->TypeLink->TypeInfos['reid'];
        }
        if(isset($this->TypeLink->TypeInfos['channeltype']))
        {
          $GLOBALS['envs']['channelid'] = $this->TypeLink->TypeInfos['channeltype'];
        }
        MakeOneTag($this->dtp,$this);
    }
    function GetArcList($templets='',$typeid=0,$row=10,$col=1,$titlelen=30,$infolen=160,
    $imgwidth=120,$imgheight=90,$listtype="all",$orderby="default",$keyword="",$innertext="",
    $tablewidth="100",$arcid=0,$idlist="",$channelid=0,$limit="",$att=0,$order='desc',$subday=0,
    $autopartid=-1,$ismember=0,$maintable='',$ctag='')
    {
        if(empty($autopartid))
        {
            $autopartid = -1;
        }
        if(empty($typeid))
        {
            $typeid=$this->TypeID;
        }
        if($autopartid!=-1)
        {
            $typeid = $this->GetAutoChannelID($autopartid,$typeid);
            if($typeid==0)
            {
                return "";
            }
        }

        if(!isset($GLOBALS['__SpGetArcList']))
        {
            require_once(dirname(__FILE__)."/inc/inc_fun_SpGetArcList.php");
        }
        return SpGetArcList($this->dsql,$templets,$typeid,$row,$col,$titlelen,$infolen,$imgwidth,$imgheight,
        $listtype,$orderby,$keyword,$innertext,$tablewidth,$arcid,$idlist,$channelid,$limit,$att,
        $order,$subday,$ismember,$maintable,$ctag);
    }

    function Close()
    {
    }
}