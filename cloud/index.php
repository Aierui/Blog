<?php

require_once(dirname(__FILE__)."/config.php");
require_once(DEDEINC.'/dedetag.class.php');

if(!file_exists($myIcoFile)) $myIcoFile = $defaultIcoFile;

include(DEDEADMIN.'/templets/index2.htm');
exit();

