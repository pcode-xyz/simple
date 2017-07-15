<?php

namespace Simple\Curl;

/**
 * Class Request
 * 发送一个curl请求
 * @package Simple\Curl
 */
class Request
{
	/**
	 * 单一一次请求
	 * @param array $option
	 * @return Response
	 *
	 * @author simple
	 */
	public static function single($option)
	{
		$ch = curl_init();
		curl_setopt_array($ch, $option);
		$content = curl_exec($ch);

		$response = Response::single($ch, $option, $content);
		curl_close($ch);

		return $response;
	}

	/**
	 * 使用并发的方式请求
	 * @param $options
	 * @return array
	 *
	 * @author simple
	 */
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

		$active = null;
		do {
			$mrc = curl_multi_exec($mh, $active);
		} while ($active > 0);

		$ret = [];
		foreach($options as $i=>$option)
		{
			$ret[$i] = Response::multi($ch[$i], $option);
			curl_multi_remove_handle($mh, $ch[$i]);
			curl_close($ch[$i]);
		}
		curl_multi_close($mh);
		return $ret;
	}
}