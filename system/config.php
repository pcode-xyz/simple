<?php

class Config
{
	public static $_config = array();

	//获取某文件中某字段 eg: database.username 获取database.php文件中的username字段的值
	public static function get($key, $default = null)
	{
		if (strpos($key, '.') !== FALSE)
		{
			list ($filename, $group) = explode('.', $key, 2);
		}
		else
		{
			//默认加载config.php配置文件
			$filename = $key;
			$group = null;
		}

		//第一次读则载入
		if (!isset(Config::$_config[$filename]))
		{
			Config::load($filename);
		}

		if (!is_null($group))
		{
			return Arr::get(Config::$_config[$filename], $group, $default);
		}
		else
		{
			return Config::$_config[$filename];
		}
	}

	//载入config
	public static function load($filename)
	{
		$path = Core::find_file('config', $filename);
		if (!$path)
		{
			die("[Wrong Type 1]: Config File Not Found! " . $filename);
		}
		else
		{
			Config::$_config[$filename] = Core::load($path);
		}
	}
}