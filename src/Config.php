<?php

namespace Simple;

use Simple\Exception\Service_Error;

/**
 * Class Config
 * 读取App/Configs目录下的配置文件
 * @package Simple
 */
class Config
{
	/**
	 * @var array $_config 全局缓存的配置文件内容
	 */
	protected static $_config = [];

	/**
	 * 获取某文件中某字段 eg: database.username 获取database.php文件中的username字段的值
	 * @var $key string 文件名
	 * @var $default mixed
	 * @return array
	 * */
	public static function get($key, $default = null)
	{
		//默认加载config.php配置文件
		$filename = $key;
		$group = null;
		if (strpos($key, '.') !== FALSE)
		{
			list ($filename, $group) = explode('.', $key, 2);
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

	/**
	 * 载入配置文件
	 * @param $filename string 配置文件文件名
	 * @throws Service_Error
	 */
	public static function load($filename)
	{
		//获取common config和对应配置目录的config路径
		$common_config_path = Simple::find_file('Configs', $filename);
		$cfg_config_path = !empty(CFG_PATH) ? Simple::find_file('Configs/'.CFG_PATH, $filename) : FALSE;
		if (!$cfg_config_path && !$common_config_path)
		{
			throw new Service_Error('Config File[' . $filename . '] Not Found!');
		}

		//合并 将CFG的内容合并至common中
		$common_config = !$common_config_path ? [] : Simple::load($common_config_path);
		$cfg_config = !$cfg_config_path ? [] : Simple::load($cfg_config_path);
		Config::$_config[$filename] = array_merge($common_config, $cfg_config);
	}
}