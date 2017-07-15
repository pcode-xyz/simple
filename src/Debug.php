<?php

namespace Simple;

/**
 * Class Debug
 * 输出代码调试信息
 * @package Simple
 */
class Debug
{
	/**
	 * trace格式化
	 * @param int $code 错误码
	 * @param string $message 错误原因
	 * @param array $trace 追踪
	 * @return array
	 *
	 * @author simple
	 */
	public static function format($code, $message, $trace)
	{
		//trace格式化
		foreach ($trace as $key=>$value)
		{
			if (!isset($value['file']))
			{
				unset($trace[$key]);
				continue;
			}
			$arg = '';
			if (!empty($value['args']))
			{
				foreach ($value['args'] as $k => $arg)
				{
					if (is_object($arg))
					{
						$value['args'][$k] = "'".get_class($arg)."'";
					}
					elseif (is_array($arg))
					{
						$value['args'][$k] = str_replace(["\n", " "], "", var_export($arg, TRUE));
					}
					elseif (is_bool($arg) || is_numeric($arg))
					{
						$value['args'][$k] = $arg;
					}
					elseif (is_null($arg))
					{
						$value['args'][$k] = 'null';
					}
					else
					{
						$value['args'][$k] = "'".$arg."'";
					}
				}
				$arg = implode(",", $value['args']);
			}
			$trace[$key]['arg'] = $arg;
		}

		return [
			'code'		=> $code,
			'message'	=> $message,
			'trace'		=> $trace,
		];
	}

	/**
	 * 输出调试信息
	 * @param int $code 错误码
	 * @param string $message 错误信息
	 * @param array $trace 代码追踪
	 *
	 * @author simple
	 */
	public static function info($code, $message, $trace)
	{
		if (!DEBUG)
		{
			return;
		}

		$array = self::format($code, $message, $trace);

		$view = View::factory();
		$view->bind($array);

		if (CLI_MODE)
		{
			$content = $view->render('Exception/debug_cli');
			Response::instance()->finish($content);
		}
		else
		{
			$content = $view->render('Exception/debug');
			Response::instance()->display($content, 500);
		}
	}
}