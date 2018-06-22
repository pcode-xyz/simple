<?php

namespace Simple;

/**
 * @const 框架版本号
 */
define('SIMPLE_VERSION', '5.0.0');

/**
 * @const 定义config环境变量
 */
defined('CFG_PATH') or define('CFG_PATH', '');

/**
 * @const 定义是否开启json_api 开启后自动将正文内容当做json解析
 */
defined('AUTO_PARAM_JSON') or define('AUTO_PARAM_JSON', FALSE);

/**
 * @const 调试开关 调试开关开启自动开启日志打印功能
 */
defined('DEBUG') or define('DEBUG', FALSE);

/**
 * @const 定义捕捉的error_type级别 debug模式下默认为全部捕捉 正式环境只捕捉E_ERROR | E_PARSE
 */
defined('ERROR_TYPE') or define('ERROR_TYPE', DEBUG ? (E_ALL | E_STRICT) : (E_ERROR | E_PARSE));

/**
 * Class Simple
 * @package Simple
 */
class Simple
{
	public static $_instance = NULL;

	private $_start_time = 0;
	private $_end_time = 0;
	private $_start_memory = 0;
	private $_end_memory = 0;
	private $_max_memory = 0;

	/**
	 * Simple constructor.
	 * 挂载autoload
	 * 挂载错误捕捉
	 */
	private function __construct()
	{
		//捕捉warning
		set_error_handler(function($errno, $error){
			throw new Exception($error, $errno);
		}, ERROR_TYPE);

		//记录初始时间/内存
		$this->_start_time = microtime(TRUE);
		$this->_start_memory = memory_get_usage();

		Log::instance()->sys('---------------app start---------------');
		Log::instance()->sys('app start time: '.$this->_start_time . '. start memory: '.$this->_start_memory);

		register_shutdown_function(function() {
			//记录终止时间/内存
			$this->_end_time = microtime(TRUE);
			$this->_end_memory = memory_get_usage();
			$this->_max_memory = memory_get_peak_usage();

			Log::instance()->sys('app end time: '.$this->_end_time . '. start memory: '.$this->_end_memory);
			Log::instance()->sys('app used time '.($this->_end_time - $this->_start_time).'s');
			Log::instance()->sys('app used memory '.($this->_end_memory - $this->_start_memory).' byte');
			Log::instance()->sys('app max used memory '.$this->_max_memory.' byte');
			Log::instance()->sys('---------------app  stop---------------');
			Log::instance()->flush();

			restore_error_handler();
		});
	}

	/**
	 * 框架执行入口
	 */
	public function run()
	{
		try
		{


			//项目启动
			Route::router();
		}
		catch (\Exception $e)
		{
			Log::instance()->error($e);
			Debug::info($e->getCode(), $e->getMessage(), $e->getTrace());

			if ($e instanceof Exception)
			{
				$e->handle();
			}
			else
			{
				Error::default_handle($e);
			}
		}
	}
}