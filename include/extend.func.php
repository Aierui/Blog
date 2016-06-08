<?php
function litimgurls($imgid=0)
{
    global $lit_imglist,$dsql;

    $row = $dsql->GetOne("SELECT c.addtable FROM #@__archives AS a LEFT JOIN #@__channeltype AS c 
                                                            ON a.channel=c.id where a.id='$imgid'");
    $addtable = trim($row['addtable']);

    $row = $dsql->GetOne("Select imgurls From `$addtable` where aid='$imgid'");

    $ChannelUnit = new ChannelUnit(2,$imgid);

    $lit_imglist = $ChannelUnit->GetlitImgLinks($row['imgurls']);

    return $lit_imglist;
}