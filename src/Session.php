<?php

namespace Simple;

use Simple\Exception\Service_Error;
use Simple\Session\Mysql;
use Simple\Session\Redis;

/**
 * Class Session
 * @package Simple
 */
abstract class Session
{
	public static $_instance = null;

	protected $_session_id = NULL;
	protected $_data = NULL;
	protected $_name = 'session';

	/**
	 * @return Redis|Mysql
	 * @throws Service_Error
	 */
	public static function instance()
	{
		if (!(Session::$_instance instanceof Session))
		{
			$config = Config::get('session');
			$mode = Arr::get($config, 'mode');
			if ($mode == 'mysql')
			{
				Session::$_instance = new Mysql();
			}
			elseif ($mode == 'redis')
			{
				Session::$_instance = new Redis();
			}
			else
			{
				throw new Service_Error('Session Config Errors, unkonw mode['.$mode.']');
			}
		}
		return Session::$_instance;
	}

	abstract public function save();

	//写入数据，需要写入数据库
	public function set($key, $value)
	{
		$this->_data[$key] = $value;
		$this->save();
	}

	//读取数据
	public function get($key, $default = NULL)
	{
		return Arr::get($this->_data, $key, $default);
	}

	//删除某一项
	public function delete($key)
	{
		if (isset($this->_data[$key]))
		{
			unset($this->_data[$key]);
			$this->save();
		}
		return true;
	}

	abstract public function remove();

	//清空session
	protected function clear()
	{
		//删除数据库条目
		$this->remove();
		//删除cookie
		Cookie::delete($this->_name);
		$this->_session_id = null;
		$this->_data = [];
		return true;
	}

	protected function _make_id()
	{
		$id = uniqid(rand(10000, 99999), TRUE);
		return md5($id);
	}
}