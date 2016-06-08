<?php
if(!defined('DEDEINC')) exit('Request Error!');

require_once(DEDEINC.'/channelunit.class.php');

function html2wml($content)
{

     preg_match_all("/<img([^>]*)>/isU", $content, $imgarr);
     if(isset($imgarr[0]) && count($imgarr[0])>0 )
     {
         foreach($imgarr[0] as $k=>$v) $content = str_replace($v, "WAP-IMG::{$k}", $content);
     }

     $content = preg_replace("/<style .*?<\/style>/is", "", $content);
     $content = preg_replace("/<script .*?<\/script>/is", "", $content);

     $content = preg_replace("/<br \s*\/?\/>/i", "\n", $content);
     $content = preg_replace("/<\/?p>/i", "\n", $content);
     $content = preg_replace("/<\/?td>/i", "\n", $content);
     $content = preg_replace("/<\/?div>/i", "\n", $content);
     $content = preg_replace("/<\/?blockquote>/i", "\n", $content);
     $content = preg_replace("/<\/?li>/i", "\n", $content);

     $content = preg_replace("/\&nbsp\;/i", " ", $content);
     $content = preg_replace("/\&nbsp/i", " ", $content);

     $content = strip_tags($content);

     $content = html_entity_decode($content, ENT_QUOTES, "GB2312");

     $content = preg_replace('/\&\#.*?\;/i', '', $content);

     $content = str_replace('$', '$$', $content);
     $content = str_replace("\r\n", "\n", htmlspecialchars($content));
     $content = explode("\n", $content);
     for ($i = 0; $i < count($content); $i++)
     {
        $content[$i] = trim($content[$i]);

        if (str_replace('¡¡', '', $content[$i]) == '') $content[$i] = '';
     }
     $content = str_replace("<p><br /></p>\n", "", '<p>'.implode("<br /></p>\n<p>", $content)."<br /></p>\n");

     if(isset($imgarr[0]) && count($imgarr[0])>0 )
     {
                foreach($imgarr[0] as $k=>$v)
                {
                    $attstr = (preg_match('#/$#', $imgarr[1][$k])) ? '<img '.$imgarr[1][$k].'>' : '<img '.$imgarr[1][$k].' />';
                    $content = str_replace("WAP-IMG::{$k}", $attstr, $content);
                }
     }
     
     $content = preg_replace("/&amp;[a-z]{3,10};/isU", ' ', $content);
     
     return $content;
}

function text2wml($content)
{
     $content = str_replace('$', '$$', $content);
     $content = str_replace("\r\n", "\n", htmlspecialchars($content));
     $content = explode("\n", $content);
     for ($i = 0; $i < count($content); $i++)
     {

        $content[$i] = trim($content[$i]);

        if (str_replace("¡¡", "", $content[$i]) == "") $content[$i] = "";
     }

     $content = str_replace("<p><br /></p>\n", "", "<p>".implode("<br /></p>\n<p>", $content)."<br /></p>\n");
     return $content;
}

function ConvertCharset($varlist)
{
    global $cfg_soft_lang;
    if(preg_match('#utf#i',$cfg_soft_lang)) return 0;
    $varlists = explode(',',$varlist);
    $numargs=count($varlists);
    for($i = 0; $i < $numargs; $i++)
    {   
        if(isset($GLOBALS[$varlists[$i]]))
        {
            $GLOBALS[$varlists[$i]] = gb2utf8($GLOBALS[$varlists[$i]]);
        }
    } 
    return 1;
}

function ConvertStr($str)
{
    $str = str_replace("&amp;","##amp;",$str);
    $str = str_replace("&","&amp;",$str);
    $str = preg_replace("#[\"><']#","",$str);
    $str = str_replace("##amp;","&amp;",$str);
    return $str;
}

?>