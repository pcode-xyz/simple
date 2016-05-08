<?php

class Cookie
{
	//标记是否已经初始化
	public static $_init = false;
	//校验
	public static $salt = NULL;
	//有效期
	public static $expire = 0;
	//服务器路径
	public static $path = '/';
	//域名
	public static $domain = NULL;
	//是否仅https
	public static $secure = FALSE;
	//是否不允许脚本语言访问
	public static $httponly = FALSE;

	//初始化，读入相关设置
	public static function init()
	{
		if (!Cookie::$_init)
		{
			Cookie::$_init = true;
		}

		Cookie::$salt = Config::get('cookie.salt', Cookie::$salt);
		Cookie::$expire = Config::get('cookie.expire', Cookie::$expire);
		Cookie::$path = Config::get('cookie.path', Cookie::$path);
		Cookie::$domain = Config::get('cookie.domain', Cookie::$domain);
		Cookie::$secure = Config::get('cookie.secure', Cookie::$secure);
		Cookie::$httponly = Config::get('cookie.httponly', Cookie::$httponly);
	}

	//读取cookie
	public static function get($key, $default = null)
	{
		//如果没有设定salt 读取config设定
		if (!Cookie::$salt)
		{
			Cookie::$salt = Config::get('cookie.salt', Cookie::$salt);
		}

		if ( ! isset($_COOKIE[$key]))
		{
			// The cookie does not exist
			return $default;
		}

		// Get the cookie value
		$cookie = $_COOKIE[$key];

		// Find the position of the split between salt and contents
		$split = strlen(Cookie::salt($key, NULL));

		if (isset($cookie[$split]) AND $cookie[$split] === '~')
		{
			// Separate the salt and the value
			list ($hash, $value) = explode('~', $cookie, 2);

			if (Cookie::salt($key, $value) === $hash)
			{
				// Cookie signature is valid
				return $value;
			}

			// The cookie signature is invalid, delete it
			Cookie::delete($key);
		}

		return $default;
	}

	//写入cookie
	public static function set($name, $value)
	{
		if (!Cookie::$_init)
		{
			Cookie::init();
		}
		//增加校验
		$value = Cookie::salt($name, $value).'~'.$value;
		return setcookie($name, $value, time()+Cookie::$expire, Cookie::$path, Cookie::$domain, Cookie::$secure, Cookie::$httponly);
	}

	//删除cookie
	public static function delete($name)
	{
		if (!Cookie::$_init)
		{
			Cookie::init();
		}
		unset($_COOKIE[$name]);
		return setcookie($name, NULL, time()-86400, Cookie::$path, Cookie::$domain, Cookie::$secure, Cookie::$httponly);
	}

	//清空cookie
	public static function clear()
	{
		foreach ($_COOKIE as $name=>$value)
		{
			Cookie::delete($name);
		}
	}

	//校验
	public static function salt($name, $value)
	{
		// Require a valid salt
		if ( ! Cookie::$salt)
		{
			Core::quit("[Wrong Type 2]: Cookie Salt Not Defined!");
		}

		// Determine the user agent
		$agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : 'unknown';

		return sha1($agent.$name.$value.Cookie::$salt);
	}
}