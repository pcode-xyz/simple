<?php

class Controller_Template extends Controller
{
	public $template = '';
	public $view = null;

	public function __construct()
	{
		$this->view = View::instance();
	}

	public function before()
	{
		// Nothing by default
		$this->template = Core::$mvc['view'];
	}

	public function after()
	{
		// Nothing by default
		$this->display();
	}

	public function bind($key, $value = NULL)
	{
		$this->view->bind($key, $value);
	}

	public function display()
	{
		$this->view->display($this->template);
	}

	public function redirect($url)
	{
		header('Location: '.$url);
		exit;
	}

} // End Controller
