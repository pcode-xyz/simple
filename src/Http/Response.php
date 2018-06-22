<?php

namespace Simple\Http;

use Simple\Exception\Service_Error;

/**
 * Class Response
 * 输出http响应
 * @package Simple\Http
 */
class Response
{
	public static $HTTP_HEADERS = [
		100 => "100 Continue",
		101 => "101 Switching Protocols",
		200 => "200 OK",
		201 => "201 Created",
		204 => "204 No Content",
		206 => "206 Partial Content",
		300 => "300 Multiple Choices",
		301 => "301 Moved Permanently",
		302 => "302 Move temporarily",
		303 => "303 See Other",
		304 => "304 Not Modified",
		307 => "307 Temporary Redirect",
		400 => "400 Bad Request",
		401 => "401 Unauthorized",
		403 => "403 Forbidden",
		404 => "404 Not Found",
		405 => "405 Method Not Allowed",
		406 => "406 Not Acceptable",
		408 => "408 Request Timeout",
		410 => "410 Gone",
		413 => "413 Request Entity Too Large",
		414 => "414 Request URI Too Long",
		415 => "415 Unsupported Media Type",
		416 => "416 Requested Range Not Satisfiable",
		417 => "417 Expectation Failed",
		500 => "500 Internal Server Error",
		501 => "501 Method Not Implemented",
		503 => "503 Service Unavailable",
		506 => "506 Variant Also Negotiates",
	];

	private static $_instance = NULL;
	/**
	 * @var array $_header 要输出的头信息
	 */
	private $_header = [];
	/**
	 * @var int $_code 输出HTTP code
	 */
	private $_code = 200;

	/**
	 * @return Response
	 */
	public static function instance()
	{
		if (empty(self::$_instance))
		{
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * 设置返回值头
	 * @param array|string $array
	 * @param null $value
	 */
	public function header($array, $value = NULL)
	{
		if (is_array($array))
		{
			foreach ($array as $key=>$item)
			{
				$this->_header[$key] = $item;
			}
		}
		elseif (is_null($value) && is_string($array))
		{
			$temp = explode(':', $array, 2);
			$this->_header[trim($temp[0])] = trim($temp[1]);
		}
		else
		{
			$this->_header[$array] = $value;
		}
	}

	/**
	 * 这是状态码
	 * @param $code
	 * @return $this
	 */
	public function status($code)
	{
		$this->header('HTTP/1.1 '.self::$HTTP_HEADERS[$code]);
		return $this;
	}

	/**
	 * 跳转
	 * @param $url
	 * @param int $mode
	 */
	public function redirect($url, $mode = 302)
	{
		$this->status($mode);
		$this->header('Location', $url);
		$this->finish();
	}

	/**
	 * 输出json
	 * @param $data array
	 */
	public function ajax($data)
	{
		$this->status(200);
		$this->header('Cache-Control', 'no-cache, must-revalidate');
		$this->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
		$this->header('Content-Type', 'application/json');
		$return = json_encode($data);
		$this->finish($return);
	}

	/**
	 * 发送文件下载
	 * @param $filepath string 完整路径及文件名
	 * @throws Service_Error
	 */
	public function sendfile($filepath)
	{
		$server_soft = $_SERVER['SERVER_SOFTWARE'];

		$filename = basename($filepath);
		$headers = [
			'Content-type'			=> 'application/octet-stream',
			'Content-Disposition'	=> 'attachment; filename="' . $filename . '"',
		];

		//中文文件名
		$ua = $_SERVER["HTTP_USER_AGENT"];
		if (preg_match("/MSIE/", $ua))
		{
			$headers['Content-Disposition'] = 'attachment; filename="' . rawurlencode($filename) . '"';
		}
		elseif (preg_match("/Firefox/", $ua))
		{
			$headers['Content-Disposition'] = "attachment; filename*=\"utf8''" . $filename . '"';
		}

		//webservice不同头不同
		if (stripos($server_soft, 'nginx') !== false)
		{
			$headers['X-Accel-Redirect'] = $filepath;
		}
		elseif (stripos($server_soft, 'apache') !== false)
		{
			$headers['X-Sendfile'] = $filepath;
		}
		else
		{
			throw new Service_Error('sendfile support only nginx / apache');
		}

		$this->header($headers);
		$this->finish();
	}

	/**
	 * 输出下载文件
	 * @param string $filename 文件名
	 * @param string $filedata 文件正文
	 * @param bool $utf8_bom 是否加utf8bom头
	 */
	public function download($filename, $filedata, $utf8_bom = FALSE)
	{
		//是否增加utf8bom头使下载的csv文件不出乱码
		if ($utf8_bom)
		{
			$filedata = chr(239) . chr(187) . chr(191) . $filedata;
		}

		$headers = [
			'Content-type'			=> 'application/octet-stream',
			'Content-Disposition'	=> 'attachment; filename="' . $filename . '"',
			'Accept-Ranges'			=> 'bytes',
			'Accept-Length'			=> strlen($filedata),
		];

		//中文文件名
		$ua = $_SERVER["HTTP_USER_AGENT"];
		if (preg_match("/MSIE/", $ua))
		{
			$headers['Content-Disposition'] = 'attachment; filename="' . rawurlencode($filename) . '"';
		}
		else if (preg_match("/Firefox/", $ua))
		{
			$headers['Content-Disposition'] = "attachment; filename*=\"utf8''" . $filename . '"';
		}
		$this->header($headers);
		$this->finish($filedata);
	}

	/**
	 * 输出页面
	 * @param string $content 文件内容
	 * @param int $code 状态码
	 */
	public function display($content = '', $code = 200)
	{
		$this->status($code);
		$this->header('Content-Type:text/html; charset=utf-8');
		$this->finish($content);
	}

	/**
	 * 输出正文并结束
	 * @param string $content
	 */
	public function finish($content = null)
	{
		die($content);
	}
}