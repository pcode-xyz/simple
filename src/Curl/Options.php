<?php

namespace Simple\Curl;

/**
 * Class Options
 * 用于构造Curl的options数组
 * @package Simple\Curl
 */
class Options
{
	/**
	 * @var array $_option 默认curl option
	 */
	protected $_option = [
		CURLOPT_SSL_VERIFYPEER		=>	FALSE,
		CURLOPT_SSL_VERIFYHOST		=>	FALSE,
		CURLOPT_AUTOREFERER			=>	FALSE,
		CURLOPT_HEADER				=>	TRUE,
		CURLOPT_RETURNTRANSFER		=>	TRUE,
		CURLOPT_FOLLOWLOCATION		=>	TRUE,
		CURLOPT_CONNECTTIMEOUT_MS	=>	200,
		CURLOPT_TIMEOUT_MS			=>	200,
		CURLOPT_USERAGENT			=>	'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.1 (KHTML, like Gecko) Chrome/6.0.440.0 Safari/534.1 FutuWebPHPCurl/2017',
		CURLOPT_ENCODING			=>	'gzip,deflate',
		CURLOPT_HTTPHEADER			=>	['Connection: Keep-Alive'],
	];

	/**
	 * @var string $_url 要请求的网址
	 */
	private $_url = NULL;

	/**
	 * 通过url初始化
	 * @param $url
	 * @return Options
	 *
	 * @author simple
	 */
	public static function factory($url)
	{
		return new self($url);
	}

	/**
	 * 通过 endpoint 初始化
	 * @param string $endPoint 格式为: "abc.com:port"
	 * @param string $scheme
	 * @return self
	 */
	public static function factory_by_endpoint($endPoint, $scheme = "http://")
	{
		return new self($scheme.$endPoint.'/');
	}

	/**
	 * Options constructor.
	 * @param string $url 要请求的url
	 */
	public function __construct($url)
	{
		$this->_url = $url;
	}

	/**
	 * 设置curl的option数组
	 * @param array $opt 直接覆盖默认的options数组
	 * @return Options
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
	 * @param string $referer 来源网址
	 * @return Options
	 */
	public function referer($referer)
	{
		return $this->merge([
			CURLOPT_REFERER	=> $referer,
		]);
	}

	/**
	 * 设置cookie 自动从文件读取并自动更新文件内容
	 * @param string $cookie_name cookie所在文件路径
	 * @return Options
	 */
	public function cookie_file($cookie_name)
	{
		return $this->merge([
			CURLOPT_COOKIEFILE	=> $cookie_name,
			CURLOPT_COOKIEJAR	=> $cookie_name,
		]);
	}

	/**
	 * 设置cookie内容
	 * @param string $cookies 直接设置cookie内容
	 * @return Options
	 *
	 * @author simple
	 */
	public function cookie($cookies)
	{
		return $this->add_header(['Cookie' => $cookies]);
	}


	/**
	 * 设置代理
	 * @param string $ip ip地址
	 * @param int|string $port 端口
	 * @return Options
	 */
	public function proxy($ip, $port)
	{
		return $this->merge([
			CURLOPT_PROXY	=> $ip . ':' . $port,
		]);
	}

	/**
	 * 合并数组 php的array_merge不支持数字key合并覆盖
	 * @param array $opt 用这个数组的内容覆盖默认options
	 * @return Options
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
	 * 将curl中CURLOPT_HTTPHEADER部分格式化为数组
	 * @param $header
	 * @return array
	 *
	 * @author simple
	 */
	private function header_format($header)
	{
		$format_header = [];
		foreach ($header as $item)
		{
			list($key, $value) = explode(':', $item);
			$value = explode(';', trim($value));
			$format_header[trim($key)] = $value;
		}
		return $format_header;
	}

	/**
	 * 添加自定义请求头 且覆盖所有以往的设置
	 * @param array $header 标准http头 header部分
	 * @return Options
	 *
	 * @author simple
	 */
	public function set_header($header)
	{
		$this->_option[CURLOPT_HTTPHEADER] = [];
		foreach ($header as $key => $value)
		{
			if (is_numeric($key))
			{
				/**
				 * 这种格式的header处理方式
				 * $header = [
				 * 		'Cache-Control: max-age=0',
				 * 		'Keep-Alive: 300',
				 * ];
				 */
				$this->_option[CURLOPT_HTTPHEADER][] = $value;
			}
			else
			{
				/**
				 * key=>value格式header处理方式
				 * $header = [
				 * 		'Cache-Control'		=> 'max-age=0;***',
				 * 		'Keep-Alive'		=> '300',
				 * 		'Accept-Language'	=> ['en-us,en', 'q=0.5'],
				 * ];
				 */
				if (is_array($value))
				{
					$value = implode(';', $value);
				}
				$this->_option[CURLOPT_HTTPHEADER][] = $key.': '.$value;
			}
		}
		return $this;
	}

