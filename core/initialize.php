<?php

defined('DS') ? null : define('DC', DIRECTORY_SEPARATOR);
defined('SITE_ROT') ? null : define('SITE_ROOT', DS . 'Ampps' . DS . 'www' . 'testingDB');
defined('INC_PATH') ? null : define('INC_PATH', SITE_ROOT.DS.'includes');
defined('CORE_PATH') ? null : define('CORE_PATH', SITE_ROOT.DS.'core');

//load config file
require_once(INC_PATH.'config.php');

//load core fclasses
require_once(CORE_PATH.'user.php');
?>