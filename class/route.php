<?php

//默认 www.xxx.com/controller/action/param
class Route
{
	public static $_init = false;
	//默认处理Controller和action
	public static $_default_controller = 'welcome';
	public static $_default_action = 'index';

	//简单路由规则
	public static $_routes = array();

	//实际参数
	public static $_controller = null;
	public static $_action = null;

	//初始化
	public static function init()
	{
		if (Route::$_init)
		{
			return true;
		}

		//加载config
		Route::$_routes = Config::get('route');
		Route::run();
	}

	//设定自定义路由
	public static function set($url, $route)
	{
		Route::$_routes[$url] = $route;
	}

	//路由入口
	public static function run()
	{
		//处理URL
		$parse_url = parse_url(Arr::get($_SERVER, 'REQUEST_URI'));
		if (!$parse_url)
		{
			//非法url
			Core::quit("[Wrong Type 3]: URL is Illegal! " . Arr::get($_SERVER, 'REQUEST_URI'));
		}
		else
		{
			$uri = strtolower(Arr::get($parse_url, 'path'));

			//移除首末的/
			$uri = trim($uri, '/');
			if (!empty($uri))
			{
				if (strpos($uri, '/') !== false)
				{
					$uri_array = explode('/', $uri);
				}
				else
				{
					$uri_array = array($uri);
				}
			}
			else
			{
				$uri_array = array();
			}

			//TODO 尽快支持正则格式的路由
			//构造controller/action字符串 判断是否被特殊定义
			$url = Arr::get($uri_array, '0').'/'.Arr::get($uri_array, '1');
			$url = trim($url, '/');
			if (isset(Route::$_routes[$url]))
			{
				//如果有特殊定义则加载定义过的controller和action
				Route::$_controller = Arr::get(Route::$_routes[$url], 'controller', Route::$_default_controller);
				Route::$_action = Arr::get(Route::$_routes[$url], 'action', Route::$_default_action);
			}
			else
			{
				//无特殊定义则默认
				Route::$_controller = Arr::get($uri_array, '0', Route::$_default_controller);
				Route::$_action = Arr::get($uri_array, '1', Route::$_default_action);
			}

			//查找对应的class 可做404
			if (Core::find_file('controller', Route::$_controller))
			{
				$controller_name = 'Controller_'.ucfirst(Route::$_controller);
				$action_name = 'action_'.Route::$_action;
				//存在此controller文件
				if (method_exists($controller_name, $action_name))
				{
					Core::$mvc = [
						'controller'	=> $controller_name,
						'action'		=> $action_name,
					];
					$controller = new $controller_name();
					$controller->before();
					$controller->$action_name();
					$controller->after();
					return;
				}
			}

			Route::error_404();
		}
	}

	//404相关
	public static function error_404()
	{
		if (isset(Route::$_routes['404']))
		{
			//如果自定义404 则调用
			$controller_name = 'Controller_'.ucfirst(Route::$_routes['404']['controller']);
			$action_name = 'action_'.Route::$_routes['404']['action'];

			$controller = new $controller_name();
			$controller->before();
			$controller->$action_name();
			$controller->after();
			return;
		}
		Core::quit("[HTTP Error 404] Page is Not Found!");
	}
}