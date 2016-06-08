<?php   if(!defined('DEDEINC')) exit('Request Error!');
function lib_arcpagelist(&$ctag, &$refObj)
{
    global $dsql;
    $attlist = "tagid|,style|1";
    FillAttsDefault($ctag->CAttribute->Items,$attlist);
    extract($ctag->CAttribute->Items, EXTR_SKIP);
    
    $row = $dsql->GetOne("SELECT * FROM #@__arcmulti WHERE tagid='$tagid'");
    if(is_array($row))
    {
      $ids = explode(',', $row['arcids']);
    
      $totalnum = count($ids);
      $pagestr = '<div id="page_'.$tagid.'">';
      if($row['pagesize'] < $totalnum)
      {
        $pagestr .= multipage($totalnum, 1, $row['pagesize'], $tagid);
      } else {
          $pagestr .= '共1页';
      }
      $pagestr .= '</div>';
      return $pagestr;
    } else {
      $pagestr = '<div id="page_'.$tagid.'">';
      $pagestr .= '没有检索到对应分页';
      $pagestr .= '</div>';
        return $pagestr;
    }
}

function multipage($allItemTotal, $currPageNum, $pageSize, $tagid='')
{
    if ($allItemTotal == 0) return "";

    $pagesNum = ceil($allItemTotal/$pageSize);

    $firstPage = ($currPageNum <= 1) ? $currPageNum ."</b>&lt;&lt;" : "<a href='javascript:multi(1,\"{$tagid}\")' title='第1页'>1&lt;&lt;</a>";

    $lastPage = ($currPageNum >= $pagesNum)? "&gt;&gt;". $currPageNum : "<a href='javascript:multi(". $pagesNum . ",\"{$tagid}\")' title='第". $pagesNum ."页'>&gt;&gt;". $pagesNum ."</a>";

    $prePage  = ($currPageNum <= 1) ? "上页" : "<a href='javascript:multi(". ($currPageNum-1) . ",\"{$tagid}\")'  accesskey='p'  title='上一页'>[上一页]</a>";

    $nextPage = ($currPageNum >= $pagesNum) ? "下页" : "<a href='javascript:multi(". ($currPageNum+1) .",\"{$tagid}\")' title='下一页'>[下一页]</a>";

    $listNums = "";
    for ($i=($currPageNum-4); $i<($currPageNum+9); $i++) {
        if ($i < 1 || $i > $pagesNum) continue;
        if ($i == $currPageNum) $listNums.= "<a href='javascript:void(0)' class='thislink'>".$i."</a>";
        else $listNums.= " <a href='javascript:multi(". $i .",\"{$tagid}\")' title='". $i ."'>". $i ."</a> ";
    }

    $returnUrl = $listNums;
    return $returnUrl;
}