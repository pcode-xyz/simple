<?php

class View
{
	//参数
	public $_params = array();

	public static $_instance = null;

	public static function instance()
	{
		if (empty(self::$_instance))
		{
			self::$_instance = new View();
		}
		return self::$_instance;
	}

	//绑定参数
	public function bind($key, $value = null)
	{
		if (is_array($key))
		{
			//数组，则合并
			$this->_params = array_merge($this->_params, $key);
		}
		else
		{
			//参数则覆盖
			$this->_params[$key] = $value;
		}
	}

	//输出页面
	public function display($filename)
	{
		$path = Core::find_file('view', $filename);
		if (!$path)
		{
			//模板文件不存在
			Core::quit("[Wrong Type 1]: View File Not Found! " . $filename);
		}
		else
		{
			//正常
			ob_start();
			ob_implicit_flush(0);
			extract($this->_params, EXTR_OVERWRITE);
			include $path;
			$content = ob_get_clean();
			header('Content-Type:text/html; charset=utf-8');
			exit($content);
		}
	}

	public static function path($filename)
	{
		$path = Core::find_file('view', $filename);
		if (!$path)
		{
			//模板文件不存在
			Core::quit("[Wrong Type 1]: View File Not Found! " . $filename);
		}
		else
		{
			return $path;
		}
	}
}