<?php

namespace Simple\Session;

use Simple\Build\DELETE;
use Simple\Build\INSERT;
use Simple\Build\SELECT;
use Simple\Build\UPDATE;
use Simple\Cookie;
use Simple\Arr;
use Simple\Config;
use Simple\Session;

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
class Mysql extends Session
{
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
	private $_expire = 2592000;	//30 days

	public function __construct()
	{
		$config = Config::get('session.mysql');
		$this->_name = Arr::get($config, 'name', $this->_name);
		$this->_table = Arr::get($config, 'table', $this->_table);
		$this->_columns = Arr::get($config, 'columns', $this->_columns);
		$this->_gc = Arr::get($config, 'gc', $this->_gc);
		$this->_expire = Arr::get($config, 'expire', $this->_expire);

		//回收
		if (mt_rand(0, $this->_gc) === $this->_gc)
		{
			//随机触发回收机制，约1/500的概率
			$this->_gc();
		}

		if ($this->_session_id || $this->_session_id = Cookie::get($this->_name))
		{
			//如果已经生成过session_id 或 可以从cookie中读取session_id 写入data
			$data = SELECT::table($this->_table)->where([
				$this->_columns['session_id']	=> $this->_session_id,
			])->find();
			$contents = Arr::get($data, $this->_columns['contents']);
			if (!empty($contents))
			{
				//数据库中正常存在 则赋值继续
				$this->_data = json_decode($contents, TRUE);
				return $this;
			}
		}

		//生成新的session_id 并 写入数据库及cookie
		$this->_regenerate();
		return $this;
	}

	public function save()
	{
		return UPDATE::table($this->_table)->where([
			$this->_columns['session_id']	=> $this->_session_id,
		])->save([
			$this->_columns['contents']		=> json_encode($this->_data),
			$this->_columns['last_active']	=> time(),
		]);
	}

	//清空session
	public function remove()
	{
		//删除数据库条目
		DELETE::table($this->_table)->where([
			$this->_columns['session_id']	=> $this->_session_id,
		])->del();
	}

	//创建新的session_id
	protected function _regenerate()
	{
		do
		{
			// Create a new session id
			$id = self::_make_id();
			$data = SELECT::table($this->_table)->where([
				$this->_columns['session_id']	=> $id,
			])->find();
		}
		while ($data);

		$this->_session_id = $id;
		$this->_data = [];
		INSERT::table($this->_table)->add([
			$this->_columns['session_id']	=> $this->_session_id,
			$this->_columns['contents']		=> json_encode($this->_data),
			$this->_columns['last_active']	=> time(),
		]);
		Cookie::set($this->_name, $this->_session_id, $this->_expire);

		return $this->_session_id;
	}

	//回收机制
	private function _gc()
	{
		//删除最后使用日期在上个月以前的所有条目
		DELETE::table($this->_table)->where([
			$this->_columns['last_active'] => ['<', time() - $this->_expire]
		])->del();
	}
}