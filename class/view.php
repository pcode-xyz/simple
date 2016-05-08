<?php

class View
{
	//参数
	public static $_params = array();

	//输出页面
	public static function display($filename)
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
			extract(View::$_params, EXTR_OVERWRITE);
			$content = include $path;
			$content = ob_get_clean();
			header('Content-Type:text/html; charset=utf-8');
			exit($content);
		}
	}

	//绑定参数
	public static function bind($key, $value = null)
	{
		if (is_array($key))
		{
			//数组，则合并
			View::$_params = array_merge(View::$_params, $key);
		}
		else
		{
			//参数则覆盖
			View::$_params[$key] = $value;
		}
	}
}