<?php

namespace Simple;

/**
 * Class Request
 * 请求头相关
 * @package Simple
 */
class Request
{
	const METHOD_GET = 'GET';
	const METHOD_HEAD = 'HEAD';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_DELETE = 'DELETE';
	const METHOD_CONNECT = 'CONNECT';
	const METHOD_OPTIONS = 'OPTIONS';
	const METHOD_TRACE = 'TRACE';

	/**
	 * 获取客户端真实IP
	 * @return string
	 */
	public static function get_client_ip()
	{
		$client_ip = '0.0.0.0';
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			$ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			foreach ($ips as $ip)
			{
				if ($ip != 'unknow')
				{
					$client_ip = $ip;
					break;
				}
			}
		}
		elseif (isset($_SERVER['HTTP_CLIENT_IP']))
		{
			$client_ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif (isset($_SERVER['REMOTE_ADDR']))
		{
			$client_ip = $_SERVER['REMOTE_ADDR'];
		}

		return $client_ip;
	}

	/**
	 * 获取HTTP method
	 * @return mixed
	 *
	 * @author simple
	 */
	public static function method()
	{
		return $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * 判断是否为post提交
	 * @return bool
	 */
	public static function is_post()
	{
		return $_SERVER['REQUEST_METHOD'] == self::METHOD_POST ? TRUE : FALSE;
	}

	/**
	 * 判断是否为get提交
	 * @return bool
	 */
	public static function is_get()
	{
		return $_SERVER['REQUEST_METHOD'] == self::METHOD_GET ? TRUE : FALSE;
	}

	/**
	 * 获取GET参数
	 * @param $key
	 * @param null $default
	 * @return mixed
	 */
	public static function get($key = NULL, $default = null)
	{
		if (is_null($key))
		{
			return $_GET;
		}
		return isset($_GET[$key]) ? $_GET[$key] : $default;
	}

	/**
	 * 获取POST参数
	 * @param $key
	 * @param null $default
	 * @return mixed
	 */
	public static function post($key = NULL, $default = null)
	{
		if (is_null($key))
		{
			return $_POST;
		}
		return isset($_POST[$key]) ? $_POST[$key] : $default;
	}
}