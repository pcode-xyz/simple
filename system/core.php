<?php

class Core
{
	//系统核心类库一览表
	public static $_system_class = array('core', 'arr', 'config', 'controller', 'cookie', 'db', 'orm', 'route', 'session', 'view', 'valid');
	public static $_path = array();

	//查找文件
	public static function find_file($dir, $filename, $ext = '.php')
	{
		$path = DOCROOT . $dir . DIRECTORY_SEPARATOR . $filename . $ext;
		if (is_file($path))
		{
			return $path;
		}
		return false;
	}

	//加载文件
	public static function load($file)
	{
		return include $file;
	}

	//自动加载 *model和系统class不得重名
	public static function auto_load($class)
	{
		$class = strtolower($class);
		$dir = 'class';
		if (in_array($class, Core::$_system_class))
		{
			//系统核心
			$path = Core::find_file('class', $class);
			$dir = 'class';
		}
		else
		{
			//用户自定义
			if (substr($class, 0, 11) == 'controller_')
			{
				//controller相关
				$path = Core::find_file('controller', substr($class, 11));
				$dir = 'controller';
			}
			else
			{
				//model
				$path = Core::find_file('model', $class);
				$dir = 'model';
			}
		}

		if (!$path)
		{
			die("[Wrong Type 1]: ".ucfirst($dir)." File Not Found! " . $class);
		}
		else
		{
			require_once $path;
		}
	}
}