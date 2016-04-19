<?php

//预定义
date_default_timezone_set('Asia/Shanghai');
set_time_limit(0);

//定义路径
define('DOCROOT', realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR);

//自动挂载
require_once DOCROOT.'class'.DIRECTORY_SEPARATOR.'core.php';
spl_autoload_register(array('Core', 'auto_load'));

//定义route并启动
Route::init();