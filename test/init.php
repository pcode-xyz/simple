<?php

require_once '../index.php';

echo ORM::factory('test')->add(array('username' => 'gavinczhang', 'password' => md5('123456')));