<?php
require_once (dirname(__FILE__) . "/../include/common.inc.php");
require_once DEDEINC.'/shopcar.class.php';
$cart = new MemberShops();

$do = isset($do) ? trim($do) : 'add';

if($do == 'add')
{
    $buynum = isset($buynum) && is_numeric($buynum) ? $buynum : 1;
    $id =empty($id)? "" : intval($id);
    $buynum = ($buynum < 1) ? 1 : $buynum;
    $rs = $dsql->GetOne("SELECT id,channel,title FROM #@__archives WHERE id='$id'");
    if(!is_array($rs))
    {
        ShowMsg("该商品已不存在！","-1");
        exit();
    }
    $cts = GetChannelTable($rs['channel']);
    $rows = $dsql->GetOne("SELECT aid as id,trueprice as price,units FROM `$cts[addtable]` WHERE aid='$id'");
    if(!is_array($rows))
    {
        ShowMsg("该商品已不存在！","-1");
        exit();
    }
    $rows['buynum'] = $buynum;
    $rows['title']     = $rs['title'];
    $cart->addItem($id, $rows);
    ShowMsg("已添加加到购物车,<a href='car.php'>查看购物车</a>","car.php");
    exit();
}
elseif($do == 'del')
{
    if(!isset($ids))
    {
        ShowMsg("请选择要删除的商品！","-1");
        exit;
    }
    if(is_array($ids))
    {
        foreach($ids as $id)
        {
            $id = intval($id);
            $cart->delItem($id);
        }
    }
    else
    {
        $ids = intval($ids);
        $cart->delItem($ids);
    }
    ShowMsg("已成功删除购物车中的商品,<a href='car.php'>查看购物车</a>","car.php");
    exit;
}
elseif($do == 'clear')
{
    $cart->clearItem();
    ShowMsg("购物车中商品已全部清空！","car.php");
    exit;
}
elseif($do == 'update')
{
    if(isset($ids) && is_array($ids))
    {
        foreach($ids as $id){
            $id = intval($id);
            $rs = $dsql->GetOne("SELECT id,channel,title FROM #@__archives WHERE id='$id'");
            if(!is_array($rs)) continue;
            $cts = GetChannelTable($rs['channel']);
            $rows = $dsql->GetOne("SELECT aid as id,trueprice as price,units FROM `$cts[addtable]` WHERE aid='$id'");
            if(!is_array($rows)) continue;
            $rows['buynum'] = intval(${'buynum'.$id});
            if($rows['buynum'] < 1)
            {
                $cart->delItem($id);
                continue;
            }
            $rows['title']     = $rs['title'];
            $cart->addItem($id, $rows);
        }
    }
    ShowMsg("购物车中商品已全部更新！","car.php");
    exit;
}