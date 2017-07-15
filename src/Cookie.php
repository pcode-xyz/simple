<?php

namespace Simple;

use Simple\Exception\Service_Error;

/**
 * Class Cookie
 * @package Simple
 */
class Cookie
{
	//标记是否已经初始化
	protected static $_init = false;
	//校验
	protected static $salt = NULL;
	//有效期
	protected static $expire = 0;
	//服务器路径
	protected static $path = '/';
	//域名
	protected static $domain = NULL;
	//是否仅https
	protected static $secure = FALSE;
	//是否不允许脚本语言访问
	protected static $httponly = FALSE;

	//初始化，读入相关设置
	protected static function init()
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
	public static function set($name, $value, $expire = NULL)
	{
		if (!Cookie::$_init)
		{
			Cookie::init();
		}
		//增加校验
		$value = Cookie::salt($name, $value).'~'.$value;
		$expire = is_null($expire) ? Cookie::$expire : $expire;
		return setcookie($name, $value, time()+$expire, Cookie::$path, Cookie::$domain, Cookie::$secure, Cookie::$httponly);
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
	protected static function salt($name, $value)
	{
		// Require a valid salt
		if ( ! Cookie::$salt)
		{
			throw new Service_Error('Cookie Salt Not Defined!');
		}
		return sha1($name.$value.Cookie::$salt);
	}
}