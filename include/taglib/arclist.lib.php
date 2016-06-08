<?php   if(!defined('DEDEINC')) exit('Request Error!');

function lib_arclist( &$ctag, &$refObj )
{
    global $envs;

    $autopartid = 0;
    $tagid = '';
    $tagname = $ctag->GetTagName();
    $channelid = $ctag->GetAtt('channelid');

    $pagesize = $ctag->GetAtt('pagesize');
    if($pagesize == '')
    {
        $multi = 0;
    } else {
        $tagid = $ctag->GetAtt('tagid');
    }

    $isweight = $ctag->GetAtt('isweight');

    if($tagname=='imglist' || $tagname=='imginfolist') {
        $listtype = 'image';
    }
    else if($tagname=='specart') {
        $channelid = -1;
        $listtype='';
    }
    else if($tagname=='coolart') {
        $listtype = 'commend';
    }
    else if($tagname=='autolist') {
        $autopartid = $ctag->GetAtt('partsort');
    }
    else {
        $listtype = $ctag->GetAtt('type');
    }

    if($ctag->GetAtt('sort')!='') $orderby = $ctag->GetAtt('sort');
    else if($tagname=='hotart') $orderby = 'click';
    else $orderby = $ctag->GetAtt('orderby');

    if(trim($ctag->GetInnerText()) != '') $innertext = $ctag->GetInnerText();
    else if($tagname=='imglist') $innertext = GetSysTemplets('part_imglist.htm');
    else if($tagname=='imginfolist') $innertext = GetSysTemplets('part_imginfolist.htm');
    else $innertext = GetSysTemplets("part_arclist.htm");

    if($ctag->GetAtt('titlelength')!='') $titlelen = $ctag->GetAtt('titlelength');
    else $titlelen = $ctag->GetAtt('titlelen');

    if($ctag->GetAtt('infolength')!='') $infolen = $ctag->GetAtt('infolength');
    else $infolen = $ctag->GetAtt('infolen');

    $typeid = trim($ctag->GetAtt('typeid'));
    if(empty($typeid)) {
        $typeid = ( isset($refObj->Fields['typeid']) ? $refObj->Fields['typeid'] : $envs['typeid'] );
    }

    if($listtype=='autolist') {
        $typeid = lib_GetAutoChannelID($ctag->GetAtt('partsort'),$typeid);
    }

    if($ctag->GetAtt('att')=='') {
        $flag = $ctag->GetAtt('flag');
    }
    else {
        $flag = $ctag->GetAtt('att');
    }

    return lib_arclistDone
           (
             $refObj, $ctag, $typeid, $ctag->GetAtt('row'), $ctag->GetAtt('col'), $titlelen, $infolen,
             $ctag->GetAtt('imgwidth'), $ctag->GetAtt('imgheight'), $listtype, $orderby,
             $ctag->GetAtt('keyword'), $innertext, $envs['aid'], $ctag->GetAtt('idlist'), $channelid,
             $ctag->GetAtt('limit'), $flag,$ctag->GetAtt('orderway'), $ctag->GetAtt('subday'), $ctag->GetAtt('noflag'),
             $tagid,$pagesize,$isweight
           );
}

