<?php

namespace Simple\Session;

use Simple\Session;
use Simple\Cookie;
use Simple\Arr;
use Simple\Config;

/**
 * Class Redis
 * 使用redis模拟session操作
 * @package Simple\Session
 */
class Redis extends Session
{
	private $_prefix = 'SESSION_';
	private $_expire = 86400;

	public function __construct()
	{
		$config = Config::get('session.redis');
		$this->_name = Arr::get($config, 'name', $this->_name);
		$this->_prefix = Arr::get($config, 'prefix', $this->_prefix);
		$this->_expire = Arr::get($config, 'expire', $this->_expire);

		if ($this->_session_id || $this->_session_id = Cookie::get($this->_name))
		{
			$contents = \Simple\Redis::instance()->get($this->_prefix.$this->_session_id);
			if (!empty($contents))
			{
				//正常存在 则赋值继续
				$this->_data = json_decode($contents, TRUE);
				return $this;
			}
		}

		//生成新的session_id 并 写入redis及cookie
		$this->_regenerate();
		return $this;
	}

	public function save()
	{
		\Simple\Redis::instance()->set($this->_prefix.$this->_session_id, json_encode($this->_data), $this->_expire);
	}

	//清空session
	public function remove()
	{
		\Simple\Redis::instance()->del($this->_prefix.$this->_session_id);
	}

	//创建新的session_id
	private function _regenerate()
	{
		do
		{
			// Create a new session id
			$id = self::_make_id();
			$data = \Simple\Redis::instance()->get($this->_prefix.$this->_session_id);
		}
		while ($data);

		$this->_session_id = $id;
		$this->_data = [];
		$this->save();
		Cookie::set($this->_name, $this->_session_id);

		return $this->_session_id;
	}
}