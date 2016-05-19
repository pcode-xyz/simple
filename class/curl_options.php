<?php

class Curl_Options
{
	public static $_options = array(
			CURLOPT_SSL_VERIFYPEER	=>	FALSE,
			CURLOPT_SSL_VERIFYHOST	=>	FALSE,
			CURLOPT_AUTOREFERER		=>	FALSE,
			CURLOPT_HEADER			=>	FALSE,
			CURLOPT_RETURNTRANSFER	=>	TRUE,
			CURLOPT_FOLLOWLOCATION	=>	TRUE,
			CURLOPT_CONNECTTIMEOUT	=>	3,
			CURLOPT_TIMEOUT			=>	10,
			CURLOPT_USERAGENT		=>	'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.1 (KHTML, like Gecko) Chrome/6.0.440.0 Safari/534.1',
			CURLOPT_ENCODING		=>	'gzip,deflate',
			CURLOPT_HTTPHEADER		=>	array('Connection: Keep-Alive'),
		);

	public static function set_options($opt)
	{
		Curl_Options::$_options = $opt;
	}

	public static function merge($arr1, $arr2)
	{
		foreach ($arr2 as $k=>$v)
		{
			$arr1[$k] = $v;
		}
		return $arr1;
	}

	public static function result($opt, $options)
	{
		if ($opt === NULL)
		{
			return Curl_Options::merge(Curl_Options::$_options, $options);
		}
		else
		{
			return Curl_Options::merge($opt, $options);
		}
	}

	public static function get($url, $data = array(), $referer = '', $opt = NULL)
	{
		if (!empty($data))
		{
			$url = $url . "?" . http_build_query ( $data );
		}
		$options = array (
			CURLOPT_URL => $url,
			CURLOPT_HTTPGET => TRUE,
			CURLOPT_POST => FALSE,
		);
		if (!empty($referer))
		{
			$options[CURLOPT_REFERER] = $referer;
		}

		return Curl_Options::result($opt, $options);
	}

	public static function post($url, $data, $referer = '', $build_query = true, $opt = NULL)
	{
		$options = array(
			CURLOPT_URL => $url,
			CURLOPT_HTTPGET => FALSE,
			CURLOPT_POST => TRUE
		);
		if ($build_query)
		{
			$options[CURLOPT_POSTFIELDS] = http_build_query($data);
		}
		else
		{
			$options[CURLOPT_POSTFIELDS] = $data;
		}
		if (!empty($referer))
		{
			$options[CURLOPT_REFERER] = $referer;
		}
		
		return Curl_Options::result($opt, $options);
	}

	public static function set_cookie_filename($cookie_name, $opt = null)
	{
		$options = array(
			CURLOPT_COOKIEFILE	=> $cookie_name,
			CURLOPT_COOKIEJAR	=> $cookie_name,
		);
		
		return Curl_Options::result($opt, $options);
	}

	public static function set_proxy($ip, $port, $opt = null)
	{
		$options[CURLOPT_PROXY] = $ip . ':' . $port;
		
		return Curl_Options::result($opt, $options);
	}
}