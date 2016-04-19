<?php

class DB
{
	private static $_instance = null;
	private $_link = null;
	//临时结果集

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
	public function connect($hostname, $username, $password, $database = '', $pconnect = false)
	{
		$func = !$pconnect ? 'mysql_connect' : 'mysql_pconnect';
		if (!$this->_link = @$func($hostname, $username, $password, 1))
		{
			$this->halt();
		}
		else
		{
			$database && @mysql_select_db($database, $this->_link);
		}
		return $this;
	}

	//执行并返回
	public function query($sql, $type = '')
	{
		$func = ($type == 'UNBUFFERED' && @function_exists('mysql_unbuffered_query')) ? 'mysql_unbuffered_query' : 'mysql_query';
		if (!($query = @$func($sql, $this->_link)) && $type != 'SILENT')
		{
			$this->halt($sql);
		}
		return $query;
	}

	//mysql_fetch_array函数
	public function fetch($query, $result_type = MYSQL_ASSOC)
	{
		return @mysql_fetch_array($query, $result_type);
	}

	//最新插入id
	public function insert_id()
	{
		return mysql_insert_id($this->_link);
	}

	//报错且退出
	public function halt($sql = null)
	{
		$str = '[Mysql Error '.mysql_errno($this->_link).']: '.mysql_error($this->_link);
		if (!empty($sql))
		{
			$str .= "\nSQL: ".$sql;
		}
		die($str);
	}

	//过滤参数
	public function check($value)
	{
		$value = trim($value);
		if (get_magic_quotes_gpc())
		{
			//删除magic方法添加的反斜杠
			$value = stripslashes($value);
		}
		if (!is_numeric($value))
		{
			$value = mysql_real_escape_string($value, $this->_link);
		}
		return $value;
	}
}