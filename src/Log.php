<?php

namespace Simple;

/**
 * Class Log
 * 输出runtime日志
 * @package Simple
 */
class Log
{
	//不开启日志
	const E_NONE = 0;
	//开启error日志
	const E_ERROR = 1;
	//开启info日志
	const E_INFO = 2;
	//开启sys日志
	const E_SYS = 4;
	//开启sql日志
	const E_SQL = 8;
	//开启debug日志
	const E_DEBUG = 16;
	//全部开启
	const E_ALL = 31;

	//定义日志级别
	const LEVEL_SYS = 'SYS';
	const LEVEL_INFO = 'INFO';
	const LEVEL_ERROR = 'ERROR';
	const LEVEL_DEBUG = 'DEBUG';
	const LEVEL_SQL = 'SQL';

	//level对应开启状态
	const E_LEVEL = [
		self::LEVEL_INFO	=> self::E_INFO,
		self::LEVEL_ERROR	=> self::E_ERROR,
		self::LEVEL_DEBUG	=> self::E_DEBUG,
		self::LEVEL_SYS		=> self::E_SYS,
		self::LEVEL_SQL		=> self::E_SQL,
	];

	private static $_instance = NULL;

	/**
	 * @var string $_path log路径 注意带/
	 */
	private $_path = APP_PATH.'Logs/';
	/**
	 * @var int $_max_log_file 最大日志文件数
	 */
	private $_max_log_file = 5;
	/**
	 * @var int $_max_file_size 单个文件最大体积 单位M
	 */
	private $_max_file_size = 100;
	/**
	 * @var int $_log_level 记录的log级别
	 */
	private $_log_level = self::E_ALL;

	/**
	 * 待打印的日志
	 * @var array
	 */
	private $_logs = [];
	private $_logs_count = 0;

	/**
	 * @return Log
	 *
	 * @author simple
	 */
	public static function instance()
	{
		if (is_null(self::$_instance))
		{
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Log constructor.
	 * 从配置文件读取日志相关配置
	 */
	private function __construct()
	{
		$config = [];
		if (Simple::find_file('Configs', 'log'))
		{
			$config = Config::get('log');
		}
		$this->_path = Arr::get($config, 'path', $this->_path);
		//自动添加反斜杠
		if (substr($this->_path, -1, 1) != '/')
		{
			$this->_path .= '/';
		}
		$this->_max_log_file = Arr::get($config, 'max_log_file', $this->_max_log_file);
		$this->_max_file_size = Arr::get($config, 'max_file_size', $this->_max_file_size);
		$this->_log_level = Arr::get($config, 'log_level', $this->_log_level);

		register_shutdown_function([$this, 'flush']);
	}

	/**
	 * 讲待打印的内容格式化后保存至$_logs中
	 * @param string $level
	 * @param string $message
	 *
	 * @author simple
	 */
	private function format($level, $message)
	{
		//debug模式 或用户设置了对应级别的log_level
		if (DEBUG || ((self::E_LEVEL[$level] & $this->_log_level) == self::E_LEVEL[$level]))
		{
			$this->_logs[] = date('Y/m/d H:i:s', time()) . ' - ' . '['.$level.']' . ' - ' . $message . "\n";
			$this->_logs_count++;
			//如果积压超过20条日志 则输出到文件
			if ($this->_logs_count > 20)
			{
				$this->flush();
			}
		}
	}

	/**
	 * 打印到文件
	 *
	 * @author simple
	 */
	public function flush()
	{
		if ($this->_logs_count <= 0)
		{
			return;
		}

		if (!is_dir($this->_path))
		{
			self::mkdir($this->_path);
		}

		//日志文件打不开
		$file_name = $this->_path.'runtime.log';
		//判断是否需要修改当前文件名
		if (is_file($file_name) && @filesize($file_name) > $this->_max_file_size * 1024 * 1024)
		{
			$this->rotate_files($file_name);
		}

		$fp = @fopen($file_name, 'a');
		if (!$fp)
		{
			return;
		}
		@flock($fp, LOCK_EX);
		foreach ($this->_logs as $log)
		{
			@fwrite($fp, $log);
		}
		@flock($fp, LOCK_UN);
		@fclose($fp);

		$this->_logs = [];
		$this->_logs_count = 0;
	}

	/**
	 * 判断当前有多少个日志文件
	 * 如果超过最多的则删除最后一个
	 * @param $filename
	 *
	 * @author simple
	 */
	private function rotate_files($filename)
	{
		for ($i = $this->_max_log_file; $i >= 0; --$i)
		{
			$temp_file = $filename . ($i === 0 ? '' : '.' . $i);
			if (is_file($temp_file))
			{
				if ($i === $this->_max_log_file)
				{
					@unlink($temp_file);
				}
				else
				{
					@rename($temp_file, $filename . '.' . ($i + 1));
				}
			}
		}
	}

	/**
	 * 调试专用 支持数组/对象等打印
	 * @param mixed $data
	 *
	 * @author simple
	 */
	public function debug($data)
	{
		$this->format(self::LEVEL_DEBUG, var_export($data, TRUE));
	}

	/**
	 * 一般信息输出
	 * @param string $message
	 *
	 * @author simple
	 */
	public function info($message)
	{
		$this->format(self::LEVEL_INFO, $message);
	}

	/**
	 * 系统信息输出
	 * @param string $message
	 *
	 * @author simple
	 */
	public function sys($message)
	{
		$this->format(self::LEVEL_SYS, $message);
	}

	/**
	 * 打印异常信息
	 * @param \Exception $e
	 *
	 * @author simple
	 */
	public function error($e)
	{
		$array = Debug::format($e->getCode(), $e->getMessage(), $e->getTrace());
		$view = View::factory();
		$view->bind($array);
		$content = $view->render('Exception/debug_cli');

		$this->format(self::LEVEL_ERROR, $content);
	}

	/**
	 * 打印SQL信息
	 * @param string $sql
	 *
	 * @author simple
	 */
	public function sql($sql)
	{
		$this->format(self::LEVEL_SQL, $sql);
	}

	/**
	 * 递归创建目录
	 * @param $path
	 * @param int $mode
	 * @param bool $recursive
	 * @return bool
	 *
	 * @author simple
	 */
	private static function mkdir($path, $mode = 0755, $recursive = TRUE)
	{
		$prev_path = dirname($path);
		if ($recursive && !is_dir($path) && !is_dir($prev_path))
		{
			self::mkdir($prev_path, $mode, $recursive);
		}

		$res  = mkdir($path, $mode);
		@chmod($path, $mode);
		return $res;
	}

}