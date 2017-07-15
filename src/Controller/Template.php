<?php

namespace Simple\Controller;

use Simple\Controller;
use Simple\Response;
use Simple\Route;
use Simple\View;

/**
 * Class Template
 * 需要模板输出的Controller基类
 * @package Simple\Controller
 */
class Template extends Controller
{
	/**
	 * @var string $_template 模板文件路径
	 */
	protected $_template = '';
	/**
	 * @var string $_ext 模板文件后缀名
	 */
	protected $_ext = '.php';

	/**
	 * @var View 模板类
	 */
	protected $_view = NULL;

	/**
	 * Template constructor.
	 * 自动构建一个view
	 */
	public function __construct()
	{
		parent::__construct();

		$this->_view = View::factory();
	}

	/**
	 * 前置函数 对模板路径进行默认赋值
	 * @author simple
	 */
	public function before()
	{
		// Nothing by default
		$this->_template = Route::$view;
	}

	/**
	 * 后置函数 输出模板
	 * @author simple
	 */
	public function after()
	{
		// Nothing by default
		$this->display();
	}

	/**
	 * 绑定变量到模板中
	 * @param string|array $key 如果为数组 则key=>value对应绑定到模板
	 * @param null|mixed $value 如果$key为字符串 则将$value的值绑定到名字为$key的变量上
	 */
	protected function bind($key, $value = NULL)
	{
		$this->_view->bind($key, $value);
	}

	/**
	 * 输出文件
	 */
	protected function display()
	{
		Response::instance()->display($this->_view->render($this->_template, $this->_ext));
	}

}
