<?php

namespace Simple;

use Simple\Exception\Service_Error;

/**
 * Class View
 * 模板类
 * @package Simple
 */
class View
{
	/**
	 * @var array 要绑定的参数
	 */
	private $_params = array();

	/**
	 * @return View
	 */
	public static function factory()
	{
		return new View();
	}

	/**
	 * View constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * 绑定参数
	 * @param $key
	 * @param null $value
	 */
	public function bind($key, $value = null)
	{
		if (is_array($key))
		{
			//数组，则合并
			$this->_params = array_merge($this->_params, $key);
		}
		else
		{
			//参数则覆盖
			$this->_params[$key] = $value;
		}
	}

	/**
	 * 返回即将输出的页面内容
	 * @param string $template_name 文件名
	 * @param string $ext 文件后缀名
	 * @throws Service_Error
	 * @return string
	 */
	public function render($template_name, $ext = '.php')
	{
		$path = View::path($template_name, $ext);

		//正常
		ob_start();
		ob_implicit_flush(0);
		extract($this->_params, EXTR_OVERWRITE);
		include $path;
		$content = ob_get_clean();

		return $content;
	}

	/**
	 * 查找模板文件路径
	 * @param string $template_name 文件名
	 * @param string $ext 后缀名
	 * @return bool|string
	 * @throws Service_Error
	 */
	public static function path($template_name, $ext = '.php')
	{
		$path = Simple::find_file('Views', $template_name, $ext);
		if (!$path)
		{
			//模板文件不存在
			throw new Service_Error('Template File['.$template_name.'] Not Found!');
		}

		return $path;
	}
}