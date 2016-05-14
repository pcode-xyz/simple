<?php

class DB
{
	private static $_instance = null;

	/**
	 * @var mysqli
	 */
	private $_link = null;

	/**
	 * @return DB
	 */
	public static function instance()
	{
		if (!(DB::$_instance instanceof DB))
		{
			DB::$_instance = new DB();
		}
		return DB::$_instance;
	}

	//读入config 开启mysql连接
	private function __construct()
	{
		$hostname = Config::get('database.hostname');
		$username = Config::get('database.username');
		$password = Config::get('database.password');
		$database = Config::get('database.database');
		$charset = Config::get('database.charset');
		$this->connect($hostname, $username, $password, $database);
		$this->query("SET NAMES '{$charset}'");
		return $this;
	}

	//连接
	public function connect($hostname, $username, $password, $database = '')
	{
		$this->_link = new mysqli($hostname, $username, $password);
		if ($this->_link->connect_errno)
		{
			$this->halt();
		}

		if (!empty($database))
		{
			$this->_link->select_db($database);
		}
		return $this;
	}

	//执行并返回
	public function query($sql)
	{
		$result = $this->_link->query($sql);
		if ($result)
		{
			return $result;
		}
		$this->halt($sql);
	}

	//mysql_fetch_array函数
	/**
	 * @param $result mysqli_result
	 * @return mixed
	 */
	public function fetch($result)
	{
		return $result->fetch_assoc();
	}

	public function fetch_all($result)
	{
		$return = [];
		while ($row = $this->fetch($result))
		{
			$return[] = $row;
		}
		return $return;
	}

	//最新插入id
	public function insert_id()
	{
		return $this->_link->insert_id;
	}

	//报错且退出
	public function halt($sql = null)
	{
		if (is_null($sql))
		{
			$str = '[Mysql Connect Error '.$this->_link->connect_errno.']: '.$this->_link->connect_error;
		}
		else
		{
			$str = '[Mysql Error '.$this->_link->errno.']: '.$this->_link->error;
		}

		if (!empty($sql))
		{
			$str .= "\nSQL: ".$sql;
		}
		Core::quit($str);
	}

	//过滤参数
	public function check($value)
	{
		$value = trim($value);
		if (!is_numeric($value))
		{
			$value = $this->_link->real_escape_string($value);
		}
		return $value;
	}
}