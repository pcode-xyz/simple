<?php

namespace Simple\Http;

use Simple\Arr;

/**
 * Class Request
 * 请求头相关
 * @package Simple\Http
 */
class Request
{
	/**
	 * @var null|Request
	 */
	private static $_instance = null;

	const METHOD_GET = 'GET';
	const METHOD_HEAD = 'HEAD';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_DELETE = 'DELETE';
	const METHOD_CONNECT = 'CONNECT';
	const METHOD_OPTIONS = 'OPTIONS';
	const METHOD_TRACE = 'TRACE';

	/**
	 * @var null|array $server $_SERVER数组 key值小写
	 */
	public $server = NULL;
	/**
	 * @var null|array $header Http请求的头部信息 key值小写
	 */
	public $header = NULL;
	public $get = NULL;
	public $post = NULL;
	public $cookie = NULL;
	public $files = NULL;

	/**
	 * @return Request
	 *
	 * @author simple
	 */
	public static function instance()
	{
		if (empty(self::$_instance))
		{
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Request constructor.
	 * 初始化 变量赋值
	 */
	public function __construct()
	{
		//初始化赋值
		$this->get = $_GET;
		$this->post = $_POST;
		$this->cookie = $_COOKIE;
		$this->files = $_FILES;

		//处理server和header 是指与swoole对齐
		foreach ($_SERVER as $key => $value)
		{
			$key = strtolower($key);
			if (substr($key, 0, 5) == 'http_')
			{
				$this->header[substr($key, 5)] = $value;
			}
			$this->server[$key] = $value;
		}

		//判断是否将json转_POST
		if (AUTO_PARAM_JSON && (stripos(Arr::get($_SERVER, 'CONTENT_TYPE'), 'application/json') !== FALSE || stripos(Arr::get($_SERVER, 'HTTP_CONTENT_TYPE'), 'application/json') !== FALSE))
		{
			$data = $this->rawContent();
			$this->post = array_merge($this->post, json_decode($data, TRUE));
		}
	}

	/**
	 * 访问请求的原始数据的只读流
	 * @return string
	 *
	 * @author simple
	 */
	public function rawContent()
	{
		return file_get_contents('php://input');
	}

	/**
	 * 获取客户端真实IP
	 * @return string
	 */
	public function get_client_ip()
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
	public function method()
	{
		return $this->server['request_method'];
	}

	/**
	 * 判断是否为post提交
	 * @return bool
	 */
	public function is_post()
	{
		return $this->method() == self::METHOD_POST ? TRUE : FALSE;
	}

	/**
	 * 判断是否为get提交
	 * @return bool
	 */
	public function is_get()
	{
		return $this->method() == self::METHOD_GET ? TRUE : FALSE;
	}

	/**
	 * 获取GET参数
	 * @param $key
	 * @param null $default
	 * @return mixed
	 */
	public function get($key = NULL, $default = null)
	{
		if (is_null($key))
		{
			return $this->get;
		}
		return isset($this->get[$key]) ? $this->get[$key] : $default;
	}

	/**
	 * 获取POST参数
	 * @param $key
	 * @param null $default
	 * @return mixed
	 */
	public function post($key = NULL, $default = null)
	{
		if (is_null($key))
		{
			return $this->post;
		}
		return isset($this->post[$key]) ? $this->post[$key] : $default;
	}
}