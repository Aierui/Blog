<?php
if(!defined('DEDEINC'))
{
    exit("Request Error!");
}

function lib_flink(&$ctag,&$refObj)
{
	
    global $dsql,$cfg_soft_lang;
    $attlist="type|textall,row|24,titlelen|24,linktype|1,typeid|0";
    FillAttsDefault($ctag->CAttribute->Items,$attlist);
    extract($ctag->CAttribute->Items, EXTR_SKIP);
    $totalrow = $row;
    $revalue = '';
    if (isset($GLOBALS['envs']['flinkid']))
    {
        $typeid = $GLOBALS['envs']['flinkid'];
    }

    $wsql = " where ischeck >= '$linktype' ";
	
    if($typeid == 0)
    {
        $wsql .= '';
    }
    else if($typeid == 999)
	{
		require (DEDEDATA.'/admin/config_update.php');
        if (!class_exists('DedeHttpDown', false)) {
            require_once(DEDEINC.'/dedehttpdown.class.php');
        }
		$del = new DedeHttpDown();
		$del->OpenUrl($linkHost);
		$linkUrl = $del->GetHtml()."flink.php?lang={$cfg_soft_lang}&site={$_SERVER['SERVER_NAME']}";
		$del->OpenUrl($linkUrl);
		$linkInfo = $del->GetHtml();
		if(!empty($linkInfo)){
			$dedelink = explode("\t", $linkInfo);
			for($i=0; $i<count($dedelink); $i++) {
				if($i%5==0 && $i!=count($dedelink)) {
					$revalue .= "<li><a href='http://".@$dedelink[$i+1]."' target='_blank' title='".@$dedelink[$i+4]."'>".@$dedelink[$i]."</a></li>";
				}
			}
		}
		return $revalue;
	}
	else
    {
        $wsql .= "And typeid = '$typeid'";
    }
    if($type=='image')
    {
        $wsql .= " And logo<>'' ";
    }
    else if($type=='text')
    {
        $wsql .= " And logo='' ";
    }

    $equery = "SELECT * FROM #@__flink $wsql order by sortrank asc limit 0,$totalrow";

    if(trim($ctag->GetInnerText())=='') $innertext = "<li>[field:link /]</li>";
    else $innertext = $ctag->GetInnerText();
    
    $dsql->SetQuery($equery);
    $dsql->Execute();
    
    while($dbrow=$dsql->GetObject())
    {
        if($type=='text'||$type=='textall')
        {
            $link = "<a href='".$dbrow->url."' target='_blank'>".cn_substr($dbrow->webname,$titlelen)."</a> ";
        }
        else if($type=='image')
        {
            $link = "<a href='".$dbrow->url."' target='_blank'><img src='".$dbrow->logo."' width='88' height='31' border='0'></a> ";
        }
        else
        {
            if($dbrow->logo=='')
            {
                $link = "<a href='".$dbrow->url."' target='_blank'>".cn_substr($dbrow->webname,$titlelen)."</a> ";
            }
            else
            {
                $link = "<a href='".$dbrow->url."' target='_blank'><img src='".$dbrow->logo."' width='88' height='31' border='0'></a> ";
            }
        }
        $rbtext = preg_replace("/\[field:url([\/\s]{0,})\]/isU", $row['url'], $innertext);
         $rbtext = preg_replace("/\[field:webname([\/\s]{0,})\]/isU", $row['webname'], $rbtext);
         $rbtext = preg_replace("/\[field:logo([\/\s]{0,})\]/isU", $row['logo'], $rbtext);
         $rbtext = preg_replace("/\[field:link([\/\s]{0,})\]/isU", $link, $rbtext);
         $revalue .= $rbtext;
    }
    return $revalue;
}