<?php

namespace Simple\Curl;

use Simple\Arr;

/**
 * Class Response
 * curl请求返回值
 * @package Simple\Curl
 */
class Response
{
	/**
	 * @var array $_header 返回头
	 */
	private $_header = NULL;
	/**
	 * @var mixed $_body 返回体
	 */
	private $_body = NULL;
	/**
	 * @var int $_http_code http码
	 */
	private $_http_code = NULL;
	/**
	 * @var int $_errno curl错误码
	 */
	private $_errno = NULL;
	/**
	 * @var string $_error curl错误信息
	 */
	private $_error = NULL;
	/**
	 * @var array $_option curl option
	 */
	private $_option = NULL;
	/**
	 * @var string $_data curl exec result
	 */
	private $_data = NULL;

	/**
	 * Response constructor.
	 * 并发方式初始化
	 * @param resource $ch curl_init result
	 * @param array $option curl_option
	 * @return self
	 */
	public static function multi($ch, $option)
	{
		$data = curl_multi_getcontent($ch);
		return new self($data, $option, curl_getinfo($ch, CURLINFO_HTTP_CODE), curl_errno($ch), curl_error($ch));
	}

	/**
	 * Response constructor.
	 * 单一方式初始化
	 * @param resource $ch curl_init result
	 * @param array $option curl_option
	 * @param string $data curl_exec result
	 * @return self
	 */
	public static function single($ch, $option, $data)
	{
		return new self($data, $option, curl_getinfo($ch, CURLINFO_HTTP_CODE), curl_errno($ch), curl_error($ch));
	}

	/**
	 * @param array $data 要分隔的数据
	 * @param string $type all|body|header 分割后返回数组 只返回body部分 返回头部数据
	 * @return array
	 */
	public static function split($data, $type = 'all')
	{
		list($header, $body) = explode("\r\n\r\n", $data, 2);

		if ($type == 'body')
		{
			$return = $body;
		}
		else
		{
			$headers = [];
			$header = explode("\n", $header);
			foreach($header as $val)
			{
				if (strpos($val, ': ') === false)
				{
					continue;
				}
				list($k, $v) = explode(': ', $val, 2);
				$headers[trim(strtoupper($k))] = trim($v);
			}
			if ($type == 'header')
			{
				$return = $headers;
			}
			else
			{
				$return = [
					'header'	=> $headers,
					'body'		=> $body,
				];
			}
		}
		return $return;
	}

	/**
	 * Response constructor.
	 * @param string $data curl请求原始内容
	 * @param array $option 发起curl请求时的options数组
	 * @param int $http_code curl请求的http状态码
	 * @param int $errno curl请求可能遇到的错误码
	 * @param string $error curl请求可能遇到的错误信息
	 */
	public function __construct($data, $option, $http_code = 200, $errno = 0, $error = '')
	{
		$this->_data = $data;
		$this->_option = $option;
		$this->_http_code = $http_code;
		$this->_errno = $errno;
		$this->_error = $error;

		//有正常响应时才需要拆分
		if ($http_code == 200 && $error == 0)
		{
			//curl option中有设置需要带header的选项 则拆分
			if ($this->_option[CURLOPT_HEADER])
			{
				$result = self::split($data);
				$this->_header = $result['header'];
				$this->_body = $result['body'];
			}
			else
			{
				$this->_body = $data;
			}

			//判断是否需要json格式化
			if (!empty($this->_header) && isset($this->_header['CONTENT-TYPE']))
			{
				$content_type = $this->_header['CONTENT-TYPE'];
				$content_type = str_replace(' ', '', $content_type);
				if (strpos($content_type, 'application/json') !== FALSE)
				{
					$this->_body = json_decode($this->_body, TRUE);
				}
			}
		}
	}

	/**
	 * 获取response的header部分
	 * @return array|null
	 *
	 * @author simple
	 */
	public function header()
	{
		return $this->_header;
	}

	/**
	 * 获取response的body部分
	 * @return string
	 *
	 * @author simple
	 */
	public function body()
	{
		return $this->_body;
	}

	/**
	 * 获取curl错误原因
	 * @return string
	 *
	 * @author simple
	 */
	public function error()
	{
		return $this->_error;
	}

	/**
	 * 获取curl错误码
	 * @return int
	 *
	 * @author simple
	 */
	public function errno()
	{
		return $this->_errno;
	}

	/**
	 * 返回http状态码
	 * @return int
	 *
	 * @author simple
	 */
	public function http_code()
	{
		return $this->_http_code;
	}

	/**
	 * 获取json标准化后的data部分
	 * @return mixed
	 *
	 * @author simple
	 */
	public function data()
	{
		return Arr::get($this->_body, 'data', NULL);
	}

	/**
	 * 获取json标准化后的code部分
	 * @return int
	 *
	 * @author simple
	 */
	public function code()
	{
		return Arr::get($this->_body, 'code', -10000);
	}

	/**
	 * 获取json标准化后的message部分
	 * @return string
	 *
	 * @author simple
	 */
	public function message()
	{
		return Arr::get($this->_body, 'message', '');
	}
}