	/**
	 * 向现有的header中添加新的属性
	 * @param $header
	 * @param null $value
	 * @return $this
	 *
	 * @author simple
	 */
	public function add_header($header, $value = NULL)
	{
		$_header = $this->header_format($this->_option[CURLOPT_HTTPHEADER]);
		if (is_array($header))
		{
			foreach ($header as $key => $value)
			{
				if (!isset($_header[$key]))
				{
					$_header[$key] = $value;
				}
				else
				{
					$value = is_array($value) ? $value : explode(';', $value);
					$_header[$key] = array_merge($_header[$key], $value);
					array_unique($_header[$key]);
				}
			}
		}
		else
		{
			$value = is_array($value) ? $value : explode(';', $value);
			$_header[$header] = array_merge($_header[$header], $value);
			array_unique($_header[$header]);
		}
		return $this->set_header($_header);
	}

	/**
	 * 要发送的是json格式
	 * @return Options
	 *
	 * @author simple
	 */
	public function is_json()
	{
		return $this->add_header(['Content-Type' => 'application/json; charset=UTF-8']);
	}

	/**
	 * 设置当前的http method
	 * @param string $method http method
	 * @return Options
	 *
	 * @author simple
	 */
	public function set_method($method)
	{
		return $this->merge([CURLOPT_CUSTOMREQUEST => $method]);
	}

	/**
	 * 同时设置链接超时和curl执行的最长时间
	 * @param int $time 毫秒级超时
	 * @return Options
	 *
	 * @author simple
	 */
	public function set_all_timeout($time)
	{
		return $this->merge([
			CURLOPT_CONNECTTIMEOUT_MS	=> $time,
			CURLOPT_TIMEOUT_MS			=> $time,
		]);
	}

	/**
	 * 设置链接超时时间
	 * @param $time int 毫秒级超时
	 * @return Options
	 *
	 * @author simple
	 */
	public function set_connect_timeout($time)
	{
		return $this->merge([
			CURLOPT_CONNECTTIMEOUT_MS	=> $time,
		]);
	}

	/**
	 * 设置curl执行的最长时间
	 * @param $time int 毫秒数
	 * @return Options
	 *
	 * @author simple
	 */
	public function set_timeout($time)
	{
		return $this->merge([
			CURLOPT_TIMEOUT_MS			=> $time,
		]);
	}

	/**
	 * get方式请求
	 * @param array $data
	 * @param bool $build_query 是否需要http_build_query
	 * @return array
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
		return $this->get_options();
	}

	/**
	 * post请求
	 * @param mixed $data
	 * @param bool $build_query 是否需要http_build_query
	 * @return array
	 */
	public function post($data, $build_query = true)
	{
		if ($build_query)
		{
			$data = http_build_query($data);
		}
		$this->merge([
			CURLOPT_HTTPGET		=> FALSE,
			CURLOPT_POST		=> TRUE,
			CURLOPT_POSTFIELDS	=> $data,
		]);
		return $this->get_options();
	}

	/**
	 * 使用PUT/DELETE等method方法
	 * @param string $method
	 * @param mixed $data
	 * @param bool $build_query
	 * @return array
	 *
	 * @author simple
	 */
	public function method($method, $data, $build_query = TRUE)
	{
		if ($build_query)
		{
			$data = http_build_query($data);
		}
		$this->merge([
			CURLOPT_CUSTOMREQUEST	=> $method,
			CURLOPT_POSTFIELDS		=> $data,
		]);
		return $this->get_options();
	}

	/**
	 * json post请求
	 * @param array $data
	 * @return array
	 */
	public function json($data)
	{
		$this->merge([
			CURLOPT_HTTPGET			=> FALSE,
			CURLOPT_POST			=> FALSE,
			CURLOPT_CUSTOMREQUEST	=> 'POST',
			CURLOPT_POSTFIELDS		=> json_encode($data),
		]);

		$this->is_json();
		return $this->get_options();
	}

	/**
	 * url后拼接get参数的同时 post一批数据过去
	 * @param array $get
	 * @param array $post
	 * @param bool $build_query
	 * @return array
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
			if ($build_query)
			{
				$post = http_build_query($post);
			}
			$this->merge([
				CURLOPT_HTTPGET		=> FALSE,
				CURLOPT_POST		=> TRUE,
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
		return $this->get_options();
	}

	/**
	 * 返回最后的options
	 * @return array
	 */
	public function get_options()
	{
		$this->merge([
			CURLOPT_URL		=> $this->_url,
		]);
		return $this->_option;
	}
}