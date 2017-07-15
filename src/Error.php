<?php

namespace Simple;

use Simple\Exception;

/**
 * Class Error
 * Error处理类
 */
class Error
{
	/**
	 * @var array $_handle_list 默认处理对应错误信息的内容
	 */
	private static $_handle_list = [];

	/**
	 * @var array $_type_list 错误类型及状态码
	 */
	private static $_type_list = [
		'not_found'				=> 404,
		'method_not_allowed'	=> 405,
		'service_error'			=> 500,
		'default'				=> 500,
	];

	/**
	 * @var int 默认错误信息输出的错误码
	 */
	protected static $_default_http_code = 500;

	/**
	 * 设置异常处理函数
	 * @param string $type not_found|service_error|default
	 * @param string $controller controller名 大小写敏感 不需要namespace
	 * @param string $action action名 不需要action_前缀
	 * @param int $http_code HTTP状态码
	 * @throws Exception\Service_Error
	 *
	 * @author simple
	 */
	public static function set_error_handle($type, $controller, $action, $http_code = NULL)
	{
		//设置错误类型及错误状态码
		self::$_type_list[$type] = is_null($http_code) ? 500 : $http_code;

		$controller_name = '\\App\\Controllers\\'.ucfirst($controller);
		$action_name = 'action_' . $action;

		if (!Simple::find_file('Controllers', $controller))
		{
			//controller文件不存在
			throw new Exception\Service_Error('set_error_handle param[controller] ('.$controller_name.') does not exist');
		}
		elseif (!method_exists($controller_name, $action_name))
		{
			//action方法未定义
			throw new Exception\Service_Error('set_error_handle param[action] ('.$controller_name.'->'.$action_name.') is undefined');
		}
		else
		{
			self::$_handle_list[$type] = [
				'controller'	=> $controller,
				'action'		=> $action,
				'http_code'		=> is_null($http_code) ? self::$_type_list[$type] : $http_code,
			];
		}
	}

	/**
	 * 处理异常对应逻辑
	 * @param string $type not_found|service_error|default|method_not_allowed
	 * @param Exception $e
	 *
	 * @author simple
	 */
	public static function handle($type, $e)
	{
		$http_code = self::$_default_http_code;
		if (isset(self::$_type_list[$type]))
		{
			$http_code = self::$_type_list[$type];
		}

		//判断用户是否有定义错误处理函数
		if (isset(self::$_handle_list[$type]))
		{
			$http_code = self::$_handle_list[$type]['http_code'];
			Response::instance()->status($http_code);
			Route::run(self::$_handle_list[$type]['controller'], self::$_handle_list[$type]['action'], $e);
			return;
		}
		else
		{
			Response::instance()->status($http_code);
		}

		Response::instance()->finish(Response::$HTTP_HEADERS[$http_code]);
	}

	/**
	 * 404错误时调用
	 * @param Exception $e
	 *
	 * @author simple
	 */
	public static function not_found($e = NULL)
	{
		self::handle('not_found', $e);
	}

	/**
	 * 405错误时调用
	 * @param Exception $e
	 *
	 * @author simple
	 */
	public static function method_not_allowed($e = NULL)
	{
		self::handle('method_not_allowed', $e);
	}

	/**
	 * 500错误时调用
	 * @param Exception $e
	 *
	 * @author simple
	 */
	public static function service_error($e = NULL)
	{
		self::handle('service_error', $e);
	}

	/**
	 * 其他类型异常时调用
	 * @param Exception $e
	 *
	 * @author simple
	 */
	public static function default_handle($e = NULL)
	{
		self::handle('default', $e);
	}
}