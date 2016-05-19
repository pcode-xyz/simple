<?php

class Curl
{
	public static function execute($options, $multi = true)
	{
		if ($multi === true)
		{
			return Curl::multi($options);
		}
		else
		{
			return Curl::single($options);
		}
	}

	public static function split($data, $type)
	{
		list($header, $body) = explode("\r\n\r\n", $data, 2);
		if ($type == 'header')
		{
			$headers = array();
			$header = explode("\n", $header);
			foreach($header as $val)
			{
				if(strpos($val, ': ') === false)
					continue;
				list($k, $v) = explode(': ', $val, 2);
				$headers[$k] = $v;
			}
		}
		elseif ($type == 'body')
		{
			return $body;
		}
	}

	public static function single($options)
	{
		$c = curl_init();
		curl_setopt_array($c, $options);
		$content = curl_exec($c);
		curl_close($c);
		return $content;
	}

	public static function multi($options)
	{
		$mh = curl_multi_init();
		$ch = array();
		foreach($options as $i=>$option)
		{
			$ch[$i] = curl_init();
			curl_setopt_array($ch[$i], $option);
			curl_multi_add_handle($mh,$ch[$i]);
		}

		/*
		$active = null;
		do {
			$mrc = curl_multi_exec($mh, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);

		while ($active and $mrc == CURLM_OK) {
			if (curl_multi_select($mh) != -1) {
				do {
					$mrc = curl_multi_exec($mh, $active);
				} while ($mrc == CURLM_CALL_MULTI_PERFORM);
			}
		}
		*/
		$active = null;
		do {
			$mrc = curl_multi_exec($mh, $active);
		} while ($active > 0);

		$ret = array();
		foreach($options as $i=>$option)
		{
			$ret[$i] = (curl_errno($ch[$i]) == 0) ? curl_multi_getcontent($ch[$i]) : '';
			curl_multi_remove_handle($mh, $ch[$i]);
		}
		curl_multi_close($mh);
		return $ret;
	}

	public static function pmatch($regex, $content, &$var1 = 'undefined', &$var2 = 'undefined', &$var3 = 'undefined')
	{
		if (preg_match ( $regex, $content, $match )) {
			if (isset ( $match [1] ) && $var1 !== 'undefined') {
				$var1 = $match [1];
			}
			if (isset ( $match [2] ) && $var2 !== 'undefined') {
				$var2 = $match [2];
			}
			if (isset ( $match [3] ) && $var3 !== 'undefined') {
				$var3 = $match [3];
			}
			return true;
		}
		return false;
	}

	public static function pmatcha($regex, $content, &$array)
	{
		if (preg_match ( $regex, $content, $match )) {
			for ($i=1; $i<count($match); $i++)
			{
				$array[$i] = $match[$i];
			}
			return true;
		}
		return false;
	}

	public static function pmatches($regex, $content, &$array, $columns = array())
	{
		if (preg_match_all ( $regex, $content, $matches )) {
			$nums = count ( $matches ) - 1;
			for($i = 0, $max = count ( $matches [0] ); $i < $max; $i ++) {
				$arr = &$array [$i];
				for($j = 1; $j <= $nums; $j ++) {
					if (isset ( $columns [$j - 1] )) {
						$arr [$columns [$j - 1]] = $matches [$j] [$i];
					} else {
						$arr [$j] = $matches [$j] [$i];
					}
				}
			}
			return true;
		}
		return false;
	}
}