<?php

namespace Simple;

/**
 * Class Curl
 * Curl类 建议后续直接使用Simple/Curl目录中的类替代本类
 * @package Simple
 */
class Curl
{
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
				$headers[$k] = $v;
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

	//单一请求
	public static function single($options)
	{
		$c = curl_init();
		curl_setopt_array($c, $options);
		$content = curl_exec($c);
		curl_close($c);
		return $content;
	}

	//并发请求
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

	private $_option = [
		CURLOPT_SSL_VERIFYPEER		=>	FALSE,
		CURLOPT_SSL_VERIFYHOST		=>	FALSE,
		CURLOPT_AUTOREFERER			=>	FALSE,
		CURLOPT_HEADER				=>	FALSE,
		CURLOPT_RETURNTRANSFER		=>	TRUE,
		CURLOPT_FOLLOWLOCATION		=>	TRUE,
		CURLOPT_CONNECTTIMEOUT_MS	=>	0,
		CURLOPT_TIMEOUT_MS			=>	0,
		CURLOPT_USERAGENT			=>	'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.1 (KHTML, like Gecko) Chrome/6.0.440.0 Safari/534.1 FutuWebPHPCurl/2016',
		CURLOPT_ENCODING			=>	'gzip,deflate',
		CURLOPT_HTTPHEADER			=>	['Connection: Keep-Alive'],
	];

	/**
	 * @var string $_url 要请求的网址
	 */
	private $_url = NULL;
	/**
	 * @var string $_result 原始返回值
	 */
	private $_result = NULL;
	/**
	 * @var array $_header 返回值http头
	 */
	private $_header = NULL;
	/**
	 * @var string $_body 返回值正文
	 */
	private $_body = NULL;
	/**
	 * @var null|resource $_ch curl连接
	 */
	private $_ch = NULL;

	public static function factory($url)
	{
		return new self($url);
	}

	public function __construct($url)
	{
		$this->_url = $url;
		$this->_ch = curl_init();
	}

	public function __destruct()
	{
		curl_close($this->_ch);
	}

	/**
	 * 设置curl的option数组
	 * @param $opt
	 * @return $this
	 */
	public function option($opt)
	{
		$this->_option = $opt;
		if (!isset($this->_option[CURLOPT_URL]) || empty($this->_option[CURLOPT_URL]))
		{
			$this->merge([
				CURLOPT_URL		=> $this->_url,
			]);
		}
		return $this;
	}

	/**
	 * 设置来源
	 * @param $referer
	 * @return $this
	 */
	public function referer($referer)
	{
		$this->merge([
			CURLOPT_REFERER	=> $referer,
		]);
		return $this;
	}

	/**
	 * 设置cookie
	 * @param $cookie_name
	 * @return $this
	 */
	public function cookie($cookie_name)
	{
		$this->merge([
			CURLOPT_COOKIEFILE	=> $cookie_name,
			CURLOPT_COOKIEJAR	=> $cookie_name,
		]);
		return $this;
	}

	/**
	 * 设置代理
	 * @param $ip
	 * @param $port
	 * @return $this
	 */
	public function proxy($ip, $port)
	{
		$this->merge([
			CURLOPT_PROXY	=> $ip . ':' . $port,
		]);
		return $this;
	}

	/**
	 * 合并数组 php的array_merge不支持数字key合并覆盖
	 * @param $opt
	 * @return $this
	 */
	public function merge($opt)
	{
		foreach ($opt as $key=>$value)
		{
			$this->_option[$key] = $value;
		}
		return $this;
	}

	/**
	 * 添加自定义请求头
	 * @param $header
	 * @return $this
	 *
	 * @author simple
	 */
	public function set_header($header)
	{
		foreach ($header as $key => $value)
		{
			$this->_option[CURLOPT_HTTPHEADER][] = $key.': '.$value;
		}
		return $this;
	}

