<?php

/**
 * 数据库模拟session
 *
 *     CREATE TABLE  `sessions` (
 *         `session_id` CHAR( 32 ) NOT NULL,
 *         `last_active` INT UNSIGNED NOT NULL,
 *         `contents` TEXT NOT NULL,
 *         PRIMARY KEY ( `session_id` ),
 *         INDEX ( `last_active` )
 *     ) ENGINE = MYISAM ;
 *
 */
class Session
{
	public static $_instance = null;

	private $_session_id = null;
	private $_table = 'sessions';
	private $_name = 'session';
	// Database column names
	private $_columns = array(
		'session_id'  => 'session_id',
		'last_active' => 'last_active',
		'contents'    => 'contents'
	);
	// Garbage collection requests
	private $_gc = 500;
	// The old session id
	private $_update_id;
	//数据
	private $_data;

	public static function instance()
	{
		if (!(Session::$_instance instanceof Session))
		{
			Session::$_instance = new Session();
		}
		return Session::$_instance;
	}

	private function __construct()
	{
		$this->_table = Config::get('session.table', $this->_table);
		$this->_columns = Config::get('session.columns', $this->_columns);
		$this->_gc = Config::get('session.gc', $this->_gc);

		//回收
		if (mt_rand(0, $this->_gc) === $this->_gc)
		{
			//随机触发回收机制，约1/500的概率
			$this->_gc();
		}

		if ($this->_session_id || $this->_session_id = Cookie::get($this->_name))
		{
			//如果已经生成过session_id 或 可以从cookie中读取session_id 写入data
			$data = ORM::factory($this->_table)->where(array($this->_columns['session_id'] => $this->_session_id))->find();
			$contents = Arr::get($data, $this->_columns['contents']);
			if (!empty($contents))
			{
				//数据库中正常存在 则赋值继续
				$this->_data = unserialize($contents);
				return $this;
			}
		}

		//生成新的session_id 并 写入数据库及cookie
		$this->_regenerate();
		return $this;
	}

	//写入数据，需要写入数据库
	public function set($key, $value)
	{
		$this->_data[$key] = $value;
		return ORM::factory($this->_table)->where(array($this->_columns['session_id']	=> $this->_session_id))->save(array(
			$this->_columns['contents']		=> serialize($this->_data),
			$this->_columns['last_active']	=> time(),
		));
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
			ORM::factory($this->_table)->where(array($this->_columns['session_id']	=> $this->_session_id))->save(array(
				$this->_columns['contents']		=> serialize($this->_data),
				$this->_columns['last_active']	=> time(),
			));
		}
		return true;
	}

	//清空session
	public function clear()
	{
		//删除数据库条目
		ORM::factory($this->_table)->where(array($this->_columns['session_id']	=> $this->_session_id))->delete();
		//删除cookie
		Cookie::delete($this->_name);
		$this->_session_id = null;
		$this->_data = array();
		return true;
	}

	//创建新的session_id
	private function _regenerate()
	{
		do
		{
			// Create a new session id
			$id = str_replace('.', '-', uniqid(NULL, TRUE));
			$id = md5($id);
			$data = ORM::factory($this->_table)->where(array($this->_columns['session_id'] => $this->_session_id))->find();
		}
		while ($data);

		$this->_session_id = $id;
		$this->_data = array();
		ORM::factory($this->_table)->add(array(
			$this->_columns['session_id']	=> $this->_session_id,
			$this->_columns['contents']		=> serialize($this->_data),
			$this->_columns['last_active']	=> time(),
		));
		Cookie::set($this->_name, $this->_session_id);

		return $this->_session_id;
	}

	//回收机制
	private function _gc()
	{
		//删除最后使用日期在上个月以前的所有条目
		ORM::factory($this->_table)->where(array($this->_columns['last_active'] => array('<', time()-2629744)))->delete();
	}
}