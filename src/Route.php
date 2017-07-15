<?php

namespace Simple;

use Simple\Exception\Method_Not_Allowed;
use Simple\Exception\Not_Found;

/**
 * Class Route
 * 路由有几种规则
 * 1. 匹配对应的url,进入对应的controller和action中
 * 2. 匹配对应的url,直接跳转某个新的url
 * 3. 规则匹配不到,按/分隔,找对应的controller和action
 * 4.
 * @package Simple
 */
class Route
{
	//正则需要匹配到的内容
	const REGEX_SEGMENT = '[^/.,;?\n]++';

	//正则表达式匹配需要过滤的字符
	const REGEX_ESCAPE = '[.\\+*?[^\\]${}=!|]';

	/**
	 * @var string 完整的带namespace的controller名称 例如Simple\Route Simple\Controller\Controller_Cli
	 */
	public static $controller = '';
	/**
	 * @var string 不带namespace的controller名称 例如Route Controller_Cli
	 */
	public static $controller_short = '';
	/**
	 * @var string 完整的带action_前缀的方法名 例如action_index action_get_list
	 */
	public static $action = '';
	/**
	 * @var string 不带action_前缀的方法名 例如index get_list
	 */
	public static $action_short = '';
	/**
	 * @var string controller_short/action_short
	 */
	public static $view = '';

	/**
	 * @var array $_routes 用户定义的路由规则,排名靠前的优先匹配
	 */
	private static $_routes = [];

	/**
	 * @var string $_default_controller 未指定时,默认访问的controller
	 */
	private static $_default_controller = 'Welcome';
	/**
	 * @var string $_default_action 未指定时,默认访问的action
	 */
	private static $_default_action = 'index';

	/**
	 * 用来自定义default_controller
	 * @param $controller string controller_name
	 */
	public static function set_default_controller($controller)
	{
		self::$_default_controller = $controller;
	}

	/**
	 * 用来自定义default_action
	 * @param $action string function_name
	 */
	public static function set_default_action($action)
	{
		self::$_default_action = $action;
	}

	/**
	 * 添加一条路由规则
	 * @param $rule string url|正则表达式
	 * @param $route array ['controller' => '', 'action' => '', 'param' => '']
	 * @param $method mixed
	 */
	public static function add($rule, $route, $method = NULL)
	{
		Route::$_routes[] = [
			'rule'	=> Route::compile($rule),
			'route'	=> $route,
			'method'=> $method,
		];
	}

	/**
	 * 格式化正则表达式
	 * @param string $rule
	 * @return string
	 *
	 * @author simple
	 */
	public static function compile($rule)
	{
		//过滤rule中的不允许定义的字符
		$expression = preg_replace('#'.Route::REGEX_ESCAPE.'#', '\\\\$0', $rule);

		//替换括号()为可选分组
		if (strpos($expression, '(') !== FALSE)
		{
			$expression = str_replace(['(', ')'], ['(?:', ')?'], $expression);
		}

		//替换<>为命名式分组
		$expression = str_replace(['<', '>'], ['(?P<', '>'.Route::REGEX_SEGMENT.')'], $expression);

		return '#^'.$expression.'$#uD';
	}

	/**
	 * 按route规则匹配当前url,查找对应的controller和action
	 */
	public static function router()
	{
		/**
		 * 过滤index.php 小写 并 移除首末的/
		 * @var string $url_path http://host/url_path
		 * @var array $uri_array [controller, action, param]
		 */
		$request_uri = $_SERVER['REQUEST_URI'];
		if (stripos($request_uri, '/index.php') === 0)
		{
			$request_uri = '/'.substr($request_uri, 10);
		}
		$url_path = parse_url($request_uri, PHP_URL_PATH);
		$url_path = strtolower(trim($url_path, '/'));

		//先进行正则匹配
		$default = [];
		foreach (Route::$_routes as $route)
		{
			//正则匹配路由
			if (preg_match($route['rule'], $url_path, $matches))
			{
				$default = [
					'controller'	=> Arr::get($route['route'], 'controller', Route::$_default_controller),
					'action'		=> Arr::get($route['route'], 'action', Route::$_default_action),
					'param'			=> Arr::get($route['route'], 'param'),
					'method'		=> Arr::get($route, 'method'),
				];

				//将匹配结果反写入default中
				foreach ($matches as $key => $value)
				{
					//过滤数字key 及 匹配结果为空字符串的情况
					if (!is_int($key) && $value !== '')
					{
						$default[$key] = $value;
					}
				}
				break;
			}
		}

		//正则匹配结果为空 再走url / 分隔逻辑
		if (empty($default))
		{
			if (empty($url_path))
			{
				//为空
				$uri_array = [];
			}
			elseif (strpos($url_path, '/') !== false)
			{
				//可以根据/分隔
				$uri_array = explode('/', $url_path, 3);
			}
			else
			{
				//单一字符串
				$uri_array = [$url_path];
			}

			$default = [
				'controller'	=> Arr::get($uri_array, 0, Route::$_default_controller),
				'action'		=> Arr::get($uri_array, 1, Route::$_default_action),
				'param'			=> Arr::get($uri_array, 2),
				'method'		=> NULL,
			];
		}

		$controller = Route::format($default['controller']);
		$action = strtolower(Route::format($default['action']));
		$param = $default['param'];
		$method = $default['method'];

		Log::instance()->sys('route info request_uri['.$request_uri.'] controller['.$controller.'] action['.$action.'] param['.$param.']');

		//判断method是否合法 不合法则走405异常
		if (!empty($method))
		{
			if (!is_array($method))
			{
				$method = [$method];
			}

			if (!in_array(Request::method(), $method))
			{
				throw new Method_Not_Allowed('method['.Request::method().'] not allowed');
			}
		}

		Route::run($controller, $action, $param);
	}

	/**
	 * 格式化 按-分隔 首字母大写 -转义_
	 * @param $str
	 * @return string
	 *
	 * @author simple
	 */
	public static function format($str)
	{
		$str = str_replace('_', '-', $str);
		$array = explode('-', $str);
		$array = array_map(function($str){
			return ucfirst($str);
		}, $array);
		return implode('_', $array);
	}

	/**
	 * 调用对应的controller和action执行
	 * @param $controller
	 * @param $action
	 * @param null $param
	 * @throws Not_Found
	 */
	public static function run($controller, $action, $param = NULL)
	{
		$controller_name = '\\App\\Controllers\\'.$controller;

		if (!Simple::find_file('Controllers', $controller))
		{
			throw new Not_Found($controller_name. ' not found');
		}

		Route::$controller = $controller_name;
		Route::$controller_short = $controller;
		Route::$action = 'action_'.$action;
		Route::$action_short = $action;
		Route::$view = $controller . '/' . $action;

		//判断action方法是否存在
		if (!method_exists(Route::$controller, Route::$action))
		{
			throw new Not_Found(Route::$controller.'->'.Route::$action. ' is undefined');
		}

		/**
		 * @var $controller Controller
		 */
		$controller = new Route::$controller();
		$controller->before();
		if (!is_null($param))
		{
			$controller->{Route::$action}($param);
		}
		else
		{
			$controller->{Route::$action}();
		}
		$controller->after();
	}
}