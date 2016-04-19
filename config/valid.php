<?php

//校验数组
return array(
	'create_title'	=> array(
		'id'	=> array(
			'digit'			=> NULL,
			'on_error'		=> '非法问卷ID',
		),
		'title'		=> array(
			'max_length'	=> array(64),
			'min_length'	=> array(4),
			'on_error'		=> '问卷标题长度限制为4-64',
		),
		'greeting'	=> array(
			'max_length'	=> array(800),
			'on_error'		=> '问卷欢迎语长度不得超过800字',
		),
	),
);