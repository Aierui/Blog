<?php   if(!defined('DEDEINC')) exit("Request Error!");

require_once(DEDEINC."/arc.partview.class.php");

class sgpage
{
    var $dsql;
    var $dtp;
    var $TypeID;
    var $Fields;
    var $TypeLink;
    var $partView;

    function __construct($aid)
    {
        global $cfg_basedir,$cfg_templets_dir,$cfg_df_style,$envs;

        $this->dsql = $GLOBALS['dsql'];
        $this->dtp = new DedeTagParse();
        $this->dtp->refObj = $this;
        $this->dtp->SetNameSpace("dede","{","}");
        $this->Fields = $this->dsql->GetOne("SELECT * FROM `#@__sgpage` WHERE aid='$aid' ");
        $envs['aid'] = $this->Fields['aid'];

        foreach($GLOBALS['PubFields'] as $k=>$v)
        {
            $this->Fields[$k] = $v;
        }
        if($this->Fields['ismake']==1)
        {
            $pv = new PartView();
            $pv->SetTemplet($this->Fields['body'],'string');
            $this->Fields['body'] = $pv->GetResult();
        }
        $tplfile = $cfg_basedir.str_replace('{style}',$cfg_templets_dir.'/'.$cfg_df_style,$this->Fields['template']);
        $this->dtp->LoadTemplate($tplfile);
        $this->ParseTemplet();
    }

    function sgpage($aid)
    {
        $this->__construct($aid);
    }

    function Display()
    {
        $this->dtp->Display();
    }

    function GetResult()
    {
        return $this->dtp->GetResult();
    }

    function SaveToHtml()
    {
        $filename = $GLOBALS['cfg_basedir'].$GLOBALS['cfg_cmspath'].'/'.$this->Fields['filename'];
        $filename = preg_replace("/\/{1,}/", '/', $filename);
        $this->dtp->SaveTo($filename);
    }

    function ParseTemplet()
    {
        $GLOBALS['envs']['likeid'] = $this->Fields['likeid'];
        MakeOneTag($this->dtp,$this);
    }

    function Close()
    {
    }
}