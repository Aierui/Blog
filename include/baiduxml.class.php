<?php
if(!defined('DEDEINC')) exit('Request Error!');

class BaiduArticleXml {
	var $ChannelUnit;
	var $MainTable;
	var $Typeid;
	var $ChannelID=1;
    var $Start=0;
	var $Row=1000;
	var $TypeLink;
    var $SitemapType=2;

	function __construct()
	{
        helper('string');
		$this->ChannelUnit = new ChannelUnit(intval($this->ChannelID));
		if($this->ChannelUnit->ChannelInfos['issystem']!=-1)
		{
			$this->MainTable='#@__archives';
		} else {
			$this->MainTable=$this->ChannelUnit->ChannelInfos['addtable'];
		}
	}

    function BaiduArticleXml()
    {
        $this->__construct();
    }
    
    function setSitemapType($sitemap_type)
    {
        $this->SitemapType=$sitemap_type;
    }
    
    function getMaxAid()
    {
        global $dsql;
        $row=$dsql->GetOne("SELECT MAX(aid) AS dd FROM `#@__addonarticle`");
        $setupmaxaid = empty($row['dd'])? 0 : intval($row['dd']);
        return $setupmaxaid;
    }
    
    function setSetupMaxAid()
    {
        $setupmaxaid = $this->getMaxAid();
        baidu_set_setting('setupmaxaid', $setupmaxaid);
    }
	
    function getTotal()
    {
        global $dsql;
        $addonQuery='';
        if($this->SitemapType==2)
        {
            $setupmaxaid = baidu_get_setting('setupmaxaid');
            $addonQuery .= "AND id>".intval($setupmaxaid);
        }
        $sql="SELECT COUNT(*) AS dd FROM `{$this->MainTable}` WHERE channel=1 {$addonQuery}";
        //var_dump($setupmaxaid);
        //var_dump($sql);
        $row=$dsql->GetOne($sql);
        return empty($row['dd'])? 0 : intval($row['dd']);
    }
    
	function getType($typeid)
	{
		$this->TypeLink = new TypeLink($typeid);
		$typeinfos = $this->TypeLink->TypeInfos;
        
		$typeinfos['typelink'] = $typeinfos['typeurl'] = GetOneTypeUrlA($this->TypeLink->TypeInfos);
		$typeinfos['position'] = Html2Text($this->TypeLink->GetPositionLink(TRUE));
		return $typeinfos;
	}
	
	function toXml()
	{
		global $dsql,$cfg_webname,$cfg_basehost,$cfg_soft_lang;
		$addonQuery=$limitQuery="";
		if(!empty($this->Typeid))
		{
			$addonQuery .= "AND arc.typeid=".intval($this->Typeid);
			$typeinfos = $this->getType($this->Typeid);
		}
        if($this->SitemapType==2)
        {
            $setupmaxaid = baidu_get_setting('setupmaxaid');
            $addonQuery .= "AND arc.id>".intval($setupmaxaid);
        }
        $this->Start = intval($this->Start);

		if(!empty($this->Row)) $limitQuery = "LIMIT  {$this->Start},".intval($this->Row);
		
		$query = "SELECT arc.*,arc.senddate AS pubdate,tp.typedir,tp.typename,tp.isdefault,tp.defaultname,tp.namerule,
			tp.namerule2,tp.ispart,tp.moresite,tp.siteurl,tp.sitepath,at.body
			FROM `{$this->MainTable}` arc LEFT JOIN `#@__arctype` tp ON arc.typeid=tp.id
            LEFT JOIN `#@__addonarticle` at ON arc.id=at.aid
			WHERE arc.arcrank=0 AND arc.arcrank > -1 AND arc.channel=1 {$addonQuery} ORDER BY arc.senddate DESC {$limitQuery}";

		$dsql->SetQuery($query);
		$dsql->Execute('dd');
        
		$xmlstr = '<?xml version="1.0" encoding="UTF-8"?>
<urlset>';
        $setupmaxaid=0;
		while ($row=$dsql->GetArray('dd')) {
			$row['id'] = isset($row['aid'])? $row['aid'] : $row['id'];
			$row['filename'] = $row['arcurl'] = GetFileUrl($row['id'],$row['typeid'],$row['senddate'],$row['title'],1,
						0,$row['namerule'],$row['typedir'],0,'',$row['moresite'],$row['siteurl'],$row['sitepath']);

			$row['showdate'] = Mydate('Y-m-d', $row['pubdate']);
			$row['priority'] = 0;
            $row['body']=isset($row['body'])? Html2Text($row['body']) : '';
            $row['body'] = empty($row['body'])? $row['description'] : $row['body'];

			if (preg_match("#c#", $row['flag'])) {
				$row['priority'] = '1.0';
			}
			if(!isset($typeinfos)) $typeinfos = $this->getType($row['typeid']);

            $row['source'] = trim(Html2Text($row['source']));
            $row['title'] = baidu_strip_invalid_xml($row['title']);
            $row['body'] = baidu_strip_invalid_xml($row['body']);
			$addstr=$copyrightstr=$yearstr="";
			$copyrightstr = !empty($row['source'])? "\r\n					<copyrightHolder><name><![CDATA[{$row['source']}]]></name></copyrightHolder>" : '';
			$addstr .= empty($row['litpic'])? "" : "\r\n					<image><![CDATA[{$row['litpic']}]]></image>";
			$yearstr = Mydate('Y', $row['pubdate']);
			$rowxmlstr = <<<EOT
	\r\n	<url>
			<loc><![CDATA[{$cfg_basehost}{$row['filename']}]]></loc>
			<lastmod>{$row['showdate']}</lastmod>
			<changefreq>always</changefreq>
			<priority>{$row['priority']}</priority>
			<data>
				<Webpage>
					<name><![CDATA[{$row['title']}]]></name>
					<url><![CDATA[{$cfg_basehost}{$row['filename']}]]></url>
					<provider>
						<name><![CDATA[{$cfg_webname}]]></name>
						<url><![CDATA[{$cfg_basehost}]]></url>
					</provider>
					<description><![CDATA[{$row['description']}]]></description>
                    <text><![CDATA[{$row['body']}]]></text>
                    <keywords><![CDATA[{$row['keywords']}]]></keywords>
					<breadcrumb><![CDATA[{$typeinfos['position']}]]></breadcrumb>
					{$addstr}
                    <isPartOf>
						<name><![CDATA[{$typeinfos['typename']}]]></name>
						<url><![CDATA[{$cfg_basehost}{$typeinfos['typelink']}]]></url>
					</isPartOf>
                    {$copyrightstr}
					<copyrightYear>{$yearstr}</copyrightYear>
				</Webpage>
			</data>
		</url>
EOT;
            if($cfg_soft_lang=='gb2312') $rowxmlstr=gb2utf8($rowxmlstr);

            $xmlstr .= $rowxmlstr;
		}

		$xmlstr .= "\r\n</urlset>";

		return $xmlstr;
	}
}

?>