function lib_arclistDone(&$refObj, &$ctag, $typeid=0, $row=10, $col=1, $titlelen=30, $infolen=160,
        $imgwidth=120, $imgheight=90, $listtype='all', $orderby='default', $keyword='',
        $innertext='', $arcid=0, $idlist='', $channelid=0, $limit='', $att='', $order='desc', $subday=0, $noflag='',$tagid='', $pagesize=0, $isweight='N')
{
    global $dsql,$PubFields,$cfg_keyword_like,$cfg_index_cache,$_arclistEnv,$envs,$cfg_cache_type,$cfg_digg_update;
    $row = AttDef($row,10);
    $titlelen = AttDef($titlelen,30);
    $infolen = AttDef($infolen,160);
    $imgwidth = AttDef($imgwidth,120);
    $imgheight = AttDef($imgheight,120);
    $listtype = AttDef($listtype,'all');
    $arcid = AttDef($arcid,0);
    $channelid = AttDef($channelid,0);
    $orderby = AttDef($orderby,'default');
    $orderWay = AttDef($order,'desc');
    $subday = AttDef($subday,0);
    $pagesize = AttDef($pagesize,0);
    $line = $row;
    $orderby = strtolower($orderby);
    $keyword = trim($keyword);
    $innertext = trim($innertext);

    $tablewidth = $ctag->GetAtt('tablewidth');
    $writer = $ctag->GetAtt('writer');
    if($tablewidth == "") $tablewidth = 100;
    if(empty($col)) $col = 1;
    $colWidth = ceil(100/$col);
    $tablewidth = $tablewidth."%";
    $colWidth = $colWidth."%";

    $attarray = compact("row", "titlelen", 'infolen', 'imgwidth', 'imgheight', 'listtype',
     'arcid', 'channelid', 'orderby', 'orderWay', 'subday','pagesize',
      'orderby', 'keyword', 'tablewidth', 'col', 'colWidth');

    if($innertext=='') $innertext = GetSysTemplets('part_arclist.htm');
    if( @$ctag->GetAtt('getall') == 1 ) $getall = 1;
    else $getall = 0;

    if($att=='0') $att='';
    if($att=='3') $att='f';
    if($att=='1') $att='h';

    $orwheres = array();
    $maintable = '#@__archives';

    if($idlist=='')
    {
        if($orderby=='near' && $cfg_keyword_like=='N') { $keyword=''; }

        if($writer=='this') {
            $wmid =  isset($refObj->Fields['mid']) ? $refObj->Fields['mid'] : 0;
            $orwheres[] = " arc.mid = '$wmid' ";
        }

        if($subday > 0)
        {
            $ntime = gmmktime(0, 0, 0, gmdate('m'), gmdate('d'), gmdate('Y'));
            $limitday = $ntime - ($subday * 24 * 3600);
            $orwheres[] = " arc.senddate > $limitday ";
        }

        if($keyword!='')
        {
            $keyword = str_replace(',', '|', $keyword);
            $orwheres[] = " CONCAT(arc.title,arc.keywords) REGEXP '$keyword' ";
        }

        if(preg_match('/commend/i', $listtype)) $orwheres[] = " FIND_IN_SET('c', arc.flag)>0  ";
        if(preg_match('/image/i', $listtype)) $orwheres[] = " FIND_IN_SET('p', arc.flag)>0  ";
        if($att != '') {
            $flags = explode(',', $att);
            for($i=0; isset($flags[$i]); $i++) $orwheres[] = " FIND_IN_SET('{$flags[$i]}', arc.flag)>0 ";
        }

        if(!empty($typeid) && $typeid != 'top')
        {

            if( preg_match('#,#', $typeid) )
            {

                if($getall==1 || empty($refObj->Fields['typeid']))
                {
                    $typeids = explode(',', $typeid);
                    foreach($typeids as $ttid) {
                        $typeidss[] = GetSonIds($ttid);
                    }
                    $typeidStr = join(',', $typeidss);
                    $typeidss = explode(',', $typeidStr);
                    $typeidssok = array_unique($typeidss);
                    $typeid = join(',', $typeidssok);
                }
                $orwheres[] = " arc.typeid IN ($typeid) ";
            }
            else
            {

                $CrossID = '';
                if($ctag->GetAtt('cross')=='1')
                {
                    $arr = $dsql->GetOne("SELECT `id`,`topid`,`cross`,`crossid`,`ispart`,`typename` FROM `#@__arctype` WHERE id='$typeid' ");
                    if( $arr['cross']==0 || ( $arr['cross']==2 && trim($arr['crossid']=='') ) )
                    {
                        $orwheres[] = ' arc.typeid IN ('.GetSonIds($typeid).')';
                  }
                    else
                    {
                        $selquery = '';
                        if($arr['cross']==1) {
                            $selquery = "SELECT id,topid FROM `#@__arctype` WHERE typename LIKE '{$arr['typename']}' AND id<>'{$typeid}' AND topid<>'{$typeid}'  ";
                        }
                        else {
                            $arr['crossid'] = preg_replace('#[^0-9,]#', '', trim($arr['crossid']));
                            if($arr['crossid']!='') $selquery = "SELECT id,topid FROM `#@__arctype` WHERE id IN('{$arr['crossid']}') AND id<>'{$typeid}' AND topid<>'{$typeid}'  ";
                        }
                        if($selquery!='')
                        {
                            $dsql->SetQuery($selquery);
                            $dsql->Execute();
                            while($arr = $dsql->GetArray())
                            {
                                $CrossID .= ($CrossID=='' ? $arr['id'] : ','.$arr['id']);
                            }
                        }
                    }
                }
                if($CrossID=='') $orwheres[] = ' arc.typeid IN ('.GetSonIds($typeid).')';
                else $orwheres[] = ' arc.typeid IN ('.GetSonIds($typeid).','.$CrossID.')';
            }
        }

        if(preg_match('#spec#i', $listtype)) $channelid==-1;

        if(!empty($channelid)) $orwheres[] = " And arc.channel = '$channelid' ";

        if(!empty($noflag))
        {
            if(!preg_match('#,#', $noflag))
            {
                $orwheres[] = " FIND_IN_SET('$noflag', arc.flag)<1 ";
            }
            else
            {
                $noflags = explode(',', $noflag);
                foreach($noflags as $noflag) {
                    if(trim($noflag)=='') continue;
                    $orwheres[] = " FIND_IN_SET('$noflag', arc.flag)<1 ";
                }
            }
        }

        $orwheres[] = ' arc.arcrank > -1 ';

    }

    $ordersql = '';
    if($orderby=='hot' || $orderby=='click') $ordersql = " ORDER BY arc.click $orderWay";
    else if($orderby == 'sortrank' || $orderby=='pubdate') $ordersql = " ORDER BY arc.sortrank $orderWay";
    else if($orderby == 'id') $ordersql = "  ORDER BY arc.id $orderWay";
    else if($orderby == 'near') $ordersql = " ORDER BY ABS(arc.id - ".$arcid.")";
    else if($orderby == 'lastpost') $ordersql = "  ORDER BY arc.lastpost $orderWay";
    else if($orderby == 'scores') $ordersql = "  ORDER BY arc.scores $orderWay";

    else if($orderby == 'goodpost') $ordersql = " order by arc.goodpost $orderWay";
    else if($orderby == 'badpost') $ordersql = " order by arc.badpost $orderWay";
    else if($orderby == 'rand') $ordersql = "  ORDER BY rand()";
    else $ordersql = " ORDER BY arc.sortrank $orderWay"; 

    $limit = trim(preg_replace('#limit#is', '', $limit));
    if($limit!='')
    {    
        $limitsql = " LIMIT $limit ";
        $limitarr = explode(',', $limit);
        $line = isset($limitarr[1])? $limitarr[1] : $line;
    }
    else $limitsql = " LIMIT 0,$line ";
    
    $orwhere = '';
    if(isset($orwheres[0])) {
        $orwhere = join(' And ',$orwheres);
        $orwhere = preg_replace("#^ And#is", '', $orwhere);
        $orwhere = preg_replace("#And[ ]{1,}And#is", 'And ', $orwhere);
    }
    if($orwhere!='') $orwhere = " WHERE $orwhere ";

    $addfield = trim($ctag->GetAtt('addfields'));
    $addfieldsSql = '';
    $addfieldsSqlJoin = '';
    if($addfield != '' && !empty($channelid))
    {
        $row = $dsql->GetOne("SELECT addtable FROM `#@__channeltype` WHERE id='$channelid' ");
        if(isset($row['addtable']) && trim($row['addtable']) != '')
        {
            $addtable = trim($row['addtable']);
            $addfields = explode(',', $addfield);
            $row['addtable'] = trim($row['addtable']);
            $addfieldsSql = ",addf.".join(',addf.', $addfields);
            $addfieldsSqlJoin = " LEFT JOIN `$addtable` addf ON addf.aid = arc.id ";
        }
    }

    $query = "SELECT arc.*,tp.typedir,tp.typename,tp.corank,tp.isdefault,tp.defaultname,tp.namerule,
        tp.namerule2,tp.ispart,tp.moresite,tp.siteurl,tp.sitepath
        $addfieldsSql
        FROM `$maintable` arc LEFT JOIN `#@__arctype` tp on arc.typeid=tp.id
        $addfieldsSqlJoin
        $orwhere $ordersql $limitsql";

    $taghash = md5(serialize($ctag).$typeid);
    $needSaveCache = true;

    if($pagesize > 0) $tagid = AttDef($tagid,'tag'.$taghash );
    
    if($idlist!='' || $GLOBALS['_arclistEnv']=='index' || $cfg_index_cache==0)
    {
        $needSaveCache = false;
    }
    else
    {
        $idlist = GetArclistCache($taghash);
        if($idlist != '') {
            $needSaveCache = false;
        }

        if($cfg_cache_type=='content' && $idlist != '')
        {
            $idlist = ($idlist==0 ? '' : $idlist);
            return $idlist;
        }
    }

    if($idlist != '')
    {
        $query = "SELECT arc.*,tp.typedir,tp.typename,tp.corank,tp.isdefault,tp.defaultname,tp.namerule,tp.namerule2,tp.ispart,
            tp.moresite,tp.siteurl,tp.sitepath
            $addfieldsSql
             FROM `$maintable` arc left join `#@__arctype` tp on arc.typeid=tp.id
             $addfieldsSqlJoin
          WHERE arc.id in($idlist) $ordersql ";
    }

	if($cfg_digg_update > 0)
	{
		if($orderby == 'goodpost' || $orderby == 'badpost')
		{
			$t1 = ExecTime();
			$postsql = "SELECT arc.id,arc.goodpost,arc.badpost,arc.scores
				FROM `$maintable` arc
				$orwhere $ordersql $limitsql";
				
			if($idlist != '')
			{
				$postsql = "SELECT arc.id,arc.goodpost,arc.badpost,arc.scores
					 FROM `$maintable` arc 
				  WHERE arc.id in($idlist) $ordersql ";
			}
			$dsql->SetQuery($query);
			$dsql->Execute('lit');
			while ($row = $dsql->GetArray('lit')) {
				$prefix = 'diggCache';
				$key = 'aid-'.$row['id'];
				$cacherow = GetCache($prefix, $key);
				$setsql = array();
				if(!empty($cacherow['scores']) && $cacherow['scores'] != $row['scores'])
				{
					$setsql[] = "scores = {$cacherow['scores']}";
				}
				if(!empty($cacherow['goodpost']) && $cacherow['goodpost'] != $row['goodpost'])
				{
					$setsql[] = "goodpost = {$cacherow['goodpost']}";
				}
				if(!empty($cacherow['badpost']) && $cacherow['badpost'] != $row['badpost'])
				{
					$setsql[] = "badpost = {$cacherow['badpost']}";
				}
				$setsql = implode(',', $setsql);
				$sql = "UPDATE `$maintable` SET {$setsql} WHERE id='{$row['id']}'";
				if(!empty($setsql))
				{
					$dsql->ExecuteNoneQuery($sql);
				}
			}

		}
	}
	
    $dsql->SetQuery($query);
    $dsql->Execute('al');

    $artlist = '';
    if($pagesize > 0)  $artlist .= "    <div id='{$tagid}'>\r\n";
    if($col > 1) $artlist = "<table width='$tablewidth' border='0' cellspacing='0' cellpadding='0'>\r\n";
    $dtp2 = new DedeTagParse();
    $dtp2->SetNameSpace('field', '[', ']');
    $dtp2->LoadString($innertext);
    $GLOBALS['autoindex'] = 0;
    $ids = array();
    $orderWeight = array();
    
    for($i=0; $i<$line; $i++)
    {
        if($col>1) $artlist .= "<tr>\r\n";
        for($j=0; $j<$col; $j++)
        {
            if($col>1) $artlist .= "    <td width='$colWidth'>\r\n";
            if($row = $dsql->GetArray("al"))
            {
                $ids[] = $row['id'];

                $row['info'] = $row['infos'] = cn_substr($row['description'],$infolen);
                $row['id'] =  $row['id'];

                if($row['corank'] > 0 && $row['arcrank']==0)
                {
                    $row['arcrank'] = $row['corank'];
                }

                $row['filename'] = $row['arcurl'] = GetFileUrl($row['id'],$row['typeid'],$row['senddate'],$row['title'],$row['ismake'],
                $row['arcrank'],$row['namerule'],$row['typedir'],$row['money'],$row['filename'],$row['moresite'],$row['siteurl'],$row['sitepath']);

                $row['typeurl'] = GetTypeUrl($row['typeid'],$row['typedir'],$row['isdefault'],$row['defaultname'],$row['ispart'],
                $row['namerule2'],$row['moresite'],$row['siteurl'],$row['sitepath']);

                if($row['litpic'] == '-' || $row['litpic'] == '')
                {
                    $row['litpic'] = $GLOBALS['cfg_cmspath'].'/images/cloudcms_img.jpg';
                }
                if(!preg_match("#^http:\/\/#i", $row['litpic']) && $GLOBALS['cfg_multi_site'] == 'Y')
                {
                    $row['litpic'] = $GLOBALS['cfg_mainsite'].$row['litpic'];
                }
                $row['picname'] = $row['litpic'];
                $row['stime'] = GetDateMK($row['pubdate']);
                $row['typelink'] = "<a href='".$row['typeurl']."'>".$row['typename']."</a>";
                $row['image'] = "<img src='".$row['picname']."' border='0' width='$imgwidth' height='$imgheight' alt='".preg_replace("#['><]#", "", $row['title'])."'>";
                $row['imglink'] = "<a href='".$row['filename']."'>".$row['image']."</a>";
                $row['fulltitle'] = $row['title'];
                $row['title'] = cn_substr($row['title'], $titlelen);
                if($row['color']!='') $row['title'] = "<font color='".$row['color']."'>".$row['title']."</font>";
                if(preg_match('#b#', $row['flag'])) $row['title'] = "<strong>".$row['title']."</strong>";

                $row['textlink'] = "<a href='".$row['filename']."'>".$row['title']."</a>";

                $row['plusurl'] = $row['phpurl'] = $GLOBALS['cfg_phpurl'];
                $row['memberurl'] = $GLOBALS['cfg_memberurl'];
                $row['templeturl'] = $GLOBALS['cfg_templeturl'];

                if(is_array($dtp2->CTags))
                {
                    foreach($dtp2->CTags as $k=>$ctag)
                    {
                        if($ctag->GetName()=='array')
                        {

                            $dtp2->Assign($k, $row);
                        } else {
                            if(isset($row[$ctag->GetName()])) $dtp2->Assign($k,$row[$ctag->GetName()]);
                            else $dtp2->Assign($k, '');
                        }
                    }
                    $GLOBALS['autoindex']++;
                }
                if($pagesize > 0)
                {
                    if($GLOBALS['autoindex'] <= $pagesize)
                    {
                        $liststr = $dtp2->GetResult();
                        $artlist .= $liststr."\r\n";
                    } else {
                        $artlist .= "";
                        $orderWeight[] = array(
                            'weight'  => $row['weight'],
                            'arclist' => ''
                        );
                    }
                } else {
                    $liststr = $dtp2->GetResult();
                    $artlist .= $liststr."\r\n";
                }
                $orderWeight[] = array(
                    'weight'  => $row['weight'],
                    'arclist' => $liststr
                );
            }
            else{
                $artlist .= '';
            }

            $isweight = strtolower($isweight);
            if ( $isweight=='y' )
            {
                $artlist = '';
                $orderWeight = list_sort_by($orderWeight, 'weight', 'asc');
                
                foreach ($orderWeight as $vv) {
                   $artlist .= $vv['arclist'];
                }
            }
            if($col>1) $artlist .= "    </td>\r\n";
        }
        if($col>1) $i += $col - 1;
        if($col>1) $artlist .= "    </tr>\r\n";
    }
    if($col>1) $artlist .= "    </table>\r\n";
    $dsql->FreeResult("al");
    $idsstr = join(',', $ids);

    if($pagesize > 0)
    {
        $artlist .= "    </div>\r\n";
      $row = $dsql->GetOne("SELECT tagid FROM #@__arcmulti WHERE tagid='$tagid'");
      $uptime = time();
      $attstr = addslashes(serialize($attarray));
      $innertext = addslashes($innertext);
      if(!is_array($row))
      {
        $query = "
          INSERT INTO #@__arcmulti(tagid,uptime,innertext,pagesize,arcids,ordersql,addfieldsSql,addfieldsSqlJoin,attstr)
          VALUES('$tagid','$uptime','$innertext','$pagesize','$idsstr','$ordersql','$addfieldsSql','$addfieldsSqlJoin','$attstr');
        ";
        $dsql->ExecuteNoneQuery($query);
      } else {
        $query = "UPDATE `#@__arcmulti`
           SET
           uptime='$uptime',
           innertext='$innertext',
           pagesize='$pagesize',
           arcids='$idsstr',
           ordersql='$ordersql',
           addfieldsSql='$addfieldsSql',
           addfieldsSqlJoin='$addfieldsSqlJoin',
           attstr='$attstr'
           WHERE tagid='$tagid'
        ";
        $dsql->ExecuteNoneQuery($query);
      }
    }

    if($needSaveCache)
    {
        if($idsstr=='') $idsstr = '0';
        if($cfg_cache_type=='content' && $idsstr!='0') {
            $idsstr = addslashes($artlist);
        }
        $inquery = "INSERT INTO `#@__arccache`(`md5hash`,`uptime`,`cachedata`) VALUES ('".$taghash."','".time()."', '$idsstr'); ";
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__arccache` WHERE md5hash='".$taghash."' ");
        $dsql->ExecuteNoneQuery($inquery);
    }
    return $artlist;
}

function GetArclistCache($md5hash)
{
    global $dsql,$envs,$cfg_makesign_cache,$cfg_index_cache,$cfg_cache_type;
    if($cfg_index_cache <= 0) return '';
    if(isset($envs['makesign']) && $cfg_makesign_cache=='N') return '';
    $mintime = time() - $cfg_index_cache;
    $arr = $dsql->GetOne("SELECT cachedata,uptime FROM `#@__arccache` WHERE md5hash = '$md5hash' ");
    if(!is_array($arr)) {
        return '';
    }
    else if($arr['uptime'] < $mintime) {
        return '';
    }
    else {
        return $arr['cachedata'];
    }
}

function lib_GetAutoChannelID($sortid, $topid)
{
    global $dsql;
    if(empty($sortid)) $sortid = 1;
    $getstart = $sortid - 1;
    $row = $dsql->GetOne("SELECT id,typename FROM #@__arctype WHERE reid='{$topid}' And ispart<2 And ishidden<>'1' ORDER BY sortrank asc limit $getstart,1");
    if(!is_array($row)) return 0;
    else return $row['id'];
}

function list_sort_by($list, $field, $sortby='asc') {
   if(is_array($list)){
       $refer = $resultSet = array();
       foreach ($list as $i => $data)
           $refer[$i] = &$data[$field];
       switch ($sortby) {
           case 'asc':
                asort($refer);
                break;
           case 'desc':
                arsort($refer);
                break;
           case 'nat':
                natcasesort($refer);
                break;
       }
       foreach ( $refer as $key=> $val)
           $resultSet[] = &$list[$key];
       return $resultSet;
   }
   return false;
}