<?php

namespace Simple;

use Simple\Exception\Service_Error;

/**
 * Class DB
 * mysql操作类
 * @package Simple
 */
class DB
{
	/**
	 * @var array $_instance 单例
	 */
	private static $_instance = [];

	/**
	 * @var \mysqli
	 */
	private $_link = null;

	/**
	 * @var string $hostname ip/host
	 */
	private $hostname = '';
	/**
	 * @var string $username 用户名
	 */
	private $username = '';
	/**
	 * @var string $password 密码
	 */
	private $password = '';
	/**
	 * @var string $database 数据库名
	 */
	private $database = '';
	/**
	 * @var int $port 端口号
	 */
	private $port = 3306;
	/**
	 * @var string $charset 字符集
	 */
	private $charset = 'utf8';

	/**
	 * 获取一个mysqli单例
	 * @param string $config database配置config中的key名
	 * @param string $node master|slave 主从
	 * @return DB
	 */
	public static function instance($config = 'default', $node = 'master')
	{
		if (!isset(DB::$_instance[$config][$node]))
		{
			DB::$_instance[$config][$node] = new DB($config, $node);
		}
		return DB::$_instance[$config][$node];
	}

	/**
	 * DB constructor.
	 * @param string $config database配置config中的key名
	 * @param string $node master|slave
	 */
	private function __construct($config = 'default', $node = 'master')
	{
		$this->load_config($config, $node);

		$this->connect($this->hostname, $this->username, $this->password, $this->database, $this->port);
		$this->query("SET NAMES '".$this->charset."'");
	}

	/**
	 * 读取配置文件
	 * @param string $config database配置config中的key名
	 * @param string $node master|slave 主从
	 * @throws Service_Error
	 */
	private function load_config($config, $node)
	{
		//判断配置文件是否存在
		$db_config = Config::get('database.'.$config);
		if (empty($db_config) || !isset($db_config['master']))
		{
			throw new Service_Error('Config[database.'.$config.'] need master node');
		}
		//slave节点配置不存在 则读取master中配置
		$config = isset($db_config[$node]) ? $db_config[$node] : $db_config['master'];

		//关键参数校验 IP/用户名/密码
		if (!isset($config['hostname']))
		{
			throw new Service_Error('Config[database.'.$config.'] need set hostname');
		}

		if (!isset($config['username']))
		{
			throw new Service_Error('Config[database.'.$config.'] need set username');
		}

		if (!isset($config['password']))
		{
			throw new Service_Error('Config[database.'.$config.'] need set password');
		}

		if (!isset($config['database']))
		{
			throw new Service_Error('Config[database.'.$config.'] need set database');
		}
		$this->hostname = Arr::get($config, 'hostname', $this->hostname);
		$this->username = Arr::get($config, 'username', $this->username);
		$this->password = Arr::get($config, 'password', $this->password);
		$this->database = Arr::get($config, 'database', $this->database);

		//可默认填充 端口3306 字符集utf8
		$this->port = Arr::get($config, 'port', $this->port);
		$this->charset = Arr::get($config, 'charset', $this->charset);
	}

	/**
	 * 连接数据库
	 * @param string $hostname ip/host
	 * @param string $username 用户名
	 * @param string $password 密码
	 * @param string $database 数据库名
	 * @param int $port 端口
	 * @return $this
	 * @throws Service_Error
	 *
	 * @author simple
	 */
	public function connect($hostname, $username, $password, $database = '', $port = 3306)
	{
		$this->_link = new \mysqli($hostname, $username, $password, $database, $port);
		if ($this->_link->connect_errno)
		{
			$this->halt();
		}

		return $this;
	}

	/**
	 * 关闭当前数据库连接
	 * @return bool
	 *
	 * @author simple
	 */
	public function close()
	{
		return $this->_link->close();
	}

	/**
	 * 使用mysqli_ping检查mysql服务器，并尝试重连
	 * @return bool
	 *
	 * @author simple
	 */
	protected function check_connection()
	{
		if (!@$this->_link->ping())
		{
			$this->close();
			$this->connect($this->hostname, $this->username, $this->password, $this->database, $this->port);
			return TRUE;
		}
		return true;
	}

	/**
	 * 执行sql并返回结果集
	 * @param $sql
	 * @return bool|\mysqli_result
	 * @throws Error
	 *
	 * @author simple
	 */
	public function query($sql)
	{
		Log::instance()->sql($sql);
		$result = FALSE;
		for ($i = 0; $i < 2; $i++)
		{
			$result = @$this->_link->query($sql);
			if (!$result)
			{
				//判断是否需要重连
				if ($this->_link->errno == 2013 || $this->_link->errno == 2006)
				{
					$connection = $this->check_connection();
					if ($connection === TRUE)
					{
						continue;
					}
				}
				$this->halt($sql);
			}
			break;
		}
		return $result;
	}

	/**
	 * 将一个mysql结果集转为数组
	 * @param $result \mysqli_result
	 * @return mixed
	 */
	public function fetch($result)
	{
		return $result->fetch_assoc();
	}

	/**
	 * 不断的将一个mysql结果集转为数组 直到结果集为空
	 * @param $result
	 * @return array
	 *
	 * @author simple
	 */
	public function fetch_all($result)
	{
		$return = [];
		while ($row = $this->fetch($result))
		{
			$return[] = $row;
		}
		return $return;
	}

	/**
	 * 获取最新一条插入id
	 * @return mixed
	 *
	 * @author simple
	 */
	public function insert_id()
	{
		return $this->_link->insert_id;
	}

	/**
	 * 抛出异常
	 * @param null $sql
	 * @throws Service_Error
	 *
	 * @author simple
	 */
	public function halt($sql = null)
	{
		if (is_null($sql))
		{
			//连接出错
			$str = '[Mysql Connect Error '.$this->_link->connect_errno.']: '.$this->_link->connect_error;
			$error_code = $this->_link->connect_errno;
		}
		else
		{
			//执行sql出错
			$str = '[Mysql Error '.$this->_link->errno.']: '.$this->_link->error;
			$error_code = $this->_link->errno;
		}

		if (!empty($sql))
		{
			$str .= "\nSQL: ".$sql;
		}
		throw new Service_Error($str, $error_code);
	}

	/**
	 * 参数过滤
	 * @param string|int $value
	 * @return string|int
	 *
	 * @author simple
	 */
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