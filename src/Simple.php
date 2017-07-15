<?php

namespace Simple;

/**
 * @const 框架版本号
 */
define('SIMPLE_VERSION', '4.3.0');

/**
 * @const 框架代码所在路径
 */
defined('SYS_PATH') or define('SYS_PATH', __DIR__.'/');

/**
 * @const 项目路径
 */
defined('APP_PATH') or define('APP_PATH', __DIR__.'/../app/');

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

	/**
	 * @var array $namespaces 根命名空间对应目录
	 */
	protected static $namespaces;

	private $_start_time = 0;
	private $_end_time = 0;
	private $_start_memory = 0;
	private $_end_memory = 0;
	private $_max_memory = 0;

	/**
	 * 按PSR-4协议加载文件 大小写敏感
	 * @param $class
	 * @throws Exception\Service_Error
	 */
	public static function autoload($class)
	{
		$class_node_list = explode('\\', trim($class, '\\'));
		//真实类名
		$real_class_name = array_pop($class_node_list);
		//获取顶级命名空间自定义地址
		$class_namespace = array_shift($class_node_list);

		//只自动加载框架命名空间下的内容
		if (isset(self::$namespaces[$class_namespace]))
		{
			$file_path = self::$namespaces[$class_namespace] . implode('/', $class_node_list).'/'.$real_class_name.'.php';
			if (is_file($file_path))
			{
				include_once $file_path;
			}
			else
			{
				throw new Exception\Service_Error('Class['.$class.'] Not Found');
			}
		}
	}

	/**
	 * 设置命名空间
	 * @param $namespace string 顶级命名空间 /结尾
	 * @param $path string 路径
	 */
	public static function add_namespace($namespace, $path)
	{
		self::$namespaces[$namespace] = $path;
	}

	/**
	 * @return Simple
	 */
	public static function instance()
	{
		if (empty(Simple::$_instance))
		{
			Simple::$_instance = new Simple();
		}
		return Simple::$_instance;
	}

	/**
	 * Simple constructor.
	 * 挂载autoload
	 * 挂载错误捕捉
	 */
	private function __construct()
	{
		//autoload
		Simple::add_namespace('Simple', SYS_PATH);
		Simple::add_namespace('App', APP_PATH);
		spl_autoload_register(array('\\Simple\\Simple', 'autoload'));

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
			//载入路由
			if (file_exists(APP_PATH.'Router.php'))
			{
				require_once APP_PATH.'Router.php';
			}

			//cli支持
			$cli = FALSE;
			if (php_sapi_name() == 'cli')
			{
				$cli = TRUE;
				$args = array_slice($_SERVER['argv'], 1);
				$_SERVER['REQUEST_URI'] = !empty($args) ? ('/'.implode('/', $args)) : '/';
			}
			define('CLI_MODE', $cli);

			//判断是否将json转_POST
			if (AUTO_PARAM_JSON && (stripos(Arr::get($_SERVER, 'CONTENT_TYPE'), 'application/json') !== FALSE || stripos(Arr::get($_SERVER, 'HTTP_CONTENT_TYPE'), 'application/json') !== FALSE))
			{
				$data = file_get_contents('php://input');
				$_POST = json_decode($data, TRUE);
				unset($data);
			}

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

	/**
	 * 查找文件
	 * @param string $dir 查找所有命名空间下子目录的某文件
	 * @param $filename
	 * @param string $ext
	 * @return bool|string
	 */
	public static function find_file($dir, $filename, $ext = '.php')
	{
		foreach (Simple::$namespaces as $namespace => $namespace_path)
		{
			$path = $namespace_path . $dir . '/' . $filename . $ext;
			if (is_file($path))
			{
				return $path;
			}
		}

		return FALSE;
	}

	/**
	 * 加载文件并返回文件内容
	 * @param $file
	 * @return mixed
	 */
	public static function load($file)
	{
		return include $file;
	}
}