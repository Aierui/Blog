<?php
define("DE_ItemEcode",'Shop_De_');

class MemberShops
{
    var $OrdersId;
    var $productsId;

    function __construct()
    {
        $this->OrdersId = $this->getCookie("OrdersId");
        if(empty($this->OrdersId))
        {
            $this->OrdersId = $this->MakeOrders();
        }
    }

    function MemberShops()
    {
        $this->__construct();
    }

    function MakeOrders()
    {
        $this->OrdersId = 'S-P'.time().'RN'.mt_rand(100,999);
        $this->deCrypt($this->saveCookie("OrdersId",$this->OrdersId));
        return $this->OrdersId;
    }

    function addItem($id, $value)
    {
        $this->productsId = DE_ItemEcode.$id;
        $this->saveCookie($this->productsId,$value);
    }

    function delItem($id)
    {
        $this->productsId = DE_ItemEcode.$id;
        setcookie($this->productsId, "", time()-3600000,"/");
    }

    function clearItem()
    {
        foreach($_COOKIE as $key => $vals)
        {
            if(preg_match('/'.DE_ItemEcode.'/', $key))
            {
                setcookie($key, "", time()-3600000,"/");
            }
        }
        return 1;
    }

    function getItems()
    {
        $Products = array();
        foreach($_COOKIE as $key => $vals)
        {
            if(preg_match("#".DE_ItemEcode."#", $key) && preg_match("#[^_0-9a-z]#", $key))
            {
                parse_str($this->deCrypt($vals), $arrays);
                $values = @array_values($arrays);
                if(!empty($values))
                {
                    $arrays['price'] = sprintf("%01.2f", $arrays['price']);
                    if($arrays['buynum'] < 1)
                    {
                        $arrays['buynum'] = 0;
                    }
                    $Products[$key] = $arrays;
                }
            }
        }
        unset($key,$vals,$values,$arrays);
        return $Products;
    }

    function getOneItem($id)
    {
        $key = DE_ItemEcode.$id;
        if(!isset($_COOKIE[$key]) && empty($_COOKIE[$key]))
        {
            return '';
        }
        $itemValue = $_COOKIE[$key];
        parse_str($this->deCrypt($itemValue), $Products);
        unset($key,$itemValue);
        return $Products;
    }

    function cartCount()
    {
        $Products = $this->getItems();
        $itemsCount = count($Products);
        $i = 0;
        if($itemsCount > 0)
        {
            foreach($Products as $val)
            {
                $i = $i+$val['buynum'];
            }
        }
        unset($Products,$val,$itemsCount);
        return $i;
    }

    function priceCount()
    {
        $price = 0.00;
        foreach($_COOKIE as $key => $vals)
        {
            if(preg_match("/".DE_ItemEcode."/", $key))
            {
                $Products = $this->getOneItem(str_replace(DE_ItemEcode,"",$key));
                if($Products['buynum'] > 0 && $Products['price'] > 0)
                {
                    $price = $price + ($Products['price']*$Products['buynum']);
                }
            }
        }
        unset($key,$vals,$Products);
        return sprintf("%01.2f", $price);
    }

    function enCrypt($txt)
    {
        srand((double)microtime() * 1000000);
        $encrypt_key = md5(rand(0, 32000));
        $ctr = 0;
        $tmp = '';
        for($i = 0; $i < strlen($txt); $i++)
        {
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $tmp .= $encrypt_key[$ctr].($txt[$i] ^ $encrypt_key[$ctr++]);
        }
        return base64_encode($this->setKey($tmp));
    }

    function deCrypt($txt)
    {
        $txt = $this->setKey(base64_decode($txt));
        $tmp = '';
        for ($i = 0; $i < strlen($txt); $i++)
        {
            $tmp .= $txt[$i] ^ $txt[++$i];
        }
        return $tmp;
    }

    function setKey($txt)
    {
        global $cfg_cookie_encode;
        $encrypt_key = md5(strtolower($cfg_cookie_encode));
        $ctr = 0;
        $tmp = '';
        for($i = 0; $i < strlen($txt); $i++)
        {
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $tmp .= $txt[$i] ^ $encrypt_key[$ctr++];
        }
        return $tmp;
    }

    function enCode($array)
    {
        $arrayenc = array();
        foreach($array as $key => $val)
        {
            $arrayenc[] = $key.'='.urlencode($val);
        }
        return implode('&', $arrayenc);
    }

    function saveCookie($key,$value)
    {
        if(is_array($value))
        {
            $value = $this->enCrypt($this->enCode($value));
        }
        else
        {
            $value = $this->enCrypt($value);
        }
        setcookie($key,$value,time()+36000,'/');
    }

    function getCookie($key)
    {
        if(isset($_COOKIE[$key]) && !empty($_COOKIE[$key]))
        {
            return $this->deCrypt($_COOKIE[$key]);
        }
    }
}