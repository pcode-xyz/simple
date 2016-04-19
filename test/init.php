<?php

require_once '../init.php';

echo ORM::factory('test')->add(array('username' => 'gavinczhang', 'password' => md5('123456')));