	/**
	 * get方式请求
	 * @param array $data
	 * @param bool $build_query 是否需要http_build_query
	 * @return $this|mixed|Curl
	 */
	public function get($data = [], $build_query = true)
	{
		if (!empty($data))
		{
			if ($build_query)
			{
				$this->_url .= '?'.http_build_query($data);
			}
			else
			{
				$this->_url .= '?' . $data;
			}
		}
		$this->merge([
			CURLOPT_HTTPGET	=> TRUE,
			CURLOPT_POST	=> FALSE,
		]);
		return $this->exec();
	}

	/**
	 * post请求
	 * @param $data
	 * @param bool $build_query 是否需要http_build_query
	 * @return $this|mixed|Curl
	 */
	public function post($data, $build_query = true)
	{
		$this->merge([
			CURLOPT_HTTPGET	=> FALSE,
			CURLOPT_POST	=> TRUE
		]);

		if ($build_query)
		{
			$data = http_build_query($data);
		}
		$this->merge([
			CURLOPT_POSTFIELDS	=> $data,
		]);
		return $this->exec();
	}

	/**
	 * json post请求
	 * @param array $data
	 * @return $this|mixed|Curl
	 */
	public function json($data)
	{
		$this->merge([
			CURLOPT_HTTPGET			=> FALSE,
			CURLOPT_POST			=> FALSE,
			CURLOPT_CUSTOMREQUEST	=> 'POST',
			CURLOPT_POSTFIELDS		=> json_encode($data),
		]);

		$this->set_header(['Content-Type' => 'application/json']);
		return $this->exec();
	}

	/**
	 * url后拼接get参数的同时 post一批数据过去
	 * @param $get
	 * @param $post
	 * @param bool $build_query
	 * @return $this|Curl|mixed
	 *
	 * @author simple
	 */
	public function get_post($get, $post, $build_query = true)
	{
		if (!empty($get))
		{
			if ($build_query)
			{
				$this->_url .= '?'.http_build_query($get);
			}
			else
			{
				$this->_url .= '?' . $get;
			}
		}

		if (!empty($post))
		{
			$this->merge([
				CURLOPT_HTTPGET	=> FALSE,
				CURLOPT_POST	=> TRUE
			]);

			if ($build_query)
			{
				$post = http_build_query($post);
			}
			$this->merge([
				CURLOPT_POSTFIELDS	=> $post,
			]);
		}
		else
		{
			$this->merge([
				CURLOPT_HTTPGET	=> TRUE,
				CURLOPT_POST	=> FALSE,
			]);
		}
		return $this->exec();
	}

	/**
	 * 执行
	 * @return $this|mixed
	 */
	public function exec()
	{
		$this->merge([
			CURLOPT_URL		=> $this->_url,
		]);

		curl_setopt_array($this->_ch, $this->_option);
		$this->_result = curl_exec($this->_ch);
		if (!empty($this->_result))
		{
			if ($this->_option[CURLOPT_HEADER])
			{
				$result = self::split($this->_result);
				$this->_header = $result['header'];
				$this->_body = $result['body'];
			}
			else
			{
				$this->_body = $this->_result;
			}
		}
		return $this;
	}

	/**
	 * 获取curl的执行错误码
	 * @return int
	 */
	public function errno()
	{
		return curl_errno($this->_ch);
	}

	/**
	 * 获取curl的执行错误原因
	 * @return string
	 */
	public function error()
	{
		return curl_error($this->_ch);
	}

	/**
	 * 获取curl执行后的http code
	 * @return mixed
	 */
	public function code()
	{
		return curl_getinfo($this->_ch, CURLINFO_HTTP_CODE);
	}

	/**
	 * 获取正文内容
	 * @return mixed
	 */
	public function body()
	{
		return $this->_body;
	}

	/**
	 * 获取http 头
	 * @return array
	 */
	public function header()
	{
		return $this->_header;
	}
}