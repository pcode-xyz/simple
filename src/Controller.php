<?php

namespace Simple;

/**
 * Class Controller
 * @package Simple
 */
class Controller
{
	/**
	 * Controller constructor.
	 */
	public function __construct()
	{
		// Nothing by default
	}

	/**
	 * 前置函数
	 * @author simple
	 */
	public function before()
	{
		// Nothing by default
	}

	/**
	 * 后置函数
	 * @author simple
	 */
	public function after()
	{
		// Nothing by default
	}

	/**
	 * 获取GET参数中的值
	 * @param null|string $key 如果为null则获取整个$_GET 否则获取$_GET[$key]
	 * @param null $value
	 * @return mixed
	 */
	protected function get($key = NULL, $value = NULL)
	{
		return Request::get($key, $value);
	}

	/**
	 * 获取POST中的值
	 * @param null|string $key 如果为null则获取整个$_POST 否则获取$_POST[$key]
	 * @param null $value
	 * @return mixed
	 */
	protected function post($key = NULL, $value = NULL)
	{
		return Request::post($key, $value);
	}

	/**
	 * 跳转
	 * @param string $url 要转向的网址
	 * @param int $mode 输出http状态码
	 */
	protected function redirect($url, $mode = 302)
	{
		Response::instance()->redirect($url, $mode);
	}

	/**
	 * 自定义结构输出json
	 * @param $data array
	 */
	protected function ajax($data)
	{
		//ajax会直接exit,因此after需要在此处调用
		$this->after();

		Response::instance()->ajax($data);
	}

	/**
	 * 使用框架提供的结构输出正确信息
	 * @param null $data
	 * @param int $code
	 * @param string $message
	 */
	protected function success($data = NULL, $code = 0, $message = '')
	{
		$result = [
			'code'		=> $code,
			'data'		=> $data,
			'message'	=> $message,
		];
		$this->ajax($result);
	}

	/**
	 * 使用框架提供的结构输出错误信息
	 * @param string $message
	 * @param int $code
	 * @param null $data
	 */
	protected function error($message = '', $code = 1, $data = NULL)
	{
		$result = [
			'code'		=> $code,
			'data'		=> $data,
			'message'	=> $message,
		];
		$this->ajax($result);
	}

}
