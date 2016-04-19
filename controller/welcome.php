<?php

class Controller_Welcome extends Controller
{
	public function action_index()
	{
		View::bind(array(
			'str' => 'Hello',
			'title' => 'Simple.PHP',
		));
		View::display('test');
	}
}